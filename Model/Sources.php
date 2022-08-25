<?php


/**
 *
 */
class Sources extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources';


    /**
     * @param string $source_title
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByTitle(string $source_title):? \Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("title = ?", $source_title);

        return $this->fetchRow($select);
    }
}