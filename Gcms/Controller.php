<?php
/**
 * @filesource Gcms/Controller.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms;

/**
 * Controller base class
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * เก็บคลาสของเมนูที่เลือก
     *
     * @var string
     */
    protected $menu;
    /**
     * ข้อความไตเติลบาร์
     *
     * @var string
     */
    protected $title;

    /**
     * init Class
     */
    public function __construct()
    {
        // ค่าเริ่มต้นของ Controller
        $this->title = strip_tags(self::$cfg->web_title);
        $this->menu = 'home';
    }

    /**
     * โหลด permissions ของโมดูลต่างๆ
     *
     * @return array
     */
    public static function getPermissions()
    {
        // permissions เริ่มต้น
        $permissions = \Kotchasan\Language::get('PERMISSIONS');
        // โหลดค่าติดตั้งโมดูล
        $dir = ROOT_PATH.'modules/';
        $f = @opendir($dir);
        if ($f) {
            while (false !== ($text = readdir($f))) {
                if ($text != '.' && $text != '..' && $text != 'index' && $text != 'css' && $text != 'js' && is_dir($dir.$text)) {
                    if (is_file($dir.$text.'/controllers/admin/init.php')) {
                        require_once $dir.$text.'/controllers/admin/init.php';
                        $className = '\\'.ucfirst($text).'\Admin\Init\Controller';
                        if (method_exists($className, 'updatePermissions')) {
                            $permissions = $className::updatePermissions($permissions);
                        }
                    }
                }
            }
            closedir($f);
        }
        return $permissions;
    }

    /**
     * ชื่อเมนูที่เลือก
     *
     * @return string
     */
    public function menu()
    {
        return $this->menu;
    }

    /**
     * ข้อความ title bar
     *
     * @return string
     */
    public function title()
    {
        return $this->title;
    }
}
