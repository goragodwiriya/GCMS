<?php
/**
 * @filesource modules/portfolio/views/view.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Portfolio\View;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Single portfolio item page.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * @param object $index
     *
     * @return object
     */
    public function render($index)
    {
        $thumb = WEB_URL.'images/no-image.webp';
        if ($index->image !== '' && is_file(ROOT_PATH.DATA_FOLDER.'portfolio/'.$index->image)) {
            $thumb = WEB_URL.DATA_FOLDER.'portfolio/'.$index->image;
        }

        // Breadcrumb: module listing page, then this item — {BREADCRUMBS}
        // is substituted later by Web\View::renderWeb(), same pattern as
        // Document\View\View.
        Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $index->topic, $index->topic);
        Gcms::$view->addBreadcrumb(\Portfolio\Index\Controller::url($index->module, $index->id), $index->title, $index->title);

        $tags = self::tags($index, $index->keywords);
        $website = '';
        if ($index->url !== '') {
            $link = Template::create($index->owner, $index->module, 'website');
            $link->add([
                '/{URL}/' => Text::htmlspecialchars($index->url)
            ]);
            $website = $link->render();
        }
        $description = mb_substr(strip_tags($index->detail), 0, 150);

        $template = Template::create($index->owner, $index->module, 'view');
        $template->add([
            '/{TITLE}/' => Text::htmlspecialchars($index->title),
            '/{TOPIC}/' => Text::htmlspecialchars($index->title),
            '/{DESCRIPTION}/' => Text::htmlspecialchars($description),
            '/{THUMB}/' => $thumb,
            '/{IMG}/' => $thumb,
            '/{DETAIL}/' => Gcms::highlighter($index->detail),
            '/{URL}/' => Text::htmlspecialchars($index->url),
            '/{WEBSITE}/' => $website,
            '/{VISITED}/' => number_format((int) $index->visited),
            '/{KEYWORDS}/' => Text::htmlspecialchars($index->keywords),
            '/{TAGS}/' => $tags,
            '/{TAGS_HIDDEN}/' => $tags === '' ? 'hidden' : '',
            '/{WEBSITE_HIDDEN}/' => $website === '' ? 'hidden' : '',
            '/{YEAR}/' => !empty($index->created_at) ? date('Y', (int) $index->created_at) : ''
        ]);

        $index->topic = $index->title;
        $index->description = $description;
        $index->canonical = \Portfolio\Index\Controller::url($index->module, $index->id);
        $index->detail = $template->render();

        return $index;
    }

    /**
     * One tag.html per keyword (comma separated), each linking to the listing
     * of that tag.
     *
     * @param object $index
     * @param string $keywords
     *
     * @return string
     */
    public static function tags($index, $keywords)
    {
        $template = Template::create($index->owner, $index->module, 'tag');
        foreach (explode(',', (string) $keywords) as $keyword) {
            $keyword = trim($keyword);
            if ($keyword !== '') {
                $template->add([
                    '/{URL}/' => Text::htmlspecialchars(\Portfolio\Index\Controller::url($index->module, 0, $keyword)),
                    '/{TAG}/' => Text::htmlspecialchars($keyword)
                ]);
            }
        }

        return $template->hasItem() ? $template->render() : '';
    }
}
