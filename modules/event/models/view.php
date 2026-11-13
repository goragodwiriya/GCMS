<?php
/**
 * @filesource modules/event/models/view.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Event\View;

use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;

/**
 * Public single-event model — ports gcms241021 Event\View\Model::get().
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @param Request $request
     * @param object  $index   module data; expects ->module_id
     *
     * @return object|null $index with the event row's fields added, or null
     *                      if not found
     */
    public static function get(Request $request, $index)
    {
        $item = static::createQuery()
            ->select(
                'D.id',
                'D.color',
                'D.topic',
                'D.description',
                'D.keywords',
                'D.detail',
                Sql::DATE('D.begin_date', 'begin_date'),
                Sql::DATE_FORMAT('D.begin_date', '%H:%i', 'from_time'),
                Sql::DATE('D.end_date', 'end_date'),
                Sql::DATE_FORMAT('D.end_date', '%H:%i', 'to_time'),
                'U.name AS writer'
            )
            ->from('event D')
            ->join('user U', ['U.id', 'D.member_id'], 'LEFT')
            ->where([
                ['D.id', $request->get('id')->toInt()],
                ['D.module_id', $index->module_id],
                ['D.published', 1]
            ])
            ->cacheOn()
            ->first();

        if (!$item) {
            return null;
        }

        // the module's title (breadcrumb of the view) before the event's replaces it
        $index->module_topic = $index->topic;
        $index->module_description = (string) $index->description;
        foreach ($item as $key => $value) {
            $index->$key = $value;
        }

        return $index;
    }
}
