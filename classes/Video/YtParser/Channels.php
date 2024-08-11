<?php
namespace Core2\Mod\Sources\Video\YtParser;

/**
 * @property \ModSourcesController $modSources
 */
class Channels extends Common {


    /**
     * @param array $content
     * @return \Zend_Db_Table_Row_Abstract|null
     * @throws \Exception
     */
    public function saveChannel(array $content):? \Zend_Db_Table_Row_Abstract {

        if (empty($content['id'])) {
            return null;
        }

        $snippet    = $content['snippet'] ?? [];
        $statistics = $content['statistics'] ?? [];

        $channel = $this->modSources->dataSourcesVideos->save($content['id'], 'yt', [
            'title'                 => ! empty($snippet['title']) ? $snippet['title'] : null,
            'name'                  => ! empty($snippet['customUrl']) ? ltrim($snippet['customUrl'], '@') : null,
            'description'           => ! empty($snippet['description']) ? $snippet['description'] : null,
            'geolocation'           => ! empty($snippet['country']) ? $snippet['country'] : null,
            'date_platform_created' => ! empty($snippet['publishedAt']) ? date('Y-m-d H:i:s', strtotime($snippet['publishedAt'])) : null,
            'subscribers_count'     => ! empty($statistics['subscriberCount']) ? $statistics['subscriberCount'] : null,
        ]);

        if ( ! empty($snippet['description'])) {
            $this->saveHashtags($channel->id, $snippet['description']);
            $this->saveLinks($channel->id, $snippet['description']);
            $this->saveChannelsVideos($snippet['description']);
        }

        return $channel;
    }


    /**
     * @param int       $channel_id
     * @param \DateTime $day
     * @param array     $content
     * @return void
     * @throws \Exception
     */
    public function saveChannelStatDay(int $channel_id, \DateTime $day, array $content): void {

        if (empty($content['id']) || empty($content['statistics'])) {
            return;
        }

        $statistics = $content['statistics'];

        $this->modSources->dataSourcesVideos->save($content['id'], 'yt', [
            'subscribers_count' => ! empty($statistics['subscriberCount']) ? $statistics['subscriberCount'] : null,
        ]);

        $this->modSources->dataSourcesVideosStats->save($channel_id, $day, [
            'subscribers_count' => ! empty($statistics['subscriberCount']) ? $statistics['subscriberCount'] : null,
            'view_count'        => ! empty($statistics['viewCount']) ? $statistics['viewCount'] : null,
            'video_count'       => ! empty($statistics['videoCount']) ? $statistics['videoCount'] : null,
        ]);
    }


    /**
     * @param int    $channel_id
     * @param string $content
     * @return void
     */
    private function saveHashtags(int $channel_id, string $content): void {

        $hashtags = $this->getHashtags($content);

        foreach ($hashtags as $hashtag) {
            $source_hashtag = $this->getHashtag($hashtag);

            $this->modSources->dataSourcesVideosChannelsHashtags->save($channel_id, $source_hashtag->id);
        }
    }


    /**
     * @param int    $channel_id
     * @param string $content
     * @return void
     */
    private function saveLinks(int $channel_id, string $content): void {

        $hashtags = $this->getLinks($content);

        foreach ($hashtags as $hashtag) {
            $source_link = $this->getLink($hashtag);

            $this->modSources->dataSourcesVideosChannelsLinks->save($channel_id, $source_link->id);
        }
    }
}