<?php
/**
 * @filesource widgets/related/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Related\Models;

/**
 * Widget Related Model – ดึงบทความที่เกี่ยวข้องกัน
 * อ้างอิงจากตาราง index_tag (บทความที่มี tag ตรงกันอย่างน้อยหนึ่งรายการ)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูล
     *
     * @param int $id
     * @param int $count
     *
     * @return object
     */
    public static function getRelated($id, $count)
    {
        $id = (int) $id;
        $count = max(1, (int) $count);
        if ($id <= 0) {
            return false;
        }
        // อ่านโมดูล จาก id ของ บทความ
        $index = static::createQuery()
            ->select('Q.id', 'Q.module_id')
            ->from('index Q')
            ->where([
                ['Q.id', $id],
                ['Q.index', '0']
            ])
            ->cacheOn()
            ->first();
        if (!$index) {
            return false;
        }
        // tags ของบทความปัจจุบัน
        $terms = array_map(function ($item) {
            return $item->tag;
        }, static::createQuery()
                ->select('tag')
                ->from('index_tag')
                ->where(['index_id', $id])
                ->cacheOn()
                ->fetchAll());
        if (empty($terms)) {
            return false;
        }
        // ดึงบทความที่มี tag ร่วมกันทั้งหมดใน query เดียว (ใช้ index idx_tag)
        // แล้วค่อยจัดลำดับ "ใกล้บทความปัจจุบัน" ใน PHP ซึ่งถูกกว่า UNION + ตัวแปร session มาก
        $candidates = static::createQuery()
            ->select('Q.id', 'D.topic', 'Q.alias', 'Q.picture', 'Q.comment_date', 'Q.published_date', 'D.description', 'Q.comments', 'Q.visited')
            ->from('index Q')
            ->join('index_detail D', [['D.id', 'Q.id'], ['D.module_id', 'Q.module_id']])
            ->join('index_tag T', ['T.index_id', 'Q.id'])
            ->where([
                ['Q.module_id', (int) $index->module_id],
                ['Q.published', '1'],
                ['Q.published_date', '<=', date('Y-m-d')],
                ['Q.index', '0'],
                ['Q.id', '!=', $id],
                ['D.language', [LANGUAGE, '']],
                ['T.tag', $terms]
            ])
            ->groupBy('Q.id')
            ->orderBy('Q.published_date', 'DESC')
            ->limit(100)
            ->cacheOn()
            ->fetchAll();
        if (empty($candidates)) {
            return false;
        }
        // แบ่งเป็นฝั่งใหม่กว่า/เก่ากว่า แล้วสลับหยิบฝั่งละรายการ (พฤติกรรมเดียวกับของเดิม)
        $newer = [];
        $older = [];
        foreach ($candidates as $item) {
            if ($item->id > $id) {
                $newer[] = $item;
            } else {
                $older[] = $item;
            }
        }
        // ใหม่กว่า: วันที่ใกล้สุดก่อน (ASC), เก่ากว่า: วันที่ล่าสุดก่อน (DESC ตรงกับลำดับ query อยู่แล้ว)
        usort($newer, function ($a, $b) {
            return strcmp($a->published_date, $b->published_date) ?: $a->id - $b->id;
        });
        $result = [];
        $rows = max(count($newer), count($older));
        for ($i = 0; $i < $rows && count($result) < $count; $i++) {
            if (isset($newer[$i])) {
                $result[] = $newer[$i];
            }
            if (count($result) < $count && isset($older[$i])) {
                $result[] = $older[$i];
            }
        }
        // เรียงผลลัพธ์สุดท้ายตามวันที่เผยแพร่ (เหมือนเดิม)
        usort($result, function ($a, $b) {
            return strcmp($a->published_date, $b->published_date) ?: $a->id - $b->id;
        });
        return $result;
    }
}
