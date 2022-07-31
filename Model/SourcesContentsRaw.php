<?php


/**
 *
 */
class SourcesContentsRaw extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_contents_raw';


    /**
     * @param string $url
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByUrl(string $url):? \Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("url = ?", $url);

        return $this->fetchRow($select);
    }


    /**
     * @return void
     */
    public function refreshStatusRows(): void {

        $where   = [];
        $where[] = "status = 'process'";
        $where[] = "DATE_ADD(date_last_update, INTERVAL 5 MINUTE) < NOW()";

        $this->update(["status" => 'pending'], $where);
    }


    /**
     * @param string $domain
     * @param int    $limit
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getRowsPendingByDomain(string $domain, int $limit = 1000): \Zend_Db_Table_Rowset_Abstract {

        $select = $this->select()
            ->where("domain = ?", $domain)
            ->where("status = 'pending'")
            ->limit($limit);

        return $this->fetchAll($select);
    }
}