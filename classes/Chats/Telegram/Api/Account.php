<?php
namespace Core2\Mod\Sources\Chats\Telegram\Api;
use Core2\Mod\Sources\Chats\Telegram\Connection;
use danog\MadelineProto\Exception;

/**
 *
 */
class Account {

    private Connection $connection;


    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection) {

        $this->connection = $connection;
    }


    /**
     * Авторизация в TG по телефону
     * @return bool
     * @throws \Zend_Config_Exception
     * @throws \Exception
     */
    public function loginPhone(): bool {

        $phone = $this->connection->getPhone();

        if (empty($phone)) {
            $api_id = $this->connection->getApiId();
            throw new \Exception("В конфигурации модуля не задан телефон для api_id = {$api_id}");
        }

        $authorization = $this->connection->getMadeline()->phoneLogin($phone, 0);

        return $authorization['_'] == 'auth.sentCode';
    }


    /**
     * Подтверждение входа по коду (способ 1)
     * @param string $code
     * @return array
     * @throws \Exception
     */
    public function completePhone(string $code): array {

        $auth = $this->connection->getMadeline()->completePhoneLogin($code);

        if ($auth['_'] === 'account.noPassword') {
            throw new \Exception('2FA is enabled but no password is set!');
        }

        return (array)$auth;
    }


    /**
     * Подтверждение входа по коду и паролю (способ 2)
     * @param string $code
     * @param string $password
     * @return array
     * @throws Exception
     */
    public function complete2faLogin(string $code, string $password): array {

        $madeline = $this->connection->getMadeline();
        $madeline->completePhoneLogin($code);
        $auth = $madeline->complete2faLogin($password);

        return (array)$auth;
    }


    /**
     * Начало работы с
     * @param string $bot_id
     * @param string $bot_username
     * @return array
     * @throws Exception
     */
    public function startBot(string $bot_id, string $bot_username): array {

        $madeline = $this->connection->getMadeline();

        $result = $madeline->contacts->resolveUsername(...[
            'username' => $bot_username
        ]);

        $access_hash = '';

        if ( ! empty($result['users']) &&
            ! empty($result['users']['0']) &&
            ! empty($result['users']['0']['access_hash'])
        ) {
            $access_hash = $result['users']['0']['access_hash'];
        }


        $update = $madeline->messages->startBot(...[
            'bot' => [
                '_'           => 'inputUser',
                'user_id'     => $bot_id,
                'access_hash' => $access_hash,
            ],
            'peer'        => [
                '_' => 'inputPeerSelf',
            ],
            'random_id'   => abs(crc32(time())),
            'start_param' => md5(time()),
        ]);

        return (array)$update;
    }


    /**
     * Получение данных о текущем пользователе
     * @return array
     */
    public function getSelf(): array {

        $user_self = $this->connection->getMadeline()->getSelf();

        return (array)$user_self;
    }
}