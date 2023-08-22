<?php
use Core2\Mod\Sources;

require_once DOC_ROOT . 'core2/inc/classes/Common.php';
require_once "classes/autoload.php";


/**
 * @property ModSourcesController $modSources
 * @property ModProxyController   $modProxy
 */
class ModSourcesCli extends Common {

    /**
     * Сайты: Получение страниц
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function loadSources(): void {

        $configs = (new Sources\Index\Model())->getConfigs();
        $test_sources = [

        ];

        if ($configs) {
            $transform = new Sources\Etl\Transform();
            $loader    = new Sources\Etl\Loader();

            foreach ($configs as $name => $config) {

                if ( ! empty($test_sources) && ! in_array($name, $test_sources)) {
                    continue;
                }

                if ( ! $config?->source || ! $config?->source?->title) {
                    continue;
                }

                $source_data = $transform->getSource($config->source->title, $config->source->tags, $config->source->region);
                $source_id   = $loader->saveSource($source_data);


                $source_sections = $config->toArray();

                foreach ($source_sections as $section_name_raw => $section) {
                    if (mb_strpos($section_name_raw, 'data__') === 0) {

                        if ( ! ($section['active'] ?? true)) {
                            continue;
                        }

                        $section_name = mb_substr($section_name_raw, 6);
                        $content_type = $section['type'] ?? 'html';


                        try {
                            switch ($content_type) {
                                case 'html':
                                    if (empty($section['start_url']) || empty($section['list'])) {
                                        continue 2;
                                    }

                                    $empty_list = true;
                                    foreach ($section['list'] as $list) {
                                        if ( ! empty($list)) {
                                            $empty_list = false;
                                            break;
                                        }
                                    }

                                    if ($empty_list) {
                                        continue 2;
                                    }


                                    $site = new Sources\Index\Site($section);

                                    $pages_list = $site->loadList();
                                    $pages_url  = [];

                                    foreach ($pages_list as $page_list) {
                                        if ( ! empty($page_list['url'])) {
                                            $pages_url[] = $page_list['url'];
                                        }
                                    }

                                    if (empty($pages_url)) {
                                        throw new \Exception(sprintf('На ресурсе %s не найдены страницы ведущие по какому-либо адресу. Проверьте правила поиска', $config->start_url));
                                    }

                                    $pages_item = $site->loadPages($pages_url);

                                    foreach ($pages_item as $page_item) {
                                        $options = [];

                                        foreach ($pages_list as $page_list) {
                                            if ($page_list['url'] == $page_item['url']) {
                                                $options['date_publish'] = $page_list['date_publish'];
                                                $options['title']        = $page_list['title'];
                                                $options['count_views']  = $page_list['count_views'];
                                                $options['tags']         = $page_list['tags'];
                                                $options['categories']   = $page_list['categories'];
                                                break;
                                            }
                                        }

                                        $content = [
                                            'content_type' => $content_type,
                                            'section_name' => $section_name,
                                            'content'      => $page_item['content'],
                                        ];

                                        $loader->saveSourceContent($source_id, $page_item['url'], $content, $options);
                                    }
                                    break;

                                case 'json': break;
                                case 'text': break;
                            }

                        } catch (\Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                        }
                    }
                }
            }
        }
    }


    /**
     * Сайты: Обработка страниц
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function parseSources(): void {

        $configs      = (new Sources\Index\Model())->getConfigs();
        $test_sources = [
            //'relax.by'
        ];

        if ($configs) {
            $loader = new Sources\Etl\Loader();

            $this->modSources->dataSourcesContentsRaw->refreshStatusRows();

            foreach ($configs as $name => $config) {

                if ( ! empty($test_sources) && ! in_array($name, $test_sources)) {
                    continue;
                }

                if ( ! $config?->source || ! $config->source?->title) {
                    continue;
                }

                $source = $this->modSources->dataSources->getRowByTitle($config->source->title);

                if (empty($source)) {
                    continue;
                }

                $pages_raw    = $this->modSources->dataSourcesContentsRaw->getRowsPendingBySourceId($source->id);
                $count_errors = 0;

                foreach ($pages_raw as $page_raw) {
                    try {
                        $section_name    = $page_raw->section_name ?: 'main';
                        $source_sections = $config->toArray();
                        $section         = $source_sections["data__{$section_name}"] ?? [];

                        if (empty($section)) {
                            throw new \Exception("В конфигурации {$name} не найден раздел [data__{$section_name}]");
                        }

                        $page_options = $page_raw->options ? json_decode($page_raw->options, true) : [];

                        switch ($page_raw->content_type) {
                            case 'html':
                                if (empty($section['page']) ||
                                    empty($section['page']['title']) ||
                                    empty($section['page']['content']) ||
                                    empty($section['page']['date_publish'])
                                ) {
                                    continue 2;
                                }


                                $page_raw->status = 'process';
                                $page_raw->save();

                                $site = new Sources\Index\Site($section);
                                $page = $site->parsePage($page_raw->url, gzuncompress($page_raw->content));


                                if (empty($page['title']) && ! empty($page_options['title']))               { $page['title'] = $page_options['title']; }
                                if (empty($page['count_views']) && ! empty($page_options['count_views']))   { $page['count_views'] = $page_options['count_views']; }
                                if (empty($page['date_publish']) && ! empty($page_options['date_publish'])) { $page['date_publish'] = $page_options['date_publish']; }
                                if (empty($page['tags']) && ! empty($page_options['tags']))                 { $page['tags'] = $page_options['tags']; }
                                if (empty($page['categories']) && ! empty($page_options['categories']))     { $page['categories'] = $page_options['categories']; }
                                if (empty($page['region']) && ! empty($page_options['region']))             { $page['region'] = $page_options['region']; }
                                if (empty($page['image']) && ! empty($page_options['image']))               { $page['image'] = $page_options['image']; }

                                $loader->savePage($page_raw->source_id, $page);

                                $page_raw->status = 'complete';
                                $page_raw->note   = null;
                                $page_raw->save();
                                break;

                            case 'json': break;
                            case 'text': break;

                            default:
                                throw new \Exception("Неизвестный тип данных {$page_raw->content_type}");
                        }

                    } catch (\Exception $e) {
                        $page_raw->status = "error";
                        $page_raw->note   = mb_substr($e->getMessage(), 0, 250);
                        $page_raw->save();

                        echo $e->getMessage() . PHP_EOL;
                        $count_errors++;

                        if ($count_errors >= 3) {
                            break;
                        }
                    }
                }
            }
        }
    }


    /**
     * Телеграм: Получение старых сообщений из чатов
     * @return void
     * @throws \danog\MadelineProto\Exception
     * @throws Exception
     */
    public function loadTgHistory(): void {

        $chat = $this->modSources->dataSourcesChats->fetchRow(
            $this->modSources->dataSourcesChats->select()
                ->where("messenger_type = 'tg'")
                ->where("type = 'channel'")
                ->where("is_connect_sw = 'Y'")
                ->where("date_history_load IS NULL OR date_old_message IS NULL OR date_old_message > date_history_load")
                ->where("old_message_id IS NULL OR old_message_id > 1")
                ->order("subscribers_count DESC")
                ->limit(1)
        );

        if (empty($chat)) {
            return;
        }

        $date_history        = '2020-01-01';
        $chat_message_old_id = $chat->old_message_id ?: null;
        $chat_message_new_id = $chat->new_message_id ?: null;
        $chat_date_old       = $chat->date_old_message ? new \DateTime($chat->date_old_message) : null;
        $chat_date_new       = $chat->date_new_message ? new \DateTime($chat->date_new_message) : null;

        $tg     = new Sources\Index\Telegram();
        $result = $tg->messages->getHistory($chat->peer_name, [
            'offset_id' => (int)$chat_message_old_id,
        ]);

        if ( ! empty($result) && ! empty($result['messages'])) {
            $content      = json_encode($result, JSON_UNESCAPED_UNICODE);
            $content_hash = md5($content);
            $chat_content = $this->modSources->dataSourcesChatsContent->getRowByTypeHash('tg_history_channel', $content_hash);

            if (empty($chat_content)) {
                $request_old_date = null;
                $request_new_date = null;
                $request_old_id   = null;
                $request_new_id   = null;
                $count_messages   = count($result['messages']);

                foreach($result['messages'] as $message) {
                    if (empty($message['date']) || empty($message['id'])) {
                        continue;
                    }

                    $message_date = (new \DateTime())->setTimestamp($message['date']);

                    if (is_null($request_old_date) || $request_old_date > $message_date) {
                        $request_old_date = $message_date;
                    }

                    if (is_null($request_new_date) || $request_new_date < $message_date) {
                        $request_new_date = $message_date;
                    }

                    if (is_null($request_old_id) || $request_old_id > $message['id']) {
                        $request_old_id = $message['id'];
                    }

                    if (is_null($request_new_id) || $request_new_id < $message['id']) {
                        $request_new_id = $message['id'];
                    }
                }


                if ($request_new_id) {
                    $chat_date_old = min($chat_date_old, $request_old_date);
                    $chat_date_new = max($chat_date_new, $request_new_date);

                    $chat_message_old_id = ! is_null($chat_message_old_id) ? min($chat_message_old_id, $request_old_id) : $request_old_id;
                    $chat_message_new_id = ! is_null($chat_message_new_id) ? max($chat_message_new_id, $request_new_id) : $request_new_id;


                    if (empty($chat_date_old) || $chat_date_old > $message_date) {
                        $chat_date_old = $message_date;
                    }

                    if (empty($chat_date_new) || $chat_date_new < $message_date) {
                        $chat_date_new = $message_date;
                    }

                    $this->modSources->dataSourcesChatsContent->saveContent('tg_history_channel', $result, [
                        'peer_name'         => $chat->peer_name,
                        'count_messages'    => $count_messages,
                        'date_old_messages' => $request_old_date->format('Y-m-d H:i:s'),
                        'date_new_messages' => $request_new_date->format('Y-m-d H:i:s'),
                        'old_messages_id'   => $request_old_id,
                        'new_messages_id'   => $request_new_id,
                    ]);

                    if ( ! $chat->date_history_load) {
                        $chat->date_history_load = $date_history;
                    }

                    $chat->old_message_id   = $chat_message_old_id;
                    $chat->new_message_id   = $chat_message_new_id;
                    $chat->date_old_message = $chat_date_old->format('Y-m-d H:i:s');
                    $chat->date_new_message = $chat_date_new->format('Y-m-d H:i:s');
                    $chat->save();
                }
            }
        }
    }


