<?php
namespace Core2\Mod\Sources\Sites\Etl;
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

        $source_row = $this->modSources->dataSourcesSites->getRowByTitle($source['title']);

        if (empty($source_row)) {
            $source_row = $this->modSources->dataSourcesSites->createRow([
                'title'  => $source['title'],
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
     * @param array  $content
     * @param array  $options
     * @return void
     */
    public function saveSourceContent(int $source_id, string $url, array $content, array $options = []): void {

        $content_row = $this->modSources->dataSourcesSitesContentsRaw->getRowByUrl($url);

        // todo возможно следует добавлять версию 2..n
        if (empty($content_row)) {
            $content_row = $this->modSources->dataSourcesSitesContentsRaw->createRow([
                'source_id'    => $source_id,
                'domain'       => parse_url($url)['host'] ?? null,
                'url'          => $url,
                'content_type' => $content['content_type'] ?? 'html',
                'section_name' => $content['section_name'] ?? null,
                'content'      => gzcompress($content['content'], 9),
                'options'      => json_encode($options),
            ]);
            $content_row->save();
        }
    }


    /**
     * @param int   $source_id
     * @param array $page
     * @return void
     * @throws \Exception
     */
    public function savePage(int $source_id, array $page): void {

        if (empty($page['url'])) {
            throw new \Exception('Отсутствует адрес страницы');
        }

        if (empty($page['title'])) {
            throw new \Exception("Отсутствует заголовок страницы: {$page['url']}");
        }


        $this->db->beginTransaction();
        try {
            $page_row = $this->modSources->dataSourcesSitesPages->getRowByUrl($page['url']);

            if (empty($page_row)) {
                $page_row = $this->modSources->dataSourcesSitesPages->createRow([
                    'source_id'     => $source_id,
                    'title'         => trim($page['title']),
                    'url'           => $page['url'],
                    'image'         => $page['image'] ?? null,
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
                $page_row->image         = $page['image'] ?? null;
                $page_row->source_domain = $page['source_domain'] ?? null;
                $page_row->source_url    = $page['source_url'] ?? null;
                $page_row->source_author = $page['author'] ?? null;
                $page_row->count_views   = (int)($page['count_views'] ?? 0) ?: null;
                $page_row->date_publish  = ($page['date_publish'] ?? '') ?: null;
                $page_row->save();
            }


            $page_content_row = $this->modSources->dataSourcesSitesPagesContents->getRowByPageId($page_row->id);

            if (empty($page_content_row)) {
                $page_content_row = $this->modSources->dataSourcesSitesPagesContents->createRow([
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



            $this->modSources->dataSourcesSitesPagesTags->deleteByPage($page_row->id);
            $this->modSources->dataSourcesSitesPagesMedia->deleteByPage($page_row->id);
            $this->modSources->dataSourcesSitesPagesReferences->deleteByPage($page_row->id);

            if ( ! empty($page['tags'])) {
                foreach ($page['tags'] as $tag) {

                    if ($tag) {
                        $tag_row = $this->modSources->dataSourcesSitesTags->saveTag($tag, 'tag');

                        $page_tag_row = $this->modSources->dataSourcesSitesPagesTags->createRow([
                            'page_id' => $page_row->id,
                            'tag_id'  => $tag_row->id,
                        ]);
                        $page_tag_row->save();
                    }
                }
            }


            if ( ! empty($page['categories'])) {
                foreach ($page['categories'] as $tag) {

                    if ($tag) {
                        $tag_row = $this->modSources->dataSourcesSitesTags->saveTag($tag, 'category');

                        $page_tag_row = $this->modSources->dataSourcesSitesPagesTags->createRow([
                            'page_id' => $page_row->id,
                            'tag_id'  => $tag_row->id,
                        ]);
                        $page_tag_row->save();
                    }
                }
            }


            if ( ! empty($page['region'])) {
                foreach ($page['region'] as $tag) {

                    if ($tag) {
                        $tag_row = $this->modSources->dataSourcesSitesTags->saveTag($tag, 'region');

                        $page_tag_row = $this->modSources->dataSourcesSitesPagesTags->createRow([
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
                        $page_media_row = $this->modSources->dataSourcesSitesPagesMedia->createRow([
                            'page_id'     => $page_row->id,
                            'url'         => $media['url'],
                            'type'        => $media['type'],
                            'description' => mb_substr($media['description'] ?? '', 0, 250),
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
                        $page_references_row = $this->modSources->dataSourcesSitesPagesReferences->createRow([
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

            throw $e;
        }
    }
}