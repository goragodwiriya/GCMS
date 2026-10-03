<?php
/**
 * @filesource modules/document/models/commentwrite.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Commentwrite;

/**
 * Document Comment Model — Edit form data
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get a comment row for the edit form.
     *
     * @param int $id Comment ID
     *
     * @return object|null
     */
    public static function get($id)
    {
        $row = static::createQuery()
            ->select('id', 'module_id', 'index_id', 'member_id', 'detail')
            ->from('comment')
            ->where(['id', $id])
            ->first();

        if (!$row) {
            return null;
        }

        // Decode stored detail
        $row->detail = str_replace('{WEBURL}', WEB_URL, $row->detail);

        return $row;
    }

    /**
     * Get the parent article of a comment (for category/permission/breadcrumb context).
     *
     * @param int $id Article ID (index.id)
     *
     * @return object|null
     */
    public static function getArticle($id)
    {
        $article = static::createQuery()
            ->select('id', 'module_id', 'category_id', 'alias')
            ->from('index')
            ->where(['id', $id])
            ->first();

        if (!$article) {
            return null;
        }

        $detail = static::createQuery()
            ->select('topic')
            ->from('index_detail')
            ->where([['id', $id], ['module_id', $article->module_id], ['language', ['', LANGUAGE]]])
            ->first();
        $article->topic = $detail ? $detail->topic : '';

        return $article;
    }
}
