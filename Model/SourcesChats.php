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
    public function getRowByTgPeer(string $peer_name):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("messenger_type = 'tg'")
                ->where("peer_name = ?", $peer_name)
        );
    }


    /**
     * @param string $peer_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByTgPeerId(string $peer_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("messenger_type = 'tg'")
                ->where("peer_id = ?", $peer_id)
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
     * @param string      $peer_id
     * @param string|null $type
     * @param string      $messenger_type
     * @param array|null  $options
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveChatId(string $peer_id, string $type = null, string $messenger_type = 'tg', array $options = null): \Zend_Db_Table_Row_Abstract {

        $chat = $this->getRowByTgPeerId($peer_id);

        if (empty($chat) && ! empty($options['peer_name'])) {
            $chat = $this->getRowByTgPeer($options['peer_name']);
        }


        if (empty($chat)) {
            $chat = $this->createRow([
                'messenger_type'    => $messenger_type,
                'type'              => $type ?? null,
                'peer_id'           => $peer_id,
                'peer_name'         => $options['peer_name'] ?? null,
                'title'             => $options['title'] ?? null,
                'description'       => $options['description'] ?? null,
                'geolocation'       => $options['geolocation'] ?? null,
                'subscribers_count' => $options['subscribers_count'] ?? null,
                'is_connect_sw'     => $options['is_connect_sw'] ?? 'N',
            ]);
            $chat->save();
        } else {
            $is_save = false;

            if (empty($chat->type) && ! empty($type)) {
                $chat->type = $type;
                $is_save = true;
            }
            if (empty($chat->peer_name) && ! empty($options['peer_name'])) {
                $chat->peer_name = $options['peer_name'];
                $is_save = true;
            }
            if (empty($chat->title) && ! empty($options['title'])) {
                $chat->title = $options['title'];
                $is_save = true;
            }
            if (empty($chat->description) && ! empty($options['description'])) {
                $chat->description = $options['description'];
                $is_save = true;
            }
            if (empty($chat->geolocation) && ! empty($options['geolocation'])) {
                $chat->geolocation = $options['geolocation'];
                $is_save = true;
            }
            if (empty($chat->subscribers_count) && ! empty($options['subscribers_count'])) {
                $chat->subscribers_count = $options['subscribers_count'];
                $is_save = true;
            }

            if ($is_save) {
                $chat->save();
            }
        }

        return $chat;
    }
}