<?php
/**
 * @filesource modules/document/controllers/commentwrite.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Commentwrite;

use Kotchasan\Http\Request;
use Web\Login;

/**
 * Frontend Controller for Document module — dedicated comment-edit page
 * (mirrors Board\Replywrite\Controller, but for the shared `comment` table).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * Main controller for the module
     *
     * @param Request $request
     * @param object  $index   Module data
     *
     * @return object
     */
    public function init(Request $request, $index)
    {
        $login = Login::isMember();
        if (!$login) {
            return \Index\Error\Controller::create()->init('unauthorized');
        }

        $index->id = $request->get('id')->toInt();
        if ($index->id === 0) {
            return \Index\Error\Controller::create()->init('document');
        }

        $comment = \Document\Commentwrite\Model::get($index->id);
        if (!$comment || $comment->module_id !== $index->module_id) {
            return \Index\Error\Controller::create()->init('document');
        }

        $article = \Document\Commentwrite\Model::getArticle($comment->index_id);
        if (!$article) {
            return \Index\Error\Controller::create()->init('document');
        }

        // Only the original author or an approver may access the edit form
        $effectiveConfig = \Document\Category\Model::getEffectiveConfig($index->config, $index->module_id, (int) $article->category_id);
        $canManage = Login::checkStatus($login, $effectiveConfig, ['can_approve']);
        if ($comment->member_id !== $login->id && !$canManage) {
            return \Index\Error\Controller::create()->init('unauthorized');
        }

        $index->comment_id = $comment->id;
        $index->article_id = $comment->index_id;
        $index->article_alias = $article->alias;
        $index->article_topic = $article->topic;
        $index->comment_detail = $comment->detail;

        // Render form
        return \Document\Commentwrite\View::create()->render($index);
    }
}
