<?php


/**
 *
 */
class SourcesChatsAccounts extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_accounts';

    /**
     * @param int $account_key
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByAccountKey(int $account_key):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()->where("account_key = ?", $account_key)
        );
    }
}