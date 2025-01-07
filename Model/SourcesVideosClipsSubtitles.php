<?php


/**
 *
 */
class SourcesVideosClipsSubtitles extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos_clips_subtitles';

    /**
     * @param int    $clip_id
     * @param string $lang
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByClipLang(int $clip_id, string $lang):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("clip_id = ?", $clip_id)
                ->where("lang = ?", $lang)
        );
    }


    /**
     * @param int    $clip_id
     * @param string $lang
     * @param array  $options
     * @return Zend_Db_Table_Row_Abstract
     */
    public function save(int $clip_id, string $lang, array $options): \Zend_Db_Table_Row_Abstract {

        $clip_subtitles = $this->getRowByClipLang($clip_id, $lang);

        if (empty($clip_subtitles)) {
            $clip_subtitles = $this->createRow([
                'clip_id'      => $clip_id,
                'lang'         => $lang,
                'content_time' => ! empty($options['content_time']) ? json_encode($options['content_time']) : null,
            ]);
            $clip_subtitles->save();
        }

        return $clip_subtitles;
    }
}