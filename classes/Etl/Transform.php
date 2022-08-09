<?php
namespace Core2\Mod\Sources\Etl;


use Core2\Mod\Sources\Page;
use JetBrains\PhpStorm\ArrayShape;

/**
 *
 */
class Transform {

    private array $months_ru = [
        'январь'   => 1,  'января'   => 1,  'янв' => 1,
        'февраль'  => 2,  'февраля'  => 2,  'фев' => 2,
        'март'     => 3,  'марта'    => 3,  'мар' => 3,
        'апрель'   => 4,  'апреля'   => 4,  'апр' => 4,
        'май'      => 5,  'мая'      => 5,
        'июнь'     => 6,  'июня'     => 6,  'июн' => 6,
        'июль'     => 7,  'июля'     => 7,  'июл' => 7,
        'август'   => 8,  'августа'  => 8,  'авг' => 8,
        'сентябрь' => 9,  'сентября' => 9,  'сен' => 9,
        'октябрь'  => 10, 'октября'  => 10, 'окт' => 10,
        'ноябрь'   => 11, 'ноября'   => 11, 'ноя' => 11,
        'декабрь'  => 12, 'декабря'  => 12, 'дек' => 12,
    ];


    /**
     * @param string $start_url
     * @param string $tags
     * @param string $regions
     * @return array
     * @throws \Exception
     */
    #[ArrayShape(['domain' => "string", 'tags' => "array", 'regions' => "array"])]
    public function getSource(string $start_url, string $tags = '', string $regions = ''): array {

        $domain = $this->getDomain($start_url);

        if ( ! $domain) {
            throw new \Exception(sprintf('На ресурсе %s не удалось выделить домен из адреса. Проверьте правила параметр start_url', $start_url));
        }

        $tags    = $tags   ? explode(',', $tags) : [];
        $regions = $regions ? explode(',', $regions) : [];

        foreach ($tags as $key => $tag) {
            if (trim($tag)) {
                $tags[$key] = trim($tag);
            }
        }

        foreach ($regions as $key => $region) {
            if (trim($region)) {
                $regions[$key] = trim($region);
            }
        }

        return [
            'domain'  => $domain,
            'tags'    => $tags,
            'regions' => $regions,
        ];
    }


    /**
     * @param string $content
     * @param array  $rules
     * @param array  $options
     * @return array
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     */
    public function parseList(string $content, array $rules, array $options = []): array {

        $pages = [];

        ini_set("mbstring.regex_retry_limit", "10000000");

        $dom = new \PHPHtmlParser\Dom();
        $dom->loadStr($content);

        foreach ($rules as $rule) {

            if (empty($rule['items'])) {
                continue;
            }

            $items = $dom->find($rule['items']);

            if ( ! empty($items)) {
                foreach ($items as $item) {
                    try {
                        $page = [
                            'url'          => '',
                            'title'        => '',
                            'count_views'  => '',
                            'date_publish' => '',
                            'tags'         => [],
                            'categories'   => [],
                        ];


                        $item_url = ! empty($rule['url'])
                            ? $item->find($rule['url'])[0]
                            : $item;

                        $url = $item_url ? trim($item_url->getAttribute('href') ?? '') : '';

                        if ( ! empty($url)) {
                            if ( ! $this->getDomain($url) && ! empty($options['url'])) {
                                $domain = $this->getDomain($options['url']);
                                $scheme = $this->getScheme($options['url']);

                                if ($domain) {
                                    $url         = ltrim($url, '/');
                                    $page['url'] = "{$scheme}://{$domain}/{$url}";
                                }

                            } else {
                                $page['url'] = $url;
                            }
                        } else {
                            continue;
                        }

                        if ( ! empty($rule['title'])) {
                            $page['title'] = $this->getTitle($item, $rule['title']);
                        }

                        if ( ! empty($rule['count_views'])) {
                            $page['count_views'] = $this->getCountView($item, $rule['count_views']);
                        }

                        if ( ! empty($rule['region'])) {
                            $page['region'] = $this->getTags($item, $rule['region']);
                        }

                        if ( ! empty($rule['tags'])) {
                            $page['tags'] = $this->getTags($item, $rule['tags']);
                        }

                        if ( ! empty($rule['category'])) {
                            $page['categories'] = $this->getTags($item, $rule['category']);
                        }

                        if ( ! empty($rule['date_publish'])) {
                            $page['date_publish'] = $this->getDatePublish($item, $rule['date_publish'], $rule['date_format'] ?? $options['date_format'] ?? '');
                        }

                        $pages[] = $page;

                    } catch (\Exception $e) {
                        echo $e->getMessage() . PHP_EOL;
                        echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;
                        // ignore
                    }
                }
            }
        }


        return $pages;
    }


