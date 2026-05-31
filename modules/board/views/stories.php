<?php
/**
 * @filesource modules/board/views/stories.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Stories;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;
use Web\Login;

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
     * Render board listing page
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
            $index->canonical = \Board\Index\Controller::url($index->module, $index->category_id);
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->description);
        }

        $login = Login::isMember();
        $canPost = $login && Login::checkStatus($login, $index->config, ['can_write']);

        // listitem.html
        $listitem = Template::create($index->owner, $index->module, 'listitem');

        foreach ($index->items as $item) {
            $url = \Board\Index\Controller::url($index->module, $index->category_id, $item->id);
            $cat = $index->categories->get('category', $item->category_id);
            if ($item->locked && $item->pin) {
                $icon = 'icon-lock';
            } elseif ($item->locked) {
                $icon = 'icon-lock';
            } elseif ($item->pin) {
                $icon = 'icon-pin';
            } else {
                $icon = '';
            }

            $listitem->add([
                '/{URL}/' => $url,
                '/{ID}/' => $item->id,
                '/{TOPIC}/' => Text::htmlspecialchars($item->topic),
                '/{SENDER}/' => empty($item->sender) ? '{LNG_Unknown}' : Text::htmlspecialchars($item->sender),
                '/{STATUS}/' => $item->status,
                '/{DATE}/' => $item->created_at,
                '/{COMMENTS}/' => (int) $item->comments,
                '/{VISITED}/' => (int) $item->visited,
                '/{CATEGORY}/' => $cat ? Text::htmlspecialchars($cat->topic) : '',
                '/{CATEGORY_ID}/' => $item->category_id,
                '/{LAST_REPLY}/' => $item->comment_date,
                '/{LAST_REPLY_BY}/' => empty($item->comment_date) ? '-' : Text::htmlspecialchars((string) $item->commentator),
                '/{REPLY_STATUS}/' => $item->reply_status,
                '/{PIN}/' => $item->pin ? ' board-pinned' : '',
                '/{LOCKED}/' => $item->locked ? ' board-locked' : '',
                '/{ICON}/' => $icon
            ]);
        }

        // list.html template
        $template = Template::create($index->owner, $index->module, 'list');

        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);

        // Build category filter links
        $catLinks = '';
        if (empty($index->config->category_display)) {
            $url = \Board\Index\Controller::url($index->module);
            $catLinks .= '<a href="'.$url.'" class="cat-link'.(empty($index->category_id) ? ' active' : '').'">{LNG_All}</a>';
            foreach ($index->categories->all('category') as $cat => $text) {
                $active = in_array($cat, $index->category_id) ? ' active' : '';
                $url = \Board\Index\Controller::url($index->module, $cat);
                $catLinks .= '<a href="'.$url.'" class="cat-link'.$active.'">'.Text::htmlspecialchars($text->topic).'</a>';
            }
        }

        if ($listitem->hasItem()) {
            $list = $listitem->render();
        } else {
            $list = '<div class="list-empty"><div class="list-empty-icon icon-comments"></div><h3>{LNG_No topics found}</h3></div>';
        }

        $params = [
            'module' => $index->module.'-write'
        ];
        if (!empty($index->category_id)) {
            $params['category_id'] = (int) $index->category_id;
        }

        $template->add([
            '/{LIST}/' => $list,
            '/{PAGINATION}/' => $uri->pagination($index->total_pages, $index->page),
            '/{TOPIC}/' => Text::htmlspecialchars($index->topic),
            '/{DESCRIPTION}/' => Text::htmlspecialchars($index->description),
            '/{MODULE}/' => $index->module,
            '/{MODULE_ID}/' => (int) $index->module_id,
            '/{CATEGORY_LINKS}/' => $catLinks,
            '/{NEW_TOPIC_URL}/' => WEB_URL.'index.php?'.http_build_query($params),
            '/{CAN_POST}/' => $canPost ? '' : ' hidden'
        ]);

        $index->detail = $template->render();

        return $index;
    }
}
