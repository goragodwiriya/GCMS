<?php
/**
 * @filesource modules/download/models/download.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Download\Download;

/**
 * Download action model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get one downloadable file by ID.
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function get($id)
    {
        $search = static::createQuery()
            ->select('D.id', 'D.module_id', 'D.name', 'D.ext', 'D.file', 'D.size', 'D.downloads', 'D.reciever', 'M.config')
            ->from('download D')
            ->join('modules M', ['M.id', 'D.module_id'])
            ->where([
                ['D.id', (int) $id],
                ['M.owner', 'download']
            ])
            ->cacheOn()
            ->first();

        if (!$search) {
            return null;
        }

        $search->config = \Download\Settings\Model::normalizeConfig(json_decode((string) $search->config));

        $reciever = \Kotchasan\ArrayTool::unserialize((string) $search->reciever);
        if (!is_array($reciever) || empty($reciever)) {
            $reciever = $search->config->can_download;
        }
        $search->reciever = array_values(array_unique(array_map('intval', $reciever)));

        return $search;
    }
}
