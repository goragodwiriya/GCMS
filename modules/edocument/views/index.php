<?php
/**
 * @filesource modules/edocument/views/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Edocument\Index;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * E-Document frontend views
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * Render the document listing page.
     *
     * @param \Kotchasan\Http\Request $request
     * @param object $index
     *
     * @return object
     */
    public function render($request, $index)
    {
        if (Gcms::$menu->isHomeMenu($index->index_id)) {
            $index->canonical = WEB_URL.'index.php';
        } else {
            $index->canonical = Gcms::createUrl($index->module);
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->description);
        }

        $senders = \Edocument\Setup\Model::memberNames(array_column($index->items, 'sender_id'));

        $listitem = Template::create($index->owner, $index->module, 'listitem');
        foreach ($index->items as $item) {
            $listitem->add([
                '/{ID}/' => (int) $item->id,
                '/{NO}/' => Text::htmlspecialchars((string) $item->document_no),
                '/{NAME}/' => Text::htmlspecialchars((string) $item->topic),
                '/{EXT}/' => Text::htmlspecialchars((string) $item->ext),
                '/{DETAIL}/' => nl2br(Text::htmlspecialchars((string) $item->detail)),
                '/{SENDER}/' => Text::htmlspecialchars($senders[(int) $item->sender_id] ?? ''),
                '/{DATE}/' => (int) $item->last_update,
                '/{SIZE}/' => Text::formatFileSize((int) $item->size),
                '/{DOWNLOADS}/' => number_format((int) $item->downloads)
            ]);
        }

        $template = Template::create($index->owner, $index->module, $listitem->hasItem() ? 'list' : 'empty');

        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);

        $template->add([
            '/{TOPIC}/' => Text::htmlspecialchars((string) $index->topic),
            '/{DETAIL}/' => Text::htmlspecialchars((string) $index->description),
            '/{LIST}/' => $listitem->render(),
            '/{SPLITPAGE}/' => $uri->pagination($index->total_pages, $index->page),
            '/{MODULE}/' => $index->module
        ]);

        $index->detail = $template->render();

        return $index;
    }
}
