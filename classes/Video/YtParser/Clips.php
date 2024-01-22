<?php
namespace Core2\Mod\Sources\Video\YtParser;

/**
 * @property \ModSourcesController $modSources
 */
class Clips extends Common {


    /**
     * @param array $content
     * @return \Zend_Db_Table_Row_Abstract|null
     * @throws \Exception
     */
    public function saveClip(array $content):? \Zend_Db_Table_Row_Abstract {

        if (empty($content['id'])) {
            return null;
        }

        $platform_id = is_array($content['id'])
            ? ( ! empty($content['id']['videoId']) ? $content['id']['videoId'] : '')
            : $content['id'];

        if (empty($platform_id)) {
            return null;
        }

        if ( ! is_string($platform_id)) {
            throw new \Exception('Некорректный идентификатор видео ролика');
        }

        $snippet    = $content['snippet'] ?? [];
        $details    = $content['contentDetails'] ?? [];
        $statistics = $content['statistics'] ?? [];
        $thumbnails = $snippet['thumbnails'] ?? [];


        if (empty($snippet['channelId'])) {
            return null;
        }

        $channel = $this->modSources->dataSourcesVideos->getRowByYtChannelId($snippet['channelId']);

        if (empty($channel)) {
            $channel = $this->modSources->dataSourcesVideos->save($snippet['channelId'], 'yt', [
                'title' => $snippet['channelTitle'] ?? null
            ]);
        }

        $clip = $this->modSources->dataSourcesVideosClips->save($platform_id, [
            'type'                  => 'yt',
            'channel_id'            => $channel->id,
            'title'                 => ! empty($snippet['title']) ? $snippet['title'] : null,
            'url'                   => "https://youtube.com/watch?v={$platform_id}",
            'description'           => ! empty($snippet['description']) ? $snippet['description'] : null,
            'date_platform_created' => ! empty($snippet['publishedAt']) ? date('Y-m-d H:i:s', strtotime($snippet['publishedAt'])) : null,
            'default_lang'          => ! empty($snippet['defaultLanguage']) ? $snippet['defaultLanguage'] : null,
            'duration'              => ! empty($details['duration']) ? new \DateInterval($details['duration']) : null,
            'viewed_count'          => ! empty($statistics['viewCount']) ? $statistics['viewCount'] : null,
            'likes_count'           => ! empty($statistics['likeCount']) ? $statistics['likeCount'] : null,
            'comments_count'        => ! empty($statistics['commentCount']) ? $statistics['commentCount'] : null,
            'is_load_info_sw'       => ! empty($snippet) && ! empty($details) && ! empty($statistics) ? 'Y' : 'N',
        ]);

        if ( ! empty($snippet['title'])) {
            $this->saveHashtags($clip->id, $snippet['title']);
        }

        if ( ! empty($snippet['description'])) {
            $this->saveHashtags($clip->id, $snippet['description']);
            $this->saveLinks($clip->id, $snippet['description']);
            $this->saveChannelsVideos($snippet['description']);
        }

        if ( ! empty($snippet['tags'])) {
            foreach ($snippet['tags'] as $hashtag) {
                if (is_string($hashtag)) {
                    $source_hashtag = $this->getHashtag($hashtag);

                    $this->modSources->dataSourcesVideosClipsHashtags->save($clip->id, $source_hashtag->id);
                }
            }
        }

        if ( ! empty($thumbnails)) {
            $this->modSources->dataSourcesVideosClipsFiles->saveFileEmpty($clip->id, $thumbnails);
        }

        return $clip;
    }


    /**
     * @param int   $clip_id
     * @param array $subtitles
     * @return void
     */
    public function saveClipSubtitles(int $clip_id, array $subtitles): void {

        $subtitles_clean = $this->getCleanSubtitlesCue($subtitles);

        if (empty($subtitles_clean)) {
            $subtitles_clean = $this->getCleanSubtitlesSearch($subtitles);
        }

        if ( ! empty($subtitles_clean)) {
            $content = [];

            foreach ($subtitles_clean['items'] as $item) {
                $content[] = $item['text'];
            }

            $this->modSources->dataSourcesVideosClipsSubtitles->save($clip_id, $subtitles_clean['lang'], [
                'content'      => implode(' ', $content),
                'content_time' => $subtitles_clean['items'],
            ]);
        }
    }


