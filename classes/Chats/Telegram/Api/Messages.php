<?php
namespace Core2\Mod\Sources\Chats\Telegram\Api;
use Core2\Mod\Sources\Chats\Telegram\Connection;
use danog\MadelineProto\Exception;


/**
 *
 */
class Messages {

    private Connection $connection;


    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection) {

        $this->connection = $connection;
    }


    /**
     * Получение истории сообщений по указанному каналу, группе, пользователю
     * @param string $peer_name
     * @param array  $options
     * @return array
     * @throws Exception
     */
    public function getHistory(string $peer_name, array $options = []): array {

        $limit = $options['limit'] ?? 100;
        $limit = $limit > 100 || $limit <= 0 ? 100 : $limit;

        $offset_id = $options['offset_id'] ?? 0;
        $offset_id = $offset_id < 0 ? 0 : $offset_id;

        $min_id = $options['min_id'] ?? 0;
        $min_id = $min_id < 0 ? 0 : $min_id;

        return $this->connection->getMadeline()->messages->getHistory(
            peer: "@{$peer_name}",
            offset_id: $offset_id,
            offset_date: 0,
            add_offset: 0,
            limit: $limit,
            max_id: 0,
            min_id: $min_id,
            hash: 0,
        );
    }
}