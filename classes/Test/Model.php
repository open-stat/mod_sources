<?php
namespace Core2\Mod\Sources\Test;
use Core2\Mod\Sources;


/**
 *
 */
class Model extends \Common {

    /**
     * @param string $rules
     * @param array  $options
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     * @throws \Zend_Config_Exception
     */
    public function testSite(string $rules, array $options = []): string {

        ini_set('display_errors', 1);

        $file_name = $this->config->temp . '/' . 'text_rules.ini';
        file_put_contents($file_name, $rules);

        $config = new \Zend_Config_Ini($file_name);
        $site   = new Sources\Index\Site($config->source);

        // загрузка одной страницы
        if ( ! empty($options['option_page_url'])) {
            $pages_url = [$options['option_page_url']];

        // Получение вписка страниц
        } else {
            $pages_list  = $site->loadList();
            $pages_url   = [];
            $pages_count = count($pages_list);

            foreach ($pages_list as $page_list) {
                if ( ! empty($page_list['url'])) {
                    $pages_url[] = $page_list['url'];
                }
            }

            if (empty($options['load_all_pages'])) {
                $pages_url = [current($pages_url)];
            }

            echo "<h4 class=\"text-muted\" style=\"cursor: pointer\" onclick=\"$(this).next().toggle()\">Найденные страницы - {$pages_count}</h4>";
            echo '<pre style="max-width: 100%">';
            print_r($pages_list);
            echo '</pre>';
        }


        $pages       = $site->loadPages($pages_url);
        $pages_count = count($pages);


        echo "<h4 class=\"text-muted\" style=\"cursor: pointer\" onclick=\"$(this).next().toggle()\">Полученные страницы - {$pages_count}</h4>";
        echo '<pre style="max-width: 1000px">';
        foreach ($pages as $page) {
            $page['content'] = htmlspecialchars($page['content']);
            $page['content'] = str_replace(["\r\n", "\r", "\n"], '', $page['content']);

            print_r($page);
        }
        echo '</pre>';


        echo "<h4 class=\"text-muted\" style=\"cursor: pointer\" onclick=\"$(this).next().toggle()\">Обработанные данные - {$pages_count}</h4>";
        echo '<pre style="max-width: 1000px">';
        foreach ($pages as $page) {
            $page_parsed = $site->parsePage($page['url'], $page['content']);

            print_r($page_parsed);
        }
        echo '</pre>';


        return '';
    }
}