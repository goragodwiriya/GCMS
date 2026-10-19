<?php
/**
 * @filesource widgets/download/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Download\Controllers;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Download widget controller
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * Render widget content.
     *
     * Supports:
     * - {WIDGET_DOWNLOAD module=my_download;limit=5}
     * - {WIDGET_DOWNLOAD_123}
     *
     * @param array $query_string
     *
     * @return string
     */
    public function get($query_string)
    {
        if (empty($query_string['module'])) {
            return '';
        }

        if (ctype_digit((string) $query_string['module'])) {
            return $this->renderSingle((int) $query_string['module']);
        }

        $index = Gcms::$module->findByModule($query_string['module']);
        if (!$index) {
            return '';
        }

        $limit = isset($query_string['limit']) ? (int) $query_string['limit'] : 5;
        $items = \Widgets\Download\Models\Index::getLatest($index->module_id, $limit);
        if (empty($items)) {
            return '';
        }

        $listitem = Template::createFromFile('widgets/download/views/item.html');
        foreach ($items as $item) {
            $name = trim((string) $item->name) !== '' ? (string) $item->name : basename((string) $item->file, '.'.(string) $item->ext);
            $listitem->add([
                '/{ID}/' => (int) $item->id,
                '/{NAME}/' => Text::htmlspecialchars($name),
                '/{EXT}/' => Text::htmlspecialchars((string) $item->ext),
                '/{ICON}/' => \Gcms\Controller::extToIcon((string) $item->ext),
                '/{DETAIL}/' => Text::htmlspecialchars((string) $item->detail),
                '/{DATE}/' => $item->updated_at,
                '/{DOWNLOADS}/' => number_format((int) $item->downloads),
                '/{SIZE}/' => Text::formatFileSize((int) $item->size)
            ]);
        }

        if (!$listitem->hasItem()) {
            return '';
        }

        // the links work through modules/download/script.js (delegated click)
        $list = Template::createFromFile('widgets/download/views/list.html');
        $list->add([
            '/{LIST}/' => $listitem->render()
        ]);

        return $list->render();
    }

    /**
     * Render old-style single download placeholder.
     *
     * @param int $id
     *
     * @return string
     */
    private function renderSingle($id)
    {
        $item = \Widgets\Download\Models\Index::get($id);
        if (!$item) {
            return '';
        }

        $path = \Download\Setup\Model::toFilePath($item->file);
        if (!$path || !is_file($path)) {
            return '';
        }

        $name = trim((string) $item->name) !== '' ? (string) $item->name : basename((string) $item->file, '.'.(string) $item->ext);
        $link = Template::createFromFile('widgets/download/views/link.html');
        $link->add([
            '/{ID}/' => (int) $item->id,
            '/{TEXT}/' => Text::htmlspecialchars($name.'.'.(string) $item->ext),
            '/{DOWNLOADS}/' => number_format((int) $item->downloads)
        ]);

        return $link->render();
    }
}
