<?php
/**
 * @filesource modules/document/models/module.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Module;

use Gcms\Gcms;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * อ่านข้อมูลโมดูล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลโมดูล
     *
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public static function get(Request $request, $index)
    {
        if (empty($index->module_id)) {
            return null;
        } else {
            // หมวดหมู่
            $categories = array();
            $value = $request->request('cat')->filter('\d,');
            if (!empty($value)) {
                foreach (explode(',', $value) as $v) {
                    $v = (int) $v;
                    if ($v > 0) {
                        $categories[$v] = $v;
                    }
                }
            }
            // Model
            $model = new static;
            // จำนวนหมวดในโมดูล
            $query = $model->db()->createQuery()
                ->selectCount()->from('category')
                ->where(array(
                    array('module_id', 'D.module_id'),
                    array('published', '1')
                ));
            $select = array(
                'D.detail',
                'D.keywords',
                'D.description',
                array($query, 'categories')
            );
            $query = $model->db()->createQuery()
                ->from('index_detail D')
                ->join('index I', 'INNER', array(array('I.index', 1), array('I.id', 'D.id'), array('I.module_id', 'D.module_id'), array('I.language', 'D.language')))
                ->where(array(
                    array('I.module_id', (int) $index->module_id),
                    array('D.language', array(Language::name(), ''))
                ))
                ->cacheOn()
                ->toArray();
            if (count($categories) == 1) {
                // มีการเลือกหมวด เพียงหมวดเดียว
                $select[] = 'C.category_id';
                $select[] = 'C.topic category';
                $select[] = 'C.detail category_description';
                $select[] = 'C.icon';
                $select[] = 'C.published';
                $select[] = 'C.config';
                $query->join('category C', 'LEFT', array(
                    array('C.category_id', (int) reset($categories)),
                    array('C.module_id', 'D.module_id'),
                    array('C.published', '1')
                ));
            }
            $result = $query->first($select);
            if ($result) {
                foreach ($result as $key => $value) {
                    switch ($key) {
                        case 'category':
                        case 'category_description':
                        case 'icon':
                            $index->$key = Gcms::ser2Str($value);
                            break;
                        case 'config':
                            try {
                                $config = unserialize($value);
                                if (is_array($config) && empty($config['can_reply'])) {
                                    $index->can_reply = array();
                                }
                            } catch (\Throwable $th) {
                            }
                            break;
                        default:
                            $index->$key = $value;
                            break;
                    }
                }
                if (!empty($categories) && empty($index->category_id)) {
                    $index->category_id = $categories;
                }
            }
            return $index;
        }
    }

    /**
     * อ่านข้อมูลความคิดเห็นสำหรับการแก้ไข
     *
     * @param int    $id    ID ที่แก้ไข
     * @param object $index ข้อมูลโมดูล
     *
     * @return object|null ข้อมูล (Object), null ถ้าไม่พบ
     */
    public static function getCommentById($id, $index)
    {
        $model = new static;
        $search = $model->db()->createQuery()
            ->from('comment R')
            ->join('index Q', 'INNER', array(array('Q.id', 'R.index_id'), array('Q.index', 0)))
            ->join('index_detail D', 'INNER', array(array('D.id', 'Q.id'), array('D.module_id', 'Q.module_id'), array('D.language', array(Language::name(), ''))))
            ->join('category C', 'LEFT', array(array('C.category_id', 'Q.category_id'), array('C.module_id', 'Q.module_id')))
            ->where(array(array('R.id', $id), array('R.module_id', (int) $index->module_id)))
            ->toArray()
            ->first('R.id', 'R.index_id', 'R.detail', 'R.module_id', 'R.member_id', 'C.config', 'C.category_id', 'C.topic category', 'D.topic');
        if ($search) {
            $search['module'] = $index;
            $search['config'] = @unserialize($search['config']);
            return (object) $search;
        }
        return null;
    }
}
