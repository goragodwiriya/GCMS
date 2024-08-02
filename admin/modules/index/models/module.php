<?php
/**
 * @filesource modules/index/models/module.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Module;

use Kotchasan\ArrayTool;
use Kotchasan\Database\Sql;

/**
 * คลาสสำหรับโหลดรายการโมดูลที่ติดตั้งแล้วทั้งหมด จากฐานข้อมูลของ GCMS (Admin)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model
{
    /**
     * รายการโมดูล เรียงลำดับตาม owner
     *
     * @var array
     */
    public $by_owner = [];
    /**
     * รายการโมดูล เรียงลำดับตาม module
     *
     * @var array
     */
    public $by_module = [];

    /**
     * อ่านรายชื่อโมดูลและไดเร็คทอรี่ของโมดูลทั้งหมดที่ติดตั้งไว้
     *
     * @param string $dir
     */
    public function __construct($dir)
    {
        // โมดูลที่ติดตั้ง
        $f = @opendir($dir);
        if ($f) {
            while (false !== ($owner = readdir($f))) {
                if ($owner != '.' && $owner != '..' && $owner != 'js' && $owner != 'css') {
                    $this->by_owner[$owner] = [];
                }
            }
            closedir($f);
        }
        // โหลดโมดูลที่ติดตั้งแล้ว จาก DB
        foreach ($this->getModules() as $item) {
            $this->by_module[$item->module] = $item;
            $this->by_owner[$item->owner][] = $item;
        }
    }

    /**
     * โหลดโมดูลที่ติดตั้งแล้ว และ config
     *
     * @return array
     */
    private function getModules()
    {
        // โหลดโมดูลที่ติดตั้ง เรียงตามลำดับโฟลเดอร์
        $query = \Kotchasan\Model::createQuery()
            ->select('id', 'module', 'owner', 'config')
            ->from('modules')
            ->where(['owner', '!=', 'index'])
            ->order('owner')
            ->toArray();
        $result = [];
        foreach ($query->execute() as $item) {
            $item = ArrayTool::unserialize($item['config'], $item);
            unset($item['config']);
            $result[] = (object) $item;
        }
        return $result;
    }

    /**
     * อ่านข้อมูลโมดูลและค่ากำหนด จาก DB
     * คืนค่าข้อมูลโมดูล (Object) ไม่พบคืนค่า false
     *
     * @param string $owner
     * @param string $module
     * @param int    $module_id
     *
     * @return object|false
     */
    public static function getModuleWithConfig($owner, $module = '', $module_id = 0)
    {
        if (empty($module) && empty($module_id)) {
            $where = ['owner', Sql::strValue($owner)];
        } elseif (empty($owner) && empty($module)) {
            $where = ['id', (int) $module_id];
        } elseif (empty($owner) && empty($module_id)) {
            $where = ['module', Sql::strValue($module)];
        } elseif (empty($module_id)) {
            $where = [['module', Sql::strValue($module)], ['owner', Sql::strValue($owner)]];
        } else {
            $where = [['id', (int) $module_id], ['owner', Sql::strValue($owner)]];
        }
        $model = new \Kotchasan\Model();
        $search = $model->db()->createQuery()
            ->from('modules')
            ->where($where)
            ->cacheOn()
            ->toArray()
            ->first('id', 'module', 'owner', 'config');
        if ($search) {
            $config = @unserialize($search['config']);
            if (is_array($config)) {
                $config['id'] = $search['id'];
                $config['module'] = $search['module'];
                $config['owner'] = $search['owner'];
                return (object) $config;
            } else {
                unset($search['config']);
                return (object) $search;
            }
        }
        return null;
    }
}
