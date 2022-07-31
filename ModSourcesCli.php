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
     * Получение информации
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function loadSources() {

        $configs = (new Sources\Index\Model())->getConfigs();

        if ($configs) {
            $extract   = new Sources\Etl\Extract();
            $transform = new Sources\Etl\Transform();
            $loader    = new Sources\Etl\Loader();

            foreach ($configs as $name => $source) {

                try {
                    if ( ! $source?->start_url ||
                         ! $source?->selectors ||
                         ! $source?->selectors?->list
                    ) {
                        continue;
                    }

                    if ( ! in_array($name, ['kv.by'])) {
                        continue;
                    }

                    $source_data = $transform->getSource($source->start_url, $source->tags, $source->region);
                    $source_id   = $loader->saveSource($source_data);


                    $list_content = $extract->loadList($source->start_url);

                    if (empty($list_content)) {
                        throw new \Exception(sprintf('На ресурсе %s не удалось получить содержимое страницы', $source->start_url));
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

                    foreach ($pages_item as $page_item) {
                        $loader->saveSourceContent($source_id, $page_item['url'], $page_item['content']);
                    }

                } catch (\Exception $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }
        }
    }


    /**
     * Обработка информации
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function parseSources() {

        $configs = (new Sources\Index\Model())->getConfigs();

        if ($configs) {
            $transform = new Sources\Etl\Transform();
            $loader    = new Sources\Etl\Loader();

            $this->modSources->dataSourcesContentsRaw->refreshStatusRows();

            foreach ($configs as $name => $source) {

                try {
                    if ( ! $source?->selectors || ! $source?->selectors?->page) {
                        continue;
                    }

                    $pages_raw    = $this->modSources->dataSourcesContentsRaw->getRowsPendingByDomain($name);
                    $count_errors = 0;

                    foreach ($pages_raw as $page_raw) {
                        try {
                            $page_raw->status = 'process';
                            $page_raw->save();

                            $page = $transform->parsePage($page_raw->content, $source->selectors->page->toArray(), [
                                'date_format' => $source->selectors->page?->date_format ?: $source->date_format
                            ]);

                            $page['url'] = $page_raw->url;

                            if ($source->page && $source->page->clear) {
                                $page = $transform->clearPage($page, $source->page->clear->toArray());
                            }

                            $loader->savePage($page_raw->source_id, $page);

                            $page_raw->status = 'complete';
                            $page_raw->save();

                        } catch (\Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                            $count_errors++;

                            if ($count_errors >= 3) {
                                break;
                            }
                        }
                    }


                } catch (\Exception $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }
        }
    }
}
