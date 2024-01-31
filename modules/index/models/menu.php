<?php
/**
 * @filesource modules/index/models/menu.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Menu;

use Kotchasan\Database\Sql;
use Kotchasan\Language;

/**
 * คลาสสำหรับโหลดรายการเมนูจากฐานข้อมูลของ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * รายการเมนูเรียงลำดับตามระดับของเมนู
     *
     * @var array
     */
    public $menus_by_pos = array();
    /**
     * รายการเมนูทั้งหมด
     *
     * @var array
     */
    public $menus = array();

    /**
     * โหลดเมนูทั้งหมดเรียงตามลำดับเมนู (รายการแรกคือหน้า Home)
     */
    public function __construct()
    {
        parent::__construct();
        // โหลดเมนูทั้งหมดเรียงตามลำดับเมนู (รายการแรกคือหน้า Home)
        $lng = array(Language::name(), '');
        $select = array(
            'U.index_id',
            'U.parent',
            'U.level',
            'U.menu_text',
            'U.menu_tooltip',
            'U.accesskey',
            'U.menu_url',
            'U.menu_target',
            'U.alias',
            'U.published',
            Sql::create("(CASE U.`parent` WHEN 'MAINMENU' THEN 0 WHEN 'SIDEMENU' THEN 1 ELSE 2 END ) AS `pos`")
        );
        $query = $this->db()->createQuery()
            ->select($select)
            ->from('menus U')
            ->where(array(array('U.language', $lng), array('U.parent', '!=', '')))
            ->order(array('pos', 'U.parent', 'U.menu_order'))
            ->cacheOn()
            ->toArray();
        // จัดลำดับเมนูตามระดับของเมนู
        foreach ($query->execute() as $i => $item) {
            $menu_obj = (object) $item;
            $this->menus[] = $menu_obj;
            if ($item['level'] == 0) {
                $this->menus_by_pos[$item['parent']]['toplevel'][$i] = $menu_obj;
            } else {
                $this->menus_by_pos[$item['parent']][$toplevel[$item['level'] - 1]][$i] = $menu_obj;
            }
            $toplevel[$item['level']] = $i;
        }
    }
}
