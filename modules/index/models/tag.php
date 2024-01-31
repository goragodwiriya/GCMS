<?php
/**
 * @filesource modules/index/models/tag.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Tag;

/**
 * Model สำหรับลิสต์รายการ Tag
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * query รายการ tag ทั้งหมด
     * เรียงลำดับตาม count
     *
     * @return array
     */
    public static function all()
    {
        return static::createQuery()
            ->select()
            ->from('tags')
            ->order('count')
            ->toArray()
            ->execute();
    }

    /**
     * ลิสต์รายการ Tag สำหรับใส่ลง select
     *
     * @return array
     */
    public static function toSelect()
    {
        $query = static::createQuery()
            ->select()
            ->from('tags')
            ->order('tag')
            ->toArray();
        $result = array();
        foreach ($query->execute() as $item) {
            $result[$item['tag']] = $item['tag'];
        }
        return $result;
    }

    /**
     * ตรวจสอบและเพิ่ม tag ลงในตารางถ้าเป็น tag ใหม่
     *
     * @param array $tags แอเรย์รายการ tag
     */
    public static function update($tags)
    {
        if (!empty($tags)) {
            $query = static::createQuery()
                ->select('tag')
                ->from('tags')
                ->where(array('tag', $tags));
            $tag_exists = array();
            foreach ($query->execute() as $item) {
                $tag_exists[$item->tag] = $item->tag;
            }
            $model = new static;
            $db = $model->db();
            $table_tags = $model->getTableName('tags');
            foreach ($tags as $item) {
                if ($item != '' && !isset($tag_exists[$item])) {
                    $db->insert($table_tags, array('tag' => $item, 'count' => 0));
                }
            }
        }
    }
}
