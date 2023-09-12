<?php
namespace Core2\Mod\Sources\Chats\Telegram;


use Core2\Mod\Sources\Chats\Telegram\API\Contacts;
use Core2\Mod\Sources\Chats\Telegram\API\Dialogs;
use Core2\Mod\Sources\Chats\Telegram\API\Messages;
use Core2\Mod\Sources\Chats\Telegram\API\Service;
use Core2\Mod\Sources\Chats\Telegram\API\Updates;

/**
 * @property Api\Account           $account
 * @property Api\Contacts          $contacts
 * @property Api\Dialogs           $dialogs
 * @property Api\Messages          $messages
 * @property Api\Updates           $updates
 * @property Api\Service           $service
 * @property \ModSourcesController $modSources
 */
class Account extends \Common {

    private Connection $connection;
    private array      $_cache;


    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection) {
        parent::__construct();
        $this->connection = $connection;
    }


    /**
     * @param $k
     * @return null|API\Account|Contacts|Dialogs|Messages|Service|Updates
     * @throws \Exception
     */
    public function __get($k) {

        if ( ! empty($this->_cache[$k])) {
            $result = $this->_cache[$k];

        } else {
            $result = match ($k) {
                'account'  => new Api\Account($this->connection),
                'contacts' => new Api\Contacts($this->connection),
                'dialogs'  => new Api\Dialogs($this->connection),
                'messages' => new Api\Messages($this->connection),
                'updates'  => new Api\Updates($this->connection),
                'service'  => new Api\Service($this->connection),
                default    => null,
            };

            if ($result !== null) {
                $this->_cache[$k] = $result;
            } else {
                $result = parent::__get($k);
            }
        }

        return $result;
    }


    /**
     * @return int
     */
    public function getApiId(): int {

        return $this->connection->getApiId();
    }


    /**
     * Проверка активности метода
     * @param string $method
     * @return bool
     */
    public function isActiveMethod(string $method): bool {

        $api_id  = $this->connection->getApiId();
        $account = $this->modSources->dataSourcesChatsAccounts->getRowByAccountKey($api_id);

        if ( ! $account) {
            $account = $this->modSources->dataSourcesChatsAccounts->createRow([
                'api_id' => $api_id,
            ]);
            $account->save();
        }

        $inactive_methods = $account->inactive_methods ? @json_decode($account->inactive_methods, true) : [];
        $inactive_methods = is_array($inactive_methods) ? $inactive_methods : [];

        return empty($inactive_methods[$method]) || $inactive_methods[$method] < date('Y-m-d H:i:s');
    }


    /**
     * Временное выключение метода
     * @param string $method
     * @param int    $ban_seconds
     */
    public function inactiveMethod(string $method, int $ban_seconds): void {

        $api_id  = $this->connection->getApiId();
        $account = $this->modSources->dataSourcesChatsAccounts->getRowByAccountKey($api_id);

        if ( ! $account) {
            $account = $this->modSources->dataSourcesChatsAccounts->createRow([
                'api_id' => $api_id,
            ]);
            $account->save();
        }

        $inactive_methods = $account->inactive_methods ? @json_decode($account->inactive_methods, true) : [];
        $inactive_methods = is_array($inactive_methods) ? $inactive_methods : [];

        $inactive_methods[$method] = date('Y-m-d H:i:s', strtotime("+ {$ban_seconds} seconds"));

        $account->inactive_methods = json_encode($inactive_methods);
        $account->save();
    }
}