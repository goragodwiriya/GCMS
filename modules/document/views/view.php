<?php
/**
 * @filesource modules/document/views/vew.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\View;

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
     * Render single article page
     *
     * @param object $index Module data (article data attached)
     *
     * @return object
     */
    public function render($index)
    {
        $article = $index->article;

        // Featured image
        if (!empty($article->picture) && file_exists(ROOT_PATH.DATA_FOLDER.'document/'.$article->picture)) {
            $image = WEB_URL.DATA_FOLDER.'document/'.$article->picture;
            $index->image_src = $image;
        } else {
            $image = '';
        }
        // module breadcrumb
        if (!Gcms::$menu->isHomeMenu($index->index_id)) {
            $menu = Gcms::$menu->getTopLevelMenuByIndexId($index->index_id);
            if ($menu) {
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $menu->menu_text, $menu->menu_tooltip);
            }
        }

        // category breadcrumb
        if (!empty($article->category_id)) {
            $categoryUrl = \Document\Index\Controller::url($index->module, $article->category_id);
            Gcms::$view->addBreadcrumb($categoryUrl, $article->category_name, $article->category_name);
        }

        // page canonical and breadcrumb
        $index->canonical = \Document\Index\Controller::url($index->module, $index->article->alias, $index->id, false);
        Gcms::$view->addBreadcrumb($index->canonical, $article->topic, $article->topic);

        // view.html template
        $template = Template::create($index->owner, $index->module, 'view');

        $imageHtml = $image ? '<section class="article-image"><img src="'.$image.'" alt="'.Text::htmlspecialchars($article->topic).'"></section>' : '';

        $template->add([
            '/{ID}/' => $article->id,
            '/{TOPIC}/' => Text::htmlspecialchars($article->topic),
            '/{IMAGE}/' => $imageHtml,
            '/{IMAGE_URL}/' => $image,
            '/{DETAIL}/' => Gcms::showDetail(str_replace(['&#x007B;', '&#x007D;'], ['{', '}'], $article->detail), true),
            '/{DESCRIPTION}/' => Text::htmlspecialchars($article->description),
            '/{DATE}/' => $article->published_date,
            '/{CATEGORY}/' => Text::htmlspecialchars((string) $article->category_name),
            '/{CATEGORY_ID}/' => $article->category_id,
            '/{VISITED}/' => number_format($article->visited),
            '/{MODULE}/' => $index->module,
            '/{TAGS}/' => Text::htmlspecialchars($article->tags)
        ]);

        $index->detail = $template->render();

        // ── JSON-LD ───────────────────────────────────────────
        Gcms::$view->setJsonLd(\Document\Jsonld\View::generate($index));

        return $index;
    }
}
