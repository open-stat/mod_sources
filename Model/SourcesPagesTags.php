<?php


/**
 *
 */
class SourcesPagesTags extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_pages_tags';


    /**
     * @param int $page_id
     * @return void
     */
    public function deleteByPage(int $page_id): void {

        $where = $this->_db->quoteInto('page_id = ?', $page_id);
        $this->delete($where);
    }
}