<?php
namespace Core2\Mod\Sources\Chats\TgParser;


/**
 * @property \ModSourcesController $modSources
 */
class Update extends Common {



    /**
     * Изменился счетчик просмотров сообщения в канале
     * @param array $update
     * @return void
     */
    public function updateChannelMessageViews(array $update): void {

        if ( ! empty($update['channel_id']) &&
             ! empty($update['id']) &&
             ! empty($update['views'])
        ) {

            $chat = $this->modSources->dataSourcesChats->getRowByTgChannelPeerId("-100{$update['channel_id']}");

            if (empty($chat)) {
                return;
            }

            $message = $this->modSources->dataSourcesChatsMessages->getRowByChatMessengerId($chat->id, $update['id']);

            if ($message && $message->viewed_count < $update['views']) {
                $message->viewed_count = $update['views'];
                $message->save();
            }
        }
    }


    /**
     * Изменился счетчик пересылки сообщения в канале
     * @param array $update
     * @return void
     */
    public function updateChannelMessageForwards(array $update): void {

        if ( ! empty($update['channel_id']) &&
             ! empty($update['id']) &&
             ! empty($update['forwards'])
        ) {

            $chat = $this->modSources->dataSourcesChats->getRowByTgChannelPeerId("-100{$update['channel_id']}");

            if (empty($chat)) {
                return;
            }

            $message = $this->modSources->dataSourcesChatsMessages->getRowByChatMessengerId($chat->id, $update['id']);

            if ($message && $message->repost_count < $update['forwards']) {
                $message->repost_count = $update['forwards'];
                $message->save();
            }
        }
    }


    /**
     * Изменяет имя, фамилию и имя пользователя
     * @param array $update
     * @return void
     */
    public function updateUserName(array $update): void {

        if ( ! empty($update['user_id'])) {

            $this->modSources->dataSourcesChatsUsers->saveUser($update['user_id'], 'user', [
                'username'     => ! empty($update['username']) ? $update['username'] : null,
                'first_name'   => ! empty($update['first_name']) ? $update['first_name'] : null,
                'last_name'    => ! empty($update['last_name']) ? $update['last_name'] : null,
                'phone_number' => ! empty($update['phone']) ? $update['phone'] : null,
            ]);
        }
    }


    /**
     * Реакции на новые сообщения » доступны
     * @param array $update
     * @return void
     */
    public function updateMessageReactions(array $update): void {

        if ( ! empty($update['channel_id']) &&
             ! empty($update['msg_id']) &&
             ! empty($update['reactions']) &&
             ! empty($update['reactions']['results']) &&
             ! empty($update['peer']) &&
             ! empty($update['peer']['_'])
        ) {

            if ($update['peer']['_'] == 'peerChannel' && ! empty($update['peer']['channel_id'])) {
                $chat = $this->modSources->dataSourcesChats->saveChatId("-100{$update['peer']['channel_id']}");

            } elseif ($update['peer']['_'] == 'peerChat' && ! empty($update['peer']['chat_id'])) {
                $chat = $this->modSources->dataSourcesChats->saveChatId("-100{$update['peer']['chat_id']}");
            }

            if (empty($chat)) {
                return;
            }

            $message = $this->modSources->dataSourcesChatsMessages->getRowByChatMessengerId($chat->id, $update['msg_id']);

            if (empty($message)) {
                return;
            }

            foreach ($update['reactions']['results'] as $reaction) {

                if ( ! empty($reaction['reaction']) &&
                     ! empty($reaction['reaction']['emoticon']) &&
                     isset($reaction['count'])
                ) {
                    $source_reaction = $this->modSources->dataSourcesChatsReactions->saveReaction($reaction['reaction']['emoticon']);

                    $this->modSources->dataSourcesChatsMessagesReactions
                        ->saveReaction($message->id, $source_reaction->id, $reaction['count']);
                }
            }
        }
    }


    /**
     * Новое сообщение отправлено в канал/супергруппу.
     * @param array $update
     * @return void
     */
    public function updateNewChannelMessage(array $update): void {

        if ( ! empty($update['message']) &&
             ! empty($update['message']['id']) &&
             ! empty($update['message']['peer_id']) &&
             ! empty($update['message']['peer_id']['channel_id'])
        ) {
            $this->saveMessage($update['message']);
        }
    }


    /**
     * Сообщение было отредактировано в канале/супергруппе.
     * @param array $update
     * @return void
     */
    public function updateEditChannelMessage(array $update): void {

        if ( ! empty($update['message']) &&
             ! empty($update['message']['id']) &&
             ! empty($update['message']['peer_id']) &&
             ! empty($update['message']['peer_id']['channel_id'])
        ) {
            $this->saveMessage($update['message'], true);
        }
    }
}