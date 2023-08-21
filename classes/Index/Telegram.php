<?php
namespace Core2\Mod\Sources\Index;
use Core2\Mod\Sources\Index\Telegram\Account;
use Core2\Mod\Sources\Index\Telegram\Contacts;
use Core2\Mod\Sources\Index\Telegram\Dialogs;
use Core2\Mod\Sources\Index\Telegram\Messages;
use Core2\Mod\Sources\Index\Telegram\Updates;


/**
 * @property Account  account
 * @property Contacts contacts
 * @property Dialogs  dialogs
 * @property Messages messages
 * @property Updates updates
 */
class Telegram {

    private static array $cache = [];


    /**
     * @param string $name
     * @return Account|Contacts|Dialogs|Messages|mixed|null
     */
    public function __get(string $name) {

        if ( ! empty(self::$cache[$name])) {
            $result = self::$cache[$name];

        } else {
            $result = match ($name) {
                'account'  => new Account(),
                'contacts' => new Contacts(),
                'dialogs'  => new Dialogs(),
                'messages' => new Messages(),
                'updates'  => new Updates(),
                default    => null,
            };
        }

        return $result;
    }
}