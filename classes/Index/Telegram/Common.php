<?php
namespace Core2\Mod\Sources\Index\Telegram;
use danog\MadelineProto;
use danog\MadelineProto\Exception;


/**
 *
 */
abstract class Common extends \Common {


    private MadelineProto\Settings $settings;
    private                        $session_file = '';

    private static MadelineProto\API $madeline;


    /**
     * @throws \Zend_Config_Exception
     * @throws \Exception
     */
    public function __construct() {

        parent::__construct();

        $config       = $this->getModuleConfig('sources');
        $api_id       = $config?->tg?->api_id;
        $api_hash     = $config?->tg?->api_hash;
        $session_file = "{$this->config->temp}/account.madeline";
        $log_file     = '';


        if ($config?->tg?->log_file) {
            $log_file = mb_substr($config?->tg?->log_file, 0, 1) != '/'
                ? realpath(DOC_ROOT . dirname($this->config?->log?->path)) . "/{$config?->tg?->log_file}"
                : $config?->tg?->log_file;
        }

        if ($config?->tg?->session_file) {
            $session_file = mb_substr($config->tg->session_file, 0, 1) != '/'
                ? realpath(DOC_ROOT . dirname("{$config->tg->session_file}")) . '/' . basename("{$config->tg->session_file}")
                : realpath($config->tg->session_file);
        }

        if (empty($api_id)) {
            throw new \Exception('В конфигурации не задан параметр tg.api_id');
        }

        if (empty($api_hash)) {
            throw new \Exception('В конфигурации не задан параметр tg.api_hash');
        }

        if ( ! file_exists(dirname($session_file))) {
            throw new \Exception(sprintf('Указанная директория не существует: %s', dirname($session_file)));
        }

        if ( ! is_writeable(dirname($session_file))) {
            throw new \Exception(sprintf('В указанной директории запрещен доступ на запись: %s', dirname($session_file)));
        }

        $this->settings = new MadelineProto\Settings();

        $this->settings->setAppInfo(
            (new MadelineProto\Settings\AppInfo)
                ->setApiId((int)$api_id)
                ->setApiHash($api_hash)
        );
        $this->settings->setLogger(
            (new MadelineProto\Settings\Logger())
                ->setType(MadelineProto\Logger::ECHO_LOGGER)
        );

        if ( ! empty($log_file)) {
            $this->settings->setLogger(
                (new MadelineProto\Settings\Logger())
                    ->setType(MadelineProto\Logger::LOGGER_FILE)
                    ->setLevel(MadelineProto\Logger::WARNING)
                    ->setExtra($log_file)
            );
        }

        $this->session_file = $session_file;
    }


    /**
     * Остановка действующего IPС процесса для текущего пользователя.
     * Процесс запускается автоматически и служит для обслуживания постоянного соединения с телеграм
     * @return void
     * @throws Exception
     */
    public function stopServer(): void {

        $madeline = $this->getMadeline();
        $madeline->stop();
    }


    /**
     * Остановка действующего IPС процесса для текущего пользователя.
     * Процесс запускается автоматически и служит для обслуживания постоянного соединения с телеграм
     * @return void
     * @throws Exception
     */
    public function restartServer(): void {

        $madeline = $this->getMadeline();
        $madeline->restart();
    }


    /**
     * @return MadelineProto\API
     * @throws Exception
     */
    protected function getMadeline(): MadelineProto\API {

        if (empty(self::$madeline)) {
            $_SERVER['SERVER_NAME'] = '';
            self::$madeline = new MadelineProto\API($this->session_file, $this->settings);
            //self::$madeline->async(true);
        }

        return self::$madeline;
    }
}