<?php


/**
 *
 */
class SourcesVideosSubscribers extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos_subscribers';

    /**
     * @param int       $channel_id
     * @param \DateTime $date_day
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByChannelIdDay(int $channel_id, \DateTime $date_day):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("channel_id = ?", $channel_id)
                ->where("date_day = ?", $date_day->format('Y-m-d'))
        );
    }


    /**
     * @param int       $channel_id
     * @param \DateTime $date_day
     * @param int       $quantity
     * @return void
     */
    public function saveDayQuantity(int $channel_id, \DateTime $date_day, int $quantity): void {

        $chat_day = $this->getRowByChannelIdDay($channel_id, $date_day);

        if (empty($chat_day)) {
            $chat_day = $this->createRow([
                'channel_id' => $channel_id,
                'date_day'   => $date_day->format('Y-m-d'),
                'quantity'   => $quantity,
            ]);
            $chat_day->save();
        }
    }
}