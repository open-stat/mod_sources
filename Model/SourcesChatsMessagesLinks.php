<?php


/**
 *
 */
class SourcesChatsMessagesLinks extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_messages_links';

    /**
     * @param int $message_id
     * @param int $link_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByMessageUrl(int $message_id, int $link_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("message_id = ?", $message_id)
                ->where("link_id = ?", $link_id)
        );
    }


    /**
     * Сохранение ссылки
     * @param int        $message_id
     * @param int        $link_id
     * @param array|null $options
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveLink(int $message_id, int $link_id, array $options = null): \Zend_Db_Table_Row_Abstract  {

        $link = $this->getRowByMessageUrl($message_id, $link_id);

        if (empty($link)) {
            $link = $this->createRow([
                'message_id' => $message_id,
                'link_id'    => $link_id,
                'offset'    => $options['offset'] ?? null,
                'length'    => $options['length'] ?? null,
            ]);

            $link->save();
        }

        return $link;
    }
}