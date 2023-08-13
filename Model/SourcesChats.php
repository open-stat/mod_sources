<?php


/**
 *
 */
class SourcesChats extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats';

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


    /**
     * @param string $peer_name
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByTgChannelPeer(string $peer_name):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("type = 'channel'")
                ->where("messenger_type = 'tg'")
                ->where("peer_name = ?", $peer_name)
        );
    }


    /**
     * @param string $peer_name
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByTgGroupPeer(string $peer_name):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("type = 'group'")
                ->where("messenger_type = 'tg'")
                ->where("peer_name = ?", $peer_name)
        );
    }
}