<?php


/**
 *
 */
class SourcesVideos extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos';

    /**
     * @param string $type
     * @param string $channel_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByTypeChannelId(string $type, string $channel_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("type = ?", $type)
                ->where("channel_id = ?", $channel_id)
        );
    }

    /**
     * @param string $type
     * @param string $name
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByTypeName(string $type, string $name):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("type = ?", $type)
                ->where("name = ?", $name)
        );
    }


    /**
     * @param string $channel_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByYtChannelId(string $channel_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("type = 'yt'")
                ->where("channel_id = ?", $channel_id)
        );
    }


    /**
     * @param string     $channel_id
     * @param string     $type
     * @param array|null $options
     * @return Zend_Db_Table_Row_Abstract
     */
    public function save(string $channel_id, string $type = 'yt', array $options = null): \Zend_Db_Table_Row_Abstract {

        $channel = $this->getRowByTypeChannelId($type, $channel_id);

        if (empty($channel)) {
            $channel = $this->createRow([
                'type'                  => $type,
                'channel_id'            => $channel_id,
                'title'                 => $options['title'] ?? null,
                'name'                  => $options['name'] ?? null,
                'description'           => $options['description'] ?? null,
                'subscribers_count'     => $options['subscribers_count'] ?? null,
                'geolocation'           => $options['geolocation'] ?? null,
                'date_platform_created' => $options['date_platform_created'] ?? null,
                'default_lang'          => $options['default_lang'] ?? null,
                'meta_data'             => ! empty($options['meta_data']) ? json_encode($options['meta_data']) : null,
                'is_connect_sw'         => $options['is_connect_sw'] ?? 'N',
            ]);
            $channel->save();

        } else {
            $is_save = false;

            if ( ! empty($options['name'])                  && $channel->name                  != $options['name'])                  { $channel->name                  = $options['name'];                  $is_save = true; }
            if ( ! empty($options['title'])                 && $channel->title                 != $options['title'])                 { $channel->title                 = $options['title'];                 $is_save = true; }
            if ( ! empty($options['description'])           && $channel->description           != $options['description'])           { $channel->description           = $options['description'];           $is_save = true; }
            if ( ! empty($options['subscribers_count'])     && $channel->subscribers_count     != $options['subscribers_count'])     { $channel->subscribers_count     = $options['subscribers_count'];     $is_save = true; }
            if ( ! empty($options['geolocation'])           && $channel->geolocation           != $options['geolocation'])           { $channel->geolocation           = $options['geolocation'];           $is_save = true; }
            if ( ! empty($options['default_lang'])          && $channel->default_lang          != $options['default_lang'])          { $channel->default_lang          = $options['default_lang'];          $is_save = true; }
            if ( ! empty($options['date_platform_created']) && $channel->date_platform_created != $options['date_platform_created']) { $channel->date_platform_created = $options['date_platform_created']; $is_save = true; }

            if ($is_save) {
                $channel->save();
            }
        }

        return $channel;
    }


    /**
     * @param string $name
     * @param string $type
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveEmptyByName(string $name, string $type = 'yt'): \Zend_Db_Table_Row_Abstract {

        $channel = $this->getRowByTypeName($type, $name);

        if (empty($channel)) {
            $channel = $this->createRow([
                'type' => $type,
                'name' => $name,
            ]);
            $channel->save();
        }

        return $channel;
    }
}