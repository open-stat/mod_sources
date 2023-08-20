<?php


/**
 *
 */
class SourcesChatsMessagesHashtag extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_messages_hashtags';

    /**
     * @param int $message_id
     * @param int $hashtag_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByMessageHashtag(int $message_id, int $hashtag_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("message_id = ?", $message_id)
                ->where("hashtag_id = ?", $hashtag_id)
        );
    }


    /**
     * Сохранение хэштега
     * @param int $message_id
     * @param int $hashtag_id
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveHashtag(int $message_id, int $hashtag_id): \Zend_Db_Table_Row_Abstract  {

        $hashtag = $this->getRowByMessageHashtag($message_id, $hashtag_id);

        if (empty($hashtag)) {
            $hashtag = $this->createRow([
                'message_id' => $message_id,
                'hashtag_id' => $hashtag_id,
            ]);

            $hashtag->save();
        }

        return $hashtag;
    }
}