    /**
     * @param string $content
     * @param array  $rules
     * @param array  $options
     * @return string[]
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     */
    public function parsePage(string $content, array $rules, array $options = []): array {

        $parser_options = new \PHPHtmlParser\Options();
        $parser_options->setEnforceEncoding('utf8');

        $dom = new \PHPHtmlParser\Dom();
        $dom->loadStr($content, $parser_options);

        try {
            $page = [
                'title'         => '',
                'content'       => '',
                'date_publish'  => '',
                'count_views'   => '',
                'author'        => '',
                'source_domain' => '',
                'source_url'    => '',
                'region'        => [],
                'categories'    => [],
                'tags'          => [],
                'media'         => [],
                'references'    => [],
            ];

            if ( ! empty($rules['title'])) {
                $page['title'] = $this->getTitle($dom, $rules['title']);
            }

            if ( ! empty($rules['source_url'])) {
                $page['source_url'] = $this->getSourceUrl($dom, $rules['source_url']);

                if ($page['source_url']) {
                    $page['source_domain'] = $this->getDomain($page['source_url']);
                }
            }

            if ( ! empty($rules['region'])) {
                $page['region'] = $this->getTags($dom, $rules['region']);
            }

            if ( ! empty($rules['tags'])) {
                $page['tags'] = $this->getTags($dom, $rules['tags']);
            }

            if ( ! empty($rules['count_views'])) {
                $page['count_views'] = $this->getCountView($dom, $rules['count_views']);
            }

            if ( ! empty($rules['category'])) {
                $page['categories'] = $this->getCategories($dom, $rules['category']);
            }

            if ( ! empty($rules['author'])) {
                $page['author'] = $this->getAuthor($dom, $rules['author']);
            }

            if ( ! empty($rules['content'])) {
                $items       = $dom->find($rules['content']);
                $content_raw = '';

                foreach ($items as $item) {
                    $content_raw .= $item ? trim($item->innerHtml) : '';
                }

                if ($content_raw) {
                    $page['content'] = $this->getContent($content_raw);
                }

                $page['media']      = $this->getMedia($dom, $rules['content']);
                $page['references'] = $this->getReferences($dom, $rules['content'], $options['url'] ?? null);
            }

            if ( ! empty($rules['media'])) {
                $media = $this->getMedia($dom, $rules['media']);

                if ( ! empty($media)) {
                    foreach ($media as $media_item) {
                        $page['media'][] = $media_item;
                    }
                }
            }


            if ( ! empty($page['media'])) {
                foreach ($page['media'] as $key => $media_item) {
                    foreach ($page['media'] as $key2 => $media_item2) {

                        if ($key != $key2 && $media_item['url'] == $media_item2['url']) {
                            unset($page['media'][$key]);
                        }
                    }
                }
            }


            if ( ! empty($page['references'])) {
                foreach ($page['references'] as $key => $ref_item) {
                    foreach ($page['references'] as $key2 => $ref_item2) {

                        if ($key != $key2 && $ref_item['url'] == $ref_item2['url']) {
                            unset($page['references'][$key]);
                        }
                    }
                }
            }


            if ($rules['date_publish']) {
                $page['date_publish'] = $this->getDatePublish($dom, $rules['date_publish'], $options['date_format'] ?? '');
            }


        } catch (\Exception $e) {
            echo $e->getMessage() .PHP_EOL;
            echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;
            // ignore
        }


        return $page;
    }


