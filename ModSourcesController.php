<?php
use Core2\Mod\Sources;

require_once DOC_ROOT . "core2/inc/classes/Common.php";
require_once DOC_ROOT . "core2/inc/classes/Alert.php";
require_once DOC_ROOT . "core2/inc/classes/Panel.php";

require_once 'classes/autoload.php';


/**
 * @property \Sources                 $dataSources
 * @property \SourcesPages            $dataSourcesPages
 * @property \SourcesPagesContents    $dataSourcesPagesContents
 * @property \SourcesPagesMedia       $dataSourcesPagesMedia
 * @property \SourcesPagesReferences  $dataSourcesPagesReferences
 */
class ModSourcesController extends Common {

    /**
     * @return string
     * @throws Exception
     */
    public function action_index(): string {

        $base_url = 'index.php?module=sources&action=index';
        $view     = new Sources\Index\View();
        $panel    = new Panel('tab');
        $content  = [];

        try {
            if ( ! empty($_GET['edit'])) {
                $page = $this->dataSourcesPages->find($_GET['edit'])->current();

                if (empty($page)) {
                    throw new Exception('Указанная страница не найдена');
                }

                $date_publish = $page->date_publish
                    ? (new \DateTime($page->date_publish))->format('d.m.Y H:i:s')
                    : 'не указано';

                $description = "Дата публикации: {$date_publish} / Просмотров: {$page->count_views} / Автор: {$page->source_author}";


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
                $content[] = $view->getTable($base_url)->render();
            }

        } catch (\Exception $e) {
            $content[] = Alert::danger($e->getMessage(), 'Ошибка');
        }

        $panel->setContent(implode('', $content));
        return $panel->render();
    }
}