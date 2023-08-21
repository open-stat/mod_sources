<?php
namespace Core2\Mod\Sources\Index;

/**
 * @property \ModSourcesController $modSources
 */
class TgParser extends \Common {


    private static array $chats    = [];
    private static array $users    = [];
    private static array $reaction = [];
    private static array $links    = [];
    private static array $hashtags = [];


    /**
     * Обработка истории
     * @param int $limit
     * @return void
     */
    public function processHistory(int $limit = 100): void {

        $history = $this->modSources->dataSourcesChatsContent->fetchAll(
            $this->modSources->dataSourcesChatsContent->select()
                ->where("type = 'tg_history_channel'")
                ->where("is_parsed_sw = 'N'")
                ->order('id ASC')
                ->limit($limit)
        );

        foreach ($history as $item) {

            $this->db->beginTransaction();
            try {
                $content = json_decode($item->content, true);


                if ( ! empty($content['users'])) {
                    foreach ($content['users'] as $user) {
                        try {
                            if ( ! empty($user['_']) &&
                                ! empty($user['id']) &&
                                $user['_'] == 'user'
                            ) {
                                $type = empty($user['bot']) ? 'user' : 'bot';
                                $this->modSources->dataSourcesChatsUsers->saveUser($user['id'], $type, [
                                    'first_name'   => ! empty($user['first_name']) ? $user['first_name'] : null,
                                    'last_name'    => ! empty($user['last_name']) ? $user['last_name'] : null,
                                    'phone_number' => ! empty($user['phone']) ? $user['phone'] : null,
                                ]);
                            }

                        } catch (\Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                            echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;
                        }
                    }
                }

                if ( ! empty($content['chats'])) {
                    foreach ($content['chats'] as $chat) {
                        if ( ! empty($chat['_']) &&
                             ! empty($chat['id']) &&
                            in_array($chat['_'], ['channel', 'group'])
                        ) {
                            $this->modSources->dataSourcesChats->saveChatId("-100{$chat['id']}", $chat['_'], 'tg', [
                                'peer_name' => ! empty($chat['username']) ? $chat['username'] : null,
                                'title'     => ! empty($chat['title']) ? $chat['title'] : null,
                            ]);
                        }
                    }
                }

                if ( ! empty($content['messages'])) {
                    foreach ($content['messages'] as $message) {
                        if ( ! empty($message['_']) &&
                             ! empty($message['id']) &&
                             ! empty($message['peer_id']) &&
                            ( ! empty($message['peer_id']['channel_id']) || ! empty($message['peer_id']['group_id'])) &&
                            $message['_'] === 'message'
                        ) {

                            $type    = ! empty($message['peer_id']['_']) && $message['peer_id']['_'] === 'peerChannel' ? 'channel' : 'group';
                            $chat_id = ! empty($message['peer_id']['_']) && $message['peer_id']['_'] === 'peerChannel'
                                ? $message['peer_id']['channel_id']
                                : $message['peer_id']['group_id'];

                            $source_chat = $this->getChat($chat_id, $type);

                            $media_type_raw = ! empty($message['media']) && ! empty($message['media']['_']) ? $message['media']['_'] : null;
                            $media_type     = match ($media_type_raw) {
                                'messageMediaDocument' => 'document',
                                'messageMediaPhoto'    => 'photo',
                                'messageMediaVideo'    => 'video',
                                'messageMediaAudio'    => 'audio',
                                'messageMediaWebPage'  => 'webpage',
                                null                   => null,
                                default                => 'other',
                            };

                            $fwd_chat_id = ! empty($message['fwd_from']) &&
                                           ! empty($message['from_id']['from_id']) &&
                                           ! empty($message['from_id']['from_id']['channel_id'])
                                ? $message['fwd_from']['from_id']['channel_id']
                                : null;

                            $fwd_message_id = ! empty($message['fwd_from']) && ! empty($message['fwd_from']['channel_post'])
                                ? $message['fwd_from']['channel_post']
                                : null;

                            $source_message = $this->modSources->dataSourcesChatsMessages->saveMessage($source_chat->id, $message['id'], [
                                'media_type'             => $media_type,
                                'content'                => ! empty($message['message']) ? $message['message'] : null,
                                'fwd_chat_id'            => $fwd_chat_id,
                                'fwd_message_id'         => $fwd_message_id,
                                'group_value'            => ! empty($message['grouped_id']) ? $message['grouped_id'] : null,
                                'date_messenger_created' => ! empty($message['date']) ? date('Y-m-d H:i:s', $message['date']) : null,
                                'date_messenger_edit'    => ! empty($message['edit_date']) ? date('Y-m-d H:i:s', $message['edit_date']) : null,
                                'comments_count'         => ! empty($message['replies']) && ! empty($message['replies']['replies']) ? (int)$message['replies']['replies'] : 0,
                                'viewed_count'           => ! empty($message['views']) ? (int)$message['views'] : 0,
                                'repost_count'           => ! empty($message['forwards']) ? (int)$message['forwards'] : 0,
                            ]);

                            // Файлы
                            if ( ! empty($message['media'])) {
                                $this->modSources->dataSourcesChatsMessagesFiles->saveFileEmpty($source_message->id, $message['media']);
                            }

                            // Ссылки
                            if ( ! empty($message['entities'])) {
                                foreach ($message['entities'] as $entity) {

                                    if ( ! empty($entity['_']) &&
                                         ! empty($entity['url']) &&
                                        $entity['_'] === 'messageEntityTextUrl' &&
                                        is_string($entity['url'])
                                    ) {
                                        $source_link = $this->getLink($entity['url']);
                                        $this->modSources->dataSourcesChatsMessagesLinks->saveLink($source_message->id, $source_link->id, [
                                            'offset' => $entity['offset'] ?? null,
                                            'length' => $entity['length'] ?? null,
                                        ]);
                                    }
                                }
                            }

                            if ( ! empty($message['media']) &&
                                 ! empty($message['media']['_']) &&
                                 ! empty($message['media']['webpage']) &&
                                 ! empty($message['media']['webpage']['url']) &&
                                $message['media']['_'] === 'messageMediaWebPage'
                            ) {
                                $type = null;

                                if ( ! empty($message['media']['webpage']['type'])) {
                                    switch ($message['media']['webpage']['type']) {
                                        case 'photo':            $type = 'photo'; break;
                                        case 'telegram_message': $type = 'tg_channel'; break;
                                        case 'telegram_channel': $type = 'tg_message'; break;
                                        case 'document':
                                            if ( ! empty($message['media']['webpage']['document']) &&
                                                 ! empty($message['media']['webpage']['document']['mime_type'])
                                            ) {
                                                if (strpos($message['media']['webpage']['document']['mime_type'], 'video') === 0) {
                                                    $type = 'video';

                                                } else if (strpos($message['media']['webpage']['document']['mime_type'], 'audio') === 0) {
                                                    $type = 'audio';

                                                } else {
                                                    $type = 'document';
                                                }

                                            } else {
                                                $type = 'document';
                                            }
                                            break;
                                    }
                                }

                                $title       = ! empty($message['media']['webpage']['title']) ? $message['media']['webpage']['title'] : null;
                                $description = ! empty($message['media']['webpage']['description']) ? $message['media']['webpage']['description'] : null;

                                $source_link = $this->getLink($message['media']['webpage']['url'], [
                                    'type'        => $type,
                                    'title'       => $title,
                                    'description' => $description,
                                ]);

                                $this->modSources->dataSourcesChatsMessagesLinks->saveLink($source_message->id, $source_link->id);

                                if ($description) {
                                    $links = $this->getLinks($description);
                                    foreach ($links as $link) {
                                        $source_link = $this->getLink($link);
                                        $this->modSources->dataSourcesChatsMessagesLinks->saveLink($source_message->id, $source_link->id);
                                    }

                                    $hashtags = $this->getHashtags($description);
                                    foreach ($hashtags as $hashtag) {
                                        $source_hashtag = $this->getHashtag($hashtag);
                                        $this->modSources->dataSourcesChatsMessagesHashtag->saveHashtag($source_message->id, $source_hashtag->id);
                                    }
                                }
                            }

                            if ($source_message->content) {
                                $links = $this->getLinks($source_message->content);
                                foreach ($links as $link) {
                                    $source_link = $this->getLink($link);
                                    $this->modSources->dataSourcesChatsMessagesLinks->saveLink($source_message->id, $source_link->id);
                                }

                                $hashtags = $this->getHashtags($source_message->content);
                                foreach ($hashtags as $hashtag) {
                                    $source_hashtag = $this->getHashtag($hashtag);
                                    $this->modSources->dataSourcesChatsMessagesHashtag->saveHashtag($source_message->id, $source_hashtag->id);
                                }
                            }


                            // Последние комментаторы
                            if ( ! empty($message['replies']) &&
                                ! empty($message['replies']['recent_repliers']) &&
                                is_array($message['replies']['recent_repliers'])
                            ) {
                                foreach ($message['replies']['recent_repliers'] as $recent_replier) {
                                    if ( ! empty($recent_replier['_']) &&
                                        ! empty($recent_replier['user_id']) &&
                                        $recent_replier['_'] === 'peerUser'
                                    ) {
                                        $source_user = $this->getUser($recent_replier['user_id'], 'user');
                                        $this->modSources->dataSourcesChatsMessagesReplies->saveReply($source_message->id, $source_user->id);
                                    }
                                }
                            }

                            // Реакции
                            if ( ! empty($message['reactions']) &&
                                ! empty($message['reactions']['results']) &&
                                is_array($message['reactions']['results'])
                            ) {
                                foreach ($message['reactions']['results'] as $reaction) {

                                    if ( ! empty($reaction['_']) &&
                                         ! empty($reaction['count']) &&
                                         ! empty($reaction['reaction']) &&
                                         ! empty($reaction['reaction']['_']) &&
                                         ! empty($reaction['reaction']['emoticon']) &&
                                        $reaction['_'] === 'reactionCount' &&
                                        $reaction['reaction']['_'] == 'reactionEmoji'
                                    ) {
                                        $source_reaction = $this->getReaction($reaction['reaction']['emoticon']);

                                        $this->modSources->dataSourcesChatsMessagesReactions
                                            ->saveReaction($source_message->id, $source_reaction->id, $reaction['count']);
                                    }
                                }
                            }
                        }
                    }
                }

                $item->is_parsed_sw = 'Y';
                $item->save();

                $this->db->commit();

            } catch (\Exception $e) {
                $this->db->rollback();
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;
            }
        }
    }


