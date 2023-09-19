<?php


/**
 *
 */
class SourcesVideosClipsComments extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_videos_clips_comments';


    /**
     * @param int    $clip_id
     * @param string $comment_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByClipCommentId(int $clip_id, string $comment_id):? \Zend_Db_Table_Row_Abstract {

        return $this->fetchRow(
            $this->select()
                ->where("clip_id = ?", $clip_id)
                ->where("platform_id = ?", $comment_id)
        );
    }


    /**
     * @param int        $clip_id
     * @param string     $comment_id
     * @param int        $user_id
     * @param array|null $options
     * @return Zend_Db_Table_Row_Abstract
     */
    public function save(int $clip_id, string $comment_id, int $user_id, array $options = null): \Zend_Db_Table_Row_Abstract {

        $comment = $this->getRowByClipCommentId($clip_id, $comment_id);

        if (empty($comment)) {
            $comment = $this->createRow([
                'clip_id'               => $clip_id,
                'platform_id'           => $comment_id,
                'user_id'               => $user_id,
                'content'               => $options['content'] ?? null,
                'reply_to_id'           => $options['reply_to_id'] ?? null,
                'likes_count'           => $options['likes_count'] ?? null,
                'dislike_count'         => $options['dislike_count'] ?? null,
                'date_platform_created' => $options['date_platform_created'] ?? null,
                'date_platform_modify'  => $options['date_platform_modify'] ?? null,
            ]);
            $comment->save();
        }

        return $comment;
    }
}