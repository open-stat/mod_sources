<?php


/**
 *
 */
class SourcesChatsMessagesFiles extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_chats_messages_files';

    /**
     * @param int    $message_id
     * @param string $hash
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByMessageHash(int $message_id, string $hash):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("refid = ?", $message_id)
                ->where("hash = ?", $hash)
        );
    }


    /**
     * Сохранение пустого файла-заготовки
     * @param int   $message_id
     * @param array $meta_data
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveFileEmpty(int $message_id, array $meta_data): \Zend_Db_Table_Row_Abstract  {

        $meta_data        = $this->clearMetaData($meta_data);
        $meta_data_encode = json_encode($meta_data);
        $hash             = md5($meta_data_encode);

        $file = $this->getRowByMessageHash($message_id, $hash);

        if (empty($file)) {
            $media_type = ! empty($message['media']) && ! empty($message['media']['_']) ? $message['media']['_'] : null;

            $file = $this->createRow([
                'refid'      => $message_id,
                'hash'       => $hash,
                'media_type' => $media_type,
                'meta_data'  => $meta_data_encode,
                'is_load_sw' => 'N',
            ]);

            $file->save();
        }

        return $file;
    }


    /**
     * @param array $meta_data
     * @return array
     */
    public function clearMetaData(array $meta_data): array {

        if ( ! empty($meta_data['document']) && ! empty($meta_data['document']['thumbs'])) {
            foreach ($meta_data['document']['thumbs'] as $key => $thumb) {
                if ( ! empty($thumb['inflated']) &&
                    ! empty($thumb['inflated']['bytes'])
                ) {
                    unset($meta_data['document']['thumbs'][$key]['inflated']['bytes']);
                }

                if ( ! empty($thumb['_']) && $thumb['_'] == 'photoStrippedSize') {
                    unset($meta_data['document']['thumbs'][$key]);
                }
            }
        }

        if ( ! empty($meta_data['photo']) && ! empty($meta_data['photo']['sizes'])) {
            foreach ($meta_data['photo']['sizes'] as $key => $size) {
                if ( ! empty($size['inflated']) &&
                    ! empty($size['inflated']['bytes'])
                ) {
                    unset($meta_data['photo']['sizes'][$key]['inflated']['bytes']);
                }

                if ( ! empty($size['_']) && $size['_'] == 'photoStrippedSize') {
                    unset($meta_data['photo']['sizes'][$key]);
                }
            }
        }

        if ( ! empty($meta_data['extended_media']) && ! empty($meta_data['extended_media']['thumb'])) {
            unset($meta_data['extended_media']['thumb']);
        }

        if ( ! empty($meta_data['webpage'])) {
            if ( ! empty($meta_data['webpage']['photo']) && ! empty($meta_data['webpage']['photo']['sizes'])) {
                foreach ($meta_data['webpage']['photo']['sizes'] as $key => $size) {
                    if ( ! empty($size['inflated']) &&
                        ! empty($size['inflated']['bytes'])
                    ) {
                        unset($meta_data['webpage']['photo']['sizes'][$key]['inflated']['bytes']);
                    }

                    if ( ! empty($size['_']) && $size['_'] == 'photoStrippedSize') {
                        unset($meta_data['webpage']['photo']['sizes'][$key]);
                    }
                }
            }

            if ( ! empty($meta_data['webpage']['document']) && ! empty($meta_data['webpage']['document']['thumbs'])) {
                foreach ($meta_data['webpage']['document']['thumbs'] as $key => $thumb) {
                    if ( ! empty($thumb['inflated']) &&
                        ! empty($thumb['inflated']['bytes'])
                    ) {
                        unset($meta_data['webpage']['document']['thumbs'][$key]['inflated']['bytes']);
                    }

                    if ( ! empty($thumb['_']) && $thumb['_'] == 'photoStrippedSize') {
                        unset($meta_data['webpage']['document']['thumbs'][$key]);
                    }
                }
            }

            if ( ! empty($meta_data['webpage']['cached_page']['documents']) && ! empty($meta_data['webpage']['cached_page']['documents'])) {
                foreach ($meta_data['webpage']['cached_page']['documents'] as $key => $document) {

                    if ( ! empty($document['thumbs'])) {
                        foreach ($document['thumbs'] as $key2 => $thumb) {
                            if ( ! empty($thumb['_']) && $thumb['_'] == 'photoStrippedSize') {
                                unset($meta_data['webpage']['cached_page']['documents'][$key]['thumbs'][$key2]);
                            }
                        }
                    }
                }
            }

            if ( ! empty($meta_data['webpage']['cached_page']['photos']) && ! empty($meta_data['webpage']['cached_page']['photos'])) {
                foreach ($meta_data['webpage']['cached_page']['photos'] as $key => $photo) {

                    if ( ! empty($photo['sizes'])) {
                        foreach ($photo['sizes'] as $key2 => $size) {
                            if ( ! empty($size['_']) && $size['_'] == 'photoStrippedSize') {
                                unset($meta_data['webpage']['cached_page']['photos'][$key]['sizes'][$key2]);
                            }
                        }
                    }
                }
            }

            if ( ! empty($meta_data['webpage']['attributes']) && ! empty($meta_data['webpage']['attributes'])) {
                foreach ($meta_data['webpage']['attributes'] as $key => $attr) {

                    if ( ! empty($attr['documents'])) {
                        foreach ($attr['documents'] as $key2 => $doc) {

                            if ( ! empty($doc['thumbs'])) {
                                foreach ($doc['thumbs'] as $key3 => $thumb) {

                                    if ( ! empty($thumb['_']) && $thumb['_'] == 'photoStrippedSize') {
                                        unset($meta_data['webpage']['attributes'][$key]['documents'][$key2]['thumbs'][$key3]);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ( ! empty($meta_data['webpage']['title'])) {
                unset($meta_data['webpage']['title']);
            }

            if ( ! empty($meta_data['webpage']['description'])) {
                unset($meta_data['webpage']['description']);
            }
        }

        return $meta_data;
    }
}