<?php


/**
 *
 */
class SourcesTags extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_sources_tags';


    /**
     * @param string $tag
     * @param string $type
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByTag(string $tag, string $type = 'tag'):? \Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("tag = ?", $tag)
            ->where('type= ?', $type);

        return $this->fetchRow($select);
    }


    /**
     * @param string $tag
     * @param string $type
     * @return Zend_Db_Table_Row_Abstract
     */
    public function saveTag(string $tag, string $type = 'tag'): \Zend_Db_Table_Row_Abstract {

        $tag_row = $this->getRowByTag($tag, $type);

        if ( ! $tag_row) {
            $tag_row = $this->createRow([
                'type' => $type,
                'tag'  => $tag,
            ]);
            $tag_row->save();
        }

        return $tag_row;
    }
}