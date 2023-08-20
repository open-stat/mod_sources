<?php
namespace Core2\Mod\Sources\Index\Telegram;

use danog\MadelineProto\Exception;

/**
 *
 */
class Updates extends Common {


    /**
     * Получение обновлений
     * @param int $offset
     * @param int $limit
     * @param int $timeout
     * @return array
     * @throws Exception
     */
    public function get(int $offset, int $limit = 1000, int $timeout = 5): array {

        return $this->getMadeline()->getUpdates(['offset' => $offset, 'limit' => $limit, 'timeout' => $timeout]);
    }
}