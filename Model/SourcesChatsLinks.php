<?php


/**
 *
 */
class SourcesChatsLinks extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_links';

    /**
     * @param string $url
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByUrl(string $url):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("url = ?", $url)
        );
    }


    /**
     * @param string $hash
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByHash(string $hash):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("hash = ?", $hash)
        );
    }


    /**
     * Сохранение ссылки
     * @param string     $url
     * @param array|null $options
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveLink(string $url, array $options = null): \Zend_Db_Table_Row_Abstract  {

        $hash = md5($url);
        $link = $this->getRowByHash($hash);

        if (empty($link)) {
            $url_parts = $this->getUrlParts($url);

            $link = $this->createRow([
                'host'        => $url_parts['host'] ?? null,
                'url'         => $url,
                'hash'        => $hash,
                'type'        => $options['type'] ?? null,
                'title'       => $options['title'] ?? null,
                'description' => $options['description'] ?? null,
            ]);

            $link->save();

        } else {
            $is_save = false;

            if (empty($link->title) && ! empty($options['title'])) {
                $link->title = $options['title'];
                $is_save     = true;
            }
            if (empty($link->description) && ! empty($options['description'])) {
                $link->description = $options['description'];
                $is_save           = true;
            }
            if (empty($link->type) && ! empty($options['type'])) {
                $link->type = $options['type'];
                $is_save    = true;
            }

            if ($is_save) {
                $link->save();
            }
        }

        return $link;
    }


    /**
     * @param string $url
     * @return array
     */
    private function getUrlParts(string $url): array {

        $url_parts = [];

        if (filter_var($url, FILTER_VALIDATE_URL) ||
            filter_var("https://{$url}", FILTER_VALIDATE_URL) ||
            ! empty(parse_url($url))
        ) {
            if ( ! filter_var($url, FILTER_VALIDATE_URL)) {
                $url = "https://{$url}";
            }

            $url_parts = parse_url($url);

            $url_parts['host_original'] = $url_parts['host'];
            $url_parts['host']          = preg_replace('~^www\.~i', '', $url_parts['host']);
        }

        return $url_parts;
    }
}