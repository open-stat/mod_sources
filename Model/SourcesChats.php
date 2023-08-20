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
     * @param string $peer_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByTgChannelPeerId(string $peer_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("type = 'channel'")
                ->where("messenger_type = 'tg'")
                ->where("peer_id = ?", $peer_id)
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


    /**
     * @param string $peer_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByTgGroupPeerId(string $peer_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("type = 'group'")
                ->where("messenger_type = 'tg'")
                ->where("peer_id = ?", $peer_id)
        );
    }


    /**
     * @param int        $chat_id
     * @param string     $type
     * @param string     $messenger_type
     * @param array|null $options
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveChatId(int $chat_id, string $type = 'channel', string $messenger_type = 'tg', array $options = null): \Zend_Db_Table_Row_Abstract {

        $chat = $type == 'channel'
            ? $this->getRowByTgChannelPeerId($chat_id)
            : $this->getRowByTgGroupPeerId($chat_id);

        if (empty($chat) && ! empty($options['peer_name'])) {
            $chat = $type == 'channel'
                ? $this->getRowByTgChannelPeer($options['peer_name'])
                : $this->getRowByTgGroupPeer($options['peer_name']);
        }


        if (empty($chat)) {
            $chat = $this->createRow([
                'messenger_type'    => $messenger_type,
                'type'              => $type,
                'peer_id'           => $chat_id,
                'peer_name'         => $options['peer_name'] ?? null,
                'title'             => $options['title'] ?? null,
                'description'       => $options['description'] ?? null,
                'geolocation'       => $options['geolocation'] ?? null,
                'subscribers_count' => $options['subscribers_count'] ?? null,
                'is_connect_sw'     => $options['is_connect_sw'] ?? 'N',
            ]);
            $chat->save();
        }

        return $chat;
    }
}