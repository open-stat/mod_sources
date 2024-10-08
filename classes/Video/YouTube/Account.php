<?php
namespace Core2\Mod\Sources\Video\YouTube;
use Alaouy\Youtube\Youtube;


/**
 * @property \ModSourcesController $modSources
 */
class Account extends \Common {

    private Youtube $connection;
    private array   $options = [];


    /**
     * @param Youtube    $connection
     * @param array|null $options
     */
    public function __construct(Youtube $connection, array $options = null) {

        parent::__construct();
        $this->connection = $connection;

        if ($options) {
            $this->options = $options;
        }
    }


    /**
     * @return int|null
     */
    public function getNmbr():? int {

        return isset($this->options['nmbr']) ? (int)$this->options['nmbr'] : null;
    }


    /**
     * Проверка активности метода
     * @param string $method
     * @return bool
     */
    public function isActiveMethod(string $method): bool {

        $account = $this->getAccountRow();

        $inactive_methods = $account->inactive_methods ? @json_decode($account->inactive_methods, true) : [];
        $inactive_methods = is_array($inactive_methods) ? $inactive_methods : [];

        return empty($inactive_methods[$method]) || $inactive_methods[$method] < date('Y-m-d H:i:s');
    }


    /**
     * Временное выключение метода
     * @param string $method
     */
    public function inactiveMethod(string $method): void {

        $account = $this->getAccountRow();

        $inactive_methods = $account->inactive_methods ? @json_decode($account->inactive_methods, true) : [];
        $inactive_methods = is_array($inactive_methods) ? $inactive_methods : [];

        $random_min = rand(0, 59);
        $random_min = str_pad($random_min, 2, '0', STR_PAD_LEFT);

        $inactive_methods[$method] = date("Y-m-d 10:{$random_min}:00", strtotime("+1 day"));

        $account->inactive_methods = json_encode($inactive_methods);
        $account->save();
    }


    /**
     * Получение действий над аккаунтом
     * @return array
     */
    public function getActions(): array {

        $actions = $this->options['actions'] ?? '';

        $actions_explode = $actions ? explode(',', $actions) : [];
        $actions_explode = array_map('trim', $actions_explode);

        return array_map('strtolower', $actions_explode);
    }


    /**
     * Получение апи ключа
     * @return string|null
     */
    public function getApikey():? string {

        return $this->options['apikey'] ?? null;
    }


    /**
     * Получение популярных видео
     * @param array $videos_id
     * @return array|null
     * @throws \Exception
     */
    public function getVideosInfo(array $videos_id):? array {

        $parts  = [ 'id', 'snippet', 'contentDetails', 'statistics', ];
        $result = $this->connection->getVideoInfo($videos_id, $parts);

        if ($result) {
            $result = json_decode(json_encode($result), true);
        }

        return is_array($result) ? $result : null;
    }


    /**
     * Получение популярных видео
     * @param string $region
     * @return array|null
     */
    public function getVideosPopular(string $region = ''):? array {

        $parts     = ['id', 'snippet', 'contentDetails', 'statistics'];
        $maxResult = 50; // max 50

        $result = $this->connection->getPopularVideos($region, $maxResult, $parts);

        if ($result) {
            $result = json_decode(json_encode($result), true);
        }

        return is_array($result) ? $result : null;
    }


    /**
     * Получение комментариев из под видео
     * @param string      $video_id
     * @param string|null $page_token
     * @return array|null
     * @throws \Exception
     */
    public function getVideosComments(string $video_id, string $page_token = null):? array {

        $part   = ['id', 'replies', 'snippet'];
        $params = array_filter([
            'videoId'    => $video_id,
            'maxResults' => 100,  // max 100
            'part'       => implode(',', $part),
            'order'      => 'time',
            'textFormat' => 'plainText',
        ]);

        if ($page_token) {
            $params['pageToken'] = $page_token;
        }

        $api_url  = $this->connection->getApi('commentThreads.list');
        $api_data = $this->connection->api_get($api_url, $params);

        $result = [
            'results' => $this->connection->decodeList($api_data),
            'info'    => $this->connection->page_info,
        ];

        $result = json_decode(json_encode($result), true);

        return is_array($result) ? $result : null;
    }


