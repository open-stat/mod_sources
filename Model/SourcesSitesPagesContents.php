<?php


/**
 *
 */
class SourcesSitesPagesContents extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_sites_pages_contents';


    /**
     * @param string $page_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByPageId(string $page_id):? \Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("page_id = ?", $page_id);

        return $this->fetchRow($select);
    }
}