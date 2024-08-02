<?php
/**
 * @filesource modules/document/controllers/tag.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Tag;

use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * หน้าแสดงบทความจาก Tag
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * หน้าแสดงบทความจาก Tag
     *
     * @param Request $request
     * @param object  $module  ข้อมูลโมดูลจาก database
     *
     * @return object
     */
    public function init(Request $request, $module)
    {
        // tag ที่เลือก
        $module->tag = $request->request('tag', isset($module->alias) ? $module->alias : '')->topic();
        // ลิสต์รายการ tag
        $index = \Document\Stories\Model::tags($request, $module);
        if ($index) {
            $index->module = 'document';
            $index->rows = self::$cfg->document_rows;
            $index->cols = self::$cfg->document_cols;
            $index->style = self::$cfg->document_style;
            $index->new_date = 0;
            $index->topic = Language::get('Tags').' '.$index->tag;
            $index->description = $index->topic;
            $index->keywords = $index->topic;
            $index->detail = '';
            return createClass('Document\Stories\View')->index($request, $index);
        }
        // 404
        return createClass('Index\Error\Controller')->init('document');
    }
}