    /**
     * Очистка данных
     * @param array $page
     * @param array $clear_rules
     * @return array
     */
    public function clearPage(array $page, array $clear_rules): array {


        if ( ! empty($page['content']) &&
             ! empty($clear_rules['content']) &&
             ! empty($clear_rules['content']['cut']) &&
            is_array($clear_rules['content']['cut'])
        ) {
            foreach ($clear_rules['content']['cut'] as $content_cut) {

                if ($content_cut) {
                    @preg_match($content_cut, '');
                    if (preg_last_error() != PREG_NO_ERROR) {
                        echo "Error content regular expr: {$content_cut}" . PHP_EOL;
                        continue;
                    }

                    $page['content'] = preg_replace($content_cut, '', $page['content']);
                    $page['content'] = trim($page['content']);
                }
            }
        }


        if ( ! empty($page['author']) && ! empty($clear_rules['author'])) {

            @preg_match($clear_rules['author'], '');
            if (preg_last_error() != PREG_NO_ERROR) {
                echo "Error author regular expr: {$clear_rules['author']}". PHP_EOL;

            } else {
                preg_match($clear_rules['author'], $page['author'], $match);

                $page['author'] = ! empty($match['author']) ? $match['author'] : '';
                $page['author'] = trim($page['author']);
            }
        }


        if ( ! empty($page['region']) && ! empty($clear_rules['region'])) {

            @preg_match($clear_rules['region'], '');
            if (preg_last_error() != PREG_NO_ERROR) {
                echo "Error author regular expr: {$clear_rules['region']}". PHP_EOL;

            } else {
                $regions = [];

                foreach ($page['region'] as $region) {
                    preg_match($clear_rules['region'], $region, $match);

                    $region = ! empty($match['region']) ? $match['region'] : '';
                    $region = trim($region);

                    if ($region) {
                        $regions_explode = preg_split('~(,| )~', $region);

                        foreach ($regions_explode as $region) {
                            $regions[] = trim($region);
                        }
                    }
                }

                $page['region'] = $regions;
            }
        }


        if ( ! empty($page['references']) &&
             ! empty($clear_rules['references']) &&
             ! empty($clear_rules['references']['reject']) &&
             is_array($clear_rules['references']['reject'])
        ) {
            foreach ($clear_rules['references']['reject'] as $reject_rule) {

                if ($reject_rule) {
                    @preg_match($reject_rule, '');
                    if (preg_last_error() != PREG_NO_ERROR) {
                        echo "Error reference regular expr: {$reject_rule}". PHP_EOL;
                        continue;
                    }

                    foreach ($page['references'] as $key => $reference) {
                        if ( ! empty($reference['url']) &&
                            preg_match($reject_rule, $reference['url'])
                        ) {
                           unset($page['references'][$key]);
                        }
                    }
                }
            }
        }


        if ( ! empty($page['tags']) &&
             ! empty($clear_rules['tags']) &&
             ! empty($clear_rules['tags']['reject']) &&
             is_array($clear_rules['tags']['reject'])
        ) {
            foreach ($clear_rules['tags']['reject'] as $reject_rule) {

                if ($reject_rule) {
                    @preg_match($reject_rule, '');
                    if (preg_last_error() != PREG_NO_ERROR) {
                        echo "Error tags regular expr: {$reject_rule}". PHP_EOL;
                        continue;
                    }

                    foreach ($page['tags'] as $key => $tag) {
                        if ( ! empty($tag) && preg_match($reject_rule, $tag)) {
                           unset($page['tags'][$key]);
                        }
                    }
                }
            }
        }


        if ( ! empty($page['categories']) &&
             ! empty($clear_rules['categories']) &&
             ! empty($clear_rules['categories']['reject']) &&
             is_array($clear_rules['categories']['reject'])
        ) {
            foreach ($clear_rules['categories']['reject'] as $reject_rule) {

                if ($reject_rule) {
                    @preg_match($reject_rule, '');
                    if (preg_last_error() != PREG_NO_ERROR) {
                        echo "Error categories regular expr: {$reject_rule}". PHP_EOL;
                        continue;
                    }

                    foreach ($page['categories'] as $key => $category) {
                        if ( ! empty($category) && preg_match($reject_rule, $category)) {
                           unset($page['categories'][$key]);
                        }
                    }
                }
            }
        }


        return $page;
    }


