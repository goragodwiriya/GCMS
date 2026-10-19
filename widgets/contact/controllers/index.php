<?php
/**
 * @filesource widgets/contact/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Contact\Controllers;

use Kotchasan\Language;
use Kotchasan\Template;

/**
 * Widget: contact form. Submits to api/index/contactsend, which notifies every
 * admin (status = 1) across all their channels (e-mail, LINE, Telegram). The
 * submit is handled by widgets/contact/script.js (loaded on every frontend
 * page). Ports gcms241021 Widgets\Contact onto the current widget convention.
 *
 * {WIDGET_CONTACT}
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * No automatic position injection; rendered via the {WIDGET_CONTACT} tag.
     *
     * @param object $obj
     * @param array  $item
     *
     * @return void
     */
    public static function widget($obj, $item)
    {
    }

    /**
     * Display Widget
     *
     * @param array $query_string  (unused)
     *
     * @return string
     */
    public function get($query_string)
    {
        // Labels resolved in PHP (not via {LNG_} tokens) so the widget renders
        // correctly both on the frontend and in the Designer preview.
        $template = Template::createFromFile(ROOT_PATH.'widgets/contact/views/contact.html');
        $template->add([
            '/{ID}/' => uniqid('ct'),
            '/{ACTION}/' => WEB_URL.'api/index/contactsend',
            '/{L_NAME}/' => Language::get('Name'),
            '/{L_EMAIL}/' => Language::get('Email'),
            '/{L_PHONE}/' => Language::get('Phone'),
            '/{L_TOPIC}/' => Language::get('Topic'),
            '/{L_MESSAGE}/' => Language::get('Message'),
            '/{L_SEND}/' => Language::get('Send')
        ]);

        return $template->render();
    }
}
