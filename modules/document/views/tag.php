<?php
/**
 * @filesource modules/document/views/tag.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Tag;

use Kotchasan\Language;
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
        $index->topic = $index->alias.' - '.Language::get('Article Tags');
        $index->description = $index->alias.' - '.Language::get('Articles tagged with this tag');

        // listitem.html
        $listitem = Template::create($index->owner, $index->module, 'listitem');

        foreach ($index->items as $item) {
            if (!empty($item->picture) && file_exists(ROOT_PATH.DATA_FOLDER.'document/'.$item->picture)) {
                $image = WEB_URL.DATA_FOLDER.'document/'.$item->picture;
            } else {
                $image = WEB_URL.'images/no-image.webp';
            }

            $articleUrl = \Document\Index\Controller::url($item->module, $item->alias, $item->id);

            $category = json_decode($item->category, true);
            $category = $category[LANGUAGE] ?? $category[''] ?? '';

            $listitem->add([
                '/{URL}/' => $articleUrl,
                '/{ID}/' => $item->id,
                '/{TOPIC}/' => Text::htmlspecialchars($item->topic),
                '/{IMAGE}/' => $image,
                '/{DESCRIPTION}/' => Text::htmlspecialchars($item->description),
                '/{DATE}/' => $item->published_date,
                '/{CATEGORY}/' => Text::htmlspecialchars($category)
            ]);
        }
        // tag.html template
        $template = Template::create($index->owner, $index->module, 'tag');

        $index->canonical = Controller::url($index->alias, false);
        Gcms::$view->addBreadcrumb($index->canonical, $index->alias, $index->alias);

        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);
        $style = isset(self::$cfg->document_style) ? strtolower(trim((string) self::$cfg->document_style)) : 'iconview';
        if (!in_array($style, ['listview', 'iconview', 'thumbview'], true)) {
            $style = 'iconview';
        }

        $template->add([
            '/{LIST}/' => $listitem->hasItem() ? $listitem->render() : '<div class="list-empty"><div class="list-empty-icon icon-file"></div><h3>{LNG_No items found}</h3></div>',
            '/{PAGINATION}/' => $uri->pagination($index->total_pages, $index->page),
            '/{TOPIC}/' => Text::htmlspecialchars($index->topic),
            '/{DESCRIPTION}/' => Text::htmlspecialchars($index->description),
            '/{STYLE}/' => $style,
            '/{COLS}/' => self::columnsToGridSize(self::$cfg->document_cols ?? 3)
        ]);

        $index->detail = $template->render();
        return $index;
    }
}
