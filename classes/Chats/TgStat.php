<?php
namespace Core2\Mod\Sources\Chats;
use Symfony\Component\DomCrawler\Crawler;


/**
 * @property \ModProxyController $modProxy
 */
class TgStat extends \Common {

    private array $options = [
        'debug_requests'      => false,
        'cache_dir'           => '',
        'cache_days'          => 60,
        'limit_empty_results' => 3,
    ];


    private int $count_empty_results = 0;


    /**
     * @param array|null $options
     */
    public function __construct(array $options = null) {

        parent::__construct();

        if ( ! empty($options['debug_requests'])) {
            $this->options['debug_requests'] = $options['debug_requests'];
        }

        if ( ! empty($options['cache_dir'])) {
            $this->options['cache_dir'] = $options['cache_dir'];
        }

        if ( ! empty($options['cache_days'])) {
            $this->options['cache_days'] = (int)$options['cache_days'];
        }

        if ( ! empty($options['limit_empty_results'])) {
            $this->options['limit_empty_results'] = (int)$options['limit_empty_results'];
        }
    }


    /**
     * Домены стран по умолчанию
     * @return string[]
     */
    public function getDomainsDefault(): array {

        return [
            'tgstat.com'     => 'Global',
            'tgstat.ru'      => 'Россия',
            'uk.tgstat.com'  => 'Украина',
            'by.tgstat.com'  => 'Беларусь',
            'uz.tgstat.com'  => 'Узбекистан',
            'kaz.tgstat.com' => 'Казахстан',
            'kg.tgstat.com'  => 'Киргизия',
            'ir.tgstat.com'  => 'Иран',
            'cn.tgstat.com'  => 'Китай',
            'in.tgstat.com'  => 'Индия',
            'et.tgstat.com'  => 'Эфиопия',
        ];
    }


    /**
     * Домены стран
     * @return array
     * @throws \Exception
     */
    public function getDomains(): array {

        $content = $this->loadDomain();
        $domains = [];

        if ( ! empty($content)) {
            $domains = $this->getListDomains($content);
        }

        return $domains;
    }


    /**
     * Получение списков рейтингов каналов
     * @param string     $domain
     * @param array|null $options
     * @return array
     * @throws \Exception
     */
    public function getTopChannels(string $domain, array $options = null): array {

        $top_list = $options['top_list'] ?? [
            'members',
            'members_t',
            'members_y',
            'members_7d',
            'members_30d',
            'reach',
            'ci',
        ];


        if (empty($top_list)) {
            return [];
        }

        $channels = [];

        foreach ($top_list as $top) {
            $content = $this->loadChannel($domain, '', $top);

            if ($content) {
                $channels['all']['title']      = 'Все категории';
                $channels['all']['tops'][$top] = $this->getListChannels($content);

                $categories = $this->getListChannelsCategories($content);

                foreach ($categories as $category) {
                    if ( ! empty($category['name']) && empty($category['is_active'])) {
                        $content = $this->loadChannel($domain, $category['name'], $top);

                        if ($content) {
                            $channels[$category['name']]['title']      = $category['title'];
                            $channels[$category['name']]['tops'][$top] = $this->getListChannels($content);
                        }
                    }
                }
            }
        }

        return $channels;
    }


    /**
     * Получение списков рейтингов каналов
     * @param string     $domain
     * @param array|null $options
     * @return array
     * @throws \Exception
     */
    public function getTopGroups(string $domain, array $options = null): array {

        $top_list = $options['top_list'] ?? [
            'members',
            'members_t',
            'members_y',
            'members_7d',
            'members_30d',
            'msgs',
            'mau',
        ];


        if (empty($top_list)) {
            return [];
        }

        $groups = [];

        foreach ($top_list as $top) {

            $content = $this->loadGroups($domain, '', $top);

            if ($content) {
                $groups['all']['title']      = 'Все категории';
                $groups['all']['tops'][$top] = $this->getListGroups($content);

                $categories = $this->getListGroupsCategories($content);

                foreach ($categories as $category) {
                    if ( ! empty($category['name']) && empty($category['is_active'])) {

                        $content = $this->loadGroups($domain, $category['name'], $top);

                        if ($content) {
                            $groups[$category['name']]['title']      = $category['name'];
                            $groups[$category['name']]['tops'][$top] = $this->getListGroups($content);
                        }
                    }
                }
            }
        }

        return $groups;
    }