    /**
     * @param int   $clip_id
     * @param array $comments
     * @return void
     * @throws \Exception
     */
    public function saveClipComments(int $clip_id, array $comments): void {

        foreach ($comments as $comment) {

            if (empty($comment['id']) || empty($comment['snippet'])) {
                continue;
            }

            $snippet = $comment['snippet'];
            $replies = $comment['replies'] ?? null;


            if ( ! empty($snippet['topLevelComment'])) {
                $top_comment = $snippet['topLevelComment'];

                if ( ! empty($top_comment['id']) && ! empty($top_comment['snippet'])) {
                    $this->saveComment($clip_id, $top_comment['id'], $top_comment['snippet']);
                }

            } else {
                $this->saveComment($clip_id, $comment['id'], $snippet);
            }


            if ( ! empty($replies) && ! empty($replies['comments'])) {
                foreach ($replies['comments'] as $reply_comment) {

                    if ( ! empty($reply_comment['id']) && ! empty($reply_comment['snippet'])) {
                        $this->saveComment($clip_id, $reply_comment['id'], $reply_comment['snippet']);
                    }
                }
            }
        }
    }


    /**
     * @param array $subtitles
     * @return array|null
     */
    private function getCleanSubtitlesSearch(array $subtitles):? array {

        if (empty($subtitles['actions']) ||
            empty($subtitles['actions'][0]) ||
            empty($subtitles['actions'][0]['updateEngagementPanelAction']) ||
            empty($subtitles['actions'][0]['updateEngagementPanelAction']['content']) ||
            empty($subtitles['actions'][0]['updateEngagementPanelAction']['content']['transcriptRenderer']) ||
            empty($subtitles['actions'][0]['updateEngagementPanelAction']['content']['transcriptRenderer']['content']['transcriptSearchPanelRenderer'])
        ) {
            return null;
        }

        $transcript = $subtitles['actions'][0]['updateEngagementPanelAction']['content']['transcriptRenderer']['content']['transcriptSearchPanelRenderer'];

        if (empty($transcript['body']) ||
            empty($transcript['body']['transcriptSegmentListRenderer']) ||
            empty($transcript['body']['transcriptSegmentListRenderer']['initialSegments']) ||
            empty($transcript['footer']) ||
            empty($transcript['footer']['transcriptFooterRenderer']) ||
            empty($transcript['footer']['transcriptFooterRenderer']['languageMenu']) ||
            empty($transcript['footer']['transcriptFooterRenderer']['languageMenu']['sortFilterSubMenuRenderer']) ||
            empty($transcript['footer']['transcriptFooterRenderer']['languageMenu']['sortFilterSubMenuRenderer']['subMenuItems']) ||
            empty($transcript['footer']['transcriptFooterRenderer']['languageMenu']['sortFilterSubMenuRenderer']['subMenuItems'][0]) ||
            empty($transcript['footer']['transcriptFooterRenderer']['languageMenu']['sortFilterSubMenuRenderer']['subMenuItems'][0]['title'])
        ) {
            return null;
        }

        $segments = $transcript['body']['transcriptSegmentListRenderer']['initialSegments'];
        $lang     = $transcript['footer']['transcriptFooterRenderer']['languageMenu']['sortFilterSubMenuRenderer']['subMenuItems'][0]['title'];

        $lang = str_replace(['(auto-generated)', '(создано автоматически)'], '', $lang);
        $lang = trim($lang);


        $content_times = [];


        foreach ($segments as $segment) {
            if ( ! empty($segment['transcriptSegmentRenderer']) &&
                   isset($segment['transcriptSegmentRenderer']['startMs']) &&
                   isset($segment['transcriptSegmentRenderer']['endMs']) &&
                 ! empty($segment['transcriptSegmentRenderer']['snippet']) &&
                 ! empty($segment['transcriptSegmentRenderer']['snippet']['runs']) &&
                 ! empty($segment['transcriptSegmentRenderer']['snippet']['runs'][0]) &&
                   isset($segment['transcriptSegmentRenderer']['snippet']['runs'][0]['text'])
            ) {
                $start_text = ! empty($segment['transcriptSegmentRenderer']['startTimeText']) && ! empty($segment['transcriptSegmentRenderer']['startTimeText']['simpleText'])
                    ? $segment['transcriptSegmentRenderer']['startTimeText']['simpleText']
                    : '';

                $content_times[] = [
                    'start_text' => $start_text,
                    'start_ms'   => (int)$segment['transcriptSegmentRenderer']['startMs'],
                    'end_ms'     => (int)$segment['transcriptSegmentRenderer']['endMs'],
                    'text'       => trim($segment['transcriptSegmentRenderer']['snippet']['runs'][0]['text']),
                ];
            }
        }

        return [
            'lang'  => $lang,
            'items' => $content_times,
        ];
    }