    /**
     * @param array $page1
     * @param array $page2
     * @return array
     */
    public function mergePage(array $page1, array $page2): array {

        $page = $page1;

        foreach ($page2 as $field => $value) {
            if ( ! empty($value)) {
                $page[$field] = $value;
            }
        }

        if ( ! empty($page1['media']) || ! empty($page2['media'])) {
            $page['media'] = $page1['media'] ?? [];

            foreach ($page2['media'] as $media) {
                if ( ! empty($media)) {
                    $page['media'][] = $media;
                }
            }
        }

        if ( ! empty($page1['references']) || ! empty($page2['references'])) {
            $page['references'] = $page1['references'] ?? [];

            foreach ($page2['references'] as $references) {
                if ( ! empty($references)) {
                    $page['references'][] = $references;
                }
            }
        }

        return $page;
    }


    /**
     * @param \PHPHtmlParser\Dom|\PHPHtmlParser\Dom\Node\HtmlNode $dom
     * @param                    $rule
     * @return array
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     */
    private function getTags($dom, $rule): array {

        $items = $dom->find($rule);
        $tags  = [];

        foreach ($items as $item) {
            $tags_text = trim($item->innerHtml) ?: trim($item->text);
            $tags_text = strip_tags($tags_text);

            foreach (explode(',', $tags_text) as $tag) {
                $tag = mb_strtolower($tag);
                $tag = preg_replace('~&[A-z#0-9]+;~', ' ', $tag);
                $tag = preg_replace('~[ ]{2,}~', ' ', $tag);
                $tag = trim($tag);

                if ( ! empty($tag) && array_search($tag, $tags) === false) {
                    $tags[] = $tag;
                }
            }
        }

        return $tags;
    }


    /**
     * @param \PHPHtmlParser\Dom|\PHPHtmlParser\Dom\Node\HtmlNode $dom
     * @param string             $rule
     * @param string             $date_format
     * @return string
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     */
    private function getDatePublish($dom, string $rule, string $date_format = ''): string {

        $item = $dom->find($rule);

        $date_publish = '';

        if ($item && $item[0]) {
            $attr_datetime = $item[0]->getAttribute('datetime');

            if ($attr_datetime && preg_match('~(\d{4}-\d{2}-\d{2}.\d{2}:\d{2}:\d{2})~', $attr_datetime, $match)) {
                $date_publish_text = $match[1] ?? '';
                $date_format       = '~(?<year>[\d]{4})\-(?<month>[\d]{2})\-(?<day>[\d]{2}).(?<hour>[\d]{1,2}):(?<min>[\d]{1,2})(?:\:(?<sec>[\d]{1,2})|)~';

            } else {
                $date_publish_text = $item && $item[0]
                    ? (trim($item[0]->innerHtml) ?: trim($item[0]->text))
                    : '';
                $date_publish_text = strip_tags($date_publish_text);

                if ($date_publish_text) {
                    if (empty($date_format)) {
                        $date_format = '~(?<day>[\d]{1,2})\.(?<month>[\d]{1,2})\.(?<year>[\d]{4})\s+(?<hour>[\d]{1,2}):(?<min>[\d]{1,2})(?:\:(?<sec>[\d]{1,2})|)~';
                    } else {
                        @preg_match($date_format, '');
                        if (preg_last_error() != PREG_NO_ERROR) {
                            echo "Error regular: {$date_format}". PHP_EOL;
                            return '';
                        }
                    }
                }
            }

            if ($date_format &&
                $date_publish_text &&
                preg_match($date_format, $date_publish_text, $match)
            ) {

                if ( ! empty($match['month_ru'])) {
                    $match['month'] = $this->months_ru[$match['month_ru']] ?? '';
                }

                if (isset($match['current_year']) &&
                    empty($match['year'])
                ) {
                    $match['year'] = date('Y');
                }

                if ( ! empty($match['year']) &&
                    ! empty($match['month']) &&
                    ! empty($match['day'])
                ) {
                    $hour = empty($match['hour']) ? '00' : $match['hour'];
                    $min  = empty($match['min']) ? '00' : $match['min'];
                    $sec  = empty($match['sec']) ? '00' : $match['sec'];

                    $date_publish = "{$match['year']}-{$match['month']}-{$match['day']} {$hour}:{$min}:{$sec}";
                }

                if ($date_publish) {
                    try {
                        $date_publish = (new \DateTime($date_publish))->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        echo '<pre>';
                        echo "Исходный текст: {$date_publish_text}" . PHP_EOL;
                        echo "Найденная дата: {$date_publish}" . PHP_EOL;
                        echo "Ошибка: {$e->getMessage()}" . PHP_EOL;
                        echo '</pre>';

                        $date_publish = '';
                    }
                }
            }
        }

        return $date_publish;
    }