    /**
     * @param string $content
     * @return array
     */
    private function getListChannelsCategories(string $content): array {

        $dom = new Crawler($content);
        return $dom->filter('#sticky-left-column__inner a.list-group-item-action')->each(function (Crawler $item) {
            try {
                $category = [
                    'url'       => '',
                    'name'      => '',
                    'title'     => '',
                    'is_active' => false,
                ];

                $category['url']       = $item->attr('href');
                $category['title']     = $item->text();
                $category['is_active'] = str_contains((string)$item->attr('class'), 'active');

                if ($category['url'] && preg_match('~ratings/channels/([a-z0-9_\-]+)~iu', $category['url'], $matches)) {
                    $category['name'] = $matches[1];
                }

                return $category;

            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;
                return null;
            }
        });
    }


    /**
     * @param string $content
     * @return array
     */
    private function getListGroupsCategories(string $content): array {

        $dom = new Crawler($content);
        return $dom->filter('#sticky-left-column__inner a.list-group-item-action')->each(function (Crawler $item) {
            try {
                $category = [
                    'url'       => '',
                    'name'      => '',
                    'title'     => '',
                    'is_active' => false,
                ];

                $category['url']       = $item->attr('href');
                $category['title']     = $item->text();
                $category['is_active'] = str_contains((string)$item->attr('class'), 'active');

                if ($category['url'] && preg_match('~ratings/chats/([a-z0-9_\-]+)~iu', $category['url'], $matches)) {
                    $category['name'] = $matches[1];
                }

                return $category;

            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;
                return null;
            }
        });
    }


    /**
     * @param string $content
     * @return array
     */
    private function getListDomains(string $content): array {

        $dom         = new Crawler($content);
        $domains_raw = $dom->filter('.row.justify-content-center > div')->each(function (Crawler $item) {
            try {
                $domain = [
                    'url'   => '',
                    'host'  => '',
                    'title' => '',
                ];

                $domain['url']   = $item->filter('a.text-body')->first()->attr('href');
                $domain['host']  = $domain['url'] ? (parse_url($domain['url'])['host'] ?? '') : '';
                $domain['title'] = $item->filter('h3')->first()->text();

                return $domain;

            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;
                return null;
            }
        });


        $domains = [];

        foreach ($domains_raw as $domain) {
            if ( ! empty($domain['host']) && ! empty($domain['title'])) {
                $domains[$domain['host']] = $domain['title'];
            }
        }

        return $domains;
    }


