<?php
/**
 * @filesource modules/portfolio/views/lists.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Portfolio\Lists;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Portfolio listing page — card grid, matches the storefront/document
 * public-page rendering convention (Template::create($index->owner, ...)).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * @param \Kotchasan\Http\Request $request
     * @param object $index
     *
     * @return object
     */
    public function render($request, $index)
    {
        $index->canonical = \Portfolio\Index\Controller::url($index->module, 0, $request->get('tag')->topic());
        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);

        $cols = self::columnsToGridSize($index->cols);

        $listitem = Template::create($index->owner, $index->module, 'listitem');
        foreach ($index->items as $item) {
            $thumb = $this->imageUrl($item->image);
            $topic = Text::htmlspecialchars($item->title);
            $listitem->add([
                '/{ID}/' => (int) $item->id,
                '/{URL}/' => \Portfolio\Index\Controller::url($index->module, $item->id),
                '/{TOPIC}/' => $topic,
                '/{SRC}/' => $thumb,
                '/{TAGS}/' => \Portfolio\View\View::tags($index, $item->keywords),
                '/{DATE}/' => $item->created_at,
                '/{VISITED}/' => number_format((int) $item->visited)
            ]);
        }

        $template = Template::create($index->owner, $index->module, 'list');
        $template->add([
            '/{LIST}/' => $listitem->render(),
            '/{TAG}/' => Text::htmlspecialchars($index->tag),
            '/{TOTAL}/' => $index->total,
            '/{COLS}/' => $cols,
            '/{TOPIC}/' => Text::htmlspecialchars($index->topic ?? ''),
            '/{DESCRIPTION}/' => Text::htmlspecialchars($index->description ?? ''),
            '/{DETAIL}/' => Gcms::highlighter($index->detail),
            '/{PAGINATION}/' => $uri->pagination($index->total_pages, $index->page)
        ]);

        $index->detail = $template->render();

        return $index;
    }

    /**
     * @param string $image filename stored in DATA_FOLDER/portfolio/
     *
     * @return string
     */
    private function imageUrl($image)
    {
        if ($image !== '' && is_file(ROOT_PATH.DATA_FOLDER.'portfolio/'.$image)) {
            return WEB_URL.DATA_FOLDER.'portfolio/'.$image;
        }

        return WEB_URL.'images/no-image.webp';
    }
}
