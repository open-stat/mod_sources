<?php
namespace Core2\Mod\Sources\Chats\Telegram\Api;
use Core2\Mod\Sources\Chats\Telegram\Connection;
use danog\MadelineProto\Exception;

/**
 *
 */
class Contacts {


    private Connection $connection;


    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection) {

        $this->connection = $connection;
    }


    /**
     * Получение списка контактов
     * @return array
     * @throws Exception
     */
    public function getContacts(): array {

        $madeline = $this->connection->getMadeline();

        $contacts = $madeline->contacts->getContacts();

        return (array)$contacts;
    }


    /**
     * Получение списка контактов
     * @return array
     * @throws Exception
     */
    public function getContactsId(): array {

        $madeline = $this->connection->getMadeline();

        $contacts = $madeline->contacts->getContactIDs();

        return (array)$contacts;
    }


    /**
     * Добавление контакта по id
     * @param string $user_id
     * @param string $first_name
     * @param string $last_name
     * @return array
     * @throws Exception
     */
    public function addContactById(string $user_id, string $first_name, string $last_name): array {

        $madeline = $this->connection->getMadeline();

        $update = $madeline->contacts->addContact(...[
            'id'         => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
        ]);

        return (array)$update;
    }


    /**
     * Добавление контакта по id и access_hash
     * @param string $user_id
     * @param string $access_hash
     * @param string $first_name
     * @param string $last_name
     * @return array
     */
    public function addContactByIdAccess(string $user_id, string $access_hash, string $first_name, string $last_name): array {

        $madeline = $this->connection->getMadeline();

        $update = $madeline->contacts->addContact(...[
            'id'         => [
                'user_id'     => $user_id,
                'access_hash' => $access_hash,
            ],
            'first_name' => $first_name,
            'last_name'  => $last_name,
        ]);

        return (array)$update;
    }


    /**
     * Добавление контакта по номеру телефона
     * @param string $phone
     * @param string $first_name
     * @param string $last_name
     * @return array
     */
    public function addContactByPhone(string $phone, string $first_name, string $last_name): array {

        $madeline = $this->connection->getMadeline();

        $update = $madeline->contacts->importContacts(...[
            'contacts' => [
                [
                    '_'          => 'inputPhoneContact',
                    'client_id'  => crc32($phone),
                    'phone'      => $phone,
                    'first_name' => $first_name,
                    'last_name'  => $last_name,
                ]
            ],
        ]);

        return (array)$update;
    }


    /**
     * Добавление контакта по номеру телефона
     * @return array
     */
    public function getSaved(): array {

        $madeline = $this->connection->getMadeline();

        $update = $madeline->contacts->getSaved();

        return (array)$update;
    }


    /**
     * Добавление контакта по номеру телефона
     * @return array
     */
    public function getContactsStatuses(): array {

        $madeline = $this->connection->getMadeline();

        $update = $madeline->contacts->getStatuses();

        return (array)$update;
    }
}