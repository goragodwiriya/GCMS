<?php
/**
 * @filesource modules/index/models/sitemap.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Sitemap;

/**
 * Class for loading the list of all installed modules from the GCMS database.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model
{
    /**
     * Read the list of all installed modules.
     *
     * @return array
     */
    public static function getModules()
    {
        if (defined('MAIN_INIT')) {
            return \Kotchasan\Model::createQuery()
                ->select('M.id', 'M.module', 'M.owner', 'D.language')
                ->from('modules M')
                ->join('index I', [['I.module_id', 'M.id'], ['I.index', 1]], 'LEFT')
                ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'M.id']], 'LEFT')
                ->where(['I.published', 1])
                ->cacheOn()
                ->fetchAll();
        } else {
            // Call method directly
            new \Kotchasan\Http\NotFound('Do not call method directly');
        }
    }
}
