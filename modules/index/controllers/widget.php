<?php
/**
 * @filesource modules/index/controllers/widget.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Widget;

/**
 * Controller หลัก สำหรับแสดง frontend ของ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Web\Controller
{
    /**
     * @var mixed
     */
    private $datas;

    /**
     * Controller สำหรับโหลด Widget
     *
     * @return object
     */
    public static function load()
    {
        $obj = new static();
        $obj->datas = [];
        foreach ($obj->loadWidgets() as $item) {
            $className = 'Widgets\\'.ucfirst($item['owner']).'\\Controllers\\Index';
            if (class_exists($className)) {
                $className::widget($obj, $item);
            }
        }
        return $obj;
    }

    /**
     * เพิ่มข้อมูล
     *
     * @param string $position
     * @param string $module
     * @param string $content
     */
    public function add($position, $module, $content)
    {
        $this->datas[] = [
            'position' => $position,
            'module' => $module,
            'detail' => $content
        ];
    }

    /**
     * คืนค่าส่วนเสริมทั้งหมดตามตำแหน่งที่เลือก
     *
     * @param string $position
     *
     * @return string
     */
    public function get($position)
    {
        $result = [];
        foreach ($this->datas as $item) {
            if ($position == $item['position']) {
                $result[] = $item['detail'];
            }
        }
        return empty($result) ? '' : implode("\n", $result);
    }

    /**
     * ค้นหารายการที่ $module
     *
     * @param string $module
     *
     * @return array
     */
    public function findByModule($module)
    {
        return \Kotchasan\ArrayTool::search($this->datas, 'module', $module);
    }

    /**
     * โหลด Widget ที่หมดที่เปิดใช้งานอยู่
     *
     * @return array
     */
    private function loadWidgets()
    {
        return [
            [
                'owner' => 'product'
            ]
        ];
    }
}
