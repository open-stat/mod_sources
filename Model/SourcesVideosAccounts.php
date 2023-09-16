<?php


/**
 *
 */
class SourcesVideosAccounts extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos_accounts';

    /**
     * @param string $account_key
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByAccountKey(string $account_key):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()->where("account_key = ?", $account_key)
        );
    }
}