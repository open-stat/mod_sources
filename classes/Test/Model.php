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
     * @throws \Exception
     */
    public function testSite(string $rules, array $options = []): string {

        ini_set('display_errors', 1);

        $file_name = $this->config->temp . '/' . 'text_rules.ini';
        file_put_contents($file_name, $rules);

        $config          = new \Zend_Config_Ini($file_name);
        $source_sections = $config->toArray();
        $config_section  = [];

        foreach ($source_sections as $section_name_raw => $section) {
            if (mb_strpos($section_name_raw, 'data__') === 0) {

                if ( ! ($section['active'] ?? true)) {
                    continue;
                }

                $config_section = $section;
                break;
            }
        }

        if (empty($config_section)) {
            throw new \Exception('Не найдены активные разделы');
        }

        $is_debug_requests = (bool)($options['debug_requests'] ?? false);

        $site = new Sources\Sites\Site($config_section, [
            'debug_requests' => $is_debug_requests
        ]);

        // загрузка одной страницы
        if ( ! empty($options['option_page_url'])) {
            $pages_url = [$options['option_page_url']];

        // Получение вписка страниц
        } else {
            if ($is_debug_requests) {
                echo "<h4 class=\"text-muted\" style=\"cursor: pointer\" onclick=\"$(this).next().toggle()\">Debug запроса</h4>";
                echo '<pre style="max-width: 100%">';
                try {
                    $pages_list = $site->loadList();
                    echo '</pre>';

                } catch (\Exception $e) {
                    echo "ОШИБКА: " . $e->getMessage();
                    echo '</pre>';
                    return '';
                }

            } else {
                $pages_list = $site->loadList();
            }

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


        if ($is_debug_requests) {
            echo "<h4 class=\"text-muted\" style=\"cursor: pointer\" onclick=\"$(this).next().toggle()\">Debug запроса</h4>";
            echo '<pre style="max-width: 100%">';
            try {
                $pages = $site->loadPages($pages_url);
                echo '</pre>';

            } catch (\Exception $e) {
                echo "ОШИБКА: " . $e->getMessage();
                echo '</pre>';
                return '';
            }

        } else {
            $pages = $site->loadPages($pages_url);
        }
        $pages_count = count($pages);


        echo "<h4 class=\"text-muted\" style=\"cursor: pointer\" onclick=\"$(this).next().toggle()\">Полученные страницы - {$pages_count}</h4>";
        echo '<pre style="max-width: 1000px">';
        foreach ($pages as $page) {
            $page['content'] = htmlspecialchars($page['content']);
            $page['content'] = str_replace(["\r\n", "\r", "\n"], '', $page['content']);

            print_r($page);
        }
        echo '</pre>';

        if ( ! empty($config_section['page'])) {
            echo "<h4 class=\"text-muted\" style=\"cursor: pointer\" onclick=\"$(this).next().toggle()\">Обработанные данные - {$pages_count}</h4>";
            echo '<pre style="max-width: 1000px;white-space: break-spaces;">';
            foreach ($pages as $page) {
                $page_parsed = $site->parsePage($page['url'], $page['content']);

                print_r($page_parsed);
            }
            echo '</pre>';
        }

        return '';
    }
}