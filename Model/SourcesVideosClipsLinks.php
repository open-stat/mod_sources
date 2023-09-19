<?php


/**
 *
 */
class SourcesVideosClipsLinks extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos_clips_links';


    /**
     * @param int $clip_id
     * @param int $link_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByClipLink(int $clip_id, int $link_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("clip_id = ?", $clip_id)
                ->where("link_id = ?", $link_id)
        );
    }


    /**
     * Сохранение ссылки
     * @param int $clip_id
     * @param int $link_id
     * @return Zend_Db_Table_Row_Abstract
     */
    public function save(int $clip_id, int $link_id): \Zend_Db_Table_Row_Abstract  {

        $link = $this->getRowByClipLink($clip_id, $link_id);

        if (empty($link)) {
            $link = $this->createRow([
                'clip_id' => $clip_id,
                'link_id' => $link_id,
            ]);

            $link->save();
        }

        return $link;
    }
}