<?php
namespace Core2\Mod\Sources\Index\Telegram;


/**
 *
 */
class Messages extends Common {


    /**
     * Получение сообщений в
     * @param string $peer_name
     * @return array
     */
    public function getMessages(string $peer_name): array {

        $madeline_messages = $this->getMadeline()->messages->getHistory([
            'peer'        => $peer_name,
            'offset_id'   => 0,
            'offset_date' => 0,
            'add_offset'  => 0,
            'limit'       => 100, //Количество постов, которые вернет клиент
            'max_id'      => 0, //Максимальный id поста
            'min_id'      => 0, //Минимальный id поста - использую для пагинации, при  0 возвращаются последние посты.
            'hash'        => 0
        ]);

        return $madeline_messages;

        $messages = [];

        foreach(array_reverse($madeline_messages['messages']) as $message) {
            $messages[] = [
                'id'       => $message['id'],
                'date'     => date('d.m.Y H:i:s', $message['date']),
                'message'  => $message['message'],
                'views'    => $message['views'],
                'forwards' => $message['forwards'],
            ];
        }

        return $messages;
    }
}