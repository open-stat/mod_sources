<?php


/**
 *
 */
class SourcesChatsUsersLinks extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_users_links';

    /**
     * @param int $chat_id
     * @param int $user_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByChatIdUserid(int $chat_id, int $user_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("chat_id = ?", $chat_id)
                ->where("user_id = ?", $user_id)
        );
    }

    /**
     * @param int $chat_id
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getRowByChatId(int $chat_id): \Zend_Db_Table_Rowset_Abstract {

        return $this->fetchAll(
            $this->select()
                ->where("chat_id = ?", $chat_id)
        );
    }


    /**
     * @param int $user_id
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getRowByUserId(int $user_id): \Zend_Db_Table_Rowset_Abstract {

        return $this->fetchAll(
            $this->select()
                ->where("user_id = ?", $user_id)
        );
    }


    /**
     * Сохранение связи
     * @param int $chat_id
     * @param int $user_id
     * @return Zend_Db_Table_Row_Abstract
     */
    public function save(int $chat_id, int $user_id): \Zend_Db_Table_Row_Abstract  {

        $link = $this->getRowByChatIdUserid($chat_id, $user_id);

        if (empty($link)) {
            $link = $this->createRow([
                'chat_id' => $chat_id,
                'user_id' => $user_id,
            ]);

            $link->save();
        }

        return $link;
    }
}