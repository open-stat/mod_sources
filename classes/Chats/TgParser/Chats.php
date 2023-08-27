<?php
namespace Core2\Mod\Sources\Chats\TgParser;


/**
 * @property \ModSourcesController $modSources
 */
class Chats extends Common {

    /**
     * @param array $content
     * @return \Zend_Db_Table_Row_Abstract|null
     */
    public function saveDialog(array $content):? \Zend_Db_Table_Row_Abstract {

        if (empty($content['id'])) {
            return null;
        }

        $type = ! empty($content['type']) && $content['type'] === 'channel'
            ? 'channel'
            : 'group';

        $peer_name = ! empty($content['username']) ? $content['username'] : null;

        if (empty($peer_name) && ! empty($content['usernames'])) {
            foreach ($content['usernames'] as $username) {
                if ( ! empty($username) &&
                     ! empty($username['username']) &&
                     ! empty($username['active'])
                ) {
                    $peer_name = $username['username'];
                }
            }
        }

        $chat = $this->modSources->dataSourcesChats->saveChatId($content['id'], $type, 'tg', [
            'peer_name'         => $peer_name,
            'title'             => ! empty($content['title']) ? $content['title'] : null,
            'description'       => ! empty($content['about']) ? $content['about'] : null,
            'subscribers_count' => ! empty($content['participants_count']) ? $content['participants_count'] : null,
        ]);


        if ( ! empty($content['photo']) &&
             ! empty($content['photo']['sizes']) &&
            is_array($content['photo']['sizes'])
        ) {
            foreach ($content['photo']['sizes'] as $size) {
                if ( ! empty($size['inflated']) && ! empty($size['inflated']['bytes'])) {

                    $logo = base64_decode($size['inflated']['bytes']);
                    $this->modSources->dataSourcesChatsFiles->saveLogo($chat->id, $logo, $content['photo']);
                    break;
                }
            }
        }


        if ( ! empty($content['participants']) && is_array($content['participants'])) {
            foreach ($content['participants'] as $participant) {
                if ( ! empty($participant['user'])) {
                    $user = $this->saveUser($participant['user']);
                    if ($user) {
                        $this->modSources->dataSourcesChatsUsersLinks->save($chat->id, $user->id);
                    }
                }
                if ( ! empty($participant['promoted_by'])) {
                    $user = $this->saveUser($participant['promoted_by']);
                    if ($user) {
                        $this->modSources->dataSourcesChatsUsersLinks->save($chat->id, $user->id);
                    }
                }
            }
        }


        return $chat;
    }


    /**
     * @param int       $chat_id
     * @param \DateTime $date_day
     * @param array     $content
     * @return void
     */
    public function saveSubscribersDay(int $chat_id, \DateTime $date_day, array $content): void {

        if ( ! empty($content['participants_count'])) {
            $this->modSources->dataSourcesChatsSubscribers->saveDayQuantity($chat_id, $date_day, $content['participants_count']);
        }
    }
}