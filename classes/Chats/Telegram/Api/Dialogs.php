<?php
namespace Core2\Mod\Sources\Chats\Telegram\Api;


use Core2\Mod\Sources\Chats\Telegram\Connection;
use danog\MadelineProto\Exception;

/**
 *
 */
class Dialogs {


    private Connection $connection;


    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection) {

        $this->connection = $connection;
    }

    /**
     * Получение списка id диалогов
     * @return array
     * @throws Exception
     */
    public function getDialogsId(): array {

        $result = $this->connection->getMadeline()->getFullDialogs();

        return array_keys((array)$result);
    }


    /**
     * Получение списка id групп
     * @return array
     * @throws Exception
     */
    public function getGroupsId(): array {

        $dialogs = $this->connection->getMadeline()->getFullDialogs();
        $groups  = [];

        foreach ((array)$dialogs as $dialog_id => $dialog) {
            if ( ! empty($dialog['peer']) &&
                ! empty($dialog['peer']['_']) &&
                $dialog['peer']['_'] === 'peerChannel'
            ) {
                $groups[] = $dialog_id;
            }
        }

        return $groups;
    }


    /**
     * Получение всей информации о группе
     * @param string $dialog_id
     * @return array
     * @throws Exception
     */
    public function getDialogInfoFull(string $dialog_id): array {

        return $this->connection->getMadeline()->getFullInfo($dialog_id);
    }


    /**
     * Получение информации о группе
     * @param string $dialog_id
     * @return array
     * @throws Exception
     */
    public function getDialogInfo(string $dialog_id): array {

        return $this->connection->getMadeline()->getInfo($dialog_id);
    }


    /**
     * Получение всей информации о группе
     * @param string $dialog_id
     * @param bool   $fullfetch
     * @return array
     */
    public function getDialogPwr(string $dialog_id, bool $fullfetch = true): array {

        return $this->connection->getMadeline()->getPwrChat($dialog_id, $fullfetch);
    }


    /**
     * Создание новой группы
     * @param string $title
     * @param string $description
     * @return string|null
     * @throws Exception
     */
    public function createGroup(string $title, string $description = ''):? string {

        $updates = $this->connection->getMadeline()->channels->createChannel(...[
            'broadcast'  => false,
            'megagroup'  => true,
            'for_import' => false,
            'title'      => $title,
            'about'      => $description,
        ]);

        $group_id = null;
        $updates  = (array)$updates;

        if ( ! empty($updates['chats']) &&
            ! empty($updates['chats'][0]) &&
            ! empty($updates['chats'][0]['id'])
        ) {
            $group_id = $updates['chats'][0]['id'];
        };

        return $group_id;
    }


    /**
     * Удаление группы
     * @param string $group_id
     * @return array
     */
    public function removeGroup(string $group_id): array {

        $madeline = $this->connection->getMadeline();

        $updates = $madeline->channels->deleteChannel(...[
            'channel' => $group_id,
        ]);

        return (array)$updates;
    }


    /**
     * Приглашение в группу пользователей или ботов
     * @param string $group_id
     * @param array  $users_id
     * @return array
     * @throws Exception
     */
    public function inviteGroup(string $group_id, array $users_id): array {

        $update = $this->connection->getMadeline()->channels->inviteToChannel(...[
            'channel' => $group_id,
            'users'   => $users_id
        ]);

        return (array)$update;
    }


    /**
     * Установка админских прав доступа пользователю в группе
     * @param string     $group_id
     * @param string     $user_id
     * @param array|null $rules
     * @param string     $rank
     * @return array
     * @throws Exception
     */
    public function setGroupAdminRules(string $group_id, string $user_id, array $rules = null, string $rank = ''): array {

        $admin_rights = $rules ?: [
            'change_info'     => true,
            'post_messages'   => true,
            'edit_messages'   => true,
            'delete_messages' => true,
            'ban_users'       => true,
            'invite_users'    => true,
            'pin_messages'    => true,
            'manage_call'     => true,
            'other'           => true,
            'manage_topics'   => true,
            'add_admins'      => false,
            'anonymous'       => false,
        ];

        $admin_rights['_'] = 'chatAdminRights';

        $update = $this->connection->getMadeline()->channels->editAdmin(...[
            'channel'      => $group_id,
            'user_id'      => $user_id,
            'admin_rights' => $admin_rights,
            'rank'         => $rank,
        ]);

        return (array)$update;
    }


    /**
     * Подписчики чата
     * @param string     $channel_name
     * @param array|null $options
     * @return array
     */
    public function getParticipants(string $channel_name, array $options = null): array {

        $madeline_participants = $this->connection->getMadeline()->channels->getParticipants([
            'channel' => $channel_name,
            'filter'  => ['_' => 'channelParticipantsRecent'],
            'offset'  => 0,
            'limit'   => 100,
            'hash'    => 0,
        ]);

        return $madeline_participants;
    }
}