<?php
namespace Core2\Mod\Sources\Chats;
use Core2\Mod\Sources\Chats\Telegram\Account;
use Core2\Mod\Sources\Chats\Telegram\Connection;
use Core2\Mod\Sources\Chats\Telegram\Connections;
use danog\MadelineProto\Exception;


/**
 *
 */
class Telegram extends Connections {

    /**
     * Получение всех аккаунтов
     * @param array|null $filter_actions
     * @return Account[]
     */
    public function getAccounts(array $filter_actions = null): array {

        $connections = $this->getConnections();
        $accounts    = [];

        foreach ($connections as $connection) {
            if ($connection instanceof Connection) {
                if ( ! empty($filter_actions)) {
                    $connection_actions = $connection->getActions();
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

                $accounts[] = new Account($connection);
            }
        }

        return $accounts;
    }


    /**
     * Получение аккаунта по id
     * @param int $app_id
     * @return Account|null
     */
    public function getAccountByApiId(int $app_id):? Account {

        $connection = $this->getConnectionByApiId($app_id);

        if (empty($connection)) {
            return null;
        }

        return new Account($connection);
    }


    /**
     * Получение аккаунта по телефону
     * @param string $phone
     * @return Account|null
     */
    public function getAccountByPhone(string $phone):? Account {

        $connection = $this->getConnectionByPhone($phone);

        if (empty($connection)) {
            return null;
        }

        return new Account($connection);
    }


    /**
     * Запуск сервиса
     * @return void
     * @throws Exception
     */
    public function start(): void {

        $account = current($this->getAccounts());

        if ($account) {
            $account->service->start();
        }
    }


    /**
     * Остановка сервиса
     * @return void
     */
    public function stop(): void {

        $account = current($this->getAccounts());

        if ($account) {
            $account->service->stop();
        }
    }


    /**
     * Перезапуск сервиса
     * @return void
     * @throws Exception
     */
    public function restart(): void {

        $account = current($this->getAccounts());

        if ($account) {
            $account->service->restart();
        }
    }
}