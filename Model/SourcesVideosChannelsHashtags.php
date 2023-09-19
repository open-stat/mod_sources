<?php


/**
 *
 */
class SourcesVideosChannelsHashtags extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos_channels_hashtags';


    /**
     * @param int $channel_id
     * @param int $hashtag_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByChannelHashtag(int $channel_id, int $hashtag_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("channel_id = ?", $channel_id)
                ->where("hashtag_id = ?", $hashtag_id)
        );
    }


    /**
     * Сохранение хэштега
     * @param int $channel_id
     * @param int $hashtag_id
     * @return Zend_Db_Table_Row_Abstract
     */
    public function save(int $channel_id, int $hashtag_id): \Zend_Db_Table_Row_Abstract  {

        $hashtag = $this->getRowByChannelHashtag($channel_id, $hashtag_id);

        if (empty($hashtag)) {
            $hashtag = $this->createRow([
                'channel_id' => $channel_id,
                'hashtag_id' => $hashtag_id,
            ]);

            $hashtag->save();
        }

        return $hashtag;
    }
}