    /**
     * @param array $subtitles
     * @return array|null
     */
    private function getCleanSubtitlesCue(array $subtitles):? array {

        if (empty($subtitles['actions']) ||
            empty($subtitles['actions'][0]) ||
            empty($subtitles['actions'][0]['updateEngagementPanelAction']) ||
            empty($subtitles['actions'][0]['updateEngagementPanelAction']['content']) ||
            empty($subtitles['actions'][0]['updateEngagementPanelAction']['content']['transcriptRenderer'])
        ) {
            return null;
        }

        $transcript = $subtitles['actions'][0]['updateEngagementPanelAction']['content']['transcriptRenderer'];

        if (empty($transcript['body']) ||
            empty($transcript['body']['transcriptBodyRenderer']) ||
            empty($transcript['body']['transcriptBodyRenderer']['cueGroups']) ||
            empty($transcript['footer']) ||
            empty($transcript['footer']['transcriptFooterRenderer']) ||
            empty($transcript['footer']['transcriptFooterRenderer']['languageMenu']) ||
            empty($transcript['footer']['transcriptFooterRenderer']['languageMenu']['sortFilterSubMenuRenderer']) ||
            empty($transcript['footer']['transcriptFooterRenderer']['languageMenu']['sortFilterSubMenuRenderer']['subMenuItems']) ||
            empty($transcript['footer']['transcriptFooterRenderer']['languageMenu']['sortFilterSubMenuRenderer']['subMenuItems'][0]) ||
            empty($transcript['footer']['transcriptFooterRenderer']['languageMenu']['sortFilterSubMenuRenderer']['subMenuItems'][0]['title'])
        ) {
            return null;
        }

        $segments = $transcript['body']['transcriptBodyRenderer']['cueGroups'];
        $lang     = $transcript['footer']['transcriptFooterRenderer']['languageMenu']['sortFilterSubMenuRenderer']['subMenuItems'][0]['title'];

        $lang = str_replace(['(auto-generated)', '(создано автоматически)'], '', $lang);
        $lang = trim($lang);

        $content_times = [];


        foreach ($segments as $segment) {
            if ( ! empty($segment['transcriptCueGroupRenderer']) &&
                 ! empty($segment['transcriptCueGroupRenderer']['cues']) &&
                 ! empty($segment['transcriptCueGroupRenderer']['cues'][0]) &&
                 ! empty($segment['transcriptCueGroupRenderer']['cues'][0]['transcriptCueRenderer']) &&
                 ! empty($segment['transcriptCueGroupRenderer']['cues'][0]['transcriptCueRenderer']['cue']) &&
                   isset($segment['transcriptCueGroupRenderer']['cues'][0]['transcriptCueRenderer']['cue']['simpleText']) &&
                   isset($segment['transcriptCueGroupRenderer']['cues'][0]['transcriptCueRenderer']['startOffsetMs']) &&
                   isset($segment['transcriptCueGroupRenderer']['cues'][0]['transcriptCueRenderer']['durationMs'])
            ) {
                $start_text = ! empty($segment['transcriptCueGroupRenderer']['formattedStartOffset']) &&
                              ! empty($segment['transcriptCueGroupRenderer']['formattedStartOffset']['simpleText'])
                    ? $segment['transcriptCueGroupRenderer']['formattedStartOffset']['simpleText']
                    : '';

                $start_ms   = $segment['transcriptCueGroupRenderer']['cues'][0]['transcriptCueRenderer']['startOffsetMs'];
                $duration_ms = $segment['transcriptCueGroupRenderer']['cues'][0]['transcriptCueRenderer']['durationMs'];

                $content_times[] = [
                    'start_text' => $start_text,
                    'start_ms'   => (int)$start_ms,
                    'end_ms'     => $start_ms + $duration_ms,
                    'text'       => trim($segment['transcriptCueGroupRenderer']['cues'][0]['transcriptCueRenderer']['cue']['simpleText']),
                ];
            }
        }

        return [
            'lang'  => $lang,
            'items' => $content_times,
        ];
    }