    /**
     * @param int $limit
     * @return void
     */
    public function processUpdates(int $limit = 100): void {

        $history = $this->modSources->dataSourcesChatsContent->fetchAll(
            $this->modSources->dataSourcesChatsContent->select()
                ->where("type = 'tg_dialogs_info'")
                ->where("is_parsed_sw = 'N'")
                ->order('id DESC')
                ->limit($limit)
        );

        foreach ($history as $item) {

        }
    }


    /**
     * @param int $limit
     * @return void
     */
    public function processDialogInfo(int $limit = 100): void {

        $history = $this->modSources->dataSourcesChatsContent->fetchAll(
            $this->modSources->dataSourcesChatsContent->select()
                ->where("type = 'tg_dialogs_info'")
                ->where("is_parsed_sw = 'N'")
                ->order('id DESC')
                ->limit($limit)
        );

        foreach ($history as $item) {

        }
    }


    /**
     * @param string $chat_id
     * @param string $type
     * @return \Zend_Db_Table_Row_Abstract
     */
    private function getChat(string $chat_id, string $type): \Zend_Db_Table_Row_Abstract {

        if (isset(self::$chats[$chat_id])) {
            return self::$chats[$chat_id];
        }

        $source_chat           = $this->modSources->dataSourcesChats->saveChatId("-100{$chat_id}", $type);
        self::$chats[$chat_id] = $source_chat;

        return $source_chat;
    }


