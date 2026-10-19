<?php
/**
 * @filesource modules/document/views/categories.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Categories;

use Kotchasan\Http\Request;
use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Category Frontend Views
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * Render category listing page
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function render(Request $request, $index)
    {
        // module breadcrumb
        if (Gcms::$menu->isHomeMenu($index->index_id)) {
            // It's the main page.
            $index->canonical = WEB_URL.'index.php';
        } else {
            $index->canonical = Gcms::createUrl($index->module);
            $menu = Gcms::$menu->getTopLevelMenuByIndexId($index->index_id);
            if ($menu) {
                // Use text from menu
                Gcms::$view->addBreadcrumb($index->canonical, $menu->menu_text, $menu->menu_tooltip);
            } else {
                // Module
                Gcms::$view->addBreadcrumb($index->canonical, $index->topic);
            }
        }

        // categoryitem.html
        $listitem = Template::create($index->owner, $index->module, 'categoryitem');

        // Picture of default module
        if (isset($index->default_icon) && is_file(ROOT_PATH.$index->default_icon)) {
            $default_icon = WEB_URL.$index->default_icon;
        } else {
            $default_icon = WEB_URL.'images/no-image.webp';
        }

        // Category list
        foreach ($index->categories->all('category') as $category_id => $item) {
            // Picture of category
            if (!empty($item->icon) && is_file(ROOT_PATH.$item->icon)) {
                $icon = WEB_URL.$item->icon;
            } else {
                $icon = $default_icon;
            }
            $listitem->add([
                '/{TOPIC}/' => Text::htmlspecialchars($item->topic),
                '/{DETAIL}/' => Text::htmlspecialchars($item->detail),
                '/{PICTURE}/' => $icon,
                '/{URL}/' => \Document\Index\Controller::url($index->module, (int) $category_id)
            ]);
        }

        // category.html
        $template = Template::create($index->owner, $index->module, 'category');
        $template->add([
            '/{TOPIC}/' => Text::htmlspecialchars($index->topic),
            '/{DESCRIPTION}/' => Text::htmlspecialchars($index->description),
            '/{LIST}/' => $listitem->render(),
            '/{STYLE}/' => empty($index->category_display) ? 'iconview' : $index->category_display,
            '/{COLS}/' => self::columnsToGridSize($index->category_cols),
            '/{MODULE}/' => $index->module
        ]);

        // JSON-LD (Index)
        Gcms::$view->setJsonLd(\Index\Jsonld\View::webpage($index));
        // คืนค่า
        return (object) [
            'canonical' => $index->canonical,
            'module' => $index->module,
            'topic' => $index->topic,
            'description' => $index->description,
            'keywords' => $index->keywords,
            'detail' => $template->render()
        ];
    }
}
