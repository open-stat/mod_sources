<?php
namespace Core2\Mod\Sources\Video;
use Core2\Mod\Sources\Model;
use Core2\Parallel;

require_once DOC_ROOT . 'core2/inc/classes/Parallel.php';


/**
 * @property \ModSourcesController $modSources
 * @property \ModMetricsApi        $apiMetrics
 */
class YtParser extends \Common {


    /**
     * Обработка сохраненных обновлений
     * @param int $limit
     * @return void
     * @throws \Zend_Exception
     * @throws \Exception
     */
    public function processVideos(int $limit = 100): void {

        $contents = $this->modSources->dataSourcesVideosRaw->fetchAll(
            $this->modSources->dataSourcesVideosRaw->select()
                ->where("type IN('yt_videos_popular', 'yt_videos_info', 'yt_channel_videos')")
                ->where("is_parsed_sw = 'N'")
                ->where("file_name IS NOT NULL")
                ->order('id ASC')
                ->limit($limit)
        );

        if (empty($contents)) {
            return;
        }

        $config   = $this->getModuleConfig('sources');
        $parallel = new Parallel([
            'pool_size'    => $config?->pool_size ?: 4,
            'print_buffer' => true,
        ]);

        foreach ($contents as $content_item) {

            $parallel->addTask(function () use ($content_item) {
                $model           = new Model();
                $yt_parser_clips = new YtParser\Clips();

                try {
                    $date_day     = new \DateTime($content_item->date_created);
                    $file_content = $model->getSourceFile('videos', $date_day, $content_item->file_name);
                    $content      = json_decode(gzuncompress(base64_decode($file_content['content'])), true);

                    if ( ! empty($content)) {
                        foreach ($content as $clip) {
                            $yt_parser_clips->saveClip($clip);
                        }
                    }

                    $content_item->is_parsed_sw = 'Y';
                    $content_item->save();


                    $this->apiMetrics->incPrometheus('core2_sources_yt_process', 1, [
                        'labels'   => ['action' => 'video'],
                        'job'      => 'core2',
                        'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                    ]);

                } catch (\Exception $e) {
                    echo $e->getMessage() . PHP_EOL;
                    echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;

                    $this->sendErrorMessage("Ошибка обработки видео роликов. row_id - {$content_item->id}", $e);
                }
            });
        }

        $parallel->start();
    }


    /**
     * Обработка сохраненных обновлений
     * @param int $limit
     * @return void
     * @throws \Zend_Exception
     * @throws \Exception
     */
    public function processVideosSubtitles(int $limit = 100): void {

        $contents = $this->modSources->dataSourcesVideosRaw->fetchAll(
            $this->modSources->dataSourcesVideosRaw->select()
                ->where("type = 'yt_video_subtitles'")
                ->where("is_parsed_sw = 'N'")
                ->where("file_name IS NOT NULL")
                ->order('id ASC')
                ->limit($limit)
        );

        if (empty($contents)) {
            return;
        }

        $config   = $this->getModuleConfig('sources');
        $parallel = new Parallel([
            'pool_size'    => $config?->pool_size ?: 4,
            'print_buffer' => true,
        ]);

        foreach ($contents as $content_item) {

            $parallel->addTask(function () use ($content_item) {

                $yt_parser_clips = new YtParser\Clips();
                $model           = new Model();

                try {
                    $date_day     = new \DateTime($content_item->date_created);
                    $file_content = $model->getSourceFile('videos', $date_day, $content_item->file_name);
                    $content      = json_decode(gzuncompress(base64_decode($file_content['content'])), true);
                    $meta_data    = $file_content['meta'];

                    if ( ! empty($content) &&
                         ! empty($meta_data) &&
                         ! empty($meta_data['video_id'])
                    ) {
                        $clip = $this->modSources->dataSourcesVideosClips->getRowByTypePlatformId('yt', $meta_data['video_id']);

                        if (empty($clip)) {
                            $clip = $this->modSources->dataSourcesVideosClips->save($meta_data['video_id'], [
                                'type' => 'yt'
                            ]);
                        }

                        $yt_parser_clips->saveClipSubtitles($clip->id, $content);
                    }

                    $content_item->is_parsed_sw = 'Y';
                    $content_item->save();

                    $this->apiMetrics->incPrometheus('core2_sources_yt_process', 1, [
                        'labels'   => ['action' => 'video_subtitles'],
                        'job'      => 'core2',
                        'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                    ]);

                } catch (\Exception $e) {
                    echo $e->getMessage() . PHP_EOL;
                    echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;

                    $this->sendErrorMessage("Ошибка обработки субтитров. row_id - {$content_item->id}", $e);
                }
            });
        }


        $parallel->start();
    }


    /**
     * Обработка сохраненных обновлений
     * @param int $limit
     * @return void
     * @throws \Exception
     */
    public function processVideosComments(int $limit = 100): void {

        $contents = $this->modSources->dataSourcesVideosRaw->fetchAll(
            $this->modSources->dataSourcesVideosRaw->select()
                ->where("type = 'yt_video_comments'")
                ->where("is_parsed_sw = 'N'")
                ->where("file_name IS NOT NULL")
                ->order('id ASC')
                ->limit($limit)
        );

        if (empty($contents)) {
            return;
        }

        $config   = $this->getModuleConfig('sources');
        $parallel = new Parallel([
            'pool_size'    => $config?->pool_size ?: 4,
            'print_buffer' => true,
        ]);

        foreach ($contents as $content_item) {

            $parallel->addTask(function () use ($content_item) {
                $yt_parser_clips = new YtParser\Clips();
                $model           = new Model();

                $this->db->beginTransaction();
                try {
                    $date_day     = new \DateTime($content_item->date_created);
                    $file_content = $model->getSourceFile('videos', $date_day, $content_item->file_name);
                    $content      = json_decode(gzuncompress(base64_decode($file_content['content'])), true);
                    $meta_data    = $file_content['meta'];

                    if ( ! empty($content) &&
                         ! empty($meta_data) &&
                         ! empty($meta_data['video_id'])
                    ) {
                        $clip = $this->modSources->dataSourcesVideosClips->getRowByTypePlatformId('yt', $meta_data['video_id']);

                        if (empty($clip)) {
                            $clip = $this->modSources->dataSourcesVideosClips->save($meta_data['video_id'], [
                                'type' => 'yt'
                            ]);
                        }

                        $yt_parser_clips->saveClipComments($clip->id, $content);
                    }

                    $content_item->is_parsed_sw = 'Y';
                    $content_item->save();


                    $this->apiMetrics->incPrometheus('core2_sources_yt_process', 1, [
                        'labels'   => ['action' => 'video_comments'],
                        'job'      => 'core2',
                        'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                    ]);

                    $this->db->commit();

                } catch (\Exception $e) {
                    $this->db->rollback();
                    echo $e->getMessage() . PHP_EOL;
                    echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;

                    $this->sendErrorMessage("Ошибка обработки комментариев. row_id - {$content_item->id}", $e);
                }
            });
        }


        $parallel->start();
    }


    /**
     * Обработка сохраненных данных о каналах
     * @param int $limit
     * @return void
     * @throws \Exception
     */
    public function processChannelInfo(int $limit = 100): void {

        $contents = $this->modSources->dataSourcesVideosRaw->fetchAll(
            $this->modSources->dataSourcesVideosRaw->select()
                ->where("type = 'yt_channel_info'")
                ->where("is_parsed_sw = 'N'")
                ->where("file_name IS NOT NULL")
                ->order('id ASC')
                ->limit($limit)
        );

        if (empty($contents)) {
            return;
        }

        $config   = $this->getModuleConfig('sources');
        $parallel = new Parallel([
            'pool_size'    => $config?->pool_size ?: 4,
            'print_buffer' => true,
        ]);


        foreach ($contents as $content_item) {

            $parallel->addTask(function () use ($content_item) {
                $yt_parser_channels = new YtParser\Channels();
                $model              = new Model();

                $this->db->beginTransaction();
                try {
                    $date_day     = new \DateTime($content_item->date_created);
                    $file_content = $model->getSourceFile('videos', $date_day, $content_item->file_name);
                    $content      = json_decode(gzuncompress(base64_decode($file_content['content'])), true);

                    $channel = $yt_parser_channels->saveChannel($content);

                    if ($channel) {
                        $yt_parser_channels->saveChannelStatDay($channel->id, $date_day, $content);
                    }

                    $content_item->is_parsed_sw = 'Y';
                    $content_item->save();

                    $this->apiMetrics->incPrometheus('core2_sources_yt_process', 1, [
                        'labels'   => ['action' => 'channel_info'],
                        'job'      => 'core2',
                        'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                    ]);

                    $this->db->commit();

                } catch (\Exception $e) {
                    $this->db->rollback();
                    echo $e->getMessage() . PHP_EOL;
                    echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;

                    $this->sendErrorMessage("Ошибка обработки информации о каналах. row_id = {$content_item->id}", $e);
                }
            });
        }


        $parallel->start();
    }


    /**
     * Обработка сохраненных данных о статистике канала
     * @param int $limit
     * @return void
     * @throws \Exception
     */
    public function processChannelStats(int $limit = 100): void {

        $contents = $this->modSources->dataSourcesVideosRaw->fetchAll(
            $this->modSources->dataSourcesVideosRaw->select()
                ->where("type = 'yt_channels_stats'")
                ->where("is_parsed_sw = 'N'")
                ->where("file_name IS NOT NULL")
                ->order('id ASC')
                ->limit($limit)
        );

        if (empty($contents)) {
            return;
        }

        $config   = $this->getModuleConfig('sources');
        $parallel = new Parallel([
            'pool_size'    => $config?->pool_size ?: 4,
            'print_buffer' => true,
        ]);

        foreach ($contents as $content_item) {

            $parallel->addTask(function () use ($content_item) {
                $yt_parser_channels = new YtParser\Channels();
                $model              = new Model();

                try {
                    $date_day     = new \DateTime($content_item->date_created);
                    $file_content = $model->getSourceFile('videos', $date_day, $content_item->file_name);
                    $content      = json_decode(gzuncompress(base64_decode($file_content['content'])), true);

                    if ( ! empty($content)) {
                        foreach ($content as $channel_stat) {
                            if ( ! empty($channel_stat['id'])) {

                                $channel = $this->modSources->dataSourcesVideos->getRowByYtChannelId($channel_stat['id']);

                                if ($channel) {
                                    $yt_parser_channels->saveChannelStatDay($channel->id, $date_day, $channel_stat);
                                }
                            }
                        }
                    }

                    $content_item->is_parsed_sw = 'Y';
                    $content_item->save();

                    $this->apiMetrics->incPrometheus('core2_sources_yt_process', count($content), [
                        'labels'   => ['action' => 'channel_stat'],
                        'job'      => 'core2',
                        'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                    ]);


                } catch (\Exception $e) {
                    echo $e->getMessage() . PHP_EOL;
                    echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;


                    $this->sendErrorMessage("Ошибка обработки статистика каналов. {$e->getCode()} raw_id = {$content_item->id}", $e);
                }
            });
        }

        $parallel->start();
    }
}