<?php
/**
 * @filesource widgets/board/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Board\Controllers;

use Kotchasan\Text;
use Web\Gcms;

/**
 * Board Widget Controller
 *
 * Renders the latest topics list as HTML
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index
{
    /**
     * Get widget HTML
     *
     * @param array $query_string  Supports: limit (1-20), module_id
     *
     * @return string HTML
     */
    public static function get($query_string)
    {
        if (empty($query_string['module'])) {
            return '';
        }

        $index = Gcms::$module->findByModule($query_string['module']);
        if (!$index) {
            return '';
        }
        $limit = isset($query_string['limit']) ? (int) $query_string['limit'] : 5;
        $limit = max(1, min(20, $limit));

        $items = \Widgets\Board\Models\Index::getLatest($index->module_id, $limit);

        if (empty($items)) {
            return '<div class="widget-board-latest"><div class="widget-empty">{LNG_No topics yet.}</div></div>';
        }

        $html = '<div class="widget-board-latest listview">';
        foreach ($items as $item) {
            $url = \Board\Index\Controller::url($index->module, $item->category_id, $item->id);
            $topic = Text::htmlspecialchars($item->topic);
            $sender = Text::htmlspecialchars($item->sender);
            $date = \Kotchasan\Date::format($item->created_at, 'timeago');
            $comments = (int) $item->comments;
            $html .= '<a class="item" href="'.$url.'">';
            $html .= '<h3 class="icon-comments" title="'.$topic.'">&nbsp;'.$topic.'</h3>';
            $html .= '<span class="widget-meta">';
            $html .= '<span style="color:var(--status'.$item->status.')">'.$sender.'</span>';
            $html .= '<span title="{LNG_Created}">'.$date.'</span>';
            $html .= '<span class="icon-visited" title="{LNG_Replies}">'.$comments.'</span>';
            $html .= '</span>';
            $html .= '</a>';
        }
        $html .= '</div>';

        return $html;
    }
}
