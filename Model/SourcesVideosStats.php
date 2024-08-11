<?php


/**
 *
 */
class SourcesVideosStats extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos_stats';

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
     * @param int      $channel_id
     * @param DateTime $date_day
     * @param array    $stat
     * @return void
     * @throws Exception
     */
    public function save(int $channel_id, \DateTime $date_day, array $stat): void {

        $channel_day = $this->getRowByChannelIdDay($channel_id, $date_day);

        if (empty($channel_day)) {
            try {
                $channel_day = $this->createRow([
                    'channel_id'        => $channel_id,
                    'date_day'          => $date_day->format('Y-m-d'),
                    'subscribers_count' => $stat['subscribers_count'] ?? null,
                    'view_count'        => $stat['view_count'] ?? null,
                    'video_count'       => $stat['video_count'] ?? null,
                ]);
                $channel_day->save();

            } catch (\Zend_Db_Exception $e) {
                if ($e->getPrevious()->getCode() != 23000) {
                    throw $e;
                }
            }
        }
    }
}