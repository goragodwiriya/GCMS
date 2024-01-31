<?php
/**
 * @filesource modules/document/models/admin/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Admin\Home;

use Kotchasan\Language;

/**
 * Model สำหรับหน้า Dashboard
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * get populate document
     *
     * @return array
     */
    public static function popularpage()
    {
        $model = new static;
        return $model->db()->createQuery()
            ->select('D.topic', 'I.visited_today', 'M.module', 'I.id')
            ->from('index I')
            ->join('modules M', 'INNER', array(array('M.id', 'I.module_id'), array('M.owner', 'document')))
            ->join('index_detail D', 'INNER', array(array('D.id', 'I.id'), array('D.module_id', 'I.module_id'), array('D.language', array(Language::name(), ''))))
            ->order('I.visited_today DESC', 'I.visited DESC')
            ->limit(12)
            ->toArray()
            ->execute();
    }
}
