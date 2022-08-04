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

                    if ( ! in_array($name, ['mfa.gov.by'])) {
                        continue;
                    }

                    $source_data = $transform->getSource($source->start_url, $source->tags, $source->region);
                    $source_id   = $loader->saveSource($source_data);


                    $site = new Sources\Index\Site($source);

                    $pages_list = $site->loadList();
                    $pages_url  = [];

                    foreach ($pages_list as $page_list) {
                        if ( ! empty($page_list['url'])) {
                            $pages_url[] = $page_list['url'];
                        }
                    }

                    if (empty($pages_url)) {
                        throw new \Exception(sprintf('На ресурсе %s не найдены страницы ведущие по какому-либо адресу. Проверьте правила поиска', $source->start_url));
                    }

                    $pages_item = $site->loadPages($pages_url);

                    foreach ($pages_item as $page_item) {
                        $options = [];

                        foreach ($pages_list as $page_list) {
                            if ($page_list['url'] == $page_item['url']) {
                                $options['count_views']  = $page_list['count_views'];
                                $options['date_publish'] = $page_list['date_publish'];
                                $options['tags']         = $page_list['tags'];
                                $options['categories']   = $page_list['categories'];
                                break;
                            }
                        }

                        $loader->saveSourceContent($source_id, $page_item['url'], $page_item['content'], $options);
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
            $loader = new Sources\Etl\Loader();

            $this->modSources->dataSourcesContentsRaw->refreshStatusRows();

            foreach ($configs as $name => $source) {

                try {
                    if ( ! $source?->selectors || ! $source?->selectors?->page) {
                        continue;
                    }

                    if ( ! in_array($name, ['mfa.gov.by'])) {
                        continue;
                    }

                    $site         = new Sources\Index\Site($source);
                    $pages_raw    = $this->modSources->dataSourcesContentsRaw->getRowsPendingByDomain($name);
                    $count_errors = 0;

                    foreach ($pages_raw as $page_raw) {
                        try {
                            $page_raw->status = 'process';
                            $page_raw->save();

                            $page = $site->parsePage($page_raw->url, $page_raw->content);

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