    /**
     * @param string     $video_id
     * @param array|null $lang Список кодов языков (en, ru). По умолчанию берется 1 первый
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getVideoSubtitles(string $video_id, array $lang = null): array {

        $client   = new \GuzzleHttp\Client();
        $response = $client->post("https://www.youtube.com/youtubei/v1/player?key={$this->getApikey()}", [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                "context" => [ "client" => ["clientName" => "WEB", "clientVersion" => "2.20210721.00.00"]],
                "videoId"  => $video_id
            ])
        ]);

        $player = [];

        if ($response->getStatusCode() == 200) {
            $content = $response->getBody()->getContents();

            if ( ! empty($content)) {
                $player = json_decode($content, true);
            }
        }

        $result = [];

        if ( ! empty($player) &&
             ! empty($player['captions']) &&
             ! empty($player['captions']['playerCaptionsTracklistRenderer']) &&
             ! empty($player['captions']['playerCaptionsTracklistRenderer']['captionTracks'])
        ) {
            foreach ($player['captions']['playerCaptionsTracklistRenderer']['captionTracks'] as $caption) {

                if ( ! empty($caption) &&
                     ! empty($caption['baseUrl']) &&
                     ! empty($caption['languageCode']) &&
                     (empty($lang) || in_array($caption['languageCode'], $lang))
                ) {
                    $client   = new \GuzzleHttp\Client();
                    $response = $client->get($caption['baseUrl']);

                    $caption_body = null;

                    if ($response->getStatusCode() == 200) {
                        $caption_body = $response->getBody()->getContents();
                    }

                    if ( ! empty($caption_body)) {
                        $dom = new \DOMDocument();
                        libxml_use_internal_errors(true);
                        $dom->loadXML($caption_body);
                        libxml_clear_errors();

                        $xpath = new \DOMXPath($dom);
                        $nodes = $xpath->query('/transcript/text'); // Query the correct XML structure

                        if ( ! empty($nodes)) {
                            $subtitles = [
                                'lang'        => $caption['languageCode'] ?? '',
                                'lang_name'   => ! empty($caption['name']) && ! empty($caption['name']['simpleText']) ? $caption['name']['simpleText'] : '',
                                'translate'   => $caption['isTranslatable'] ?? '',
                                'transcripts' => [],
                            ];

                            foreach ($nodes as $node) {
                                if ($node->nodeValue !== null) {
                                    $subtitles['transcripts'][] = [
                                        'text'  => html_entity_decode($node->nodeValue),
                                        'start' => (float)$node->getAttribute('start'),
                                        'dur'   => (float)$node->getAttribute('dur'),
                                    ];
                                }
                            }

                            $result[] = $subtitles;
                        }
                    }

                    if (empty($lang)) {
                        break;
                    }
                }
            }
        }


        return $result;
    }


    /**
     * Получение информации о канале по ID
     * @param string $channel_id
     * @param array  $part
     * @return array|null
     * @throws \Exception
     */
    public function getChannelInfoById(string $channel_id, array $part = ['id', 'snippet', 'statistics']):? array {

        $result = $this->connection->getChannelById($channel_id, [], $part);

        if ($result) {
            $result = json_decode(json_encode($result), true);
        }

        return is_array($result) ? $result : null;
    }


    /**
     * Получение информации о канале по названию
     * @param string $name
     * @param array  $part
     * @return array|null
     * @throws \Exception
     */
    public function getChannelInfoByName(string $name, array $part = ['id', 'snippet', 'statistics']):? array {

        $result = $this->connection->getChannelByName($name, [], $part);

        if ($result) {
            $result = json_decode(json_encode($result), true);
        }

        return is_array($result) ? $result : null;
    }


    /**
     * Получение информации о канале по ID
     * @param array $channels_id
     * @param array $part
     * @return array|null
     * @throws \Exception
     */
    public function getChannelsInfoById(array $channels_id, array $part = ['id', 'snippet', 'statistics']):? array {

        $result = $this->connection->getChannelById($channels_id, [], $part);

        if ($result) {
            $result = json_decode(json_encode($result), true);
        }

        return is_array($result) ? $result : null;
    }


    /**
     * Получение роликов из канала
     * @param string     $channel_id
     * @param array|null $options
     * @return array|null
     * @throws \Exception
     */
    public function getChannelVideos(string $channel_id, array $options = null):? array {

        $part   = ['id', 'snippet'];
        $params = [
            'type'       => 'video',
            'channelId'  => $channel_id,
            'part'       => implode(',', $part),
            'maxResults' => 50, // max 50
            'order'      => 'date',
        ];

        if ( ! empty($options['published_after']) && $options['published_after'] instanceof \DateTime) {
            $options['published_after']->setTimezone(new \DateTimeZone("UTC"));
            $params['publishedAfter'] = $options['published_after']->format('Y-m-d\TH:i:s\Z');
        }

        if ( ! empty($options['published_before']) && $options['published_before'] instanceof \DateTime) {
            $options['published_before']->setTimezone(new \DateTimeZone("UTC"));
            $params['publishedBefore'] = $options['published_before']->format('Y-m-d\TH:i:s\Z');
        }

        $result = $this->connection->searchAdvanced($params, true);

        if ($result) {
            $result = json_decode(json_encode($result), true);
        }

        return is_array($result) ? $result : null;
    }


    /**
     * @return \Zend_Db_Table_Row_Abstract
     */
    private function getAccountRow(): \Zend_Db_Table_Row_Abstract {

        $account_key = "yt_" . $this->getNmbr();
        $account     = $this->modSources->dataSourcesVideosAccounts->getRowByAccountKey($account_key);

        if ( ! $account) {
            $account = $this->modSources->dataSourcesVideosAccounts->createRow([
                'account_key' => $account_key,
            ]);
            $account->save();
        }

        return $account;
    }
}