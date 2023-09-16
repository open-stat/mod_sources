<?php
namespace Core2\Mod\Sources\Video;
use Core2\Mod\Sources\Video\YouTube\Account;


/**
 *
 */
class YouTube extends \Common {

    private static array $accounts = [];

    /**
     * Получение всех аккаунтов
     * @param array|null $filter_actions
     * @return Account[]
     * @throws \Zend_Config_Exception
     */
    public function getAccounts(array $filter_actions = null): array {

        $this->initAccounts();

        $accounts = [];

        foreach (self::$accounts as $account) {

            if ( ! empty($filter_actions)) {
                $connection_actions = $account['account']->getActions();
                $isset_all_actions  = true;

                foreach ($filter_actions as $filter_action) {
                    if ( ! in_array($filter_action, $connection_actions)) {
                        $isset_all_actions = false;
                        break;
                    }
                }

                if ( ! $isset_all_actions) {
                    continue;
                }
            }

            $accounts[] = $account['account'];
        }

        return $accounts;
    }


    /**
     * Получение аккаунта по номеру
     * @param int $nmbr
     * @return Account|null
     * @throws \Zend_Config_Exception
     */
    public function getAccountByNmbr(int $nmbr):? Account {

        $this->initAccounts();

        $account = self::$accounts[$nmbr] ?? null;

        return $account ? $account['account'] : null;
    }


    /**
     * @return void
     * @throws \Zend_Config_Exception
     * @throws \Exception
     */
    private function initAccounts(): void {

        if ( ! empty(self::$accounts)) {
            return;
        }


        $config = $this->getModuleConfig('sources');

        if (empty($config?->yt?->accounts)) {
            return;
        }

        foreach ($config?->yt?->accounts as $nmbr => $conf_account) {
            if (empty($conf_account?->apikey) || ! is_string($conf_account?->apikey)) {
                continue;
            }

            $yt_connection = new \Alaouy\Youtube\Youtube($conf_account?->apikey);

            $account = new Account($yt_connection, [
                'nmbr'    => $nmbr,
                'actions' => $conf_account?->actions,
            ]);

            self::$accounts[$nmbr] = [
                'nmbr'    => $nmbr,
                'account' => $account
            ];
        }
    }
}