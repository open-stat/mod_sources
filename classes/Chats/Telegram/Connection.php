<?php
namespace Core2\Mod\Sources\Chats\Telegram;
use danog\MadelineProto;
use danog\MadelineProto\API;


/**
 *
 */
class Connection {

    private MadelineProto\API $madeline;
    private array             $options = [];


    /**
     * @param API   $madeline
     * @param array $options
     */
    public function __construct(MadelineProto\API $madeline, array $options = []) {

        $this->madeline = $madeline;
        $this->options  = $options;
    }


    /**
     * Получение сессии tg
     * @return API
     */
    public function getMadeline(): API {
        return $this->madeline;
    }


    /**
     * Получение телефона сессии
     * @return string|null
     */
    public function getPhone():? string {
        return $this->options['phone'] ?? null;
    }


    /**
     * Получение id сессии
     * @return string|null
     */
    public function getApiId():? string {
        return $this->options['api_id'] ?? null;
    }


    /**
     * Получение действий над аккаунтом
     * @return array
     */
    public function getActions(): array {

        $actions = $this->options['actions'] ?? '';

        $actions_explode = $actions ? explode(',', $actions) : [];
        $actions_explode = array_map('trim', $actions_explode);
        $actions_explode = array_map('strtolower', $actions_explode);

        return $actions_explode;
    }
}