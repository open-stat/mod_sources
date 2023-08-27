<?php


/**
 *
 */
class SourcesSitesPagesMedia extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_sites_pages_media';


    /**
     * @param int $page_id
     * @return void
     */
    public function deleteByPage(int $page_id): void {

        $where = $this->_db->quoteInto('page_id = ?', $page_id);
        $this->delete($where);
    }
}