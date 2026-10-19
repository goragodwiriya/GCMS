<?php
/**
 * @filesource modules/gallery/views/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Gallery\Index;

use Kotchasan\Template;
use Web\Gcms;

/**
 * Gallery Frontend Views
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * Render album listing page
     *
     * @param object $index Module data
     *
     * @return object
     */
    public function render($index)
    {
        // listitem.html
        $listitem = Template::create($index->owner, $index->module, 'album-item');

        foreach ($index->items as $item) {
            // cover image
            if (!empty($item->image) && file_exists(ROOT_PATH.DATA_FOLDER.'gallery/'.$item->id.'/'.$item->image)) {
                $cover = WEB_URL.DATA_FOLDER.'gallery/'.$item->id.'/'.$item->image;
            } else {
                $cover = WEB_URL.'images/no-image.webp';
            }

            $listitem->add([
                '/{URL}/' => Controller::url($index->module, $item->id),
                '/{ID}/' => $item->id,
                '/{TOPIC}/' => $item->topic,
                '/{DESCRIPTION}/' => nl2br($item->detail),
                '/{COVER}/' => $cover,
                '/{DATE}/' => $item->published_date
            ]);
        }

        // template list.html
        $template = Template::create($index->owner, $index->module, 'list');

        if (Gcms::$menu->isHomeMenu($index->index_id)) {
            $index->canonical = WEB_URL.'index.php';
        } else {
            $index->canonical = Gcms::createUrl($index->module);
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->description);
        }

        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);

        // get detail
        $detail = \Index\Index\Model::getDetail($index->index_id, $index->module_id);

        if ($listitem->hasItem()) {
            $list = $listitem->render();
        } else {
            $list = '<div class="list-empty"><div class="list-empty-icon icon-gallery"></div><h3>{LNG_No items found}</h3></div>';
        }

        $template->add([
            '/{LIST}/' => $list,
            '/{PAGINATION}/' => $uri->pagination($index->totalPages, $index->page),
            '/{TOPIC}/' => $index->topic,
            '/{DESCRIPTION}/' => $index->description,
            '/{DETAIL}/' => Gcms::highlighter($detail),
            '/{MODULE}/' => $index->module,
            '/{COLS}/' => 3
        ]);

        $index->detail = $template->render();
        return $index;
    }

    /**
     * Render single album (with slideshow)
     *
     * @param object $index Module data (album + images)
     *
     * @return object
     */
    public function renderAlbum($index)
    {
        // slideshow items
        $slides = '';
        $i = 0;
        foreach ($index->images as $img) {
            $imgUrl = WEB_URL.DATA_FOLDER.'gallery/'.$index->id.'/'.$img->image;
            $slides .= '<div class="now-slide" data-index="'.$i.'">'
            .'<img src="'.$imgUrl.'" alt="'.$index->topic.'" loading="lazy">'
                .'</div>';
            $i++;
        }

        // template album.html
        $template = Template::create($index->owner, $index->module, 'album');

        // module breadcrumb
        if (!Gcms::$menu->isHomeMenu($index->index_id)) {
            $menu = Gcms::$menu->getTopLevelMenuByIndexId($index->index_id);
            if ($menu) {
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $menu->menu_text, $menu->menu_tooltip);
            }
        }

        // page canonical and breadcrumb
        $index->canonical = Controller::url($index->module, $index->id);
        Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->topic);

        $template->add([
            '/{ID}/' => $index->id,
            '/{TOPIC}/' => $index->topic,
            '/{DESCRIPTION}/' => nl2br($index->detail),
            '/{SLIDES}/' => $slides,
            '/{COUNT}/' => count($index->images),
            '/{DATE}/' => $index->published_date,
            '/{MODULE}/' => $index->module,
            '/{DEFAULT_MODE}/' => empty(self::$cfg->gallery_album_view) ? 'slideshow' : self::$cfg->gallery_album_view
        ]);

        $index->detail = $template->render();
        return $index;
    }
}
