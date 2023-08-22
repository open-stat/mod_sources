<?php


/**
 *
 */
class SourcesChatsSubscribers extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_subscribers';

    /**
     * @param string   $chat_id
     * @param \DateTime $date_day
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByChatDay(string $chat_id, \DateTime $date_day):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("chat_id = ?", $chat_id)
                ->where("date_day = ?", $date_day->format('Y-m-d'))
        );
    }


    /**
     * @param int       $chat_id
     * @param \DateTime $date_day
     * @param int       $quantity
     * @return void
     */
    public function saveDayQuantity(int $chat_id, \DateTime $date_day, int $quantity): void {

        $chat_day = $this->getRowByChatDay($chat_id, $date_day);

        if (empty($chat_day)) {
            $chat_day = $this->createRow([
                'chat_id'  => $chat_id,
                'date_day' => $date_day->format('Y-m-d'),
                'quantity' => $quantity,
            ]);
            $chat_day->save();
        }
    }
}