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
     * @param bool       $force_update
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveMessage(int $chat_id, int $message_id, array $options = null, bool $force_update = false): \Zend_Db_Table_Row_Abstract {

        $source_message = $this->getRowByChatMessengerId($chat_id, $message_id);

        if (empty($source_message)) {
            $source_message = $this->createRow([
                'chat_id'                => $chat_id,
                'messenger_id'           => $message_id,
                'user_id'                => $options['user_id'] ?? null,
                'media_type'             => $options['media_type'] ?? null,
                'content'                => $options['content'] ?? null,
                'reply_to_id'            => $options['reply_to_id'] ?? null,
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

        } else {
            $is_save = false;

            if (($force_update || empty($source_message->user_id)) && ! empty($options['user_id'])) {
                $source_message->user_id = $options['user_id'];
                $is_save = true;
            }
            if (($force_update || empty($source_message->media_type)) && ! empty($options['media_type'])) {
                $source_message->media_type = $options['media_type'];
                $is_save = true;
            }
            if (($force_update || empty($source_message->content)) && ! empty($options['content'])) {
                $source_message->content = $options['content'];
                $is_save = true;
            }
            if (($force_update || empty($source_message->reply_to_id)) && ! empty($options['reply_to_id'])) {
                $source_message->reply_to_id = $options['reply_to_id'];
                $is_save = true;
            }
            if (($force_update || empty($source_message->fwd_chat_id)) && ! empty($options['fwd_chat_id'])) {
                $source_message->fwd_chat_id = $options['fwd_chat_id'];
                $is_save = true;
            }
            if (($force_update || empty($source_message->fwd_message_id)) && ! empty($options['fwd_message_id'])) {
                $source_message->fwd_message_id = $options['fwd_message_id'];
                $is_save = true;
            }
            if (($force_update || empty($source_message->group_value)) && ! empty($options['group_value'])) {
                $source_message->group_value = $options['group_value'];
                $is_save = true;
            }
            if (($force_update || empty($source_message->comments_count)) && ! empty($options['comments_count'])) {
                $source_message->comments_count = $options['comments_count'];
                $is_save = true;
            }
            if (($force_update || empty($source_message->viewed_count)) && ! empty($options['viewed_count'])) {
                $source_message->viewed_count = $options['viewed_count'];
                $is_save = true;
            }
            if (($force_update || empty($source_message->repost_count)) && ! empty($options['repost_count'])) {
                $source_message->repost_count = $options['repost_count'];
                $is_save = true;
            }
            if (($force_update || empty($source_message->date_messenger_created)) && ! empty($options['date_messenger_created'])) {
                $source_message->date_messenger_created = $options['date_messenger_created'];
                $is_save = true;
            }
            if (($force_update || empty($source_message->date_messenger_edit)) && ! empty($options['date_messenger_edit'])) {
                $source_message->date_messenger_edit = $options['date_messenger_edit'];
                $is_save = true;
            }

            if ($is_save) {
                $source_message->save();
            }
        }

        return $source_message;
    }
}