<?php


/**
 *
 */
class SourcesChatsFiles extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_files';

    /**
     * @param int    $chat_id
     * @param string $hash
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowLogoByChatHash(int $chat_id, string $hash):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("refid = ?", $chat_id)
                ->where("hash = ?", $hash)
        );
    }


    /**
     * @param int        $chat_id
     * @param string     $content
     * @param array|null $meta_data
     * @return void
     */
    public function saveLogo(int $chat_id, string $content, array $meta_data = null): void {

        $content_hash = md5($content);
        $chat_content = $this->getRowLogoByChatHash($chat_id, $content_hash);

        if (empty($chat_content)) {
            $chat_content = $this->createRow([
                'content'   => $content,
                'refid'     => $chat_id,
                'hash'      => $content_hash,
                'filesize'  => strlen($content),
                'fieldid'   => 'logo_current',
                'filename'  => 'logo.png',
                'type'      => 'image/png',
                'meta_data' => json_encode($meta_data, JSON_UNESCAPED_UNICODE),
            ]);
            $chat_content->save();

            $old_logo_current = $this->fetchAll(
                $this->select()
                    ->where("refid = ?", $chat_id)
                    ->where("fieldid = 'logo_current'")
                    ->where("id != ?", $chat_content->id)
            );

            if ( ! empty($old_logo_current)) {
                foreach ($old_logo_current as $row) {
                    $row->fieldid = 'logo';
                    $row->save();
                }
            }
        }
    }
}