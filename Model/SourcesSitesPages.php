<?php


/**
 *
 */
class SourcesSitesPages extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_sites_pages';

    /**
     * @param string $url
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByUrl(string $url):? \Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("url = ?", $url);

        return $this->fetchRow($select);
    }
}