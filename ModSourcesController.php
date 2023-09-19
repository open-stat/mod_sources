<?php
use Core2\Mod\Sources;

require_once DOC_ROOT . "core2/inc/classes/Common.php";
require_once DOC_ROOT . "core2/inc/classes/Alert.php";
require_once DOC_ROOT . "core2/inc/classes/Panel.php";

require_once 'classes/autoload.php';


/**
 * @property \SourcesSites                  $dataSources
 * @property \SourcesSitesContentsRaw       $dataSourcesSitesContentsRaw
 * @property \SourcesSitesPages             $dataSourcesSitesPages
 * @property \SourcesSitesPagesContents     $dataSourcesSitesPagesContents
 * @property \SourcesSitesPagesMedia        $dataSourcesSitesPagesMedia
 * @property \SourcesSitesPagesReferences   $dataSourcesSitesPagesReferences
 * @property \SourcesSitesPagesTags         $dataSourcesSitesPagesTags
 * @property \SourcesSitesTags              $dataSourcesSitesTags
 *                                                               
 * @property \SourcesChats                   $dataSourcesChats
 * @property \SourcesChatsAccounts           $dataSourcesChatsAccounts
 * @property \SourcesChatsAccountsSubscribes $dataSourcesChatsAccountsSubscribes
 * @property \SourcesChatsCategories         $dataSourcesChatsCategories
 * @property \SourcesChatsCategoriesLink     $dataSourcesChatsCategoriesLink
 * @property \SourcesChatsContent            $dataSourcesChatsContent
 * @property \SourcesChatsUsers              $dataSourcesChatsUsers
 * @property \SourcesChatsUsersLinks         $dataSourcesChatsUsersLinks
 * @property \SourcesChatsReactions          $dataSourcesChatsReactions
 * @property \SourcesChatsLinks              $dataSourcesChatsLinks
 * @property \SourcesChatsHashtags           $dataSourcesChatsHashtags
 * @property \SourcesChatsFiles              $dataSourcesChatsFiles
 * @property \SourcesChatsSubscribers        $dataSourcesChatsSubscribers
 * @property \SourcesChatsMessages           $dataSourcesChatsMessages
 * @property \SourcesChatsMessagesReactions  $dataSourcesChatsMessagesReactions
 * @property \SourcesChatsMessagesFiles      $dataSourcesChatsMessagesFiles
 * @property \SourcesChatsMessagesLinks      $dataSourcesChatsMessagesLinks
 * @property \SourcesChatsMessagesReplies    $dataSourcesChatsMessagesReplies
 * @property \SourcesChatsMessagesHashtag    $dataSourcesChatsMessagesHashtag
 *
 * @property \SourcesVideos                 $dataSourcesVideos
 * @property \SourcesVideosAccounts         $dataSourcesVideosAccounts
 * @property \SourcesVideosChannelsHashtags $dataSourcesVideosChannelsHashtags
 * @property \SourcesVideosChannelsLinks    $dataSourcesVideosChannelsLinks
 * @property \SourcesVideosClips            $dataSourcesVideosClips
 * @property \SourcesVideosClipsComments    $dataSourcesVideosClipsComments
 * @property \SourcesVideosClipsFiles       $dataSourcesVideosClipsFiles
 * @property \SourcesVideosClipsHashtags    $dataSourcesVideosClipsHashtags
 * @property \SourcesVideosClipsLinks       $dataSourcesVideosClipsLinks
 * @property \SourcesVideosClipsSubtitles   $dataSourcesVideosClipsSubtitles
 * @property \SourcesVideosClipsTags        $dataSourcesVideosClipsTags
 * @property \SourcesVideosHashtags         $dataSourcesVideosHashtags
 * @property \SourcesVideosLinks            $dataSourcesVideosLinks
 * @property \SourcesVideosStats            $dataSourcesVideosStats
 * @property \SourcesVideosRaw              $dataSourcesVideosRaw
 * @property \SourcesVideosUsers            $dataSourcesVideosUsers
 */