    /**
     * @param \PHPHtmlParser\Dom $dom
     * @param string             $rule
     * @return string
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     */
    private function getSourceUrl(\PHPHtmlParser\Dom $dom, string $rule): string {

        $item = $dom->find($rule);
        return  $item && $item[0] ? trim($item[0]->getAttribute('href')) : '';
    }


    /**
     * @param \PHPHtmlParser\Dom|\PHPHtmlParser\Dom\Node\HtmlNode $dom
     * @param string             $rule
     * @return string
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     */
    private function getTitle($dom, string $rule): string {

        $item = $dom->find($rule);
        $title = $item && $item[0]
            ? trim($item[0]->innerHtml) ?: trim($item[0]->text)
            : '';

        $title = strip_tags($title);
        $title = preg_replace('~&[A-z#0-9]+;~', ' ', $title);

        return preg_replace('~[ ]{2,}~', ' ', $title);
    }


    /**
     * @param \PHPHtmlParser\Dom|\PHPHtmlParser\Dom\Node\HtmlNode $dom
     * @param string             $rule
     * @return string
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     */
    private function getCountView($dom, string $rule): string {

        $item_count_views = $dom->find($rule);
        $count_views = $item_count_views && $item_count_views[0] ? trim($item_count_views[0]->text) : '';
        return preg_replace('~[^0-9]~', '', $count_views);
    }


    /**
     * @param \PHPHtmlParser\Dom $dom
     * @param string             $rule
     * @return string
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     */
    private function getAuthor(\PHPHtmlParser\Dom $dom, string $rule): string {

        $item = $dom->find($rule);

        $author = strip_tags($item && $item[0])
            ? (trim($item[0]->innerHtml) ?: trim($item[0]->text))
            : '';

        $author = strip_tags($author);
        $author = htmlspecialchars_decode($author);
        $author = str_replace('&nbsp', ' ', $author);
        $author = preg_replace('~&[A-z#0-9]+;~', ' ', $author);
        $author = preg_replace('~[ ]{2,}~', ' ', $author);

        return trim($author);
    }


    /**
     * @param \PHPHtmlParser\Dom $dom
     * @param string             $rule
     * @return array
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     */
    private function getCategories(\PHPHtmlParser\Dom $dom, string $rule): array {

        $items      = $dom->find($rule);
        $categories = [];

        foreach ($items as $item) {
            $tags_text = $item ? trim($item->text) : '';

            foreach (explode(',', $tags_text) as $category) {
                $category = mb_strtolower($category);
                $category = preg_replace('~&[A-z#0-9]+;~', ' ', $category);
                $category = preg_replace('~[ ]{2,}~', ' ', $category);
                $category = trim($category);

                if ( ! empty($category) && array_search($category, $categories) === false) {
                    $categories[] = trim($category);
                }
            }
        }

        return $categories;
    }


    /**
     * @param \PHPHtmlParser\Dom $dom
     * @param string             $rule
     * @param string|null        $source_url
     * @return array
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     */
    private function getReferences(\PHPHtmlParser\Dom $dom, string $rule, string $source_url = null): array {

        $references    = [];
        $content_links = $dom->find($rule . ' a');

        if ( ! empty($content_links)) {
            $scheme = $source_url ? $this->getScheme($source_url) : 'http';

            foreach ($content_links as $content_link) {
                $url = $content_link?->getAttribute('href') ?? '';
                $url = str_replace('&amp;', '&', $url);

                if (preg_match('~\.(jpg|jpeg|png|gif|webp|mp4)$~', $url)) {
                    continue;
                }

                if (mb_substr($url, 0, 2) == '//') {
                    $url = "{$scheme}://" . mb_substr($url, 2);
                }

                if ( ! empty($url) && $url != '#') {
                    if ($domain = $this->getDomain($url)) {
                        $references[] = [
                            'domain' => $domain,
                            'url'    => $url,
                        ];

                    } else {
                        if ($domain = $this->getDomain($source_url)) {
                            $url = ltrim($url, '/');

                            $references[] = [
                                'domain' => $domain,
                                'url'    => "{$scheme}://{$domain}/{$url}",
                            ];
                        }
                    }
                }
            }
        }

        return $references;
    }


