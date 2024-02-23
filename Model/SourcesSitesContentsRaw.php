<?php


/**
 *
 */
class SourcesSitesContentsRaw extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_sites_contents_raw';


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
     * @param int $source_id
     * @param int $limit
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getRowsPendingBySourceId(int $source_id, int $limit = 1000): \Zend_Db_Table_Rowset_Abstract {

        $select = $this->select()
            ->where("source_id = ?", $source_id)
            ->where("status = 'pending'")
            ->where("file_name IS NOT NULL")
            ->limit($limit);

        return $this->fetchAll($select);
    }
}