    /**
     * Телеграм: Получение новых обновлений
     * @return void
     * @throws \danog\MadelineProto\Exception
     */
    public function loadTgUpdates(): void {

        $setting = $this->modAdmin->dataSettings->fetchRow(
            $this->modAdmin->dataSettings->select()
                ->where("code = 'tg_update_id'")
        );

        if (empty($setting)) {
            $seq = 1 + (int)$this->db->fetchOne("
                SELECT MAX(seq)
                FROM core_settings 
            ");

            $setting = $this->modAdmin->dataSettings->createRow([
                'system_name'    => 'ID обновления в телеграмм',
                'value'          => 0,
                'code'           => 'tg_update_id',
                'visible'        => 'Y',
                'is_custom_sw'   => 'Y',
                'is_personal_sw' => 'N',
                'type'           => 'text',
                'seq'            => $seq,
            ]);
            $setting->save();
        }


        $update_id = $setting ? (int)$setting->value : 0;

        $tg = new Sources\Index\Telegram();
        $updates = $tg->updates->get($update_id + 1);

        if ( ! empty($updates)) {
            foreach ($updates as $key => $update) {
                if ( ! empty($update['update_id']) && $update['update_id'] > $update_id) {
                    $update_id = $update['update_id'];
                }

                if ( ! empty($update['update']) &&
                     ! empty($update['update']['_']) &&
                    in_array($update['update']['_'], [ 'updateChannelUserTyping', 'updateUserStatus' ])
                ) {
                    unset($updates[$key]);
                }
            }

            if ( ! empty($updates)) {
                $this->modSources->dataSourcesChatsContent->saveContent('tg_updates', $updates, [
                    'max_update_id' => $update_id,
                    'count_updates' => count($updates),
                ]);
            }

            $setting->value = $update_id;
            $setting->save();
        }
    }


    /**
     * Телеграм: Получение информации о каналах и группах
     * @return void
     */
    public function loadTgPeersInfo(): void {

        $chats = $this->modSources->dataSourcesChats->fetchAll(
            $this->modSources->dataSourcesChats->select()
                ->where("messenger_type = 'tg'")
                ->where("is_connect_sw = 'Y'")
                ->order(new \Zend_Db_Expr('peer_id IS NULL DESC'))
                ->order("date_state_info ASC")
        );


        $tg = new Sources\Index\Telegram();

        foreach ($chats as $chat) {
            try {
                $dialog_id = $chat->peer_id ?: $chat->peer_name;
                $dialog    = $tg->dialogs->getDialogInfo($dialog_id);

                if ($dialog) {
                    if ( ! $chat->peer_id && ! empty($dialog['id'])) {
                        $chat->peer_id = $dialog['id'];
                    }
                    if ( ! empty($dialog['title'])) {
                        $chat->title = $dialog['title'];
                    }
                    if ( ! empty($dialog['username'])) {
                        $chat->peer_name = $dialog['username'];
                    }
                    if ( ! empty($dialog['participants_count'])) {
                        $chat->subscribers_count = $dialog['participants_count'];
                    }
                    if ( ! empty($dialog['about'])) {
                        $chat->description = $dialog['about'];
                    }

                    $chat->date_state_info = new \Zend_Db_Expr('NOW()');
                    $chat->save();

                    $this->modSources->dataSourcesChatsContent->saveContent('tg_dialogs_info', $dialog, [
                        'peer_id'   => $dialog['id'] ?? null,
                        'peer_name' => $dialog['username'] ?? null,
                        'date'      => date('Y-m-d H:i:s'),
                    ]);
                }
            } catch (\Exception $e) {
                echo "Chat: {$dialog_id}" .PHP_EOL;
                echo $e->getMessage() .PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL . PHP_EOL;

                if (strpos($e->getMessage(), 'FLOOD_WAIT_') === 0) {
                    break;
                }
            }

            sleep(60);
        }
    }


    /**
     * Телеграм: Загрузка списка топовых каналов и групп
     * @return void
     * @throws Exception
     */
    public function loadTgTopChannels(): void {

        $tg_stat = new Sources\Index\TgStat([
            'debug_requests' => true,
            'cache_dir'      => realpath(DOC_ROOT . "../tmp/tgstat")
        ]);

        $domains      = $tg_stat->getDomains() ?: $tg_stat->getDomainsDefault();
        $top_domains  = [];
        $top_channels = [
            'members',
            // 'members_t',
            // 'members_y',
            // 'members_7d',
            'members_30d',
            'reach',
            'ci',
        ];
        $top_groups = [
            'members',
            // 'members_t',
            // 'members_y',
            // 'members_7d',
            'members_30d',
            'msgs',
            'mau',
        ];

        // Получение данных
        foreach ($domains as $domain => $domain_title) {
            $top_domains[$domain]['channels'] = $tg_stat->getTopChannels($domain, [ 'top_list' => $top_channels ]);
            $top_domains[$domain]['groups']   = $tg_stat->getTopGroups($domain, [ 'top_list' => $top_groups ]);
        }


        // Сохранение
        foreach ($top_domains as $domain => $top_domain) {

            // Каналы
            foreach ($top_domain['channels'] as $category) {

                foreach ($category['tops'] as $top => $channels) {

                    foreach ($channels as $channel) {
                        if (empty($channel['name'])) {
                            continue;
                        }

                        $chat = $this->modSources->dataSourcesChats->getRowByTgChannelPeer($channel['name']);

                        if (empty($chat)) {
                            $tgstat = [
                                $top => [
                                    'subscribers_count' => $channel['subscribers_count'] ?? null,
                                    'reach_count'       => $channel['reach_count'] ?? null,
                                    'index_citation'    => $channel['index_citation'] ?? null,
                                    'date_update'       => date('Y-m-d'),
                                ],
                            ];

                            $subscribers_count = strpos($top, 'members_') === false && ! empty($channel['subscribers_count'])
                                ? (int)$channel['subscribers_count']
                                : null;

                            $chat = $this->modSources->dataSourcesChats->createRow([
                                'messenger_type'    => 'tg',
                                'type'              => 'channel',
                                'peer_name'         => $channel['name'],
                                'title'             => $channel['title'],
                                'subscribers_count' => $subscribers_count,
                                'geolocation'       => $domains[$domain],
                                'tgstat'            => json_encode($tgstat),
                            ]);
                            $chat->save();

                        } else {
                            $tgstat = $chat->tgstat ? json_decode($chat->tgstat, true) : null;
                            $tgstat = is_array($tgstat) ? $tgstat : [];

                            $tgstat[$top]['subscribers_count'] = $channel['subscribers_count'] ?? null;
                            $tgstat[$top]['reach_count']       = $channel['reach_count'] ?? null;
                            $tgstat[$top]['index_citation']    = $channel['index_citation'] ?? null;
                            $tgstat[$top]['date_update']       = date('Y-m-d');

                            $chat->tgstat = json_encode($tgstat);
                            $chat->save();
                        }


                        $category_row = $this->modSources->dataSourcesChatsCategories->getRowByTitle($channel['category_title']);
                        if (empty($category_row)) {
                            $category_row = $this->modSources->dataSourcesChatsCategories->createRow([
                                'title' => $channel['category_title'],
                            ]);
                            $category_row->save();
                        }

                        $link_category = $this->modSources->dataSourcesChatsCategoriesLink->getRowByChatCategory($chat->id, $category_row->id);
                        if ( ! $link_category) {
                            $link_category = $this->modSources->dataSourcesChatsCategoriesLink->createRow([
                                'messenger_id' => $chat->id,
                                'category_id'  => $category_row->id,
                            ]);
                            $link_category->save();
                        }
                    }
                }
            }


            // Группы
            foreach ($top_domain['groups'] as $category) {

                foreach ($category['tops'] as $top => $groups) {

                    foreach ($groups as $group) {
                        if (empty($group['name'])) {
                            continue;
                        }

                        $chat = $this->modSources->dataSourcesChats->getRowByTgGroupPeer($group['name']);

                        if (empty($chat)) {
                            $tgstat = [
                                $top => [
                                    'subscribers_count' => $channel['subscribers_count'] ?? null,
                                    'messages_7d_count' => $channel['messages_7d_count'] ?? null,
                                    'mau_count'         => $channel['mau_count'] ?? null,
                                    'date_update'       => date('Y-m-d'),
                                ],
                            ];

                            $subscribers_count = strpos($top, 'members_') === false && ! empty($channel['subscribers_count'])
                                ? (int)$channel['subscribers_count']
                                : null;

                            $chat = $this->modSources->dataSourcesChats->createRow([
                                'messenger_type'    => 'tg',
                                'type'              => 'group',
                                'peer_name'         => $group['name'],
                                'title'             => $group['title'],
                                'subscribers_count' => $subscribers_count,
                                'geolocation'       => $domains[$domain],
                                'tgstat'            => json_encode($tgstat),
                            ]);
                            $chat->save();

                        } else {
                            $tgstat = $chat->tgstat ? json_decode($chat->tgstat, true) : null;
                            $tgstat = is_array($tgstat) ? $tgstat : [];

                            $tgstat[$top]['subscribers_count'] = $channel['subscribers_count'] ?? null;
                            $tgstat[$top]['messages_7d_count'] = $channel['messages_7d_count'] ?? null;
                            $tgstat[$top]['mau_count']         = $channel['mau_count'] ?? null;
                            $tgstat[$top]['date_update']       = date('Y-m-d');

                            $chat->tgstat = json_encode($tgstat);
                            $chat->save();
                        }


                        $category_row = $this->modSources->dataSourcesChatsCategories->getRowByTitle($group['category_title']);
                        if (empty($category_row)) {
                            $category_row = $this->modSources->dataSourcesChatsCategories->createRow([
                                'title' => $group['category_title'],
                            ]);
                            $category_row->save();
                        }

                        $link_category = $this->modSources->dataSourcesChatsCategoriesLink->getRowByChatCategory($chat->id, $category_row->id);
                        if ( ! $link_category) {
                            $link_category = $this->modSources->dataSourcesChatsCategoriesLink->createRow([
                                'messenger_id' => $chat->id,
                                'category_id'  => $category_row->id,
                            ]);
                            $link_category->save();
                        }
                    }
                }
            }
        }
    }


    /**
     * Телеграм: Обработка загруженных данных
     * @return void
     * @throws Exception
     */
    public function parsingTgContent(): void {

        $tg_parser = new Sources\Index\TgParser();

        $tg_parser->processHistory(100);
        $tg_parser->processDialogInfo(100);
//        $tg_parser->processUpdates(100);
    }


    /**
     * Телеграм: логин в клиенте. Отправка кода на телефон
     * @return void
     * @throws Zend_Config_Exception
     */
    public function loginTgSendCode(): void {

        $tg = new Sources\Index\Telegram();
        $tg->account->loginPhone();
    }


    /**
     * Телеграм: логин в клиенте. Установка ранее отправленного кода
     * @param string $code
     * @return void
     * @throws Zend_Config_Exception
     * @throws Exception
     */
    public function loginTgSetCode(string $code): void {

        $tg = new Sources\Index\Telegram();
        $tg->account->completePhone($code);
    }


    /**
     * Телеграм: логин в клиенте. Установка ранее отправленного кода и пароля
     * @param string $code
     * @param string $password
     * @return void
     * @throws \danog\MadelineProto\Exception
     */
    public function loginTgSetPassword(string $code, string $password): void {

        $tg = new Sources\Index\Telegram();
        $tg->account->complete2faLogin($code, $password);
    }
}
