<?php
/**
 * @filesource modules/download/views/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Download\Index;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Download frontend views
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * Render download listing page.
     *
     * @param \Kotchasan\Http\Request $request
     * @param object $index
     *
     * @return object
     */
    public function render($request, $index)
    {
        $categories = $index->categories->toSelect('category');
        $selectedCategoryId = !empty($index->category_id) ? (int) reset($index->category_id) : 0;

        if (Gcms::$menu->isHomeMenu($index->index_id)) {
            $index->canonical = WEB_URL.'index.php';
        } else {
            $index->canonical = \Download\Index\Controller::url($index->module, $index->category_id);
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->description);
        }

        if ($selectedCategoryId > 0 && !empty($categories[$selectedCategoryId])) {
            $index->canonical = \Download\Index\Controller::url($index->module, $selectedCategoryId);
            Gcms::$view->addBreadcrumb($index->canonical, $categories[$selectedCategoryId], $categories[$selectedCategoryId]);
        }

        $listitem = Template::create($index->owner, $index->module, 'listitem');
        foreach ($index->items as $item) {
            $name = trim((string) $item->name) !== '' ? $item->name : basename((string) $item->file);
            $categoryName = empty($categories[$item->category_id]) ? '{LNG_Uncategorized}' : $categories[$item->category_id];

            $listitem->add([
                '/{ID}/' => (int) $item->id,
                '/{NAME}/' => Text::htmlspecialchars($name),
                '/{EXT}/' => Text::htmlspecialchars((string) $item->ext),
                '/{ICON}/' => self::ext2Icon((string) $item->ext),
                '/{DETAIL}/' => Text::htmlspecialchars((string) $item->detail),
                '/{DATE}/' => $item->updated_at,
                '/{DOWNLOADS}/' => number_format((int) $item->downloads),
                '/{SIZE}/' => Text::formatFileSize((int) $item->size),
                '/{CATEGORY}/' => Text::htmlspecialchars((string) $categoryName),
                '/{MODULE_ID}/' => (int) $item->module_id
            ]);
        }

        // Category tabs: one categoryitem.html per category, "All" first
        $categoryTabs = Template::create($index->owner, $index->module, 'categoryitem');
        $categoryTabs->add([
            '/{URL}/' => Text::htmlspecialchars(\Download\Index\Controller::url($index->module, 0)),
            '/{ACTIVE}/' => $selectedCategoryId === 0 ? 'active' : '',
            '/{TOPIC}/' => '{LNG_All}'
        ]);
        foreach ($categories as $category_id => $topic) {
            $categoryTabs->add([
                '/{URL}/' => Text::htmlspecialchars(\Download\Index\Controller::url($index->module, (int) $category_id)),
                '/{ACTIVE}/' => (int) $category_id === $selectedCategoryId ? 'active' : '',
                '/{TOPIC}/' => Text::htmlspecialchars((string) $topic)
            ]);
        }

        $template = Template::create($index->owner, $index->module, $listitem->hasItem() ? 'list' : 'empty');

        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);

        $template->add([
            '/{CATEGORIES}/' => $categoryTabs->render(),
            '/{TOPIC}/' => Text::htmlspecialchars((string) $index->topic),
            // the module's description — its detail is the page's rich text
            '/{DETAIL}/' => Text::htmlspecialchars((string) $index->description),
            '/{LIST}/' => $listitem->render(),
            '/{SPLITPAGE}/' => $uri->pagination($index->total_pages, $index->page),
            '/{MODULE}/' => $index->module
        ]);

        $index->detail = $template->render();

        return $index;
    }

    /**
     * แปลงนามสกุลของไฟล์เป็น icon ที่รู้จัก (ชื่อ icon ไม่รวม prefix icon-)
     * ไม่รู้จักคืนค่า file
     *
     * @param string $ext
     *
     * @return string
     */
    public static function ext2Icon($ext)
    {
        static $icons = null;
        if ($icons === null) {
            $groups = [
                'image' => ['jpg', 'jpeg', 'jpe', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico', 'tif', 'tiff', 'avif', 'heic', 'heif', 'psd', 'ai', 'eps'],
                'pdf' => ['pdf'],
                'word' => ['doc', 'docx', 'docm', 'dot', 'dotx', 'odt', 'rtf'],
                'excel' => ['xls', 'xlsx', 'xlsm', 'xlsb', 'xlt', 'xltx', 'ods', 'csv'],
                'zip' => ['zip', 'rar', '7z', 'tar', 'gz', 'tgz', 'bz2', 'xz', 'iso'],
                'video' => ['mp4', 'm4v', 'mov', 'avi', 'wmv', 'flv', 'mkv', 'webm', 'mpg', 'mpeg', '3gp'],
                'song' => ['mp3', 'wav', 'ogg', 'oga', 'm4a', 'aac', 'flac', 'wma', 'mid', 'midi'],
                'code' => ['html', 'htm', 'css', 'js', 'json', 'xml', 'php', 'py', 'java', 'c', 'cpp', 'h', 'cs', 'sql', 'sh', 'ts', 'yml', 'yaml']
            ];
            $icons = [];
            foreach ($groups as $icon => $exts) {
                foreach ($exts as $item) {
                    $icons[$item] = $icon;
                }
            }
        }
        $ext = strtolower(ltrim(trim((string) $ext), '.'));

        return isset($icons[$ext]) ? $icons[$ext] : 'file';
    }
}
