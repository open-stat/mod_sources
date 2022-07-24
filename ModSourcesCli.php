<?php
use Core2\Mod\Sources;

require_once DOC_ROOT . 'core2/inc/classes/Common.php';
require_once "classes/autoload.php";


/**
 * @property ModSourcesController $modSources
 * @property ModProxyController   $modProxy
 */
class ModSourcesCli extends Common {

    /**
     * Получение актуальных статей
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function loadSources() {

        $configs = (new Sources\Index\Model())->getConfigs();

        if ($configs) {$extract   = new Sources\Etl\Extract();
            $transform = new Sources\Etl\Transform();
            $loader    = new Sources\Etl\Loader();

            foreach ($configs as $name => $source) {

                try {
                    if ( ! $source?->start_url ||
                         ! $source?->selectors ||
                         ! $source?->selectors?->list ||
                         ! $source?->selectors?->page
                    ) {
                        continue;
                    }

                    if ( ! in_array($name, ['auto.onliner.by', 'tech.onliner.by'])) {
                        continue;
                    }

                    $source_data = $transform->getSource($source->start_url, $source->tags, $source->region);
                    $source_id   = $loader->saveSource($source_data);


                    $list_content = $extract->loadList($source->start_url);

                    if (empty($list_content)) {
                        throw new \Exception('На ресурсе %s не удалось получить содержимое страницы');
                    }

                    $pages_list = $transform->parseList($list_content, $source->selectors->list->toArray(), [
                        'date_format' => $source->date_format,
                        'url'         => $source->start_url,
                    ]);

                    if (empty($pages_list)) {
                        throw new \Exception(sprintf('На ресурсе %s не найдены страницы. Проверьте правила поиска', $source->start_url));
                    }

                    $pages_url = [];
                    foreach ($pages_list as $page_list) {
                        if ( ! empty($page_list['url'])) {
                            $pages_url[] = $page_list['url'];
                        }
                    }

                    if (empty($pages_url)) {
                        throw new \Exception(sprintf('На ресурсе %s не найдены страницы ведущие по какому-либо адресу. Проверьте правила поиска', $source->start_url));
                    }


                    $pages_item = $extract->loadPages($pages_url);
                    $pages      = [];


                    foreach ($pages_item as $page_item) {
                        $loader->saveSourceContent($page_item['url'], $page_item['content']);

                        foreach ($pages_list as $page_list) {

                            if ($page_item['url'] == $page_list['url']) {
                                $page = $transform->parsePage($page_item['content'], $source->selectors->page->toArray(), [
                                    'date_format' => $source->selectors->page?->date_format ?: $source->date_format
                                ]);

                                $pages[] = $transform->mergePage($page_list, $page);
                                break;
                            }
                        }
                    }

                    foreach ($pages as $key => $page) {
                        if ($source->page && $source->page->clear) {
                            $pages[$key] = $transform->clearPage($page, $source->page->clear->toArray());
                        }
                    }

                    foreach ($pages as $page) {
                        $loader->savePage($source_id, $page);
                    }

                } catch (\Exception $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }
        }
    }
}