    /**
     * @param int    $clip_id
     * @param string $content
     * @return void
     */
    private function saveHashtags(int $clip_id, string $content): void {

        $hashtags = $this->getHashtags($content);

        foreach ($hashtags as $hashtag) {
            $source_hashtag = $this->getHashtag($hashtag);

            $this->modSources->dataSourcesVideosClipsHashtags->save($clip_id, $source_hashtag->id);
        }
    }


    /**
     * @param int    $clip_id
     * @param string $content
     * @return void
     */
    private function saveLinks(int $clip_id, string $content): void {

        $links = $this->getLinks($content);

        foreach ($links as $link) {
            $source_link = $this->getLink($link);

            $this->modSources->dataSourcesVideosClipsLinks->save($clip_id, $source_link->id);
        }
    }


    /**
     * @param int    $clip_id
     * @param string $comment_id
     * @param array  $snippet
     * @return void
     * @throws \Exception
     */
    private function saveComment(int $clip_id, string $comment_id, array $snippet): void {

        if ( ! empty($snippet['authorChannelId'])) {
            $author_channel_id = is_array($snippet['authorChannelId'])
                ? ($snippet['authorChannelId']['value'] ?? '')
                : (is_string($snippet['authorChannelId']) ? $snippet['authorChannelId'] : '');

        } else {
            $author_channel_id = 'yt_empty_user';
        }


        if (empty($author_channel_id)) {
            echo "В комментарии не найдены данные пользователя: " . json_encode($snippet) . PHP_EOL;
            return;
        }

        $content = $snippet['textOriginal'] ?? null;

        if ($content) {
            $this->saveHashtags($clip_id, $content);
            $this->saveLinks($clip_id, $content);
            $this->saveChannelsVideos($content);
        }

        $user = $this->modSources->dataSourcesVideosUsers->save($author_channel_id, 'yt', [
            'name'               => $snippet['authorDisplayName'] ?? null,
            'profile_url'        => $snippet['authorChannelUrl'] ?? null,
            'profile_avatar_url' => $snippet['authorProfileImageUrl'] ?? null,
        ]);

        $this->modSources->dataSourcesVideosClipsComments->save($clip_id, $comment_id, $user->id, [
            'content'               => $content,
            'reply_to_id'           => $snippet['parentId'] ?? null,
            'likes_count'           => $snippet['likeCount'] ?? null,
            'date_platform_created' => ! empty($snippet['publishedAt']) ? date('Y-m-d H:i:s', strtotime($snippet['publishedAt'])) : null,
            'date_platform_modify'  => ! empty($snippet['updatedAt']) ? date('Y-m-d H:i:s', strtotime($snippet['updatedAt'])) : null,
        ]);
    }
}