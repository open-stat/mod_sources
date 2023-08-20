<?php


/**
 *
 */
class SourcesChatsMessagesReplies extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_messages_replies';

    /**
     * @param int $message_id
     * @param int $user_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByMessageUser(int $message_id, int $user_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("message_id = ?", $message_id)
                ->where("user_id = ?", $user_id)
        );
    }


    /**
     * Сохранение пользователя комментатора
     * @param int $message_id
     * @param int $user_id
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveReply(int $message_id, int $user_id): \Zend_Db_Table_Row_Abstract  {

        $reply = $this->getRowByMessageUser($message_id, $user_id);

        if (empty($reply)) {
            $reply = $this->createRow([
                'message_id' => $message_id,
                'user_id'    => $user_id,
            ]);

            $reply->save();
        }

        return $reply;
    }
}