<?php
/**
 * @filesource modules/document/views/stories.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Stories;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Document Frontend Views
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * Render article listing page
     *
     * @param object $index Module data
     *
     * @return object
     */
    public function render($index)
    {
        if (!empty($index->category_id) && count($index->category_id) === 1) {
            $category = $index->categories->get('category', $index->category_id[0]);
            if ($category) {
                $index->topic = $category->topic;
                $index->description = $category->detail;
            }
        }

        if (Gcms::$menu->isHomeMenu($index->index_id)) {
            $index->canonical = WEB_URL.'index.php';
        } else {
            $index->canonical = \Document\Index\Controller::url($index->module, $index->category_id);
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->description);
        }

        // listitem.html
        $listitem = Template::create($index->owner, $index->module, 'listitem');

        // Picture of default module
        if (!empty($index->config->default_icon) && file_exists(ROOT_PATH.$index->config->default_icon)) {
            $default_icon = WEB_URL.$index->config->default_icon;
        } else {
            $default_icon = WEB_URL.'images/no-image.webp';
        }

        foreach ($index->items as $item) {
            if (!empty($item->picture) && file_exists(ROOT_PATH.DATA_FOLDER.'document/'.$item->picture)) {
                $image = WEB_URL.DATA_FOLDER.'document/'.$item->picture;
            } else {
                $image = $default_icon;
            }

            $url = \Document\Index\Controller::url($index->module, $item->alias, $item->id);

            $cat = $index->categories->get('category', $item->category_id);

            $listitem->add([
                '/{URL}/' => $url,
                '/{ID}/' => $item->id,
                '/{TOPIC}/' => Text::htmlspecialchars($item->topic),
                '/{IMAGE}/' => $image,
                '/{DESCRIPTION}/' => Text::htmlspecialchars($item->description),
                '/{DATE}/' => $item->published_date,
                '/{CATEGORY}/' => $cat ? Text::htmlspecialchars($cat->topic) : '',
                '/{CATEGORY_ID}/' => $item->category_id
            ]);
        }

        // list.html template
        $template = Template::create($index->owner, $index->module, 'list');

        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);

        // Build category filter links
        $catLinks = '';
        if (empty($index->config->category_display)) {
            $url = \Document\Index\Controller::url($index->module);
            $catLinks .= '<a href="'.$url.'" class="cat-link'.(empty($index->category_id) ? ' active' : '').'">{LNG_All}</a>';
            foreach ($index->categories->all('category') as $cat => $text) {
                $active = in_array($cat, $index->category_id) ? ' active' : '';
                $url = \Document\Index\Controller::url($index->module, $cat);
                $catLinks .= '<a href="'.$url.'" class="cat-link'.$active.'">'.Text::htmlspecialchars($text->topic).'</a>';
            }
        }

        if ($listitem->hasItem()) {
            $list = $listitem->render();
        } else {
            $list = '<div class="list-empty"><div class="list-empty-icon icon-file"></div><h3>{LNG_No articles found}</h3></div>';
        }

        $template->add([
            '/{LIST}/' => $list,
            '/{PAGINATION}/' => $uri->pagination($index->total_pages, $index->page),
            '/{TOPIC}/' => Text::htmlspecialchars($index->topic),
            '/{DESCRIPTION}/' => Text::htmlspecialchars($index->description),
            '/{MODULE}/' => $index->module,
            '/{MODULE_ID}/' => (int) $index->module_id,
            '/{CATEGORY_LINKS}/' => $catLinks,
            '/{COLS}/' => self::columnsToGridSize($index->config->cols)
        ]);

        $index->detail = $template->render();

        return $index;
    }
}
