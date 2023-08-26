<?php


/**
 *
 */
class SourcesChatsUsers extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_users';

    /**
     * @param string $id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByMessengerId(string $id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()->where("messenger_Id = ?", $id)
        );
    }


    /**
     * @param string $phone
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByPhoneNumber(string $phone):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()->where("phone_number = ?", $phone)
        );
    }


    /**
     * @param int        $user_id
     * @param string     $type
     * @param array|null $options
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveUser(int $user_id, string $type = 'user', array $options = null): \Zend_Db_Table_Row_Abstract {

        $source_user = $this->getRowByMessengerId($user_id);

        if (empty($source_user)) {
            $source_user = $this->createRow([
                'messenger_id' => $user_id,
                'type'         => $type,
                'username'     => $options['username'] ?? null,
                'phone_number' => $options['phone_number'] ?? null,
                'first_name'   => $options['first_name'] ?? null,
                'last_name'    => $options['last_name'] ?? null,
            ]);
            $source_user->save();

        } else {
            $is_save = false;

            if (empty($source_user->username) && ! empty($options['username'])) {
                $source_user->username = $options['username'];
                $is_save = true;
            }
            if (empty($source_user->phone_number) && ! empty($options['phone_number'])) {
                $source_user->phone_number = $options['phone_number'];
                $is_save = true;
            }
            if (empty($source_user->first_name) && ! empty($options['first_name'])) {
                $source_user->first_name = $options['first_name'];
                $is_save = true;
            }
            if (empty($source_user->last_name) && ! empty($options['last_name'])) {
                $source_user->last_name = $options['last_name'];
                $is_save = true;
            }

            if ($is_save) {
                $source_user->save();
            }
        }

        return $source_user;
    }
}