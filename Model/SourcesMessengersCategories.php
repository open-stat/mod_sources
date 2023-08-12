<?php


/**
 *
 */
class SourcesMessengersCategories extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_messengers_categories';

    /**
     * @param string $title
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByTitle(string $title):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select() ->where("title = ?", $title)
        );
    }
}