<?php


/**
 *
 */
class SourcesVideosLinks extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos_links';


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
     * @param string $url
     * @return Zend_Db_Table_Row_Abstract
     */
    public function save(string $url): \Zend_Db_Table_Row_Abstract  {

        if (mb_strlen($url) > 10000) {
            $url = mb_substr($url, 0, 10000);
        }

        $hash = md5($url);
        $link = $this->getRowByHash($hash);

        if (empty($link)) {
            $url_parts = $this->getUrlParts($url);

            $link = $this->createRow([
                'host' => $url_parts['host'] ?? null,
                'url'  => $url,
                'hash' => $hash,
            ]);

            $link->save();
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