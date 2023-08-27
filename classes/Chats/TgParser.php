<?php
namespace Core2\Mod\Sources\Chats;

/**
 * @property \ModSourcesController $modSources
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
                ->order('id ASC')
                ->limit($limit)
        );

        $tg_parser_history = new TgParser\History();

        foreach ($history as $item) {

            $this->db->beginTransaction();
            try {
                $content = json_decode(gzuncompress($item->content_bin), true);

                if ( ! empty($content['users'])) {
                    foreach ($content['users'] as $user) {
                        $tg_parser_history->saveUser($user);
                    }
                }

                if ( ! empty($content['chats'])) {
                    foreach ($content['chats'] as $chat) {
                        $tg_parser_history->saveChat($chat);
                    }
                }

                if ( ! empty($content['messages'])) {
                    foreach ($content['messages'] as $message) {
                        $tg_parser_history->saveMessage($message);
                    }
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
                ->order('id ASC')
                ->limit($limit)
        );

        $tg_parser_update = new TgParser\Update();

        foreach ($contents as $content_item) {

            $this->db->beginTransaction();
            try {
                $content = json_decode(gzuncompress($content_item->content_bin), true);

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
                ->order('id ASC')
                ->limit($limit)
        );

        $tg_parser_chats = new TgParser\Chats();

        foreach ($contents as $content_item) {
            $this->db->beginTransaction();
            try {
                $content   = json_decode(gzuncompress($content_item->content_bin), true);
                $meta_data = json_decode($content_item->meta_data, true);

                $chat = $tg_parser_chats->saveDialog($content);

                if ($chat) {
                    $date_day = ! empty($meta_data['date'])
                        ? new \DateTime($meta_data['date'])
                        : new \DateTime($content_item->date_created);

                    $tg_parser_chats->saveSubscribersDay($chat->id, $date_day, $content);
                }

                $content_item->is_parsed_sw = 'Y';
                $content_item->save();

                $this->db->commit();

            } catch (\Exception $e) {
                $this->db->rollback();
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;
            }
        }
    }
}