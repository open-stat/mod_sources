<?php
namespace Core2\Mod\Sources\Chats;

use Core2\Mod\Sources\Model;

/**
 * @property \ModSourcesController $modSources
 * @property \ModMetricsApi        $apiMetrics
 */
class TgParser extends \Common {


    /**
     * Обработка сохраненной истории
     * @param int $limit
     * @return void
     */
    public function processHistory(int $limit = 100): void {

        $history = $this->modSources->dataSourcesChatsContent->fetchAll(
            $this->modSources->dataSourcesChatsContent->select()
                ->where("type = 'tg_history_channel'")
                ->where("is_parsed_sw = 'N'")
                ->where("file_name IS NOT NULL")
                ->order('id ASC')
                ->limit($limit)
        );

        $tg_parser_history = new TgParser\History();
        $model             = new Model();

        foreach ($history as $item) {

            $this->db->beginTransaction();
            try {
                $date_day     = new \DateTime($item->date_created);
                $file_content = $model->getSourceFile('chats', $date_day, $item->file_name);
                $content      = json_decode(gzuncompress(base64_decode($file_content['content'])), true);

                if ( ! empty($content['users'])) {
                    foreach ($content['users'] as $user) {
                        $tg_parser_history->saveUser($user);
                    }

                    $this->apiMetrics->incPrometheus('core2_sources_tg_process', count($content['users']), [
                        'labels'   => ['action' => 'channel_history_users'],
                        'job'      => 'core2',
                        'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                    ]);
                }

                if ( ! empty($content['chats'])) {
                    foreach ($content['chats'] as $chat) {
                        $tg_parser_history->saveChat($chat);
                    }

                    $this->apiMetrics->incPrometheus('core2_sources_tg_process', count($content['chats']), [
                        'labels'   => ['action' => 'channel_history_chats'],
                        'job'      => 'core2',
                        'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                    ]);
                }

                if ( ! empty($content['messages'])) {
                    foreach ($content['messages'] as $message) {
                        $tg_parser_history->saveMessage($message);
                    }

                    $this->apiMetrics->incPrometheus('core2_sources_tg_process', count($content['messages']), [
                        'labels'   => ['action' => 'channel_history_messages'],
                        'job'      => 'core2',
                        'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                    ]);
                }

                $item->is_parsed_sw = 'Y';
                $item->save();

                $this->db->commit();

            } catch (\Exception $e) {
                $this->db->rollback();
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;
            }
        }
    }


    /**
     * Обработка сохраненных обновлений
     * @param int $limit
     * @return void
     */
    public function processUpdates(int $limit = 100): void {

        $contents = $this->modSources->dataSourcesChatsContent->fetchAll(
            $this->modSources->dataSourcesChatsContent->select()
                ->where("type = 'tg_updates'")
                ->where("is_parsed_sw = 'N'")
                ->where("file_name IS NOT NULL")
                ->order('id ASC')
                ->limit($limit)
        );

        $tg_parser_update = new TgParser\Update();
        $model            = new Model();

        foreach ($contents as $content_item) {

            $this->db->beginTransaction();
            try {
                $date_day     = new \DateTime($content_item->date_created);
                $file_content = $model->getSourceFile('chats', $date_day, $content_item->file_name);
                $content      = json_decode(gzuncompress(base64_decode($file_content['content'])), true);

                foreach ($content as $item) {
                    if ( ! empty($item['update']) && ! empty($item['update']['_'])) {
                        $update = $item['update'];

                        switch ($item['update']['_']) {
                            case 'updateChannelMessageViews':    $tg_parser_update->updateChannelMessageViews($update); break;
                            case 'updateChannelMessageForwards': $tg_parser_update->updateChannelMessageForwards($update); break;
                            case 'updateMessageReactions':       $tg_parser_update->updateMessageReactions($update); break;
                            case 'updateUserName':               $tg_parser_update->updateUserName($update); break;
                            case 'updateNewChannelMessage':      $tg_parser_update->updateNewChannelMessage($update); break;
                            case 'updateEditChannelMessage':     $tg_parser_update->updateEditChannelMessage($update); break;
                        }
                    }
                }

                $content_item->is_parsed_sw = 'Y';
                $content_item->save();

                $this->apiMetrics->incPrometheus('core2_sources_tg_process', count($content), [
                    'labels'   => ['action' => 'channel_updates'],
                    'job'      => 'core2',
                    'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                ]);

                $this->db->commit();

            } catch (\Exception $e) {
                $this->db->rollback();
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;
            }
        }
    }


    /**
     * Обработка сохраненных данных о чатах
     * @param int $limit
     * @return void
     */
    public function processDialogInfo(int $limit = 100): void {

        $contents = $this->modSources->dataSourcesChatsContent->fetchAll(
            $this->modSources->dataSourcesChatsContent->select()
                ->where("type = 'tg_dialogs_info'")
                ->where("is_parsed_sw = 'N'")
                ->where("file_name IS NOT NULL")
                ->order('id ASC')
                ->limit($limit)
        );

        $tg_parser_chats = new TgParser\Chats();
        $model           = new Model();

        foreach ($contents as $content_item) {
            $this->db->beginTransaction();
            try {
                $date_day     = new \DateTime($content_item->date_created);
                $file_content = $model->getSourceFile('chats', $date_day, $content_item->file_name);
                $content      = json_decode(gzuncompress(base64_decode($file_content['content'])), true);

                $chat = $tg_parser_chats->saveDialog($content);

                if ($chat) {
                    $tg_parser_chats->saveSubscribersDay($chat->id, $date_day, $content);
                }

                $content_item->is_parsed_sw = 'Y';
                $content_item->save();

                $this->apiMetrics->incPrometheus('core2_sources_tg_process', 1, [
                    'labels'   => ['action' => 'channel_info'],
                    'job'      => 'core2',
                    'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                ]);

                $this->db->commit();

            } catch (\Exception $e) {
                $this->db->rollback();
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;
            }
        }
    }
}