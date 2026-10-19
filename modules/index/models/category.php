<?php
/**
 * @filesource modules/index/models/category.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Category;

use Kotchasan;

/**
 * API Category Model
 *
 * Handles category table operations
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get all category for editing
     *
     * @param string $type
     *
     * @return array
     */
    public static function get($type)
    {
        $languages = \Index\Languages\Model::getLanguageColumns();
        $query = static::createQuery()
            ->select('category_id', 'topic', 'language')
            ->from('category')
            // GCMS : หมวดระดับเว็บไซต์คือ module_id = 0 (โมดูลอย่าง personnel ก็ใช้ type 'department')
            ->where([['type', $type], ['module_id', 0]]);
        $data = [];
        foreach ($query->fetchAll() as $item) {
            if (!isset($data[$item->category_id])) {
                $data[$item->category_id]['id'] = $item->category_id;
                foreach ($languages as $lng) {
                    $data[$item->category_id][$lng] = $item->topic;
                }
            }
            if (!isset($data[$item->category_id][$item->language])) {
                $data[$item->category_id][$item->language] = $item->topic;
            }
        }
        if (empty($data)) {
            $data[0] = ['id' => 1];
            foreach ($languages as $lng) {
                $data[0][$lng] = '';
            }
        }
        return array_values($data);
    }

    /**
     * Save category data
     *
     * @param string $type
     * @param array $save
     * @param bool $multiLanguage ยังไม่มีผลจนกว่า UNIQUE ของตาราง category จะรวม language (ดูด้านล่าง)
     *
     * @return void
     */
    public static function save($type, $save, $multiLanguage)
    {
        $db = Kotchasan\DB::create();

        // ลบเฉพาะหมวดระดับเว็บไซต์ ไม่งั้นหมวด type เดียวกันของโมดูลอื่น (แผนกของ personnel) หายไปด้วย
        $db->delete('category', [['type', $type], ['module_id', 0]], 0);

        $saved = [];
        foreach ($save as $item) {
            // UNIQUE ของตาราง category ในฐานข้อมูลของไซต์ยังเป็น (module_id, type, category_id) ไม่มี language
            // หมวดหนึ่งจึงเก็บได้แถวเดียว ใช้แถวแรกที่ controller ส่งมา (ภาษาหลัก) และให้ใช้กับทุกภาษา
            // ถ้าเปลี่ยน UNIQUE เป็น (module_id, type, category_id, language) แล้ว ใช้ key category_id|language
            // และคง language ของแถวไว้เมื่อ $multiLanguage เป็น true ได้ (แบบ GCMS 15)
            if (isset($saved[$item['category_id']])) {
                continue;
            }
            $saved[$item['category_id']] = true;
            $item['language'] = '';
            // config/detail/icon เป็น mediumtext NOT NULL ไม่มีค่าเริ่มต้น
            $db->insert('category', $item + [
                'module_id' => 0,
                'config' => '{}',
                'detail' => '',
                'icon' => '',
                'published' => '1'
            ]);
        }
    }

    /**
     * Get columns for table
     *
     * @return array
     */
    public static function getColumns()
    {
        $columns = [
            [
                'field' => 'id',
                'label' => 'ID',
                'cellElement' => 'text',
                'size' => 5
            ]
        ];

        foreach (\Index\Languages\Model::getLanguageColumns() as $language) {
            $columns[] = [
                'field' => $language,
                'label' => ucfirst($language),
                'cellElement' => 'text',
                'size' => 20
            ];
        }

        return $columns;
    }
}
