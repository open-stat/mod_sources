<?php
namespace Core2\Mod\Sources\Etl;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\DomCrawler\Crawler;

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
     * @param string      $title
     * @param string|null $tags
     * @param string|null $regions
     * @return array
     */
    #[ArrayShape(['domain' => "string", 'tags' => "array", 'regions' => "array"])]
    public function getSource(string $title, string $tags = null, string $regions = null): array {

        $tags    = $tags    ? explode(',', $tags) : [];
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
            'title'   => $title,
            'tags'    => $tags,
            'regions' => $regions,
        ];
    }


    /**
     * @param string $content
     * @param array  $rules
     * @param array  $options
     * @return array
     */
    public function parseList(string $content, array $rules, array $options = []): array {

        $pages = [];
        $dom    = new Crawler($content);

        foreach ($rules as $rule) {

            if (empty($rule['items'])) {
                continue;
            }

            $pages_rule = $this->filter($dom, $rule['items'])->each(function (Crawler $item) use ($rule, $options) {
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
                        ? $this->filter($item, $rule['url'])
                        : $item;

                    $url = $item_url->count() > 0 ? trim($item_url->attr('href')) : '';

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
                        return null;
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
                    return $page;

                } catch (\Exception $e) {
                    echo $e->getMessage() . PHP_EOL;
                    echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;
                    return null;
                }
            });

            foreach ($pages_rule as $page) {
                if ($page) {
                    $pages[] = $page;
                }
            }
        }


        return $pages;
    }


    /**
     * @param string $content
     * @param array  $rules
     * @param array  $options
     * @return array
     */
    public function parsePage(string $content, array $rules, array $options = []): array {

        $dom  = new Crawler($content);
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

        try {
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
                $page['categories'] = $this->getTags($dom, $rules['category']);
            }

            if ( ! empty($rules['author'])) {
                $page['author'] = $this->getAuthor($dom, $rules['author']);
            }

            if ( ! empty($rules['content'])) {
                $items       = $this->filter($dom, $rules['content']);
                $content_raw = $items->each(function (Crawler $item) {
                    return trim($item->html());
                });

                if ($content_raw) {
                    $page['content'] = $this->getContent(implode(' ', $content_raw));
                }

                $page['media']      = $this->getMedia($dom, $rules['content']);
                $page['references'] = $this->getReferences($dom, $rules['content'], $options['url'] ?? null);
            }

            // Удаление одинаковых ссылок
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
     * @param Crawler $dom
     * @param string  $rule
     * @return array
     */
    private function getTags(Crawler $dom, string $rule): array {

        $items = $this->filter($dom, $rule);
        $tags  = [];

        foreach ($items as $item) {
            foreach (explode(',', $item->textContent) as $tag) {
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
     * @param Crawler $dom
     * @param string  $rule
     * @param string  $date_format
     * @return string
     */
    private function getDatePublish(Crawler $dom, string $rule, string $date_format = ''): string {

        $items = $this->filter($dom, $rule);

        $date_publish = '';

        if ($items->count() > 0) {
            $attr_datetime = $items->attr('datetime');

            if ($attr_datetime && preg_match('~(\d{4}-\d{2}-\d{2}.\d{2}:\d{2}:\d{2})~', $attr_datetime, $match)) {
                $date_publish_text = $match[1] ?? '';
                $date_format       = '~(?<year>[\d]{4})\-(?<month>[\d]{2})\-(?<day>[\d]{2}).(?<hour>[\d]{1,2}):(?<min>[\d]{1,2})(?:\:(?<sec>[\d]{1,2})|)~';

            } else {
                $date_publish_text = $items->text();
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
     * @param Crawler $dom
     * @param string  $rule
     * @return string
     */
    private function getSourceUrl(Crawler $dom, string $rule): string {

        $item = $this->filter($dom, $rule);
        return (string)($item->count() > 0 ? $item->attr('href') : '');
    }


    /**
     * @param Crawler $dom
     * @param string  $rule
     * @return string
     */
    private function getTitle(Crawler $dom, string $rule): string {

        $item = $this->filter($dom, $rule);

        $title = $item->count() > 0 ? $item->html() : '';
        $title = strip_tags($title);
        $title = preg_replace('~&[A-z#0-9]+;~', ' ', $title);
        $title = preg_replace('~ ~', ' ', $title);
        $title = preg_replace('~[ ]{2,}~', ' ', $title);
        $title = trim($title);

        return $title;
    }


    /**
     * @param Crawler $dom
     * @param string  $rule
     * @return int
     */
    private function getCountView(Crawler $dom, string $rule): int {

        $elements    = $this->filter($dom, $rule);
        $count_views = $elements->count() > 0 ? $elements->text() : '';

        return (int)preg_replace('~[^0-9]~', '', $count_views);
    }


    /**
     * @param Crawler $dom
     * @param string  $rule
     * @return string
     */
    private function getAuthor(Crawler $dom, string $rule): string {

        $elements = $this->filter($dom, $rule);

        $author = $elements->count() > 0 ? $elements->html() : '';
        $author = strip_tags($author);
        $author = htmlspecialchars_decode($author);
        $author = preg_replace('~&[A-z#0-9]+;~', ' ', $author);
        $author = str_replace('&nbsp', ' ', $author);
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
     * @param Crawler     $dom
     * @param string      $rule
     * @param string|null $source_url
     * @return array
     */
    private function getReferences(Crawler $dom, string $rule, string $source_url = null): array {

        $references    = [];
        $scheme        = $source_url ? $this->getScheme($source_url) : 'http';
        $content_links = $this->filter($dom, $rule . ($this->isXpath($rule) ? '*//a' : ' a'));

        foreach ($content_links as $content_link) {
            $url = $content_link->getAttribute('href');
            $url = str_replace('&amp;', '&', $url);


            if (preg_match('~\.(jpg|jpeg|png|gif|webp|mp4)$~', $url) ||
                preg_match('~^mailto:~', $url)
            ) {
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
        $content = str_replace("\n", ' ', $content);
        $content = preg_replace('~&[A-z#0-9]+;~', ' ', $content);
        $content = preg_replace('~[\s]{2,}~muis', ' ', $content);

        return trim($content);
    }


    /**
     * @param Crawler $dom
     * @param string  $rule
     * @return array
     */
    private function getMedia(Crawler $dom, string $rule): array {

        $media    = [];
        $elements = $this->filter($dom, $rule);


        if ($elements->count() > 0) {
            if (in_array(strtolower($elements->nodeName()), ['img', 'video', 'audio'])) {
                $src = trim($elements->attr('src') ?? '');

                if ($src) {
                    $description = trim($elements->attr('alt') ?? '');
                    $description = preg_replace('~&[A-z#0-9]+;~', ' ', $description);
                    $description = preg_replace('~[ ]{2,}~', ' ', $description);
                    $description = mb_strtolower($description);

                    $media[] = [
                        'type'        => $elements->nodeName(),
                        'url'         => $src,
                        'description' => $description,
                    ];
                }

            } else {
                $items_img   = $this->filter($dom, $rule . ($this->isXpath($rule) ? '*//img'   : ' img'));
                $items_video = $this->filter($dom, $rule . ($this->isXpath($rule) ? '*//video' : ' video'));
                $items_audio = $this->filter($dom, $rule . ($this->isXpath($rule) ? '*//audio' : ' audio'));

                foreach ($items_img as $item) {
                    $src = trim($item->getAttribute('src'));

                    if ($src) {
                        $description = trim($item->getAttribute('alt'));
                        $description = preg_replace('~&[A-z#0-9]+;~', ' ', $description);
                        $description = preg_replace('~[ ]{2,}~', ' ', $description);
                        $description = mb_strtolower($description);

                        $media[] = [
                            'type'        => $item->tagName,
                            'url'         => $src,
                            'description' => $description,
                        ];
                    }
                }

                foreach ($items_video as $item) {
                    $src = trim($item?->getAttribute('src'));

                    if ($src) {
                        $description = trim($item->getAttribute('alt'));
                        $description = preg_replace('~&[A-z#0-9]+;~', ' ', $description);
                        $description = preg_replace('~[ ]{2,}~', ' ', $description);
                        $description = mb_strtolower($description);

                        $media[] = [
                            'type'        => $item->tagName,
                            'url'         => $src,
                            'description' => $description,
                        ];
                    }
                }

                foreach ($items_audio as $item) {

                    $src = trim($item?->getAttribute('src'));

                    if ($src) {
                        $description = trim($item->getAttribute('alt'));
                        $description = preg_replace('~&[A-z#0-9]+;~', ' ', $description);
                        $description = preg_replace('~[ ]{2,}~', ' ', $description);
                        $description = mb_strtolower($description);

                        $media[] = [
                            'type'        => $item->tagName,
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

        $regex = [];

        foreach ($tags as $tag) {
            $regex[] = "~(<{$tag}[^>]*>.*?</{$tag}[^>]*>)~muis";
        }

        return (string)preg_replace($regex, '', $string);
    }


    /**
     * @param Crawler $dom
     * @param string  $rule
     * @return Crawler
     */
    private function filter(Crawler $dom, string $rule): Crawler {

        if ($this->isXpath($rule)) {
            return $dom->filterXPath(mb_substr($rule, 6));
        } else {
            return $dom->filter($rule);
        }
    }


    /**
     * @param string $rule
     * @return bool
     */
    private function isXpath(string $rule): bool {

        return mb_strpos($rule, 'xpath:') === 0;
    }
}