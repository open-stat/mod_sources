<?php


/**
 *
 */
class SourcesVideosClipsHashtags extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos_clips_hashtags';

    /**
     * @param int $clip_id
     * @param int $hashtag_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByClipHashtag(int $clip_id, int $hashtag_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("clip_id = ?", $clip_id)
                ->where("hashtag_id = ?", $hashtag_id)
        );
    }


    /**
     * Сохранение хэштега
     * @param int $clip_id
     * @param int $hashtag_id
     * @return Zend_Db_Table_Row_Abstract
     */
    public function save(int $clip_id, int $hashtag_id): \Zend_Db_Table_Row_Abstract  {

        $hashtag = $this->getRowByClipHashtag($clip_id, $hashtag_id);

        if (empty($hashtag)) {
            $hashtag = $this->createRow([
                'clip_id'    => $clip_id,
                'hashtag_id' => $hashtag_id,
            ]);

            $hashtag->save();
        }

        return $hashtag;
    }
}