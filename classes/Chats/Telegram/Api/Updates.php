<?php
namespace Core2\Mod\Sources\Chats\Telegram\Api;
use Core2\Mod\Sources\Chats\Telegram\Connection;


/**
 *
 */
class Updates {

    private Connection $connection;


    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection) {

        $this->connection = $connection;
    }


    /**
     * Получение обновлений
     * @param int $offset
     * @param int $limit
     * @param int $timeout
     * @return array
     */
    public function get(int $offset, int $limit = 1000, int $timeout = 5): array {

        return $this->connection->getMadeline()->getUpdates(['offset' => $offset, 'limit' => $limit, 'timeout' => $timeout]);
    }
}