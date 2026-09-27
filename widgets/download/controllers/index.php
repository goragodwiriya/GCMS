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
            $path = \Download\Setup\Model::toFilePath($item->file);
            if (!$path || !is_file($path)) {
                continue;
            }

            $name = trim((string) $item->name) !== '' ? (string) $item->name : basename((string) $item->file, '.'.(string) $item->ext);
            $listitem->add([
                '/{ID}/' => (int) $item->id,
                '/{NAME}/' => Text::htmlspecialchars($name),
                '/{EXT}/' => Text::htmlspecialchars((string) $item->ext),
                '/{DETAIL}/' => Text::htmlspecialchars((string) $item->detail),
                '/{DATE}/' => date('Y-m-d H:i:s', (int) $item->last_update),
                '/{DOWNLOADS}/' => number_format((int) $item->downloads),
                '/{SIZE}/' => Text::formatFileSize((int) $item->size)
            ]);
        }

        if (!$listitem->hasItem()) {
            return '';
        }

        $containerId = 'download_widget_'.substr(md5(uniqid((string) $index->module_id, true)), 0, 8);
        $title = !empty($query_string['title']) ? $query_string['title'] : $index->topic;

        $html = '<section class="widget widget_bg widget_bg_color download '.Text::htmlspecialchars($index->module).'">';
        $html .= '<header><h2 class="icon-download">'.Text::htmlspecialchars((string) $title).'</h2></header>';
        $html .= '<div class="widget_body widget_bdr" id="'.$containerId.'">'.$listitem->render().'</div>';
        $html .= '<p class="next"><a class="icon-next" href="'.\Download\Index\Controller::url($index->module).'">{LNG_View all}</a></p>';
        $html .= '</section>';
        $html .= '<script>window.addEventListener("DOMContentLoaded",function(){if(typeof initDownloadList==="function"){initDownloadList("'.$containerId.'");}});</script>';

        return $html;
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
        $text = Text::htmlspecialchars($name.'.'.(string) $item->ext);

        $html = '<a id="getdl_'.(int) $item->id.'" href="" target="downloading" class="icon-download" title="'.$text.'">'.$text.'</a>';
        $html .= '&nbsp;(<span id="downloads_'.(int) $item->id.'">'.number_format((int) $item->downloads).'</span>)';
        $html .= '<script>window.addEventListener("DOMContentLoaded",function(){if(typeof initDownloadList==="function"){initDownloadList("getdl_'.(int) $item->id.'");}});</script>';

        return $html;
    }
}
