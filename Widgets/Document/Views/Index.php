<?php
/**
 * @filesource Widgets/Document/Views/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Document\Views;

use Document\Index\Controller;

/**
 * Controller หลัก สำหรับแสดงผล Widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Gcms\View
{
    /**
     * แสดงผลรายการ
     *
     * @param object $index
     * @param object $item
     * @param int    $valid_date
     * @param int    $cols
     *
     * @return array
     */
    public static function renderItem($index, $item, $valid_date, $cols)
    {
        if (!empty($item->picture) && is_file(ROOT_PATH.DATA_FOLDER.'document/'.$item->picture)) {
            $thumb = WEB_URL.DATA_FOLDER.'document/'.$item->picture;
        } elseif (!empty($index->icon) && is_file(ROOT_PATH.DATA_FOLDER.'document/'.$index->icon)) {
            $thumb = WEB_URL.DATA_FOLDER.'document/'.$index->icon;
        } else {
            $thumb = WEB_URL.(isset($index->default_icon) ? $index->default_icon : 'modules/document/img/document-icon.png');
        }
        if ($item->create_date > $valid_date && $item->comment_date == 0) {
            $icon = 'new';
        } elseif ($item->last_update > $valid_date) {
            $icon = 'update';
        } elseif ($item->comment_date > $valid_date) {
            $icon = 'update';
        } else {
            $icon = '';
        }
        return array(
            '/{URL}/' => Controller::url($index->module, $item->alias, $item->id),
            '/{TOPIC}/' => $item->topic,
            '/{DETAIL}/' => $item->description,
            '/{DATE}/' => $item->create_date,
            '/{UID}/' => $item->member_id,
            '/{SENDER}/' => $item->sender,
            '/{STATUS}/' => $item->status,
            '/{COMMENTS}/' => number_format($item->comments),
            '/{VISITED}/' => number_format($item->visited),
            '/{PICTURE}/' => $thumb,
            '/{ICON}/' => $icon
        );
    }
}
