<?php


/**
 *
 */
class SourcesChatsCategoriesLink extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_categories_link';


    /**
     * @param int $chat_id
     * @param int $category_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByChatCategory(int $chat_id, int $category_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("chat_id = ?", $chat_id)
                ->where("category_id = ?", $category_id)
        );
    }
}