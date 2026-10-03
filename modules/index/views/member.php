<?php
/**
 * @filesource modules/index/views/member.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Member;

use Gcms\Login;
use Kotchasan\Template;

/**
 * module=dologin
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * หน้า login
     *
     * @param Request $request
     *
     * @return object
     */
    public function render($request, $module)
    {
        // Load template
        $template = Template::create('', '', $module);
        $template->add([
            //'/{TOPIC}/' => $index->topic,
            //'/{DETAIL}/' => $index->description
        ]);
        // Return
        return (object) [
            'topic' => self::$cfg->web_title,
            'detail' => $template->render(),
            'description' => self::$cfg->web_description,
            'keywords' => self::$cfg->web_description
        ];
    }
}
