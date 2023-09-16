<?php


/**
 *
 */
class SourcesVideosRaw extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos_raw';


    /**
     * @param string $type
     * @param string $hash
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByTypeHash(string $type, string $hash):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("type = ?", $type)
                ->where("hash = ?", $hash)
        );
    }


    /**
     * @param string $type
     * @param array  $content
     * @param array  $meta_data
     * @return void
     */
    public function saveContent(string $type, array $content, array $meta_data): void {

        $content          = json_encode($content, JSON_UNESCAPED_UNICODE);
        $content_hash     = md5($content);
        $content_compress = gzcompress($content, 9);
        $chat_content     = $this->getRowByTypeHash($type, $content_hash);

        if (empty($chat_content)) {
            $chat_content = $this->createRow([
                'type'      => $type,
                'content'   => $content_compress,
                'hash'      => $content_hash,
                'meta_data' => json_encode($meta_data, JSON_UNESCAPED_UNICODE),
            ]);
            $chat_content->save();
        }
    }
}