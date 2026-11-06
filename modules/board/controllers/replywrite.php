<?php
/**
 * @filesource modules/board/controllers/replywrite.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Replywrite;

use Kotchasan\Http\Request;
use Web\Login;

/**
 * Frontend Controller for Board module — dedicated reply-edit page
 * (mirrors Board\Write\Controller, but for board_r instead of board_q).
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

        $reply = \Board\Replywrite\Model::get($index->id);
        if (!$reply || $reply->module_id !== $index->module_id) {
            return \Index\Error\Controller::create()->init('document');
        }

        $topic = \Board\Replywrite\Model::getTopic($reply->index_id);
        if (!$topic) {
            return \Index\Error\Controller::create()->init('document');
        }

        // Only the original author or a moderator may access the edit form
        $effectiveConfig = \Board\Category\Model::getEffectiveConfig($index->config, $index->module_id, (int) $topic->category_id);
        $canModerate = Login::checkStatus($login, $effectiveConfig, ['moderator']);
        if ($reply->member_id !== $login->id && !$canModerate) {
            return \Index\Error\Controller::create()->init('unauthorized');
        }

        $index->reply_id = $reply->id;
        $index->topic_id = $reply->index_id;
        $index->category_id = (int) $topic->category_id;
        $index->topic_subject = $topic->topic;
        $index->reply_detail = $reply->detail;
        $index->picture = $reply->picture;

        // Image upload settings for the form
        $index->img_upload_type = isset($effectiveConfig['img_upload_type']) && is_array($effectiveConfig['img_upload_type']) ? $effectiveConfig['img_upload_type'] : [];
        $index->img_law = $effectiveConfig['img_law'] ?? 0;

        // Render form
        return \Board\Replywrite\View::create()->render($index);
    }
}
