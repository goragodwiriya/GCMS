<?php
/**
 * @filesource modules/board/views/replywrite.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Replywrite;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Board Frontend Views — dedicated reply-edit page
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * Render the reply-edit form
     *
     * @param object $index Module data (reply_id/topic_id/etc must be set)
     *
     * @return object
     */
    public function render($index)
    {
        // module breadcrumb
        $menu = Gcms::$menu->getTopLevelMenuByIndexId($index->index_id);
        if ($menu) {
            Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $menu->menu_text, $menu->menu_tooltip);
        }

        // page canonical and breadcrumb — canonicalizes to the topic's own
        // permalink (same convention as Board\Write\View for topic edits),
        // since the edit form itself isn't meant to be indexed separately.
        $index->canonical = \Board\Index\Controller::url($index->module, $index->category_id, $index->topic_id);
        Gcms::$view->addBreadcrumb($index->canonical, $index->topic_subject, $index->topic_subject);

        $uploadEnabled = !empty($index->img_upload_type);
        $picture = empty($index->picture) ? [] : [[
            'url' => WEB_URL.DATA_FOLDER.'board/'.$index->picture,
            'name' => $index->picture
        ]];

        $template = Template::create($index->owner, $index->module, 'replywrite');
        $template->add([
            '/{TOPIC}/' => Text::htmlspecialchars($index->topic_subject),
            '/{TOPIC_URL}/' => $index->canonical,
            '/{DETAIL}/' => $index->reply_detail,
            '/{MODULE_ID}/' => (int) $index->module_id,
            '/{MODULE}/' => Text::htmlspecialchars($index->module),
            '/{ID}/' => (int) $index->reply_id,
            '/{TOPIC_ID}/' => (int) $index->topic_id,
            '/{HAS_UPLOAD}/' => $uploadEnabled ? 'has-upload' : 'hidden',
            '/{IMG_TYPES}/' => implode(', ', $index->img_upload_type),
            '/{IMG_LAW}/' => \Kotchasan\Language::get('IMG_LAW', '', $index->img_law),
            '/{PICTURE_JSON}/' => Text::htmlspecialchars(json_encode($picture))
        ]);

        $index->detail = $template->render();
        $index->topic = $index->topic_subject;

        return $index;
    }
}
