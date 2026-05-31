<?php
/**
 * @filesource modules/board/controllers/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Write;

use Kotchasan\Http\Request;
use Web\Login;

/**
 * Frontend Controller for Board module
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
        // Must be logged in to write/edit
        $login = Login::isMember();
        if (!$login) {
            return \Index\Error\Controller::create()->init('unauthorized');
        }

        // Category ID from request (if any)
        $index->category_id = $request->get('category_id')->toInt();
        // Categories
        $index->categories = \Web\Category::create($index->module_id);

        // Topic ID — 0 = new, >0 = edit existing
        $index->id = $request->get('id')->toInt();

        if ($index->id > 0) {
            // Load existing topic for edit form
            $topic = \Board\Write\Model::get($index->id);
            if (!$topic) {
                return \Index\Error\Controller::create()->init('document');
            }
            // Only original author or moderator may access the edit form
            $canApprove = Login::checkStatus($login, $index->config, ['can_approve']);
            if ($topic->member_id !== $login->id && !$canApprove) {
                return \Index\Error\Controller::create()->init('unauthorized');
            }
            $index->category_id = $topic->category_id;
            $index->subject = $topic->topic;
            $index->detail = $topic->detail;
        } else {
            // New topic, set default values
            $index->subject = '';
            $index->detail = '';
        }

        // Render form
        return \Board\Write\View::create()->render($index);
    }
}
