<?php
/**
 * @filesource modules/download/models/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Download\Write;

/**
 * Download write model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get a file record for editing.
     *
     * @param int $id
     * @param int $module_id
     *
     * @return object|null
     */
    public static function get($id, $module_id)
    {
        $module = \Index\Module\Model::getModuleWithConfig('download', $module_id);
        if (!$module) {
            return null;
        }

        $module->config = \Download\Settings\Model::normalizeConfig($module->config);

        if ($id === 0) {
            return (object) [
                'id' => 0,
                'module_id' => (int) $module->id,
                'module' => $module->module,
                'member_id' => 0,
                'category_id' => 0,
                'name' => '',
                'detail' => '',
                'ext' => '',
                'size' => 0,
                'file' => '',
                'downloads' => 0,
                'reciever' => $module->config->can_download,
                'file_typies' => $module->config->file_typies,
                'upload_size' => (int) $module->config->upload_size,
                'is_owner' => true
            ];
        }

        $row = static::createQuery()
            ->select('id', 'module_id', 'member_id', 'category_id', 'name', 'detail', 'ext', 'size', 'file', 'downloads', 'reciever')
            ->from('download')
            ->where([
                ['id', (int) $id],
                ['module_id', (int) $module->id]
            ])
            ->first();

        if (!$row) {
            return null;
        }

        $row->module = $module->module;
        $row->file_typies = $module->config->file_typies;
        $row->upload_size = (int) $module->config->upload_size;

        $reciever = \Kotchasan\ArrayTool::unserialize((string) $row->reciever);
        if (!is_array($reciever) || empty($reciever)) {
            $reciever = $module->config->can_download;
        }
        $row->reciever = array_values(array_unique(array_map('intval', $reciever)));

        return $row;
    }

    /**
     * Normalize stored file path to relative DATA_FOLDER path.
     *
     * @param string $path
     *
     * @return string
     */
    public static function normalizeFilePath($path)
    {
        $path = trim(str_replace('\\', '/', (string) $path));
        if ($path === '') {
            return '';
        }

        if (strpos($path, '..') !== false) {
            return '';
        }

        if (strpos($path, DATA_FOLDER) === 0) {
            $path = substr($path, strlen(DATA_FOLDER));
        }

        $path = ltrim($path, '/');
        if (!preg_match('/^[a-zA-Z0-9_\-\/\.]+$/', $path)) {
            return '';
        }

        return $path;
    }
}
