<?php


/**
 *
 */
class SourcesVideosChannelsLinks extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos_channels_links';


    /**
     * @param int $channel_id
     * @param int $link_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByChannelLink(int $channel_id, int $link_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("channel_id = ?", $channel_id)
                ->where("link_id = ?", $link_id)
        );
    }


    /**
     * Сохранение ссылки
     * @param int        $channel_id
     * @param int        $link_id
     * @return Zend_Db_Table_Row_Abstract
     */
    public function save(int $channel_id, int $link_id): \Zend_Db_Table_Row_Abstract  {

        $link = $this->getRowByChannelLink($channel_id, $link_id);

        if (empty($link)) {
            $link = $this->createRow([
                'channel_id' => $channel_id,
                'link_id'    => $link_id,
            ]);

            $link->save();
        }

        return $link;
    }
}