    /**
     * @param string $user_id
     * @param string $type
     * @return \Zend_Db_Table_Row_Abstract
     */
    private function getUser(string $user_id, string $type): \Zend_Db_Table_Row_Abstract {

        if (isset(self::$users[$user_id])) {
            return self::$users[$user_id];
        }

        $source_user           = $this->modSources->dataSourcesChatsUsers->saveUser($user_id, $type);
        self::$users[$user_id] = $source_user;

        return $source_user;
    }


    /**
     * @param string $emoticon
     * @return \Zend_Db_Table_Row_Abstract
     */
    private function getReaction(string $emoticon): \Zend_Db_Table_Row_Abstract {

        if (isset(self::$reaction[$emoticon])) {
            return self::$reaction[$emoticon];
        }

        $source_reaction           = $this->modSources->dataSourcesChatsReactions->saveReaction($emoticon);
        self::$reaction[$emoticon] = $source_reaction;

        return $source_reaction;
    }


    /**
     * @param string $hashtag
     * @return \Zend_Db_Table_Row_Abstract
     */
    private function getHashtag(string $hashtag): \Zend_Db_Table_Row_Abstract {

        if (isset(self::$hashtags[$hashtag])) {
            return self::$hashtags[$hashtag];
        }

        $source_hashtag           = $this->modSources->dataSourcesChatsHashtags->saveHashtag($hashtag);
        self::$hashtags[$hashtag] = $source_hashtag;

        return $source_hashtag;
    }


