<?php
/**
 * @filesource modules/board/views/view.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\View;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Board Frontend Views
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * Render single topic + replies view
     *
     * @param object $index Module data (topic_data must be set)
     *
     * @return object
     */
    public function render($index)
    {
        $topic = $index->topic_data;

        // module breadcrumb
        if (!Gcms::$menu->isHomeMenu($index->index_id)) {
            $menu = Gcms::$menu->getTopLevelMenuByIndexId($index->index_id);
            if ($menu) {
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $menu->menu_text, $menu->menu_tooltip);
            }
        }

        if (!empty($topic->category_id)) {
            $categoryUrl = \Board\Index\Controller::url($index->module, $topic->category_id);
            Gcms::$view->addBreadcrumb($categoryUrl, $topic->category_name, $topic->category_name);
        }

        // page canonical and breadcrumb
        $index->canonical = \Board\Index\Controller::url($index->module, $topic->category_id, $index->id);
        Gcms::$view->addBreadcrumb($index->canonical, $topic->topic, $topic->topic);

        $login = \Web\Login::isMember();
        // Check if user is moderator (can approve)
        $isModerator = \Web\Login::checkStatus($login, $index->config, ['can_approve']);
        $canReply = !!$login;

        // Build replies HTML
        $replyTemplate = Template::create($index->owner, $index->module, 'reply');
        foreach ($index->replies as $reply) {
            $replyTemplate->add([
                '/{REPLY_ID}/' => $reply->id,
                '/{REPLY_SENDER}/' => empty($reply->sender) ? '{LNG_Unknown}' : Text::htmlspecialchars($reply->sender),
                '/{REPLY_DATE}/' => $reply->updated_at,
                '/{REPLY_DETAIL}/' => Gcms::highlighter(nl2br($reply->detail)),
                '/{REPLY_MEMBER_ID}/' => (int) $reply->member_id
            ]);
        }

        $reply_form = '';
        if ($canReply && !$topic->locked) {
            $reply_form = Template::create($index->owner, $index->module, 'replyform');
            $reply_form = $reply_form->render();
        }
        $template = Template::create($index->owner, $index->module, 'view');
        $template->add([
            '/{REPLY_FORM}/' => $reply_form,
            '/{REPLIES}/' => $replyTemplate->hasItem() ? $replyTemplate->render() : '',
            '/{TOPIC}/' => Text::htmlspecialchars($topic->topic),
            '/{DETAIL}/' => Gcms::highlighter(nl2br($topic->detail)),
            '/{SENDER}/' => Text::htmlspecialchars($topic->sender),
            '/{DATE}/' => $topic->created_at,
            '/{CATEGORY}/' => Text::topic($topic->category_name ?? ''),
            '/{CATEGORY_ID}/' => (int) $topic->category_id,
            '/{VISITED}/' => (int) $topic->visited,
            '/{COMMENTS}/' => (int) $topic->comments,
            '/{LOCKED}/' => $topic->locked ? 'unlock' : 'lock',
            '/{PIN}/' => $topic->pin ? 'unpin' : 'pin',
            '/{MODULE_ID}/' => (int) $index->module_id,
            '/{MODERATOR}/' => $isModerator ? 'moderator' : 'hidden',
            '/{ID}/' => $topic->id
        ]);

        $index->detail = $template->render();
        $index->topic = $topic->topic;
        $index->description = mb_substr(strip_tags($topic->detail), 0, 160);

        // ── JSON-LD ───────────────────────────────────────────
        Gcms::$view->setJsonLd(\Board\Jsonld\View::generate($index));

        return $index;
    }
}
