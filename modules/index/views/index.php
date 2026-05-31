<?php
/**
 * @filesource modules/index/views/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Index;

use Kotchasan\Template;
use Web\Gcms;

/**
 * Load the HTML page from the index module.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * Render the view
     *
     * @param object $index Module data
     */
    public function render($index)
    {
        // template module/module.html owner/module.html module.html
        try {
            $template = Template::create($index->owner, $index->module, $index->module);
        } catch (\Kotchasan\Exception\TemplateNotFoundException $e) {
            $template = Template::create('', '', 'main');
        }
        // canonical
        if (Gcms::$menu->isHomeMenu($index->index_id)) {
            $index->canonical = WEB_URL.'index.php';
        } else {
            $index->canonical = Gcms::createUrl($index->module);
            // breadcrumb of the page
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->description);
        }
        // add template
        $template->add([
            // content
            '/{DETAIL}/' => Gcms::showDetail(str_replace(['&#x007B;', '&#x007D;'], ['{', '}'], $index->detail), true, false),
            // topic, description
            '/{TOPIC}/' => $index->topic,
            '/{DESCRIPTION}/' => $index->description,
            // Module name
            '/{MODULE}/' => $index->module
        ]);
        // detail
        $index->detail = $template->render();

        // JSON-LD (Index)
        Gcms::$view->setJsonLd(\Index\Jsonld\View::webpage($index));
        // Return
        return $index;
    }
}
