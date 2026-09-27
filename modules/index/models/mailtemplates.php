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
 * Handles mail template table operations
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Gcms\Sysadmin\Model
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
            ->from('emailtemplate')
            ->where(['customer_id', [0, CUSTOMER_ID]]);

        $overrideKeys = static::createQuery()
            ->select('email_id')
            ->from('emailtemplate')
            ->where(['customer_id', CUSTOMER_ID]);

        $baseRowsHiddenByOverride = static::createQuery()
            ->select('id')
            ->from('emailtemplate')
            ->where([
                ['customer_id', 0],
                ['email_id', 'IN', $overrideKeys]
            ]);

        $query->where([
            ['id', 'NOT IN', $baseRowsHiddenByOverride],
            ['module', ['member', 'share']]
        ]);

        return $query;
    }

    /**
     * Delete mail template by ID
     * Removes records from emailtemplate table.
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

        return self::createDB()->delete('emailtemplate', [
            ['id', $ids],
            ['customer_id', CUSTOMER_ID]
        ], 0);
    }
}
