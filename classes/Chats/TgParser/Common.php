<?php
namespace Core2\Mod\Sources\Chats\TgParser;


/**
 * @property \ModSourcesController $modSources
 */
abstract class Common extends \Common {

    private static array $chats    = [];
    private static array $users    = [];
    private static array $reaction = [];
    private static array $links    = [];
    private static array $hashtags = [];


    /**
     * @param array $user
     * @return \Zend_Db_Table_Row_Abstract|null
     */
    public function saveUser(array $user):? \Zend_Db_Table_Row_Abstract {

        if ( ! empty($user['_']) &&
            ! empty($user['id']) &&
            $user['_'] == 'user'
        ) {
            $type = empty($user['bot']) ? 'user' : 'bot';
            return $this->modSources->dataSourcesChatsUsers->saveUser($user['id'], $type, [
                'username'     => ! empty($user['username']) ? $user['username'] : null,
                'first_name'   => ! empty($user['first_name']) ? $user['first_name'] : null,
                'last_name'    => ! empty($user['last_name']) ? $user['last_name'] : null,
                'phone_number' => ! empty($user['phone']) ? $user['phone'] : null,
            ]);
        }

        return null;
    }


    /**
     * @param array $chat
     * @return \Zend_Db_Table_Row_Abstract|null
     */
    public function saveChat(array $chat):? \Zend_Db_Table_Row_Abstract {

        if ( ! empty($chat['_']) &&
             ! empty($chat['id']) &&
            in_array($chat['_'], ['channel', 'group'])
        ) {
            return $this->modSources->dataSourcesChats->saveChatId("-100{$chat['id']}", $chat['_'], 'tg', [
                'peer_name' => ! empty($chat['username']) ? $chat['username'] : null,
                'title'     => ! empty($chat['title']) ? $chat['title'] : null,
            ]);
        }

        return null;
    }


    /**
     * @param array $message
     * @param bool  $force_update
     * @return \Zend_Db_Table_Row_Abstract|null
     */
    public function saveMessage(array $message, bool $force_update = false):? \Zend_Db_Table_Row_Abstract {

        if (empty($message['_']) ||
            empty($message['id']) ||
            empty($message['peer_id']) ||
            empty($message['peer_id']['channel_id']) ||
            $message['_'] != 'message'
        ) {
            return null;
        }


        $chat = $this->getChat($message['peer_id']['channel_id']);


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


        $user_id = null;

        if ( ! empty($message['from_id']) &&
             ! empty($message['from_id']['_']) &&
             ! empty($message['from_id']['user_id']) &&
            $message['from_id']['_'] == 'peerUser'
        ) {
            $user = $this->modSources->dataSourcesChatsUsers->saveUser($message['from_id']['user_id']);

            if ($user) {
                $user_id = $user->id;
                $this->modSources->dataSourcesChatsUsersLinks->save($chat->id, $user->id);
            }
        }


        $fwd_chat_id = ! empty($message['fwd_from']) &&
                       ! empty($message['fwd_from']['from_id']) &&
                       ! empty($message['fwd_from']['from_id']['channel_id'])
            ? $message['fwd_from']['from_id']['channel_id']
            : null;

        $fwd_message_id = ! empty($message['fwd_from']) && ! empty($message['fwd_from']['channel_post'])
            ? $message['fwd_from']['channel_post']
            : null;

        $reply_to_id = ! empty($message['reply_to']) && ! empty($message['reply_to']['reply_to_msg_id'])
            ? $message['reply_to']['reply_to_msg_id']
            : null;

        $message_row = $this->modSources->dataSourcesChatsMessages->saveMessage($chat->id, $message['id'], [
            'media_type'             => $media_type,
            'user_id'                => $user_id,
            'content'                => ! empty($message['message']) ? $message['message'] : null,
            'reply_to_id'            => $reply_to_id,
            'fwd_chat_id'            => $fwd_chat_id,
            'fwd_message_id'         => $fwd_message_id,
            'group_value'            => ! empty($message['grouped_id']) ? $message['grouped_id'] : null,
            'date_messenger_created' => ! empty($message['date']) ? date('Y-m-d H:i:s', $message['date']) : null,
            'date_messenger_edit'    => ! empty($message['edit_date']) ? date('Y-m-d H:i:s', $message['edit_date']) : null,
            'comments_count'         => ! empty($message['replies']) && ! empty($message['replies']['replies']) ? (int)$message['replies']['replies'] : 0,
            'viewed_count'           => ! empty($message['views']) ? (int)$message['views'] : 0,
            'repost_count'           => ! empty($message['forwards']) ? (int)$message['forwards'] : 0,
            'post_author_name'       => ! empty($message['post_author']) ? (int)$message['post_author'] : null,
        ], $force_update);


        $this->saveMessageFile($message_row->id, $message);
        $this->saveMessageLinkEntity($message_row->id, $message);
        $this->saveMessageLinkMedia($message_row->id, $message);

        if ($message_row->content) {
            $this->saveMessageLinkContent($message_row->id, $message_row->content);
            $this->saveMessageHashtagsContent($message_row->id, $message_row->content);
        }

        $this->saveMessageReplies($message_row->id, $message);
        $this->saveMessageReactions($message_row->id, $message);

        return $message_row;
    }


