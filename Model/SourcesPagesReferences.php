<?php


/**
 *
 */
class SourcesPagesReferences extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_pages_references';


    /**
     * @param string $page_id
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getRowsByPageId(string $page_id): \Zend_Db_Table_Rowset_Abstract {

        $select = $this->select()
            ->where("page_id = ?", $page_id);

        return $this->fetchAll($select);
    }


    /**
     * @param int $page_id
     * @return int
     */
    public function getCountByPageId(int $page_id): int {

        $select = $this->select()
            ->from($this->_name, ['count' => 'COUNT(*)'])
            ->where("refid = ?", $page_id);

        $row = $this->fetchRow($select);

        return $row ? (int)$row['count'] : 0;
    }


    /**
     * @param int $page_id
     * @return void
     */
    public function deleteByPage(int $page_id): void {

        $where = $this->_db->quoteInto('page_id = ?', $page_id);
        $this->delete($where);
    }
}