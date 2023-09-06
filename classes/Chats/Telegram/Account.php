<?php
namespace Core2\Mod\Sources\Chats\Telegram;


/**
 * @property Api\Account  $account
 * @property Api\Contacts $contacts
 * @property Api\Dialogs  $dialogs
 * @property Api\Messages $messages
 * @property Api\Updates  $updates
 * @property Api\Service  $service
 */
class Account {

    private Connection $connection;
    private array      $cache;


    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection) {

        $this->connection = $connection;
    }


    /**
     * @param string $name
     * @return null|API\Account|API\Contacts|API\Dialogs|API\Messages|API\Service|API\Updates
     */
    public function __get(string $name) {

        if ( ! empty($this->cache[$name])) {
            $result = $this->cache[$name];

        } else {
            $result = match ($name) {
                'account'  => new Api\Account($this->connection),
                'contacts' => new Api\Contacts($this->connection),
                'dialogs'  => new Api\Dialogs($this->connection),
                'messages' => new Api\Messages($this->connection),
                'updates'  => new Api\Updates($this->connection),
                'service'  => new Api\Service($this->connection),
                default    => null,
            };

            $this->cache[$name] = $result;
        }

        return $result;
    }


    /**
     * @return int
     */
    public function getApiId(): int {

        return $this->connection->getApiId();
    }
}