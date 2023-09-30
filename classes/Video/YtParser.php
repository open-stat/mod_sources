<?php
namespace Core2\Mod\Sources\Video;

/**
 * @property \ModSourcesController $modSources
 */
class YtParser extends \Common {



    /**
     * Обработка сохраненных обновлений
     * @param int $limit
     * @return void
     */
    public function processVideos(int $limit = 100): void {

        $contents = $this->modSources->dataSourcesVideosRaw->fetchAll(
            $this->modSources->dataSourcesVideosRaw->select()
                ->where("type IN('yt_videos_popular', 'yt_videos_info', 'yt_channel_videos')")
                ->where("is_parsed_sw = 'N'")
                ->order('id ASC')
                ->limit($limit)
        );

        if (empty($contents)) {
            return;
        }

        $yt_parser_clips = new YtParser\Clips();

        foreach ($contents as $content_item) {

            $this->db->beginTransaction();
            try {
                $content = json_decode(gzuncompress($content_item->content), true);

                if ( ! empty($content)) {
                    foreach ($content as $clip) {
                        $yt_parser_clips->saveClip($clip);
                    }
                }

                $content_item->is_parsed_sw = 'Y';
                $content_item->save();

                $this->db->commit();

            } catch (\Exception $e) {
                $this->db->rollback();
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;

                $this->sendErrorMessage('Ошибка обработки видео роликов', $e);
            }
        }
    }


    /**
     * Обработка сохраненных обновлений
     * @param int $limit
     * @return void
     */
    public function processVideosSubtitles(int $limit = 100): void {

        $contents = $this->modSources->dataSourcesVideosRaw->fetchAll(
            $this->modSources->dataSourcesVideosRaw->select()
                ->where("type = 'yt_video_subtitles'")
                ->where("is_parsed_sw = 'N'")
                ->order('id ASC')
                ->limit($limit)
        );

        if (empty($contents)) {
            return;
        }

        $yt_parser_clips = new YtParser\Clips();

        foreach ($contents as $content_item) {

            $this->db->beginTransaction();
            try {
                $content   = json_decode(gzuncompress($content_item->content), true);
                $meta_data = json_decode($content_item->meta_data, true);

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

                $this->db->commit();

            } catch (\Exception $e) {
                $this->db->rollback();
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;

                $this->sendErrorMessage('Ошибка обработки субтитров', $e);
            }
        }
    }


    /**
     * Обработка сохраненных обновлений
     * @param int $limit
     * @return void
     */
    public function processVideosComments(int $limit = 100): void {

        $contents = $this->modSources->dataSourcesVideosRaw->fetchAll(
            $this->modSources->dataSourcesVideosRaw->select()
                ->where("type = 'yt_video_comments'")
                ->where("is_parsed_sw = 'N'")
                ->order('id ASC')
                ->limit($limit)
        );

        if (empty($contents)) {
            return;
        }

        $yt_parser_clips = new YtParser\Clips();

        foreach ($contents as $content_item) {

            $this->db->beginTransaction();
            try {
                $content   = json_decode(gzuncompress($content_item->content), true);
                $meta_data = json_decode($content_item->meta_data, true);

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

                $this->db->commit();

            } catch (\Exception $e) {
                $this->db->rollback();
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;

                $this->sendErrorMessage('Ошибка обработки комментариев', $e);
            }
        }
    }


    /**
     * Обработка сохраненных данных о каналах
     * @param int $limit
     * @return void
     */
    public function processChannelInfo(int $limit = 100): void {

        $contents = $this->modSources->dataSourcesVideosRaw->fetchAll(
            $this->modSources->dataSourcesVideosRaw->select()
                ->where("type = 'yt_channel_info'")
                ->where("is_parsed_sw = 'N'")
                ->order('id ASC')
                ->limit($limit)
        );

        if (empty($contents)) {
            return;
        }

        $yt_parser_channels = new YtParser\Channels();

        foreach ($contents as $content_item) {
            $this->db->beginTransaction();
            try {
                $content   = json_decode(gzuncompress($content_item->content), true);
                $meta_data = json_decode($content_item->meta_data, true);

                $channel = $yt_parser_channels->saveChannel($content);

                if ($channel) {
                    $date_day = ! empty($meta_data['date'])
                        ? new \DateTime($meta_data['date'])
                        : new \DateTime($content_item->date_created);

                    $yt_parser_channels->saveChannelStatDay($channel->id, $date_day, $content);
                }

                $content_item->is_parsed_sw = 'Y';
                $content_item->save();

                $this->db->commit();

            } catch (\Exception $e) {
                $this->db->rollback();
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;

                $this->sendErrorMessage('Ошибка обработки субтитров', $e);
            }
        }
    }


    /**
     * Обработка сохраненных данных о статистике канала
     * @param int $limit
     * @return void
     */
    public function processChannelStats(int $limit = 100): void {

        $contents = $this->modSources->dataSourcesVideosRaw->fetchAll(
            $this->modSources->dataSourcesVideosRaw->select()
                ->where("type = 'yt_channels_stats'")
                ->where("is_parsed_sw = 'N'")
                ->order('id ASC')
                ->limit($limit)
        );

        if (empty($contents)) {
            return;
        }

        $yt_parser_channels = new YtParser\Channels();

        foreach ($contents as $content_item) {
            $this->db->beginTransaction();
            try {
                $content   = json_decode(gzuncompress($content_item->content), true);
                $meta_data = json_decode($content_item->meta_data, true);

                if ( ! empty($content)) {
                    foreach ($content as $channel_stat) {
                        if ( ! empty($channel_stat['id'])) {

                            $channel = $this->modSources->dataSourcesVideos->getRowByYtChannelId($channel_stat['id']);

                            if ($channel) {
                                $date_day = ! empty($meta_data['date'])
                                    ? new \DateTime($meta_data['date'])
                                    : new \DateTime($content_item->date_created);

                                $yt_parser_channels->saveChannelStatDay($channel->id, $date_day, $channel_stat);
                            }
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

                $this->sendErrorMessage('Ошибка обработки статистика каналов', $e);
            }
        }
    }
}