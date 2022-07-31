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
     * @param int    $source_id
     * @param string $url
     * @param string $content
     * @return void
     */
    public function saveSourceContent(int $source_id, string $url, string $content): void {

        $content_row = $this->modSources->dataSourcesContentsRaw->getRowByUrl($url);

        if (empty($content_row)) {
            $content_row = $this->modSources->dataSourcesContentsRaw->createRow([
                'source_id' => $source_id,
                'domain'    => parse_url($url)['host'] ?? null,
                'url'       => $url,
                'content'   => $content,
            ]);
            $content_row->save();
        }
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
                    'source_domain' => $page['source_domain'] ?? null,
                    'source_url'    => $page['source_url'] ?? null,
                    'source_author' => $page['author'] ?? null,
                    'count_views'   => (int)($page['count_views'] ?? 0) ?: null,
                    'date_publish'  => ($page['date_publish'] ?? '') ?: null,
                ]);
                $page_row->save();

            } else {
                $page_row->title         = $page['title'];
                $page_row->url           = $page['url'];
                $page_row->source_domain = $page['source_domain'] ?? null;
                $page_row->source_url    = $page['source_url'] ?? null;
                $page_row->source_author = $page['author'] ?? null;
                $page_row->count_views   = (int)($page['count_views'] ?? 0) ?: null;
                $page_row->date_publish  = ($page['date_publish'] ?? '') ?: null;
                $page_row->save();
            }


            $page_content_row = $this->modSources->dataSourcesPagesContents->getRowByPageId($page_row->id);

            if (empty($page_content_row)) {
                $page_content_row = $this->modSources->dataSourcesPagesContents->createRow([
                    'page_id' => $page_row->id,
                    'content' => $page['content'],
                    'hash'    => md5($page['content']),
                ]);
                $page_content_row->save();

            } else {
                $page_content_row->content = $page['content'];
                $page_content_row->hash    = md5($page['content']);
                $page_content_row->save();
            }



            $this->modSources->dataSourcesPagesTags->deleteByPage($page_row->id);
            $this->modSources->dataSourcesPagesMedia->deleteByPage($page_row->id);
            $this->modSources->dataSourcesPagesReferences->deleteByPage($page_row->id);

            if ( ! empty($page['tags'])) {
                foreach ($page['tags'] as $tag) {

                    if ( ! $tag) {
                        $tag_row = $this->modSources->dataSourcesTags->saveTag($tag, 'tag');

                        $page_tag_row = $this->modSources->dataSourcesPagesTags->createRow([
                            'page_id' => $page_row->id,
                            'tag_id'  => $tag_row->id,
                        ]);
                        $page_tag_row->save();
                    }
                }
            }


            if ( ! empty($page['categories'])) {
                foreach ($page['categories'] as $tag) {

                    if ( ! $tag) {
                        $tag_row = $this->modSources->dataSourcesTags->saveTag($tag, 'category');

                        $page_tag_row = $this->modSources->dataSourcesPagesTags->createRow([
                            'page_id' => $page_row->id,
                            'tag_id'  => $tag_row->id,
                        ]);
                        $page_tag_row->save();
                    }
                }
            }


            if ( ! empty($page['region'])) {
                foreach ($page['region'] as $tag) {

                    if ( ! $tag) {
                        $tag_row = $this->modSources->dataSourcesTags->saveTag($tag, 'region');

                        $page_tag_row = $this->modSources->dataSourcesPagesTags->createRow([
                            'page_id' => $page_row->id,
                            'tag_id'  => $tag_row->id,
                        ]);
                        $page_tag_row->save();
                    }
                }
            }



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

            $this->db->commit();

        } catch (\Exception $e) {
            $this->db->rollback();
            echo $e->getMessage();

            return false;
        }


        return true;
    }
}