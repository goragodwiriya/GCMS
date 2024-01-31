<?php
/**
 * @filesource modules/edocument/controllers/init.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Init;

use Gcms\Gcms;
use Gcms\Login;

/**
 * เริ่มต้นใช้งานโมดูล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * Init Module
     *
     * @param array $modules
     */
    public function init($modules)
    {
        if (!empty($modules)) {
            // login
            $login = Login::isMember();
            // บอกว่าไม่สามารถอัปโหลดได้ไว้ก่อน
            $can_upload = false;
            // ตรวจสอบว่าสามารถอัปโหลดได้หรือไม่ โมดูลใดก็ได้
            foreach ($modules as $module) {
                if ($login && isset($module->can_upload) && $login && in_array($login['status'], $module->can_upload)) {
                    $can_upload = true;
                    break;
                }
            }
            if ($can_upload) {
                Gcms::$member_tabs['edocument'] = array('E-Document', 'Edocument\Member\View', 'icon-edocument');
                Gcms::$member_tabs['edocumentwrite'] = array(null, 'Edocument\Write\View');
                Gcms::$member_tabs['edocumentreport'] = array(null, 'Edocument\Report\View');
            }
        }
    }
}
