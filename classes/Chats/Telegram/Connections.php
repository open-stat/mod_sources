<?php
namespace Core2\Mod\Sources\Chats\Telegram;
use danog\MadelineProto;


/**
 *
 */
class Connections extends \Common {

    private string $log_dir     = '';
    private string $session_dir = '';
    private array  $accounts    = [];

    private static array $madeline_accounts;


    /**
     * @throws \Zend_Config_Exception
     * @throws \Exception
     */
    public function __construct() {

        parent::__construct();

        $config = $this->getModuleConfig('sources');

        if ($this->config?->log?->path) {
            $this->log_dir = mb_substr($this->config->log->path, 0, 1) != '/'
                ? realpath(DOC_ROOT . dirname($this->config->log->path))
                : realpath(dirname($this->config->log->path));
        }

        $this->session_dir = mb_substr((string)$config?->tg?->session_dir, 0, 1) != '/'
            ? realpath(DOC_ROOT . "{$config?->tg?->session_dir}")
            : realpath((string)$config?->tg?->session_dir);

        $this->accounts = $config?->tg?->accounts?->toArray() ?? [];

        if (empty($this->log_dir)) {
            throw new \Exception('В конфигурации системы не задан параметр log.path');
        }

        if (empty($this->session_dir)) {
            throw new \Exception('В конфигурации модуля не задан параметр tg.session_dir');
        }

        if ( ! is_dir($this->session_dir)) {
            throw new \Exception(sprintf('Указанная директория не существует: %s', $this->session_dir));
        }

        if ( ! is_writeable($this->session_dir)) {
            throw new \Exception(sprintf('В указанной директории запрещен доступ на запись: %s', $this->session_dir));
        }
    }


    /**
     * Получение всех подключений
     * @return array
     */
    protected function getConnections(): array {

        $this->initMadeline();

        return array_column(self::$madeline_accounts, 'connection');
    }


    /**
     * Получение подключения по id
     * @param int $api_id
     * @return Connection|null
     */
    protected function getConnectionByApiId(int $api_id):? Connection {

        $this->initMadeline();

        $madeline_account = self::$madeline_accounts[$api_id] ?? null;

        return $madeline_account['connection'] ?? null;
    }


    /**
     * Получение подключения по телефону
     * @param string $phone
     * @return Connection|null
     */
    protected function getConnectionByPhone(string $phone):? Connection {

        $this->initMadeline();

        $connection = null;

        foreach (self::$madeline_accounts as $madeline_account) {
            if ($madeline_account['phone'] == $phone) {
                $connection = $madeline_account['connection'];
                break;
            }
        }

        return $connection;
    }


    /**
     * Инициализация подключений
     * @return void
     */
    private function initMadeline(): void {

        if (empty(self::$madeline_accounts) && ! empty($this->accounts)) {
            // для записи логов в эту папку
            if ( ! empty($this->log_dir)) {
                chdir($this->log_dir);
            }

            self::$madeline_accounts = [];

            $request_uri = $_SERVER['REQUEST_URI'] ?? null;
            unset($_SERVER['REQUEST_URI']);


            foreach ($this->accounts as $account) {
                if (empty($account['api_id']) || empty($account['api_hash'])) {
                    continue;
                }

                try {
                    $session_file = "{$this->session_dir}/{$account['api_id']}.madeline";
                    $settings     = $this->getMadelineSettings((int)$account['api_id'], $account['api_hash']);


                    // Медленный режим
                    // \danog\MadelineProto\Magic::$isIpcWorker = false;

                    $madeline = new MadelineProto\API($session_file, $settings);
                    $options = [
                        'api_id'  => $account['api_id'],
                        'phone'   => $account['phone'] ?? null,
                        'actions' => $account['actions'] ?? null,
                    ];

                    self::$madeline_accounts[$account['api_id']]['phone']      = $account['phone'] ?? null;
                    self::$madeline_accounts[$account['api_id']]['connection'] = new Connection($madeline, $options);

                } catch (\Exception $e) {
                    echo "Api_id: {$account['api_id']}" .PHP_EOL;
                    echo $e->getMessage() .PHP_EOL;
                    echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;
                }
            }

            if ( ! empty($request_uri)) {
                $_SERVER['REQUEST_URI'] = $request_uri;
            }
        }
    }


    /**
     * @param int    $api_id
     * @param string $api_hash
     * @return MadelineProto\Settings
     * @throws \Zend_Config_Exception
     */
    private function getMadelineSettings(int $api_id, string $api_hash): MadelineProto\Settings {

        $config   = $this->getModuleConfig('sources');
        $log_file = realpath(DOC_ROOT . dirname($this->config?->log?->path)) . "/tg_{$api_id}.log";

        $settings = new MadelineProto\Settings();
        $settings->setAppInfo(
            (new MadelineProto\Settings\AppInfo)
                ->setApiId($api_id)
                ->setApiHash($api_hash)
        );
        $settings->setLogger(
            (new MadelineProto\Settings\Logger())
                ->setType(MadelineProto\Logger::LOGGER_FILE)
                ->setLevel(MadelineProto\Logger::LEVEL_WARNING)
                ->setExtra($log_file)
        );
        $settings->setSerialization(
            (new MadelineProto\Settings\Serialization())
                ->setInterval(300)
        );
        $settings->setPeer(
            (new MadelineProto\Settings\Peer())
                ->setCacheAllPeersOnStartup(true)
        );

        if ($config?->tg?->db?->uri &&
            $config?->tg?->db?->database &&
            $config?->tg?->db?->user &&
            $config?->tg?->db?->pass
        ) {
            $settings->setDb(
                (new MadelineProto\Settings\Database\Mysql())
                    ->setUri($config->tg->db->uri)
                    ->setDatabase($config->tg->db->database)
                    ->setUsername($config->tg->db->user)
                    ->setPassword($config->tg->db->pass)
            );
        }

        return $settings;
    }
}