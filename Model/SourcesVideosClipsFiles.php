<?php


/**
 *
 */
class SourcesVideosClipsFiles extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos_clips_files';

    /**
     * @param int    $clip_id
     * @param string $hash
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByClipHash(int $clip_id, string $hash):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("refid = ?", $clip_id)
                ->where("hash = ?", $hash)
        );
    }


    /**
     * Сохранение пустого файла-заготовки
     * @param int   $clip_id
     * @param array $meta_data
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveFileEmpty(int $clip_id, array $meta_data): \Zend_Db_Table_Row_Abstract  {

        $meta_data_encode = json_encode($meta_data);
        $hash             = md5($meta_data_encode);
        $file = $this->getRowByClipHash($clip_id, $hash);

        if (empty($file)) {
            $file = $this->createRow([
                'refid'      => $clip_id,
                'hash'       => $hash,
                'meta_data'  => $meta_data_encode,
                'is_load_sw' => 'N',
            ]);

            $file->save();
        }

        return $file;
    }
}