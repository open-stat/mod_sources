<?php


/**
 *
 */
class Sources extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources';


    /**
     * @param string $source_domain
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByDomain(string $source_domain):? \Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("domain = ?", $source_domain);

        return $this->fetchRow($select);
    }
}