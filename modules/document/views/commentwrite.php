<?php
/**
 * @filesource modules/document/views/commentwrite.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Commentwrite;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Document Frontend Views — dedicated comment-edit page
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * Render the comment-edit form
     *
     * @param object $index Module data (comment_id/article_id/etc must be set)
     *
     * @return object
     */
    public function render($index)
    {
        // module breadcrumb
        if (!Gcms::$menu->isHomeMenu($index->index_id)) {
            $menu = Gcms::$menu->getTopLevelMenuByIndexId($index->index_id);
            if ($menu) {
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $menu->menu_text, $menu->menu_tooltip);
            }
        }

        // page canonical and breadcrumb — canonicalizes to the article's own
        // permalink (same convention as Board\Replywrite\View), since the
        // edit form itself isn't meant to be indexed separately.
        $index->canonical = \Document\Index\Controller::url($index->module, $index->article_alias, $index->article_id);
        Gcms::$view->addBreadcrumb($index->canonical, $index->article_topic, $index->article_topic);

        $template = Template::create($index->owner, $index->module, 'commentwrite');
        $template->add([
            '/{TOPIC}/' => Text::htmlspecialchars($index->article_topic),
            '/{ARTICLE_URL}/' => $index->canonical,
            '/{DETAIL}/' => $index->comment_detail,
            '/{MODULE_ID}/' => (int) $index->module_id,
            '/{MODULE}/' => Text::htmlspecialchars($index->module),
            '/{ID}/' => (int) $index->comment_id,
            '/{ARTICLE_ID}/' => (int) $index->article_id
        ]);

        $index->detail = $template->render();
        $index->topic = $index->article_topic;

        return $index;
    }
}
