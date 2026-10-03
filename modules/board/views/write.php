<?php
/**
 * @filesource modules/board/views/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Write;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Board Frontend Views
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * Render single topic + replies view
     *
     * @param object $index Module data (topic_data must be set)
     *
     * @return object
     */
    public function render($index)
    {
        // module breadcrumb
        $menu = Gcms::$menu->getTopLevelMenuByIndexId($index->index_id);
        if ($menu) {
            Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $menu->menu_text, $menu->menu_tooltip);
        }

        if (!empty($index->category_id)) {
            $category = $index->categories->get('category', $index->category_id);
            if ($category) {
                $categoryUrl = \Board\Index\Controller::url($index->module, $index->category_id);
                Gcms::$view->addBreadcrumb($categoryUrl, $category->topic, $category->topic);
            }
        }

        // page canonical and breadcrumb
        $index->canonical = \Board\Index\Controller::url($index->module, $index->category_id, $index->id);
        Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->topic);

        $options = [];
        foreach ($index->categories->all('category') as $cat => $item) {
            $options[] = '<option value="'.$cat.'"'.($cat == $index->category_id ? ' selected' : '').'>'.$item->topic.'</option>';
        }

        $uploadEnabled = !empty($index->img_upload_type);
        $picture = empty($index->picture) ? [] : [[
            'url' => WEB_URL.DATA_FOLDER.'board/'.$index->picture,
            'name' => $index->picture
        ]];

        $template = Template::create($index->owner, $index->module, 'write');
        $template->add([
            '/{NEW_TOPIC}/' => $index->id > 0 ? '{LNG_Edit Topic}' : '{LNG_New Topic}',
            '/{CATEGORIES}/' => implode("\n", $options),
            '/{HAS_CATEGORY}/' => empty($options) ? 'hidden' : 'has-category',
            '/{TOPIC}/' => Text::htmlspecialchars($index->topic),
            '/{DESCRIPTION}/' => Text::htmlspecialchars($index->description),
            '/{SUBJECT}/' => Text::htmlspecialchars($index->subject),
            '/{DETAIL}/' => $index->detail,
            '/{MODULE_ID}/' => (int) $index->module_id,
            '/{MODULE}/' => Text::htmlspecialchars($index->module),
            '/{ID}/' => (int) $index->id,
            '/{HAS_UPLOAD}/' => $uploadEnabled ? 'has-upload' : 'hidden',
            '/{IMG_TYPES}/' => implode(', ', $index->img_upload_type),
            '/{IMG_LAW}/' => \Kotchasan\Language::get('IMG_LAW', '', $index->img_law),
            '/{PICTURE_JSON}/' => Text::htmlspecialchars(json_encode($picture))
        ]);

        $index->detail = $template->render();

        return $index;
    }
}
