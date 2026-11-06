<?php
/**
 * @filesource modules/video/views/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Video\Index;

use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Video listing page — ports gcms241021 Video\Index\View onto the current
 * public-page rendering convention (Template::create($index->owner, ...)).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public function render(Request $request, $index)
    {
        // breadcrumb ของโมดูล
        if (Gcms::$menu->isHomeMenu($index->index_id)) {
            $index->canonical = WEB_URL.'index.php';
        } else {
            $index->canonical = \Video\Index\Controller::url($index->module);
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->description);
        }

        $cols = self::columnsToGridSize($index->cols ?? 4);

        $listitem = Template::create($index->owner, $index->module, 'listitem');
        foreach ($index->items as $item) {
            if (is_file(ROOT_PATH.DATA_FOLDER.'video/'.$item->youtube.'.jpg')) {
                $picture = WEB_URL.DATA_FOLDER.'video/'.$item->youtube.'.jpg';
            } else {
                $picture = WEB_URL.'images/no-image.webp';
            }

            $listitem->add([
                '/{ID}/' => (int) $item->id,
                '/{TOPIC}/' => Text::htmlspecialchars($item->topic),
                '/{DESCRIPTION}/' => Text::htmlspecialchars($item->description),
                '/{PICTURE}/' => $picture,
                '/{YOUTUBE}/' => $item->youtube,
                '/{DATE}/' => Date::format($item->last_update, 'd M Y'),
                '/{DATEISO}/' => date('Y-m-d', (int) $item->last_update),
                '/{VIEWS}/' => number_format((int) $item->views),
                '/{COLS}/' => $cols
            ]);
        }

        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);

        $template = Template::create($index->owner, $index->module, $listitem->hasItem() ? 'list' : 'empty');
        $template->add([
            '/{TOPIC}/' => Text::htmlspecialchars((string) $index->topic),
            '/{DESCRIPTION}/' => Text::htmlspecialchars((string) $index->description),
            '/{DETAIL}/' => Gcms::highlighter($index->detail),
            '/{LIST}/' => $listitem->render(),
            '/{COLS}/' => $cols,
            '/{PAGINATION}/' => $uri->pagination($index->total_pages, $index->page),
            '/{MODULE}/' => $index->module,
            '/{MODULE_ID}/' => (int) $index->module_id
        ]);

        $index->detail = $template->render();

        return $index;
    }
}
