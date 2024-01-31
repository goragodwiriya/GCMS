<?php
/**
 * @filesource modules/index/controllers/installing.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Installing;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=installing
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * เพิ่มโมดูลแบบที่สามารถใช้ซ้ำได้
     *
     * @param Request $request
     *
     * @return JSON
     */
    public function index(Request $request)
    {
        // session, referer, can_config
        if ($request->initSession() && $request->isReferer() && Login::checkPermission(Login::adminAccess(), 'can_config')) {
            // โมดูลหรือ Widget ที่จะติดตั้ง
            $class = $request->post('module')->filter('\\a-zA-Z');
            if (class_exists($class) && method_exists($class, 'install')) {
                define('MAIN_INIT', 'installing');
                $result = createClass($class)->install($request);
            }
            $fieldset = Html::create('fieldset', array(
                'title' => Language::get('Install')
            ));
            if (empty($result)) {
                $fieldset = Html::create('aside', array(
                    'class' => 'error',
                    'innerHTML' => Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed')
                ));
            } else {
                if (!empty($result['content'])) {
                    $fieldset->add('ol', array(
                        'class' => 'install',
                        'innerHTML' => $result['content']
                    ));
                }
                if (empty($result['error'])) {
                    $fieldset->add('div', array(
                        'class' => 'message',
                        'innerHTML' => Language::get('<strong>Successfully installed.</strong> Now you can run these modules installed already. (Please refresh)')
                    ));
                } elseif ($result['error'] == 'module_already_exists') {
                    $fieldset->add('div', array(
                        'class' => 'error',
                        'innerHTML' => Language::get('Can not install this module. Because this module is already installed. If you want to install this module, you will need to rename installed module to a different name. (This module is to use this name only).')
                    ));
                }
            }
            $ret = array(
                'location' => empty($result['location']) ? '' : $result['location'],
                'content' => $fieldset->render()
            );
            // คืนค่า JSON
            echo json_encode($ret);
        }
    }
}
