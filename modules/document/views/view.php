<?php
/**
 * @filesource modules/document/views/vew.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\View;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Document Frontend Views
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * Render single article page
     *
     * @param object $index Module data (article data attached)
     *
     * @return object
     */
    public function render($index)
    {
        $article = $index->article;
        $login = \Web\Login::isMember();
        $moduleConfig = isset($index->config) ? $index->config : (object) [];
        $effectiveConfig = \Document\Category\Model::getEffectiveConfig($moduleConfig, $index->module_id, (int) $article->category_id);
        // checkStatus() returns null for guests unconditionally (no login = no
        // status to check), so a guest-allowed can_view (e.g. the [-1, 1]
        // default) must also be checked explicitly via isGuestAllowed().
        $canView = ((int) ($index->viewing ?? 0) === 1)
        || \Web\Login::checkStatus($login, $effectiveConfig, ['can_view']) !== null
        || $this->isGuestAllowed($effectiveConfig, 'can_view');

        if (!$canView) {
            $index->topic = $article->topic;
            $index->description = $article->description;
            $index->detail = '<div class="notice-restricted"><div class="notice-restricted-icon icon-lock"></div><p>{LNG_Members Only}</p></div>';
            return $index;
        }

        // Featured image
        if (!empty($article->picture) && file_exists(ROOT_PATH.DATA_FOLDER.'document/'.$article->picture)) {
            $image = WEB_URL.DATA_FOLDER.'document/'.$article->picture;
            $index->image_src = $image;
        } else {
            $image = '';
        }
        // module breadcrumb
        if (!Gcms::$menu->isHomeMenu($index->index_id)) {
            $menu = Gcms::$menu->getTopLevelMenuByIndexId($index->index_id);
            if ($menu) {
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $menu->menu_text, $menu->menu_tooltip);
            }
        }

        // category breadcrumb
        if (!empty($article->category_id)) {
            $categoryUrl = \Document\Index\Controller::url($index->module, $article->category_id);
            Gcms::$view->addBreadcrumb($categoryUrl, $article->category_name, $article->category_name);
        }

        // page canonical and breadcrumb
        $index->canonical = \Document\Index\Controller::url($index->module, $index->article->alias, $index->id, false);
        Gcms::$view->addBreadcrumb($index->canonical, $article->topic, $article->topic);

        $isApprover = \Web\Login::checkStatus($login, $effectiveConfig, ['can_approve']);
        $canReply = ($login && (
            $isApprover
            || \Web\Login::checkStatus($login, $effectiveConfig, ['can_reply'])
        )) || $this->isGuestAllowed($effectiveConfig, 'can_reply');

        // Build comments HTML
        $commentTemplate = Template::create($index->owner, $index->module, 'comment');
        foreach ($index->comments as $comment) {
            $canEditComment = $login && ($isApprover || (int) $comment->member_id === (int) $login->id);
            $commentEditUrl = WEB_URL.'index.php?'.http_build_query([
                'module' => $index->module.'-commentwrite',
                'id' => $comment->id
            ]);
            $commentTemplate->add([
                '/{COMMENT_ID}/' => $comment->id,
                '/{COMMENT_SENDER}/' => empty($comment->sender) ? '{LNG_Unknown}' : Text::htmlspecialchars($comment->sender),
                '/{COMMENT_DATE}/' => $comment->updated_at,
                '/{COMMENT_DETAIL}/' => Gcms::highlighter(nl2br($comment->detail)),
                '/{COMMENT_MEMBER_ID}/' => (int) $comment->member_id,
                '/{COMMENT_CAN_EDIT}/' => $canEditComment ? '' : 'hidden',
                '/{COMMENT_EDIT_URL}/' => $commentEditUrl,
                '/{APPROVER}/' => $isApprover ? 'approver' : 'hidden'
            ]);
        }

        $comment_form = '';
        if ($canReply) {
            $comment_form = Template::create($index->owner, $index->module, 'commentform');
            $comment_form = $comment_form->render();
        }

        // view.html template
        $template = Template::create($index->owner, $index->module, 'view');

        $imageHtml = $image ? '<section class="article-image"><img src="'.$image.'" alt="'.Text::htmlspecialchars($article->topic).'"></section>' : '';

        $template->add([
            '/{COMMENT_LIST}/' => $commentTemplate->hasItem() ? $commentTemplate->render() : '',
            '/{COMMENT_FORM}/' => $comment_form,
            '/{ID}/' => $article->id,
            '/{TOPIC}/' => Text::htmlspecialchars($article->topic),
            '/{IMAGE}/' => $imageHtml,
            '/{IMAGE_URL}/' => $image,
            '/{DETAIL}/' => Gcms::highlighter($article->detail),
            '/{DESCRIPTION}/' => Text::htmlspecialchars($article->description),
            '/{DATE}/' => $article->published_date,
            '/{CATEGORY}/' => Text::htmlspecialchars((string) $article->category_name),
            '/{CATEGORY_ID}/' => $article->category_id,
            '/{VISITED}/' => number_format($article->visited),
            '/{MODULE}/' => $index->module,
            '/{TAGS}/' => Text::htmlspecialchars($article->tags),
            '/{MODULE_ID}/' => (int) $index->module_id,
            '/{COMMENTS}/' => (int) $article->comments,
            '/{APPROVER}/' => $isApprover ? 'approver' : 'hidden'
        ]);

        $index->detail = $template->render();

        // ── JSON-LD ───────────────────────────────────────────
        Gcms::$view->setJsonLd(\Document\Jsonld\View::generate($index));

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
