<?php


/**
 *
 */
class SourcesVideosHashtags extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos_hashtags';

    /**
     * @param string $hashtag
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByHashtag(string $hashtag):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("hashtag = ?", $hashtag)
        );
    }


    /**
     * Сохранение хэштега
     * @param string $hashtag
     * @return Zend_Db_Table_Row_Abstract
     */
    public function save(string $hashtag): \Zend_Db_Table_Row_Abstract  {

        $hashtag     = mb_substr($hashtag, 0, 255);
        $hashtag_row = $this->getRowByHashtag($hashtag);

        if (empty($hashtag_row)) {
            $hashtag_row = $this->createRow([
                'hashtag' => $hashtag,
            ]);

            $hashtag_row->save();
        }

        return $hashtag_row;
    }
}