    /**
     * @param string $content
     * @return string
     */
    private function getContent(string $content): string {

        $content = $this->deleteTags($content, ['script', 'style']);
        $content = strip_tags($content);
        $content = htmlspecialchars_decode($content);
        $content = preg_replace('~&[A-z#0-9]+;~', ' ', $content);
        $content = preg_replace('~[ ]{2,}~', ' ', $content);

        return trim($content);
    }


    /**
     * @param \PHPHtmlParser\Dom $dom
     * @param string             $rule
     * @return array
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     */
    private function getMedia(\PHPHtmlParser\Dom $dom, string $rule): array {

        $media = [];
        $item  = $dom->find($rule);


        if ($item &&
            $item[0] &&
            in_array(strtolower($item[0]->getTag()->name()), ['img', 'video', 'audio'])
        ) {
            $description = trim($item[0]?->getAttribute('alt') ?? '');
            $description = preg_replace('~&[A-z#0-9]+;~', ' ', $description);
            $description = preg_replace('~[ ]{2,}~', ' ', $description);
            $description = mb_strtolower($description);

            $src = trim($item[0]?->getAttribute('src') ?? '');

            if ($src) {
                $media[] = [
                    'type'        => $item[0]->getTag()->name(),
                    'url'         => $src,
                    'description' => $description,
                ];
            }

        } else {
            $items_img   = $dom->find($rule . ' img');
            $items_video = $dom->find($rule . ' video');
            $items_audio = $dom->find($rule . ' audio');

            if ( ! empty($items_img)) {
                foreach ($items_img as $item) {
                    $description = trim($item->getAttribute('alt') ?? '');
                    $description = preg_replace('~&[A-z#0-9]+;~', ' ', $description);
                    $description = preg_replace('~[ ]{2,}~', ' ', $description);
                    $description = mb_strtolower($description);

                    $src = trim($item->getAttribute('src') ?? '');

                    if ($src) {
                        $media[] = [
                            'type'        => $item->getTag()->name(),
                            'url'         => $src,
                            'description' => $description,
                        ];
                    }
                }
            }

            if ( ! empty($items_video)) {
                foreach ($items_video as $item) {
                    $description = trim($item->getAttribute('alt') ?? '');
                    $description = preg_replace('~&[A-z#0-9]+;~', ' ', $description);
                    $description = preg_replace('~[ ]{2,}~', ' ', $description);
                    $description = mb_strtolower($description);

                    $src = trim($item?->getAttribute('src') ?? '');

                    if ($src) {
                        $media[] = [
                            'type'        => $item->getTag()->name(),
                            'url'         => $src,
                            'description' => $description,
                        ];
                    }
                }
            }

            if ( ! empty($items_audio)) {
                foreach ($items_audio as $item) {
                    $description = trim($item->getAttribute('alt') ?? '');
                    $description = preg_replace('~&[A-z#0-9]+;~', ' ', $description);
                    $description = preg_replace('~[ ]{2,}~', ' ', $description);
                    $description = mb_strtolower($description);

                    $src = trim($item?->getAttribute('src') ?? '');

                    if ($src) {
                        $media[] = [
                            'type'        => $item->getTag()->name(),
                            'url'         => $src,
                            'description' => $description,
                        ];
                    }
                }
            }
        }

        return $media;
    }


    /**
     * @param $url
     * @return string
     */
    private function getDomain($url): string {

        $parse_url = parse_url($url);
        return $parse_url['host'] ?? '';
    }


    /**
     * @param $url
     * @return string
     */
    private function getScheme($url): string {

        $parse_url = parse_url($url);
        return $parse_url['scheme'] ?? 'http';
    }


    /**
     * @param string $string
     * @param array  $tags
     * @return string
     */
    private function deleteTags(string $string, array $tags): string {

        $html = [];

        foreach ($tags as $tag) {
            $html[] = "/(<(?:\/{$tag}|{$tag})[^>]*>)/i";
        }

        return (string)preg_replace($html, '', $string);
    }
}