    /**
     * Получение списка чатов
     * @param string $content
     * @return array
     */
    private function getListChannels(string $content): array {

        $dom          = new Crawler($content);
        $channels_raw = $dom->filter('#sticky-center-column .peer-item-row')->each(function (Crawler $item) {
            try {
                $channel = [
                    'url'               => '',
                    'name'              => '',
                    'title'             => '',
                    'category_title'    => '',
                    'img_url'           => '',
                    'subscribers_count' => 0,
                    'reach_count'       => 0,
                    'index_citation'    => 0,
                ];

                $channel['url']            = $item->filter('.row a')->count() > 0            ? $item->filter('.row a')->first()->attr('href') : '';
                $channel['img_url']        = $item->filter('.row a img')->count() > 0        ? $item->filter('.row a img')->first()->attr('src') : '';
                $channel['title']          = $item->filter('.row a .col > div')->count() > 0 ? $item->filter('.row a .col > div')->first()->text() : '';
                $channel['category_title'] = $item->filter('.row a .col > div')->count() > 0 ? $item->filter('.row a .col > div')->last()->text() : '';

                if ($channel['url'] && preg_match('~/channel/@([a-z0-9_\-]+)~iu', $channel['url'], $matches)) {
                    $channel['name'] = $matches[1];
                }


                $block_stat = $item->filter('.card-body > .row > .col')->last();

                $channel['subscribers_count'] = $block_stat->filter('.row .col')->eq(0)->filter('h4')->first()->text();
                $channel['reach_count']       = $block_stat->filter('.row .col')->eq(1)->filter('h4')->first()->text();
                $channel['index_citation']    = $block_stat->filter('.row .col')->eq(2)->filter('h4')->first()->text();

                $channel['subscribers_count'] = preg_replace('~\s~', '', (string)$channel['subscribers_count']);
                $channel['reach_count']       = preg_replace('~\s~', '', (string)$channel['reach_count']);
                $channel['index_citation']    = preg_replace('~\s~', '', (string)$channel['index_citation']);

                if (strpos($channel['reach_count'], 'm') !== false) {
                    $channel['reach_count'] = str_replace('m', '', $channel['reach_count']);
                    $channel['reach_count'] = (float)$channel['reach_count'] * 1000000;

                } elseif (strpos($channel['reach_count'], 'k') !== false) {
                    $channel['reach_count'] = str_replace('k', '', $channel['reach_count']);
                    $channel['reach_count'] = (float)$channel['reach_count'] * 1000;
                }

                if (strpos($channel['index_citation'], 'm') !== false) {
                    $channel['index_citation'] = str_replace('m', '', $channel['index_citation']);
                    $channel['index_citation'] = (float)$channel['index_citation'] * 1000000;

                } elseif (strpos($channel['index_citation'], 'k') !== false) {
                    $channel['index_citation'] = str_replace('k', '', $channel['index_citation']);
                    $channel['index_citation'] = (float)$channel['index_citation'] * 1000;
                }

                return $channel;

            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;
                return null;
            }
        });


        $channels = [];
        foreach ($channels_raw as $channel) {
            $channels[] = $channel;
        }

        return $channels;
    }


    /**
     * Получение списка групп
     * @param string $content
     * @return array
     */
    private function getListGroups(string $content): array {

        $dom          = new Crawler($content);
        $channels_raw = $dom->filter('#sticky-center-column .peer-item-row')->each(function (Crawler $item) {
            try {
                $channel = [
                    'url'               => '',
                    'name'              => '',
                    'title'             => '',
                    'category_title'    => '',
                    'img_url'           => '',
                    'subscribers_count' => 0,
                    'messages_7d_count' => 0,
                    'mau_count'         => 0,
                ];

                $channel['url']            = $item->filter('.row a')->count() > 0            ? $item->filter('.row a')->first()->attr('href') : '';
                $channel['img_url']        = $item->filter('.row a img')->count() > 0        ? $item->filter('.row a img')->first()->attr('src') : '';
                $channel['title']          = $item->filter('.row a .col > div')->count() > 0 ? $item->filter('.row a .col > div')->first()->text() : '';
                $channel['category_title'] = $item->filter('.row a .col > div')->count() > 0 ? $item->filter('.row a .col > div')->last()->text() : '';

                if ($channel['url'] && preg_match('~/chat/@([a-z0-9_\-]+)~iu', $channel['url'], $matches)) {
                    $channel['name'] = $matches[1];
                }


                $block_stat = $item->filter('.card-body > .row > .col')->last();

                $channel['subscribers_count'] = $block_stat->filter('.row .col')->eq(0)->filter('h4')->first()->text();
                $channel['messages_7d_count'] = $block_stat->filter('.row .col')->eq(1)->filter('h4')->first()->text();
                $channel['mau_count']         = $block_stat->filter('.row .col')->eq(2)->filter('h4')->first()->text();

                $channel['subscribers_count'] = preg_replace('~\s~', '', (string)$channel['subscribers_count']);
                $channel['messages_7d_count'] = preg_replace('~\s~', '', (string)$channel['messages_7d_count']);
                $channel['mau_count']         = preg_replace('~\s~', '', (string)$channel['mau_count']);


                if (strpos($channel['messages_7d_count'], 'm') !== false) {
                    $channel['messages_7d_count'] = str_replace('m', '', $channel['messages_7d_count']);
                    $channel['messages_7d_count'] = (float)$channel['messages_7d_count'] * 1000000;

                } elseif (strpos($channel['messages_7d_count'], 'k') !== false) {
                    $channel['messages_7d_count'] = str_replace('k', '', $channel['messages_7d_count']);
                    $channel['messages_7d_count'] = (float)$channel['messages_7d_count'] * 1000;
                }

                if (strpos($channel['mau_count'], 'm') !== false) {
                    $channel['mau_count'] = str_replace('m', '', $channel['mau_count']);
                    $channel['mau_count'] = (float)$channel['mau_count'] * 1000000;

                } elseif (strpos($channel['mau_count'], 'k') !== false) {
                    $channel['mau_count'] = str_replace('k', '', $channel['mau_count']);
                    $channel['mau_count'] = (float)$channel['mau_count'] * 1000;
                }

                return $channel;

            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;
                return null;
            }
        });


        $channels = [];
        foreach ($channels_raw as $channel) {
            $channels[] = $channel;
        }

        return $channels;
    }


