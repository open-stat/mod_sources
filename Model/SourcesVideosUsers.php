<?php


/**
 *
 */
class SourcesVideosUsers extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos_users';


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
     * @param string     $platform_id
     * @param string     $type
     * @param array|null $options
     * @return Zend_Db_Table_Row_Abstract
     */
    public function save(string $platform_id, string $type = 'yt', array $options = null): \Zend_Db_Table_Row_Abstract {

        $user = $this->getRowByTypePlatformId($type, $platform_id);

        if (empty($user)) {
            $user = $this->createRow([
                'platform_id'        => $platform_id,
                'type'               => $type,
                'name'               => $options['name'] ?? null,
                'profile_url'        => $options['profile_url'] ?? null,
                'profile_avatar_url' => $options['profile_avatar_url'] ?? null,
            ]);
            $user->save();
        }

        return $user;
    }
}