<?php
declare(strict_types=1);
namespace Core2\Mod\Sources\Chats\Telegram;
use danog\MadelineProto\SimpleEventHandler;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Message\GroupMessage;
use danog\MadelineProto\EventHandler\Message\ChannelMessage;



/**
 * Event handler class.
 * All properties returned by __sleep are automatically stored in the database.
 */
class Handler extends SimpleEventHandler {
    /**
     * @var int|string Username or ID of bot admin
     */
    const ADMIN = "@me"; // !!! Change this to your username !!!


    /**
     * Get peer(s) where to report errors.
     * @return int|string|array
     */
    public function getReportPeers() {
        return [self::ADMIN];
    }


    /**
     * Handle incoming updates from users, chats and channels.
     */
    #[Handler]
    public function handleMessage(ChannelMessage $message): void {

        // In this example code, send the "This userbot is powered by MadelineProto!" message only once per chat.
        // Ignore all further messages coming from this chat.
        if ( ! isset($this->notifiedChats[$message->chatId])) {
            $this->notifiedChats[$message->chatId] = true;

//            $message->reply(
//                message: "This userbot is powered by [MadelineProto](https://t.me/MadelineProto)!",
//                parseMode: ParseMode::MARKDOWN
//            );
        }
    }


    /**
     * Handle updates from users.
     *
     * 100+ other types of onUpdate... method types are available, see https://docs.madelineproto.xyz/API_docs/types/Update.html for the full list.
     * You can also use onAny to catch all update types (only for debugging)
     * A special onUpdateCustomEvent method can also be defined, to send messages to the event handler from an API instance, using the sendCustomEvent method.
     *
     * @param array $update Update
     */
    public function onUpdateNewMessage(array $update): void {

        if ($update['message']['_'] === 'messageEmpty') {
            return;
        }

        $this->logger($update);

        // Chat ID
        $id = $this->getId($update);

        // Sender ID, not always present
        $from_id = isset($update['message']['from_id'])
            ? $this->getId($update['message']['from_id'])
            : null;


        // In this example code, send the "This userbot is powered by MadelineProto!" message only once per chat.
        // Ignore all further messages coming from this chat.
        if ( ! isset($this->notifiedChats[$id])) {
            $this->notifiedChats[$id] = true;

            $this->messages->sendMessage(
                peer: $update,
                message: "This userbot is powered by [MadelineProto](https://t.me/MadelineProto)!",
                reply_to_msg_id: $update['message']['id'] ?? null,
                parse_mode: 'Markdown'
            );
        }
    }
}

