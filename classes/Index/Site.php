<?php
namespace Core2\Mod\Sources\Index;
use Core2\Mod\Sources;

/**
 *
 */
class Site {

    /**
     * @var \Zend_Config
     */
    private $source;


    /**
     * @param \Zend_Config $source
     */
    public function __construct(\Zend_Config $source) {

        $this->source = $source;
    }


    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     * @throws \Exception
     */
    public function loadList(): array {

        $extract   = new Sources\Etl\Extract();
        $transform = new Sources\Etl\Transform();


        $list_content = $extract->loadList($this->source->start_url);

        if (empty($list_content)) {
            throw new \Exception(sprintf('На ресурсе %s не удалось получить содержимое страницы', $this->source->start_url));
        }

        $pages_list = $transform->parseList($list_content, $this->source->selectors->list->toArray(), [
            'date_format' => $this->source->date_format,
            'url'         => $this->source->start_url,
        ]);

        if (empty($pages_list)) {
            throw new \Exception(sprintf('На ресурсе %s не найдены страницы. Проверьте правила поиска', $this->source->start_url));
        }

        return $pages_list;
    }


    /**
     * @param array $pages_url
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     * @throws \Exception
     */
    public function loadPages(array $pages_url): array {

        $extract    = new Sources\Etl\Extract();
        $pages_item = $extract->loadPages($pages_url);

        foreach ($pages_item as $k => $page_item) {
            if (isset($this->source->encoding) && $this->source->encoding) {
                $pages_item[$k]['content'] = iconv($this->source->encoding, 'utf-8', $page_item['content']);
            }
        }

        return $pages_item;
    }


    /**
     * @param string $url
     * @param string $content
     * @return array
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     */
    public function parsePage(string $url, string $content): array {

        $transform = new Sources\Etl\Transform();

        $page        = [];
        $page['url'] = $url;
        $page += $transform->parsePage($content, $this->source->selectors->page->toArray(), [
            'date_format' => $this->source->selectors->page?->date_format ?: $this->source->date_format,
            'url'         => $url
        ]);


        if ($this->source->page && $this->source->page->clear) {
            $page = $transform->clearPage($page, $this->source->page->clear->toArray());
        }

        return $page;
    }
}