    /**
     * @param string     $url
     * @param array|null $options
     * @return \Zend_Db_Table_Row_Abstract
     */
    private function getLink(string $url, array $options = null): \Zend_Db_Table_Row_Abstract {

        if (isset(self::$links[$url])) {
            $link    = self::$links[$url];
            $is_save = false;

            if (empty($link->title) && ! empty($options['title'])) {
                $link->title = $options['title'];
                $is_save     = true;
            }
            if (empty($link->description) && ! empty($options['description'])) {
                $link->description = $options['description'];
                $is_save           = true;
            }
            if (empty($link->type) && ! empty($options['type'])) {
                $link->type = $options['type'];
                $is_save    = true;
            }

            if ($is_save) {
                $link->save();
            }

            return $link;
        }

        $source_link       = $this->modSources->dataSourcesChatsLinks->saveLink($url, $options);
        self::$links[$url] = $source_link;

        return $source_link;
    }


    /**
     * @param string $text
     * @return array
     */
    private function getLinks(string $text): array {

        //preg_match_all('~((:?http://|https://)?(:?www)?(:?[\da-z\.-]+)\.(:?[a-z\.]{2,6})(:?[/\w\.-\?\%\&\+\-]*)*\/?)~iu', $text, $matches);
        preg_match_all('~((ftp|http|https):\/\/)?(www\.)?([A-Za-zА-Яа-я0-9]{1}[A-Za-zА-Яа-я0-9\-]*\.?)+\.{1}[A-Za-zА-Яа-я-]{2,8}(\/([\[\]\(\)\{\}\|\w\#\~\'\!\;\:\.\,\?\+\*\=\&\%\@\!\-\/])*)?~miu', $text, $matches);

        $links = [];

        if ( ! empty($matches[0])) {
            foreach ($matches[0] as $match) {
                if ( ! empty($match)) {
                    $link = trim($match, '.');

                    if (filter_var($link, FILTER_VALIDATE_URL)) {
                        $links[] = $link;

                    } elseif (filter_var("https://$link", FILTER_VALIDATE_URL)) {
                        $links[] = $link;
                    }
                }
            }
        }

        return array_unique($links);
    }


    /**
     * @param string $text
     * @return array
     */
    private function getHashtags(string $text): array {

        preg_match_all('~(#[\w\da-zA-Zа-яА-Я]+)~ium', $text, $matches);

        $hashtags = [];

        if ( ! empty($matches[0])) {
            foreach ($matches[0] as $match) {
                if ( ! empty($match)) {
                    $hashtags[] = trim($match, '.');
                }
            }
        }

        return array_unique($hashtags);
    }
}