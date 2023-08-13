<?php


/**
 *
 */
class SourcesChatsCategoriesLink extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_categories_link';


    /**
     * @param int $messenger_id
     * @param int $category_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByChatCategory(int $messenger_id, int $category_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("messenger_id = ?", $messenger_id)
                ->where("category_id = ?", $category_id)
        );
    }
}