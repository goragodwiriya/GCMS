<?php
/**
 * @filesource Widgets/Board/Views/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Board\Views;

use Board\Index\Controller;

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
     *
     * @return array
     */
    public static function renderItem($index, $item, $valid_date)
    {
        if ($item->picture != '' && is_file(ROOT_PATH.DATA_FOLDER.'board/thumb-'.$item->picture)) {
            $thumb = WEB_URL.DATA_FOLDER.'board/thumb-'.$item->picture;
        } else {
            $thumb = WEB_URL.(isset($index->default_icon) ? $index->default_icon : 'modules/board/img/board-icon.png');
        }
        if ($item->create_date > $valid_date && $item->comment_date == 0) {
            $icon = 'new';
        } elseif ($item->last_update > $valid_date || $item->comment_date > $valid_date) {
            $icon = 'update';
        } else {
            $icon = '';
        }
        return array(
            '/{URL}/' => Controller::url($index->module, $item->id),
            '/{TOPIC}/' => $item->topic,
            '/{DATE}/' => $item->create_date,
            '/{VISITED}/' => $item->visited,
            '/{UID}/' => $item->member_id,
            '/{SENDER}/' => $item->displayname,
            '/{STATUS}/' => $item->status,
            '/{PICTURE}/' => $thumb,
            '/{ICON}/' => $icon
        );
    }
}