    /**
     * @return string
     * @throws \Exception
     */
    private function loadDomain(): string {

        $result = '';

        if ( ! empty($this->options['cache_dir'])) {
            if ( ! is_dir($this->options['cache_dir'])) {
                throw new \Exception(sprintf("Директория не найдена: %s", $this->options['cache_dir']));
            }

            if ( ! is_writeable($this->options['cache_dir'])) {
                throw new \Exception(sprintf("Нет доступа на запись в директорию: %s", $this->options['cache_dir']));
            }


            $file_cache = "{$this->options['cache_dir']}/domains.html";

            if ($this->options['cache_days'] > 0 && file_exists($file_cache)) {
                $file_modified = filemtime($file_cache);
                $date_modified = $file_modified ? (new \DateTime())->setTimestamp($file_modified) : 0;
                $date          = (new \DateTime())->modify("-{$this->options['cache_days']} day");

                if ($date < $date_modified) {
                    $result = file_get_contents($file_cache);
                }
            }
        }

        if (empty($result) &&
            ($this->options['limit_empty_results'] < 0 ||
             $this->options['limit_empty_results'] >= $this->count_empty_results)
        ) {
            $pages = $this->load(['https://tgstat.com/ru']);
            $page  = current($pages);

            if ( ! empty($page) && ! empty($page['content'])) {
                $result  = $page['content'];
                $domains = $this->getListDomains($result);

                if ($domains) {
                    if ($this->options['cache_days'] > 0) {
                        if ( ! is_writeable($this->options['cache_dir'])) {
                            throw new \Exception(sprintf('Директория не доступна для записи: %s', $this->options['cache_dir']));
                        }

                        $file_cache = "{$this->options['cache_dir']}/domains.html";

                        if (file_exists($file_cache) && ! is_writeable($file_cache)) {
                            throw new \Exception(sprintf('Файл не доступен для записи: %s', $file_cache));
                        }

                        file_put_contents($file_cache, $page['content']);
                    }

                } else {
                    $this->count_empty_results++;
                }

            } else {
                $this->count_empty_results++;
            }
        }

        return $result;
    }


    /**
     * @param string $domain
     * @param string $category
     * @param string $top
     * @return string
     * @throws \Exception
     */
    private function loadChannel(string $domain, string $category, string $top): string {

        $category_file = $category ?: 'all';
        $file_cache    = $this->getCacheDir('channels') . "/{$domain}_{$category_file}_{$top}.html";
        $result        = '';

        if ($this->options['cache_days'] > 0 && file_exists($file_cache)) {
            $file_modified = filemtime($file_cache);
            $date_modified = $file_modified ? (new \DateTime())->setTimestamp($file_modified) : 0;
            $date          = (new \DateTime())->modify("-{$this->options['cache_days']} day");

            if ($date < $date_modified) {
                $result = file_get_contents($file_cache);
            }
        }

        if (empty($result) &&
            ($this->options['limit_empty_results'] < 0 ||
             $this->options['limit_empty_results'] >= $this->count_empty_results)
        ) {
            $category_path = $category ? "/{$category}" : '';
            $pages         = $this->load([ "https://{$domain}/ratings/channels{$category_path}/?sort={$top}" ]);
            $page          = current($pages);

            if ( ! empty($page) && ! empty($page['content'])) {
                $result = $page['content'];
                $groups = $this->getListChannels($result);

                if ($groups) {
                    if ($this->options['cache_days'] > 0) {
                        file_put_contents($file_cache, $page['content']);
                    }
                } else {
                    $this->count_empty_results++;
                }

            } else {
                $this->count_empty_results++;
            }
        }


        return $result;
    }


