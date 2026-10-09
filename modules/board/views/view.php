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
        $moduleConfig = isset($index->config) ? $index->config : (object) [];
        $effectiveConfig = \Board\Category\Model::getEffectiveConfig($moduleConfig, $index->module_id, (int) $topic->category_id);
        // checkStatus() returns null for guests unconditionally (no login = no
        // status to check), so a guest-allowed can_view (e.g. the [-1, 1]
        // default) must also be checked explicitly via isGuestAllowed().
        $canView = ((int) ($index->viewing ?? 0) === 1)
        || \Web\Login::checkStatus($login, $effectiveConfig, ['can_view']) !== null
        || $this->isGuestAllowed($effectiveConfig, 'can_view');

        if (!$canView) {
            $index->detail = '<div class="notice-restricted"><div class="notice-restricted-icon icon-lock"></div><p>{LNG_Members Only}</p></div>';
            $index->topic = $topic->topic;
            $index->description = mb_substr(strip_tags($topic->detail), 0, 160);
            return $index;
        }

        // Check if user is moderator (can approve)
        $isModerator = \Web\Login::checkStatus($login, $effectiveConfig, ['moderator']);
        $canEditTopic = $login && ($isModerator || (int) $topic->member_id === (int) $login->id);
        $editUrl = WEB_URL.'index.php?'.http_build_query([
            'module' => $index->module.'-write',
            'category_id' => (int) $topic->category_id,
            'id' => $topic->id
        ]);
        $canReply = ($login && (
            \Web\Login::checkStatus($login, $effectiveConfig, ['moderator'])
            || \Web\Login::checkStatus($login, $effectiveConfig, ['can_reply'])
        )) || $this->isGuestAllowed($effectiveConfig, 'can_reply');

        // Build replies HTML
        $replyTemplate = Template::create($index->owner, $index->module, 'reply');
        foreach ($index->replies as $reply) {
            $replyPicture = empty($reply->picture) ? '' : '<div class="figure"><img class="board-reply-picture" src="'.WEB_URL.DATA_FOLDER.'board/'.$reply->picture.'" alt=""></div>';
            $canEditReply = $login && ($isModerator || (int) $reply->member_id === (int) $login->id);
            $replyEditUrl = WEB_URL.'index.php?'.http_build_query([
                'module' => $index->module.'-replywrite',
                'id' => $reply->id
            ]);
            $replyTemplate->add([
                '/{REPLY_ID}/' => $reply->id,
                '/{REPLY_SENDER}/' => empty($reply->sender) ? '{LNG_Unknown}' : Text::htmlspecialchars($reply->sender),
                '/{REPLY_DATE}/' => $reply->updated_at,
                '/{REPLY_DETAIL}/' => Gcms::highlighter(nl2br($reply->detail)),
                '/{REPLY_PICTURE}/' => $replyPicture,
                '/{REPLY_MEMBER_ID}/' => (int) $reply->member_id,
                '/{REPLY_CAN_EDIT}/' => $canEditReply ? '' : 'hidden',
                '/{REPLY_EDIT_URL}/' => $replyEditUrl,
                // Per-item scope needs its own copies of these — reply.html's
                // delete button (data-id="delete-{MODULE_ID}-{ID}-{REPLY_ID}")
                // and its {MODERATOR} visibility class reference them, but the
                // outer $template->add() below runs in a separate substitution
                // pass and never reaches into this already-rendered fragment.
                '/{ID}/' => $topic->id,
                '/{MODULE_ID}/' => (int) $index->module_id,
                '/{MODERATOR}/' => $isModerator ? 'moderator' : 'hidden'
            ]);
        }

        $reply_form = '';
        if ($canReply && !$topic->locked) {
            $reply_form = Template::create($index->owner, $index->module, 'replyform');
            $reply_form = $reply_form->render();
        }
        // getEffectiveConfig() always returns an array
        $uploadTypes = isset($effectiveConfig['img_upload_type']) && is_array($effectiveConfig['img_upload_type']) ? $effectiveConfig['img_upload_type'] : [];
        $picture = empty($topic->picture) ? '' : '<div class="figure"><img class="board-view-picture" src="'.WEB_URL.DATA_FOLDER.'board/'.$topic->picture.'" alt="'.Text::htmlspecialchars($topic->topic).'"></div>';

        $template = Template::create($index->owner, $index->module, 'view');
        $template->add([
            '/{REPLY_FORM}/' => $reply_form,
            '/{REPLIES}/' => $replyTemplate->hasItem() ? $replyTemplate->render() : '',
            '/{TOPIC}/' => Text::htmlspecialchars($topic->topic),
            '/{PICTURE}/' => $picture,
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
            '/{CAN_EDIT}/' => $canEditTopic ? '' : 'hidden',
            '/{EDIT_URL}/' => $editUrl,
            '/{ID}/' => $topic->id,
            '/{HAS_UPLOAD}/' => $uploadTypes ? 'has-upload' : 'hidden',
            '/{IMG_TYPES}/' => implode(', ', $uploadTypes),
            '/{IMG_LAW}/' => \Kotchasan\Language::get('IMG_LAW', '', $effectiveConfig['img_law'] ?? 0)
        ]);

        $index->detail = $template->render();
        $index->topic = $topic->topic;
        $index->description = mb_substr(strip_tags($topic->detail), 0, 160);

        // ── JSON-LD ───────────────────────────────────────────
        Gcms::$view->setJsonLd(\Board\Jsonld\View::generate($index));

        return $index;
    }

    /**
     * Check whether guest status (-1) is allowed for a permission key.
     */
    private function isGuestAllowed($config, string $key): bool
    {
        if (is_object($config) && property_exists($config, $key)) {
            $allowed = $config->{$key};
        } elseif (is_array($config) && array_key_exists($key, $config)) {
            $allowed = $config[$key];
        } else {
            return false;
        }

        if (is_array($allowed)) {
            foreach ($allowed as $status) {
                if ((int) $status === -1) {
                    return true;
                }
            }
        }

        return (int) $allowed === -1;
    }
}