class ModSourcesController extends Common {

    /**
     * @return string
     * @throws Exception
     */
    public function action_sites(): string {

        $base_url = 'index.php?module=sources&action=sites';
        $view     = new Sources\Sites\View();
        $panel    = new Panel('tab');
        $content  = [];

        try {
            if ( ! empty($_GET['edit'])) {
                $page = $this->dataSourcesSitesPages->find($_GET['edit'])->current();

                if (empty($page)) {
                    throw new Exception('Указанная страница не найдена');
                }
                $source = $this->dataSourcesSites->find($page->source_id)->current();
                $date_publish = $page->date_publish
                    ? (new \DateTime($page->date_publish))->format('d.m.Y H:i:s')
                    : 'не указано';

                $description = "{$source->title} / {$date_publish}";


                $panel->setTitle($page->title, $description, $base_url);
                $edit = $view->getEdit($page);

                $base_url .= "&edit={$page->id}";

                if (empty($_GET['edited'])) {
                    $edit->readOnly = true;
                    $edit->addButtonCustom("<input type=\"button\" class=\"button btn btn-info\"
                                                   value=\"Редактировать\" onclick=\"load('{$base_url}&edited=1')\">");
                } else {
                    $edit->addButton($this->translate->tr("Отменить"), "load('{$base_url}')");
                }

                $content[] = $edit->render();

            } else {

                $panel->addTab('Публикации', 'publications', $base_url);
                $panel->addTab('Источники',  'sources',      $base_url);

                switch ($panel->getActiveTab()) {
                    case 'publications': $content[] = $view->getTable($base_url)->render(); break;
                    case 'sources':      $content[] = $view->getTableSources($base_url)->render();break;
                }
            }

        } catch (\Exception $e) {
            $content[] = Alert::danger($e->getMessage(), 'Ошибка');
        }

        $panel->setContent(implode('', $content));
        return $panel->render();
    }


    /**
     * @return string
     * @throws Exception
     */
    public function action_test(): string {

        if ( ! empty($_GET['data'])) {
            try {
                switch ($_GET['data']) {
                    case 'test_site':
                        if (empty($_POST['rules'])) {
                            throw new Exception($this->_('Не переданы правила для запуска'));
                        }

                        $model = new Sources\Test\Model();

                        ob_start();
                        $time_start = microtime(true);
                        $body       = $model->testSite($_POST['rules'], $_POST['options'] ?? []);
                        $time_end   = microtime(true);
                        $body      .= ob_get_clean();

                        return json_encode([
                            'status' => 'success',
                            'time'   => round($time_end - $time_start, 3),
                            'mem'    => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                            'body'   => $body,
                        ]);
                        break;
                }

            } catch (Exception $e) {
                return json_encode([
                    'status'        => 'error',
                    'error_message' => $e->getMessage()
                ]);
            }
        }

        $base_url = 'index.php?module=sources&action=test';
        $view     = new Sources\Test\View();
        $panel    = new Panel('tab');
        $content  = [];

        try {
            $panel->addTab('Сайты',    'sites',    $base_url);
            $panel->addTab('Telegram', 'telegram', $base_url);
            $panel->addTab('Youtube',  'youtube',  $base_url);

            ob_start();
            $this->printJsModule('sources', '/assets/test/js/source.test.js');
            $this->printCssModule('sources', '/assets/test/css/source.test.css');
            $content[] = ob_get_clean();

            switch ($panel->getActiveTab()) {
                case 'sites':    $content[] = $view->getSiteContainer(); break;
                case 'telegram': break;
                case 'youtube':  break;
            }

        } catch (\Exception $e) {
            $content[] = Alert::danger($e->getMessage(), 'Ошибка');
        }

        $panel->setContent(implode('', $content));
        return $panel->render();
    }


}