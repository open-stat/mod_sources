<?php
namespace Core2\Mod\Sources\Index;
use Core2\Mod\Sources;

/**
 *
 */
class Site {

    /**
     * @var array
     */
    private $config;


    /**
     * @param array $config
     */
    public function __construct(array $config) {

        $this->config = $config;
    }


    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function loadList(): array {

        $extract   = new Sources\Etl\Extract();
        $transform = new Sources\Etl\Transform();

        $list_content = $extract->loadList($this->config['start_url']);

        if (empty($list_content)) {
            throw new \Exception(sprintf('На ресурсе %s не удалось получить содержимое страницы', $this->config['start_url']));
        }

        $pages_list = $transform->parseList($list_content, $this->config['list'], [
            'url' => $this->config['start_url'],
        ]);

        if (empty($pages_list)) {
            throw new \Exception(sprintf('На ресурсе %s не найдены страницы. Проверьте правила поиска', $this->config['start_url']));
        }

        return $pages_list;
    }


    /**
     * @param array $pages_url
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function loadPages(array $pages_url): array {

        $extract    = new Sources\Etl\Extract();
        $pages_item = $extract->loadPages($pages_url);

        foreach ($pages_item as $k => $page_item) {
            if (isset($this->config['encoding']) && $this->config['encoding']) {
                $pages_item[$k]['content'] = iconv($this->config['encoding'], 'utf-8', $page_item['content']);
            }
        }

        return $pages_item;
    }


    /**
     * @param string $url
     * @param string $content
     * @return array
     */
    public function parsePage(string $url, string $content): array {

        if (empty($this->config['page'])) {
            return [];
        }

        $transform = new Sources\Etl\Transform();

        $page        = [];
        $page['url'] = $url;
        $page += $transform->parsePage($content, $this->config['page'], [
            'date_format' => $this->config['page']['date_format'] ?? null,
            'url'         => $url
        ]);


        if ( ! empty($this->config['page']['clear'])) {
            $page = $transform->clearPage($page, $this->config['page']['clear']);
        }

        return $page;
    }
}