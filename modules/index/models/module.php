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

use Kotchasan\Database\Sql;

/**
 * คลาสสำหรับโหลดรายการโมดูลที่ติดตั้งแล้วทั้งหมด จากฐานข้อมูลของ GCMS
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
    public $by_owner;
    /**
     * รายการโมดูล เรียงลำดับตาม module
     *
     * @var array
     */
    public $by_module = [];

    /**
     * อ่านรายชื่อโมดูลและไดเร็คทอรี่ของโมดูลทั้งหมดที่ติดตั้งไว้
     *
     * @param string                 $dir
     * @param \Index\Menu\Controller $menu
     */
    public function __construct($dir, $menu)
    {
        $this->by_owner = ['index' => []];
        // โมดูลที่ติดตั้ง
        $f = @opendir($dir);
        if ($f) {
            while (false !== ($owner = readdir($f))) {
                if ($owner != '.' && $owner != '..' && $owner != 'index' && $owner != 'js' && $owner != 'css') {
                    $this->by_owner[$owner] = [];
                }
            }
            closedir($f);
        }
        // โหลดโมดูลที่ติดตั้งแล้ว และสามารถใช้งานได้
        $modules = $this->getModules();
        // ใส่ข้อมูลโมดูลลงในเมนู
        if ($menu instanceof \Index\Menu\Controller) {
            foreach ($menu->getMenus() as $item) {
                if (isset($modules[$item->index_id])) {
                    $item->module = $modules[$item->index_id];
                    $this->by_module[$item->module->module] = null;
                }
            }
        }
        // เรียงลำดับข้อมูลโมดูลตาม module และ owner
        foreach ($modules as $item) {
            $this->by_module[$item->module] = $item;
            $this->by_owner[$item->owner][] = $item;
        }
    }

    /**
     * โหลดโมดูลที่ติดตั้งแล้ว และสามารถใช้งานได้
     *
     * @return array
     */
    private function getModules()
    {
        $query = \Kotchasan\Model::createQuery()
            ->select('I.id index_id', 'I.module_id', 'M.module', 'M.owner', 'M.config', 'D.topic', 'D.keywords', 'D.description')
            ->from('modules M')
            ->join('index I', 'INNER', [
                ['I.index', 1],
                ['I.module_id', 'M.id'],
                ['I.published', 1],
                ['I.language', [\Kotchasan\Language::name(), '']]
            ])
            ->join('index_detail D', 'INNER', [['D.id', 'I.id'], ['D.module_id', 'I.module_id'], ['D.language', 'I.language']])
            ->cacheOn()
            ->toArray();
        $result = [];
        foreach ($query->execute() as $item) {
            $config = @unserialize($item['config']);
            if (is_array($config)) {
                foreach ($config as $key => $value) {
                    $item[$key] = $value;
                }
            }
            unset($item['config']);
            $result[$item['index_id']] = (object) $item;
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

    /**
     * อ่านรายละเอียดของโมดูล
     * topic, details, keywords, description
     * ไม่พบคืนค่า null
     *
     * @param object $index
     * @param string $page  ถ้าไม่ระบุ (default null) อ่านข้อมูลหลักของโมดูล
     *
     * @return object
     */
    public static function getDetails($index, $page = null)
    {
        $query = \Kotchasan\Model::createQuery()
            ->from('index I')
            ->join('index_detail D', 'INNER', [['D.id', 'I.id'], ['D.module_id', 'I.module_id'], ['D.language', 'I.language']])
            ->cacheOn()
            ->toArray();
        if (empty($page)) {
            $query->where([
                ['I.id', (int) $index->index_id],
                ['I.module_id', (int) $index->module_id],
                ['I.page', '']
            ]);
        } else {
            $query->where([
                ['I.module_id', (int) $index->module_id],
                ['I.page', $page]
            ])
                ->order('I.page DESC');
        }
        $search = $query->first('D.topic', 'D.detail', 'D.keywords', 'D.description');
        if ($search) {
            $index->topic = $search['topic'];
            $index->detail = $search['detail'];
            $index->keywords = $search['keywords'];
            $index->description = $search['description'];
            return $index;
        }
        return null;
    }
}
