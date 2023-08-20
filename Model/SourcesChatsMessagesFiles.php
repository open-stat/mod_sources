<?php


/**
 *
 */
class SourcesChatsMessagesFiles extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_messages_files';

    /**
     * @param int    $message_id
     * @param string $hash
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByMessageHash(int $message_id, string $hash):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("refid = ?", $message_id)
                ->where("hash = ?", $hash)
        );
    }


    /**
     * Сохранение пустого файла-заготовки
     * @param int   $message_id
     * @param array $meta_data
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveFileEmpty(int $message_id, array $meta_data): \Zend_Db_Table_Row_Abstract  {

        $meta_data_encode = json_encode($meta_data);
        $hash             = md5($meta_data_encode);
        $file = $this->getRowByMessageHash($message_id, $hash);

        if (empty($file)) {
            $media_type = ! empty($message['media']) && ! empty($message['media']['_']) ? $message['media']['_'] : null;

            $file = $this->createRow([
                'refid'      => $message_id,
                'hash'       => $hash,
                'media_type' => $media_type,
                'meta_data'  => $meta_data_encode,
                'is_load_sw' => 'N',
            ]);

            $file->save();
        }

        return $file;
    }
}