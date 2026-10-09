<?php
/**
 * @filesource modules/index/models/mailtemplates.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\MailTemplates;

/**
 * API Admin Mail Templates Model
 *
 * รายการแม่แบบอีเมลของเว็บนี้ (ตาราง emailtemplate) ทุกแถว = แม่แบบที่ระบบส่งจริง
 * (registration, activation, account_approved, password_reset, admin_new_member,
 * payment_notify ฯลฯ) หนึ่งแถวต่อหนึ่งภาษา กับแถว legacy_* ที่เก็บไว้จาก GCMS รุ่นเดิม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query data to send to DataTable
     *
     * @param array $params
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        $query = static::createQuery()
            ->select('id', 'module', 'email_id', 'code', 'language', 'name', 'subject', 'updated_at')
            ->from('emailtemplate');

        // Search (OR across name / subject / code)
        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['name', 'LIKE', $search],
                ['subject', 'LIKE', $search],
                ['code', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }

    /**
     * Delete mail template by ID
     *
     * แม่แบบที่ระบบส่ง (code ที่ไม่ขึ้นต้นด้วย legacy_) ต้องเหลืออย่างน้อยหนึ่งภาษา
     * ลบภาษาสุดท้ายแล้ว Gcms\EmailTemplate::send() จะหาแม่แบบไม่เจอ อีเมลนั้นหยุดส่งเงียบ ๆ
     *
     * @param int|array $ids Mail Template ID
     *
     * @return int Number of deleted records (0 if not found)
     */
    public static function remove($ids)
    {
        if (empty($ids)) {
            return 0;
        }
        $db = static::createDB();
        $removed = 0;
        foreach ((array) $ids as $id) {
            $template = $db->first('emailtemplate', [['id', (int) $id]]);
            if (!$template) {
                continue;
            }
            if (strpos((string) $template->code, 'legacy_') !== 0) {
                $others = $db->first('emailtemplate', [
                    ['code', $template->code],
                    ['id', '!=', $template->id]
                ]);
                if (!$others) {
                    continue;
                }
            }
            $removed += (int) $db->delete('emailtemplate', [['id', $template->id]]);
        }

        return $removed;
    }
}
