<?php


/**
 *
 */
class SourcesMessengers extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_messengers';

    /**
     * @param string $peer_name
     * @param string $type
     * @param string $messenger_type
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByPeerType(string $peer_name, string $type, string $messenger_type):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("peer_name = ?", $peer_name)
                ->where("type = ?", $type)
                ->where("messenger_type = ?", $messenger_type)
        );
    }
}