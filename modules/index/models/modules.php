<?php
/**
 * @filesource modules/index/models/modules.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Modules;

use Kotchasan\Language;

/**
 * คลาสสำหรับโหลดรายการโมดูลที่ติดตั้งแล้วทั้งหมด จากฐานข้อมูลของ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{

    /**
     * โหลดโมดูลที่ติดตั้งแล้ว
     *
     * @param string $owner เจ้าของโมดูล
     *
     * @return array
     */
    public static function getInstalledModules($owner)
    {
        $query = \Kotchasan\Model::createQuery()
            ->select('I.id', 'I.module_id', 'M.module', 'D.topic', 'M.config')
            ->from('modules M')
            ->join('index I', ['I.module_id', 'M.id'])
            ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'I.module_id']])
            ->where([
                ['M.owner', $owner],
                ['I.index', 1],
                ['D.language', ['', Language::name()]]
            ])
            ->cacheOn();
        $result = [];
        foreach ($query->fetchAll() as $item) {
            $item->config = $item->config ? json_decode($item->config) : new \stdClass();
            $result[] = $item;
        }
        return $result;
    }
}
