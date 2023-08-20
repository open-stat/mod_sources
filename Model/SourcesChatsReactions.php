<?php


/**
 *
 */
class SourcesChatsReactions extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_reactions';

    /**
     * @param string $emoticon
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByEmoticon(string $emoticon):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("emoticon = ?", $emoticon)
        );
    }


    /**
     * Сохранение реакции
     * @param string $emoticon
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveReaction(string $emoticon): \Zend_Db_Table_Row_Abstract  {

        $reaction = $this->getRowByEmoticon($emoticon);

        if (empty($reaction)) {
            $reaction = $this->createRow([
                'emoticon' => $emoticon,
            ]);

            $reaction->save();
        }

        return $reaction;
    }
}