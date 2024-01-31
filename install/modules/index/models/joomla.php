<?php
/**
 * @filesource joomla.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Joomla;

use Gcms\Gcms;

/**
 * Description
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @param $module
     */
    public static function import($module)
    {
        $news = Gcms::$install_modules[$module];
        $model = new static;
        $query = $model->db()->createQuery()
            ->select('id')
            ->from('index')
            ->where(array(
                array('module_id', $news->module_id),
                array('index', 0)
            ));
        $ids = array();
        foreach ($query->execute() as $item) {
            $ids[] = $item->id;
        }
        $gcms_index = $model->getTableName('index');
        $gcms_index_detail = $model->getTableName('index_detail');
        $model->db()->delete($gcms_index, array('id', $ids), 0);
        $model->db()->delete($gcms_index_detail, array(
            array('module_id', $news->module_id),
            array('id', $ids)
        ), 0);
        $query = $model->db()->createQuery()
            ->select('title', 'alias', 'introtext', 'catid', 'created', 'hits', 'state')
            ->from('content')
            ->order('ordering', 'id');
        foreach ($query->execute() as $item) {
            $save = array(
                'module_id' => $news->module_id,
                'topic' => $item->title,
                'relate' => $item->title,
                'detail' => $item->introtext,
                'keywords' => \Kotchasan\Text::oneLine(strip_tags($item->title)),
                'description' => \Kotchasan\Text::oneLine(strip_tags($item->introtext), 255),
                'language' => ''
            );
            $save['id'] = $model->db()->insert($gcms_index, array(
                'module_id' => $news->module_id,
                'index' => 0,
                'category_id' => $item->catid,
                'alias' => $item->alias,
                'visited' => $item->hits,
                'published' => $item->state == 1 ? 1 : 0,
                'member_id' => 1,
                'create_date' => strtotime($item->created),
                'last_update' => strtotime($item->created),
                'published_date' => $item->created,
                'language' => ''
            ));
            $model->db()->insert($gcms_index_detail, $save);
        }
    }
}