    /**
     * @param string $domain
     * @param string $category
     * @param string $top
     * @return string
     * @throws \Exception
     */
    private function loadGroups(string $domain, string $category, string $top): string {

        $category_file = $category ?: 'all';
        $file_cache    = $this->getCacheDir('groups') . "/{$domain}_{$category_file}_{$top}.html";
        $result        = '';

        if ($this->options['cache_days'] > 0 && file_exists($file_cache)) {
            $file_modified = filemtime($file_cache);
            $date_modified = $file_modified ? (new \DateTime())->setTimestamp($file_modified) : 0;
            $date          = (new \DateTime())->modify("-{$this->options['cache_days']} day");

            if ($date < $date_modified) {
                $result = file_get_contents($file_cache);
            }
        }

        if (empty($result) &&
            ($this->options['limit_empty_results'] < 0 ||
            $this->options['limit_empty_results'] >= $this->count_empty_results)
        ) {
            $category_path = $category ? "/{$category}" : '';
            $pages         = $this->load([ "https://{$domain}/ratings/chats{$category_path}/?sort={$top}" ]);
            $page          = current($pages);

            if ( ! empty($page) && ! empty($page['content'])) {
                $result = $page['content'];
                $groups = $this->getListGroups($result);

                if ($groups) {
                    if ($this->options['cache_days'] > 0) {
                        file_put_contents($file_cache, $page['content']);
                    }
                } else {
                    $this->count_empty_results++;
                }

            } else {
                $this->count_empty_results++;
            }
        }


        return $result;
    }


    /**
     * @param array $addresses
     * @return array
     * @throws \Exception
     */
    private function load(array $addresses): array {

        $responses = $this->modProxy->request('get', $addresses, [
            'request' => [
                'connection'         => 10,
                'connection_timeout' => 10,
                'verify'             => false,
            ],
            'level_anonymity' => ['elite', 'anonymous', /* 'non_anonymous'*/ ],
            'max_try'         => 5,
            'limit'           => 5,
            'debug'           => ! empty($this->options['debug_requests']) ? 'print' : '',
        ]);


        $pages = [];

        foreach ($responses as $response) {

            if ($response['status'] == 'success' &&
                $response['http_code'] == '200' &&
                ! empty($response['content'])
            ) {
                $pages[] = [
                    'url'     => $response['url'],
                    'content' => $response['content'],
                ];

            } elseif ( ! empty($this->options['debug_requests'])) {
                print_r($response);
            }
        }


        return $pages;
    }


    /**
     * @param string $dir_name
     * @return string
     * @throws \Exception
     */
    private function getCacheDir(string $dir_name): string {

        if ( ! empty($this->options['cache_dir'])) {

            if ( ! is_dir($this->options['cache_dir'])) {
                throw new \Exception(sprintf( "Директория не найдена: %s", $this->options['cache_dir']));
            }

            if ( ! is_dir("{$this->options['cache_dir']}/{$dir_name}")) {

                if (is_writeable("{$this->options['cache_dir']}")) {
                    mkdir("{$this->options['cache_dir']}/{$dir_name}", '644');
                    $cache_dir = "{$this->options['cache_dir']}/{$dir_name}";

                } else {
                    throw new \Exception(sprintf(
                        "Не удалось создать директорию, нет доступа на запись: %s",
                        "{$this->options['cache_dir']}/{$dir_name}"
                    ));
                }

            } else {
                if (is_writeable("{$this->options['cache_dir']}/{$dir_name}")) {
                    $cache_dir = "{$this->options['cache_dir']}/{$dir_name}";

                } else {
                    throw new \Exception(sprintf(
                        "В директории нет доступа на запись: %s",
                        "{$this->options['cache_dir']}/{$dir_name}"
                    ));
                }
            }

        } else {
            $cache_dir = sys_get_temp_dir() . "/{$dir_name}";
            mkdir($cache_dir, '644');
        }


        return $cache_dir;
    }
}