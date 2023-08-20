<?php


/**
 *
 */
class SourcesChatsMessages extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_messages';

    /**
     * @param int    $chat_id
     * @param string $id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByChatMessengerId(int $chat_id, string $id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("chat_Id = ?", $chat_id)
                ->where("messenger_Id = ?", $id)
        );
    }


    /**
     * @param int        $chat_id
     * @param int        $message_id
     * @param array|null $options
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveMessage(int $chat_id, int $message_id, array $options = null): \Zend_Db_Table_Row_Abstract {

        $source_message = $this->getRowByChatMessengerId($chat_id, $message_id);

        if (empty($source_message)) {
            $source_message = $this->createRow([
                'chat_id'                => $chat_id,
                'messenger_id'           => $message_id,
                'media_type'             => $options['media_type'] ?? null,
                'content'                => $options['content'] ?? null,
                'fwd_chat_id'            => $options['fwd_chat_id'] ?? null,
                'fwd_message_id'         => $options['fwd_message_id'] ?? null,
                'group_value'            => $options['group_value'] ?? null,
                'date_messenger_created' => $options['date_messenger_created'] ?? null,
                'date_messenger_edit'    => $options['date_messenger_edit'] ?? null,
                'comments_count'         => $options['comments_count'] ?? 0,
                'viewed_count'           => $options['viewed_count'] ?? 0,
                'repost_count'           => $options['repost_count'] ?? 0,
            ]);
            $source_message->save();
        }

        return $source_message;
    }
}