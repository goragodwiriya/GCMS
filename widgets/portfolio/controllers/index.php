<?php
/**
 * @filesource widgets/portfolio/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Portfolio\Controllers;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Widget: show latest published portfolio items.
 *
 * {WIDGET_PORTFOLIO module=work;limit=6}
 *
 * Mirrors widgets/product/controllers/index.php's shape.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * No automatic position injection — rendered via the {WIDGET_PORTFOLIO}
     * template tag.
     *
     * @param object $obj
     * @param array  $item
     *
     * @return void
     */
    public static function widget($obj, $item)
    {
    }

    /**
     * @param array $query_string
     *
     * @return string
     */
    public function get($query_string)
    {
        if (empty($query_string['module'])) {
            return '';
        }
        $index = Gcms::$module->findByModule($query_string['module']);
        if (!$index) {
            return '';
        }

        $limit = isset($query_string['limit']) ? (int) $query_string['limit'] : 6;
        $limit = max(1, min(20, $limit));

        $items = \Kotchasan\Model::createQuery()
            ->select('id', 'title', 'image')
            ->from('portfolio')
            ->where([
                ['module_id', $index->module_id],
                ['published', '1']
            ])
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->cacheOn()
            ->fetchAll();

        if (empty($items)) {
            return '';
        }

        $listitem = Template::createFromFile(ROOT_PATH.'widgets/portfolio/views/item.html');
        foreach ($items as $item) {
            $thumb = WEB_URL.'images/no-image.webp';
            if ($item->image !== '' && is_file(ROOT_PATH.DATA_FOLDER.'portfolio/'.$item->image)) {
                $thumb = WEB_URL.DATA_FOLDER.'portfolio/'.$item->image;
            }
            $listitem->add([
                '/{ID}/' => (int) $item->id,
                '/{URL}/' => \Portfolio\Index\Controller::url($query_string['module'], $item->id),
                '/{TITLE}/' => Text::htmlspecialchars($item->title),
                '/{THUMB}/' => $thumb
            ]);
        }

        return '<div class="widget-portfolio-latest ggrid">'.$listitem->render().'</div>';
    }
}
