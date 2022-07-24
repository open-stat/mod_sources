<?php
namespace Core2\Mod\Sources\Etl;
use Core2\Mod\Sources;

/**
 * @property \ModSourcesController $modSources
 */
class Loader extends \Common {

    /**
     * @param array $source
     * @return int
     */
    public function saveSource(array $source): int {

        $source_row = $this->modSources->dataSources->getRowByDomain($source['domain']);

        if (empty($source_row)) {
            $source_row = $this->modSources->dataSources->createRow([
                'domain' => $source['domain'],
                'tags'   => implode(', ', $source['tags']),
                'region' => implode(', ', $source['regions']),
            ]);
            $source_row->save();
        }

        return (int)$source_row->id;
    }


    /**
     * @param string $url
     * @param string $content
     * @return void
     */
    public function saveSourceContent(string $url, string $content): void {

        $domain     = parse_url($url)['host'] ?? 'none';
        $news_dir   = "{$this->config->temp}/news";
        $domain_dir = "{$news_dir}/{$domain}";

        if ( ! is_dir($news_dir)) {
            mkdir($news_dir, 0777);
        }
        if ( ! is_dir($domain_dir)) {
            mkdir($domain_dir, 0777);
        }

        if (is_dir($domain_dir) && is_writable($domain_dir)) {
            $file_name = md5($url) . '.html';
            file_put_contents("{$domain_dir}/{$file_name}", $content);
        }

//        $content_row = $this->modSources->dataSourcesContents->getRowByUrl($url);
//
//        if (empty($content_row)) {
//            $content_row = $this->modSources->dataSourcesContents->createRow([
//                'url'     => $url,
//                'content' => $content,
//                'hash'    => md5($content),
//            ]);
//            $content_row->save();
//        }
    }


    /**
     * @param int   $source_id
     * @param array $page
     * @return bool
     */
    public function savePage(int $source_id, array $page): bool {

        if (empty($page['url']) ||
            empty($page['title']) ||
            empty($page['content'])
        ) {
            return false;
        }


        $this->db->beginTransaction();
        try {
            $page_row = $this->modSources->dataSourcesPages->getRowByUrl($page['url']);

            if (empty($page_row)) {
                $page_row = $this->modSources->dataSourcesPages->createRow([
                    'source_id'     => $source_id,
                    'title'         => $page['title'],
                    'url'           => $page['url'],
                    'categories'    => implode(', ', $page['categories'] ?? []),
                    'tags'          => implode(', ', $page['tags'] ?? []),
                    'region'        => implode(', ', $page['region'] ?? []),
                    'source_domain' => $page['source_domain'] ?? null,
                    'source_url'    => $page['source_url'] ?? null,
                    'source_author' => $page['author'] ?? null,
                    'count_views'   => (int)($page['count_views'] ?? 0) ?: null,
                    'date_publish'  => ($page['date_publish'] ?? '') ?: null,
                ]);
                $page_row->save();


                $page_content_row = $this->modSources->dataSourcesPagesContents->createRow([
                    'page_id' => $page_row->id,
                    'content' => $page['content'],
                    'hash'    => md5($page['content']),
                ]);
                $page_content_row->save();



                if ( ! empty($page['media'])) {
                    foreach ($page['media'] as $media) {

                        if ( ! empty($media['url']) &&
                             ! empty($media['type'])
                        ) {
                            $page_media_row = $this->modSources->dataSourcesPagesMedia->createRow([
                                'page_id'     => $page_row->id,
                                'url'         => $media['url'],
                                'type'        => $media['type'],
                                'description' => $media['description'] ?? '',
                            ]);
                            $page_media_row->save();
                        }
                    }
                }


                if ( ! empty($page['references'])) {
                    foreach ($page['references'] as $reference) {

                        if ( ! empty($reference['domain']) &&
                             ! empty($reference['url'])
                        ) {
                            $page_references_row = $this->modSources->dataSourcesPagesReferences->createRow([
                                'page_id' => $page_row->id,
                                'domain'  => $reference['domain'],
                                'url'     => $reference['url'],
                            ]);
                            $page_references_row->save();
                        }
                    }
                }
            }

            $this->db->commit();

        } catch (\Exception $e) {
            $this->db->rollback();
            echo $e->getMessage();

            return false;
        }


        return true;
    }
}