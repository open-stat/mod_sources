<?php
namespace Core2\Mod\Sources\Chats\Telegram\Api;
use Core2\Mod\Sources\Chats\Telegram\Connection;
use danog\MadelineProto\Exception;


/**
 *
 */
class Service {

    private Connection $connection;


    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection) {

        $this->connection = $connection;
    }


    /**
     * Запуск действующего IPС процесса для текущего пользователя.
     * Процесс служит для обслуживания постоянного соединения с телеграм
     * @return void
     * @throws Exception
     */
    public function start(): void {

        $this->connection->getMadeline()->start();
    }


    /**
     * Остановка действующего IPС процесса для текущего пользователя.
     * @param bool $force
     * @return void
     */
    public function stop(bool $force = false): void {

        if ($force) {
            exec("ps -ef | grep 'madeline-ipc' | grep -v grep | awk '{print $2}' | xargs -r kill -9");
            exec("ps -ef | grep 'MadelineProto' | grep -v grep | awk '{print $2}' | xargs -r kill -9");
        } else {
            $this->connection->getMadeline()->stop();
        }
    }


    /**
     * Остановка действующего IPС процесса для текущего пользователя.
     * Процесс запускается автоматически и служит для обслуживания постоянного соединения с телеграм
     * @return void
     * @throws Exception
     */
    public function restart(): void {

        $this->connection->getMadeline()->restart();
    }
}