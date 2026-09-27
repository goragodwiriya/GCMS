<?php
/**
 * @filesource modules/index/models/mailtemplate.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Mailtemplate;

/**
 * Class for loading mail template items from the GCMS database
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Gcms\Sysadmin\Model
{
    /**
     * Get mail template by ID
     * id = 0 return new mail template with default values
     *
     * @param int    $id
     *
     * @return object|null Return mail template object, null if not found
     */
    public static function get($id)
    {
        if (empty($id)) {
            // new page, return default values
            return (object) [
                'id' => 0,
                'subject' => '',
                'from_email' => '',
                'copy_to' => [],
                'detail' => '',
                'language' => ''
            ];
        }
        // find existing page from database
        $index = static::createQuery()
            ->select()
            ->from('emailtemplate')
            ->where(['id', $id])
            ->cacheOn()
            ->first();
        if ($index) {
            $index->copy_to = empty($index->copy_to) ? [] : explode(',', $index->copy_to);
            $index->detail = str_replace(
                ['&#x007B;', '&#x007D;', '&#92;', '{WEBURL}'],
                ['{', '}', '\\', WEB_URL],
                $index->detail
            );
        }
        return $index;
    }
}
