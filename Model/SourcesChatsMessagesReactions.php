<?php


/**
 *
 */
class SourcesChatsMessagesReactions extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_messages_reactions';

    /**
     * @param int $message_id
     * @param int $reaction_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByMessageReaction(int $message_id, int $reaction_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("message_id = ?", $message_id)
                ->where("reaction_id = ?", $reaction_id)
        );
    }


    /**
     * Сохранение реакции
     * @param int $message_id
     * @param int $reaction_id
     * @param int $count
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveReaction(int $message_id, int $reaction_id, int $count): \Zend_Db_Table_Row_Abstract  {

        $reaction = $this->getRowByMessageReaction($message_id, $reaction_id);

        if (empty($reaction)) {
            $reaction = $this->createRow([
                'message_id'  => $message_id,
                'reaction_id' => $reaction_id,
                'count'       => $count,
            ]);

            $reaction->save();
        } else {
            if ($reaction->count != $reaction['count']) {
                $reaction->count = $reaction['count'];
                $reaction->save();
            }
        }

        return $reaction;
    }
}