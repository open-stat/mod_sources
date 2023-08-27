<?php


/**
 *
 */
class SourcesSitesPagesTags extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_sites_pages_tags';


    /**
     * @param int $page_id
     * @return void
     */
    public function deleteByPage(int $page_id): void {

        $where = $this->_db->quoteInto('page_id = ?', $page_id);
        $this->delete($where);
    }


    /**
     * @param int $page_id
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getRowsByPageId(int $page_id): \Zend_Db_Table_Rowset_Abstract {

        $select = $this->select()
            ->where("page_id = ?", $page_id);

        return $this->fetchAll($select);
    }
}