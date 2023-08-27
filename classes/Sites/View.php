<?php
namespace Core2\Mod\Sources\Sites;
use Core2\Classes\Table;


require_once DOC_ROOT . "core2/inc/classes/Common.php";
require_once DOC_ROOT . "core2/inc/classes/Alert.php";
require_once DOC_ROOT . "core2/inc/classes/class.edit.php";
require_once DOC_ROOT . "core2/inc/classes/Table/Db.php";


/**
 * @property \ModSourcesController $modSources
 */
class View extends \Common {


    /**
     * @param string $base_url
     * @return Table\Db
     * @throws Table\Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function getTable(string $base_url): Table\Db {

        $table = new Table\Db($this->resId);
        $table->setTable("mod_sources_sites_pages");
        $table->setPrimaryKey('id');
        $table->setEditUrl("{$base_url}&edit=TCOL_ID");
        $table->showDelete();
        $table->showColumnManage();

        $table->setQuery("
            SELECT sp.id,
                   sp.title,
                   sp.url,
                   sp.source_domain,
                   sp.source_url,
                   sp.source_author,
                   sp.count_views,
                   sp.date_publish,
                   s.title AS source_title
            
            FROM mod_sources_sites_pages AS sp
                JOIN mod_sources_sites AS s ON sp.source_id = s.id
                JOIN mod_sources_sites_pages_contents AS spc ON sp.id = spc.page_id   
            ORDER BY sp.date_publish DESC
        ");

        $table->addFilter("CONCAT_WS('|', sp.title, s.title)", $table::FILTER_TEXT, $this->_("Заголовок, источник"));
        $table->addFilter("spc.content", $table::FILTER_TEXT, $this->_("Содержимое"));

        $table->addSearch($this->_("Источник"),         "s.title",         $table::SEARCH_TEXT);
        $table->addSearch($this->_("Дата публикации"),  "sp.date_publish", $table::SEARCH_DATE);
        $table->addSearch($this->_("Заголовок"),        "sp.title",        $table::SEARCH_TEXT);
        $table->addSearch($this->_("Просмотров"),       "sp.count_views",  $table::SEARCH_TEXT);
        $table->addSearch($this->_("Ссылка"),           "sp.url",          $table::SEARCH_TEXT);


        $table->addColumn($this->_("Источник"),        'source_title',  $table::COLUMN_TEXT, 120);
        $table->addColumn($this->_("Дата публикации"), 'date_publish',  $table::COLUMN_DATETIME, 130);
        $table->addColumn($this->_("Заголовок"),       'title',         $table::COLUMN_TEXT);
        $table->addColumn($this->_("Автор"),           'source_author', $table::COLUMN_NUMBER, 160);
        $table->addColumn($this->_("Просмотров"),      'count_views',   $table::COLUMN_NUMBER, 100);
        $table->addColumn($this->_("Ссылка"),          'url',           $table::COLUMN_HTML, 1)->sorting(false);




        $rows = $table->fetchRows();
        if ( ! empty($rows)) {
            foreach ($rows as $row) {

                // Ссылка
                $row->url->setAttr('onclick', "event.cancelBubble = true;");
                $row->url = "<a href=\"{$row->url}\" class=\"btn btn-xs btn-default\" target=\"_blank\"><i class=\"fa fa-external-link\"></i></a>";
            }
        }

        return $table;
    }


    /**
     * @param string $base_url
     * @return Table\Db
     * @throws Table\Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function getTableSources(string $base_url): Table\Db {

        $table = new Table\Db($this->resId);
        $table->setTable("mod_sources_sites");
        $table->setPrimaryKey('id');
        $table->setRecordsPerPage(100);
        $table->hideCheckboxes();

        $table->setQuery("
            SELECT s.id,
                   s.title,
                   s.region,
                   s.tags,
                   COUNT(sp.id) AS count_tags
            
            FROM mod_sources_sites AS s
                LEFT JOIN mod_sources_sites_pages AS sp ON s.id = sp.source_id
            GROUP BY s.id
            ORDER BY s.date_created DESC
        ");

        $table->addFilter("s.title", $table::FILTER_TEXT, $this->_("Название"));


        $table->addColumn($this->_("Название"),              'title',        $table::COLUMN_TEXT, 250);
        $table->addColumn($this->_("Регион"),                'region',       $table::COLUMN_TEXT, 200);
        $table->addColumn($this->_("Тэги"),                  'tags',         $table::COLUMN_TEXT);
        $table->addColumn($this->_("Количество публикаций"), 'count_tags',   $table::COLUMN_NUMBER, 180);




        $rows = $table->fetchRows();
        if ( ! empty($rows)) {
            foreach ($rows as $row) {

            }
        }

        return $table;
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract $page
     * @return \editTable
     * @throws \Zend_Config_Exception
     */
    public function getEdit(\Zend_Db_Table_Row_Abstract $page): \editTable {

        $edit = new \editTable($this->resId);
        $edit->table = 'mod_sources_sites_pages';

        $page_content    = $this->modSources->dataSourcesSitesPagesContents->getRowByPageId($page->id);
        $page_references = $this->modSources->dataSourcesSitesPagesReferences->getRowsByPageId($page->id);
        $page_tags       = $this->modSources->dataSourcesSitesPagesTags->getRowsByPageId($page->id);

        $tags_id    = [];
        $tags       = [];
        $categories = [];
        $regions    = [];

        foreach ($page_tags as $page_tag) {
            $tags_id[] = $page_tag->tag_id;
        }

        $page_tags = $this->modSources->dataSourcesSitesTags->find($tags_id);

        foreach ($page_tags as $page_tag) {
            switch ($page_tag->type) {
                case 'tag':      $tags[]       = "<span class=\"label label-default\">{$page_tag->tag}</span>"; break;
                case 'category': $categories[] = "<span class=\"label label-default\">{$page_tag->tag}</span>"; break;
                case 'region':   $regions[]    = "<span class=\"label label-default\">{$page_tag->tag}</span>"; break;
            }
        }

        $edit->SQL = [
            [
                'id'            => $page?->id,
                'title'         => $page?->title,
                'date_publish'  => $page?->date_publish,
                'categories'    => null,
                'tags'          => null,
                'region'        => null,
                'url'           => $page?->url,
                'source_url'    => $page?->source_url,
                'source_author' => $page?->source_author,
                'count_views'   => $page?->count_views,
                'content'       => $page_content?->content,
                'references'    => '',
            ],
        ];

        $references = [];

        foreach ($page_references as $page_reference) {
            $references[] = "<li><a href=\"{$page_reference->url}\" target=\"_blank\">{$page_reference->url}</a></li>";
        }


        $edit->addControl('Заголовок',              "TEXT",       'style="width:600px;"');
        $edit->addControl('Дата публикации',        "DATETIME2");
        $edit->addControl('Категории',              "CUSTOM",     $categories ? implode(' ', $categories) : '');
        $edit->addControl('Теги',                   "CUSTOM",     $tags ? implode(' ', $tags) : '');
        $edit->addControl('Регион',                 "CUSTOM",     $regions ? implode(' ', $regions) : '');
        $edit->addControl('Ссылка',                 "LINK",       'style="width:600px;"');
        $edit->addControl('Источник новости',       "TEXT",       'style="width:300px;"');
        $edit->addControl('Автор',                  "TEXT",       'style="width:300px;"');
        $edit->addControl('Количество просмотров',  "TEXT",       'style="width:300px;"');
        $edit->addControl('Содержимое',             "TEXTAREA",   'style="min-width:600px;min-height:500px"');
        $edit->addControl('Ссылки',                 "CUSTOM",     $references ? "<ul>" . implode('', $references) . "</ul>" : '-');


        $edit->firstColWidth = "200px";
        $edit->save("xajax_savePage(xajax.getFormValues(this.id))");

        return $edit;
    }
}