    /**
     * @param string      $chat_id
     * @param string|null $type
     * @return \Zend_Db_Table_Row_Abstract
     */
    protected function getChat(string $chat_id, string $type = null): \Zend_Db_Table_Row_Abstract {

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
    protected function getUser(string $user_id, string $type): \Zend_Db_Table_Row_Abstract {

        if (isset(self::$users[$user_id])) {
            return self::$users[$user_id];
        }

        $source_user           = $this->modSources->dataSourcesChatsUsers->saveUser($user_id, $type);
        self::$users[$user_id] = $source_user;

        return $source_user;
    }


    /**
     * @param int   $message_id
     * @param array $message
     * @return \Zend_Db_Table_Row_Abstract|null
     */
    private function saveMessageFile(int $message_id, array $message):? \Zend_Db_Table_Row_Abstract {

        if (empty($message['media'])) {
            return null;
        }

        return $this->modSources->dataSourcesChatsMessagesFiles->saveFileEmpty($message_id, $message['media']);
    }


    /**
     * @param int   $message_id
     * @param array $message
     * @return void
     */
    private function saveMessageLinkEntity(int $message_id, array $message): void{

        if (empty($message['entities'])) {
            return;
        }

        foreach ($message['entities'] as $entity) {

            if ( ! empty($entity['_']) &&
                 ! empty($entity['url']) &&
                $entity['_'] === 'messageEntityTextUrl' &&
                is_string($entity['url'])
            ) {
                $source_link = $this->getLink($entity['url']);

                $this->modSources->dataSourcesChatsMessagesLinks->saveLink($message_id, $source_link->id, [
                    'offset' => $entity['offset'] ?? null,
                    'length' => $entity['length'] ?? null,
                ]);
            }
        }
    }


    /**
     * @param int   $message_id
     * @param array $message
     * @return void
     */
    private function saveMessageLinkMedia(int $message_id, array $message): void{

        if (empty($message['media']) ||
            empty($message['media']['_']) ||
            empty($message['media']['webpage']) ||
            empty($message['media']['webpage']['url']) ||
            $message['media']['_'] !== 'messageMediaWebPage'
        ) {
            return;
        }

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

        $this->modSources->dataSourcesChatsMessagesLinks->saveLink($message_id, $source_link->id);

        if ($description) {
            $links = $this->getLinks($description);

            foreach ($links as $link) {
                $source_link = $this->getLink($link);
                $this->modSources->dataSourcesChatsMessagesLinks->saveLink($message_id, $source_link->id);
            }

            $hashtags = $this->getHashtags($description);

            foreach ($hashtags as $hashtag) {
                $source_hashtag = $this->getHashtag($hashtag);
                $this->modSources->dataSourcesChatsMessagesHashtag->saveHashtag($message_id, $source_hashtag->id);
            }
        }
    }


    /**
     * @param int    $message_id
     * @param string $content
     * @return void
     */
    private function saveMessageLinkContent(int $message_id, string $content): void {

        $links = $this->getLinks($content);

        foreach ($links as $link) {
            $source_link = $this->getLink($link);
            $this->modSources->dataSourcesChatsMessagesLinks->saveLink($message_id, $source_link->id);
        }
    }


    /**
     * @param int    $message_id
     * @param string $content
     * @return void
     */
    private function saveMessageHashtagsContent(int $message_id, string $content): void {

        $hashtags = $this->getHashtags($content);

        foreach ($hashtags as $hashtag) {
            $source_hashtag = $this->getHashtag($hashtag);

            $this->modSources->dataSourcesChatsMessagesHashtag->saveHashtag($message_id, $source_hashtag->id);
        }
    }


    /**
     * @param int   $message_id
     * @param array $message
     * @return void
     */
    private function saveMessageReplies(int $message_id, array $message): void {

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

                    $this->modSources->dataSourcesChatsMessagesReplies->saveReply($message_id, $source_user->id);
                }
            }
        }
    }


    /**
     * @param int   $message_id
     * @param array $message
     * @return void
     */
    private function saveMessageReactions(int $message_id, array $message): void {

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
                        ->saveReaction($message_id, $source_reaction->id, $reaction['count']);
                }
            }
        }
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
     * Получение ссылки
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
     * Получение ссылок из текста
     * @param string $text
     * @return array
     */
    private function getLinks(string $text): array {

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
     * Получение хэштегов из текста
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