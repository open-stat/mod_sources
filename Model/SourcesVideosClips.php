<?php


/**
 *
 */
class SourcesVideosClips extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos_clips';


    /**
     * @param string $type
     * @param string $platform_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByTypePlatformId(string $type, string $platform_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("type = ?", $type)
                ->where("platform_id = ?", $platform_id)
        );
    }


    /**
     * @param string $channel_id
     * @param string $platform_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByChannelPlatformId(string $channel_id, string $platform_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("channel_id = ?", $channel_id)
                ->where("platform_id = ?", $platform_id)
        );
    }


    /**
     * @param string     $platform_id
     * @param array|null $options
     * @return Zend_Db_Table_Row_Abstract
     * @throws Exception
     */
    public function save(string $platform_id, array $options = null): \Zend_Db_Table_Row_Abstract {

        if ( ! empty($options['channel_id'])) {
            $clip = $this->getRowByChannelPlatformId($options['channel_id'], $platform_id);

        } elseif ( ! empty($options['type'])) {
            $clip = $this->getRowByTypePlatformId($options['type'], $platform_id);

        } else {
            throw new \Exception('Указаны некорректные данные для сохранения ролика');
        }

        if ( ! empty($options['duration']) && $options['duration'] instanceof \DateInterval) {
            $duration  = ($options['duration']->s);
            $duration += ($options['duration']->i * 60);
            $duration += ($options['duration']->h * 60 * 60);
            $duration += ($options['duration']->d * 60 * 60 * 24);
            $duration += ($options['duration']->m * 60 * 60 * 24 * 30);
            $duration += ($options['duration']->y * 60 * 60 * 24 * 365);
        } else {
            $duration = null;
        }


        if ( ! empty($options['url']) && mb_strlen($options['url']) > 255) {
            $options['url'] = mb_substr($options['url'], 0, 255);
        }

        if (empty($clip)) {
            $clip = $this->createRow([
                'platform_id'           => $platform_id,
                'channel_id'            => $options['channel_id'] ?? null,
                'duration'              => $duration,
                'type'                  => $options['type'] ?? null,
                'url'                   => $options['url'] ?? null,
                'title'                 => $options['title'] ?? null,
                'description'           => $options['description'] ?? null,
                'viewed_count'          => $options['viewed_count'] ?? null,
                'comments_count'        => $options['comments_count'] ?? null,
                'likes_count'           => $options['likes_count'] ?? null,
                'default_lang'          => $options['default_lang'] ?? null,
                'date_platform_created' => $options['date_platform_created'] ?? null,
                'is_load_info_sw'       => $options['is_load_info_sw'] ?? 'N',
            ]);
            $clip->save();

        } else {
            $is_save = false;

            if ( ! empty($duration)                         && $clip->duration              != $duration)                         { $clip->duration              = $duration;                         $is_save = true; }
            if ( ! empty($options['url'])                   && $clip->url                   != $options['url'])                   { $clip->url                   = $options['url'];                   $is_save = true; }
            if ( ! empty($options['title'])                 && $clip->title                 != $options['title'])                 { $clip->title                 = $options['title'];                 $is_save = true; }
            if ( ! empty($options['description'])           && $clip->description           != $options['description'])           { $clip->description           = $options['description'];           $is_save = true; }
            if ( ! empty($options['viewed_count'])          && $clip->viewed_count          != $options['viewed_count'])          { $clip->viewed_count          = $options['viewed_count'];          $is_save = true; }
            if ( ! empty($options['comments_count'])        && $clip->comments_count        != $options['comments_count'])        { $clip->comments_count        = $options['comments_count'];        $is_save = true; }
            if ( ! empty($options['likes_count'])           && $clip->likes_count           != $options['likes_count'])           { $clip->likes_count           = $options['likes_count'];           $is_save = true; }
            if ( ! empty($options['default_lang'])          && $clip->default_lang          != $options['default_lang'])          { $clip->default_lang          = $options['default_lang'];          $is_save = true; }
            if ( ! empty($options['date_platform_created']) && $clip->date_platform_created != $options['date_platform_created']) { $clip->date_platform_created = $options['date_platform_created']; $is_save = true; }

            if ($is_save) {
                $clip->save();
            }
        }

        return $clip;
    }
}