<?php


/**
 *
 */
class SourcesChatsContent extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_content';

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
     * @throws Zend_Config_Exception
     */
    public function saveContent(string $type, array $content, array $meta_data): void {

        $content_json = json_encode($content, JSON_UNESCAPED_UNICODE);
        $content_hash = md5($content_json);
        $chat_content = $this->getRowByTypeHash($type, $content_hash);

        if (empty($chat_content)) {
            $date      = new \DateTime();
            $file_name = "{$type}-{$content_hash}.json";
            $contents  = json_encode([
                'type'    => $type,
                'date'    => $date->format('Y-m-d H:i:s'),
                'meta'    => $meta_data,
                'content' => base64_encode(gzcompress($content_json, 9)),
            ], JSON_UNESCAPED_UNICODE);

            $file_path = (new \Core2\Mod\Sources\Model())->saveSourceFile('chats', $date, $file_name, $contents);


            $chat_content = $this->createRow([
                'type'        => $type,
                'file_name'   => $file_name,
                'file_size'   => filesize($file_path),
                'hash'        => $content_hash,
                'meta_data'   => json_encode($meta_data, JSON_UNESCAPED_UNICODE),
            ]);
            $chat_content->save();
        }
    }
}