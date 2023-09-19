<?php
namespace Core2\Mod\Sources\Video\YtParser;


/**
 * @property \ModSourcesController $modSources
 */
abstract class Common extends \Common {

    private static array $links    = [];
    private static array $hashtags = [];


    /**
     * @param string $hashtag
     * @return \Zend_Db_Table_Row_Abstract
     */
    protected function getHashtag(string $hashtag): \Zend_Db_Table_Row_Abstract {

        if (isset(self::$hashtags[$hashtag])) {
            return self::$hashtags[$hashtag];
        }

        $source_hashtag           = $this->modSources->dataSourcesVideosHashtags->save($hashtag);
        self::$hashtags[$hashtag] = $source_hashtag;

        return $source_hashtag;
    }


    /**
     * Получение ссылки
     * @param string     $url
     * @param array|null $options
     * @return \Zend_Db_Table_Row_Abstract
     */
    protected function getLink(string $url, array $options = null): \Zend_Db_Table_Row_Abstract {

        if (isset(self::$links[$url])) {
            $link    = self::$links[$url];
            $is_save = false;

            if (empty($link->title) && ! empty($options['title'])) {
                $link->title = $options['title'];
                $is_save     = true;
            }
            if (empty($link->description) && ! empty($options['description'])) {
                $link->description = $options['description'];
                $is_save           = true;
            }
            if (empty($link->type) && ! empty($options['type'])) {
                $link->type = $options['type'];
                $is_save    = true;
            }

            if ($is_save) {
                $link->save();
            }

            return $link;
        }

        $source_link       = $this->modSources->dataSourcesVideosLinks->save($url);
        self::$links[$url] = $source_link;

        return $source_link;
    }


    /**
     * Получение ссылок из текста
     * @param string $text
     * @return array
     */
    protected function getLinks(string $text): array {

        preg_match_all('~((ftp|http|https):\/\/)?(www\.)?([A-Za-zА-Яа-я0-9]{1}[A-Za-zА-Яа-я0-9\-]*\.?)+\.{1}[A-Za-zА-Яа-я-]{2,8}(\/([\[\]\(\)\{\}\|\w\#\~\'\!\;\:\.\,\?\+\*\=\&\%\@\!\-\/])*)?~miu', $text, $matches);

        $links = [];

        if ( ! empty($matches[0])) {
            foreach ($matches[0] as $match) {
                if ( ! empty($match)) {
                    $link = trim($match, '.');

                    if (filter_var($link, FILTER_VALIDATE_URL)) {
                        $links[] = $link;

                    } elseif (filter_var("https://$link", FILTER_VALIDATE_URL)) {
                        $links[] = $link;
                    }
                }
            }
        }

        return array_unique($links);
    }


    /**
     * Получение хэштегов из текста
     * @param string $text
     * @return array
     */
    protected function getHashtags(string $text): array {

        preg_match_all('~(#[\w\da-zA-Zа-яА-Я]+)~ium', $text, $matches);

        $hashtags = [];

        if ( ! empty($matches[0])) {
            foreach ($matches[0] as $match) {
                if ( ! empty($match)) {
                    $hashtags[] = trim($match, '.');
                }
            }
        }

        return array_unique($hashtags);
    }


    /**
     * Поиск и сохранение ссылок на каналы или видео
     * @param string $content
     * @return void
     * @throws \Exception
     */
    protected function saveChannelsVideos(string $content): void {

        $links = $this->getLinks($content);

        foreach ($links as $link) {
            if ($channel_id = $this->getYtChannelId($link)) {
                $channel = $this->modSources->dataSourcesVideos->getRowByYtChannelId($channel_id);

                if (empty($channel)) {
                    $this->modSources->dataSourcesVideos->save($channel_id, 'yt');
                }

            } elseif ($channel_name = $this->getYtChannelName($link)) {
                $channel = $this->modSources->dataSourcesVideos->getRowByTypeName('yt', $channel_name);

                if (empty($channel)) {
                    $this->modSources->dataSourcesVideos->saveEmptyByName($channel_name, 'yt');
                }

            } elseif ($video_id = $this->getYtVideoId($link)) {
                $clip = $this->modSources->dataSourcesVideosClips->getRowByTypePlatformId('yt', $video_id);

                if (empty($clip)) {
                    $this->modSources->dataSourcesVideosClips->save($video_id, [
                        'type' => 'yt',
                        'url'  => $link,
                    ]);
                }
            }
        }
    }


    /**
     * @param string $url
     * @return string|null
     */
    protected function getYtChannelId(string $url):? string {

        if ( ! str_contains($url, 'youtube.com')) {
            return null;
        }

        $array = parse_url($url);
        $path  = $array['path'] ?? '';

        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, '/channel/') ||
            str_starts_with($path, '/c/')
        ) {
            $segments   = explode('/', $path);
            $channel_id = $segments[2] ?? null;

            if ($channel_id && strlen($channel_id) == 24) {
                return $channel_id;
            }
        }

        return null;
    }


    /**
     * @param string $url
     * @return string|null
     */
    protected function getYtChannelName(string $url):? string {

        if ( ! str_contains($url, 'youtube.com')) {
            return null;
        }

        $array = parse_url($url);
        $path  = $array['path'] ?? '';

        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, '/channel/') ||
            str_starts_with($path, '/c/') ||
            str_starts_with($path, '/user/')
        ) {
            $segments   = explode('/', $path);
            $channel_id = $segments[2] ?? null;

            if ($channel_id && strlen($channel_id) != 24) {
                return $channel_id;
            }

        } elseif (preg_match('~^/\@([^/]*)~', $path, $match)) {
            return $match[1] ?? null;

        } else {
            $reserve_path = [
                '/feed',
                '/shorts',
                '/playlist',
                '/watch',
                '/embed',
                '/premium',
                '/gaming',
                '/account',
                '/reporthistory',
                '/upload',
                '/account_notifications',
                '/logout',
                '/paid_memberships',
            ];

            $reserve_paths = implode('|', $reserve_path);

            if ( ! preg_match("~^{$reserve_paths}~", $path)) {
                $segments   = explode('/', $path);
                $channel_id = $segments[1] ?? null;


                if ($channel_id) {
                    return $channel_id;
                }
            }
        }


        return null;
    }


    /**
     * @param string $url
     * @return string|null
     */
    protected function getYtVideoId(string $url):? string {

        if (str_contains($url, 'youtube.com')) {
            if (strpos($url, 'embed')) {
                $array = parse_url($url);
                $path  = $array['path'] ?? null;

                return $path ? substr($path, 7) : null;

            } else {
                $params = $this->getQueryParams($url);
                return $params['v'] ?? null;
            }

        } else if (str_contains($url, 'youtu.be')) {
            $array = parse_url($url);
            $path  = $array['path'] ?? null;

            return $path ? substr($path, 1, 11) : null;
        }

        return null;
    }


    /**
     * @param string $url
     * @return array
     */
    private function getQueryParams(string $url): array {

        $array = parse_url($url);
        $query = $array['query'] ?? null;

        if (empty($query)) {
            return [];
        }

        $query_parts = explode('&', $query);
        $params      = [];

        foreach ($query_parts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = empty($item[1]) ? '' : $item[1];
        }

        return $params;
    }
}