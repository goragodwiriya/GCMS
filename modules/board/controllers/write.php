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
        $login = Login::isMember();

        // Category ID from request (if any)
        $index->category_id = $request->get('category_id')->toInt();
        $effectiveConfig = \Board\Category\Model::getEffectiveConfig($index->config, $index->module_id, $index->category_id);
        // Categories
        $index->categories = \Web\Category::create($index->module_id);

        // Topic ID — 0 = new, >0 = edit existing
        $index->id = $request->get('id')->toInt();

        if ($index->id > 0) {
            if (!$login) {
                return \Index\Error\Controller::create()->init('unauthorized');
            }
            // Load existing topic for edit form
            $topic = \Board\Write\Model::get($index->id);
            if (!$topic) {
                return \Index\Error\Controller::create()->init('document');
            }
            // Only original author or moderator may access the edit form
            $effectiveConfig = \Board\Category\Model::getEffectiveConfig($index->config, $index->module_id, $topic->category_id);
            $canModerate = Login::checkStatus($login, $effectiveConfig, ['moderator']);
            if ($topic->member_id !== $login->id && !$canModerate) {
                return \Index\Error\Controller::create()->init('unauthorized');
            }
            $index->category_id = $topic->category_id;
            $index->subject = $topic->topic;
            $index->detail = $topic->detail;
            $index->picture = $topic->picture;
        } else {
            $canPost = Login::checkStatus($login, $effectiveConfig, ['can_post']);
            $guestAllowed = $this->isGuestAllowed($effectiveConfig, 'can_post');
            if (!$canPost && !$guestAllowed) {
                return \Index\Error\Controller::create()->init('unauthorized');
            }
            // New topic, set default values
            $index->subject = '';
            $index->detail = '';
            $index->picture = null;
        }

        // Image upload settings for the form (shared by new + edit)
        $index->img_upload_type = isset($effectiveConfig['img_upload_type']) && is_array($effectiveConfig['img_upload_type']) ? $effectiveConfig['img_upload_type'] : [];
        $index->img_law = $effectiveConfig['img_law'] ?? 0;

        // Render form
        return \Board\Write\View::create()->render($index);
    }

    /**
     * Check whether guest status (-1) is allowed for a permission key.
     *
     * @param array|object $config The effective configuration for the category/module
     * @param string $key The permission key to check (e.g., 'can_post')
     *
     * @return bool True if guest is allowed, false otherwise
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
