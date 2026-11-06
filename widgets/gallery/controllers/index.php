<?php
/**
 * @filesource widgets/gallery/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Gallery\Controllers;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Widget shows latest albums.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * Display Widget
     *
     * @param array $query_string  limit=N
     *
     * @return string
     */
    public function get($query_string)
    {
        // Module
        $module = empty($query_string['module']) ? 'gallery' : $query_string['module'];
        $index = Gcms::$module->findByModule($module);
        if (!$index) {
            return '';
        }

        // Quantity to display
        $limit = isset($query_string['count']) ? (int) $query_string['count'] : 6;
        // Latest albums
        $albums = \Widgets\Gallery\Models\Index::getLatest($index->module_id, $limit);

        if (empty($albums)) {
            return '';
        }

        // item template
        $listitem = Template::createFromFile('widgets/gallery/views/item.html');
        foreach ($albums as $album) {
            if (!empty($album->image) && file_exists(ROOT_PATH.DATA_FOLDER.'gallery/'.$album->id.'/'.$album->image)) {
                $cover = WEB_URL.DATA_FOLDER.'gallery/'.$album->id.'/'.$album->image;
            } else {
                $cover = WEB_URL.'images/no-image.webp';
            }
            $listitem->add([
                '/{URL}/' => WEB_URL.'gallery/id/'.$album->id,
                '/{THUMB}/' => $cover,
                '/{TOPIC}/' => Text::htmlspecialchars($album->topic),
                '/{DATE}/' => $album->published_date,
                '/{DESCRIPTION}/' => !empty($album->detail) ? Text::htmlspecialchars($album->detail) : '',
                '/{BADGET}/' => ''
            ]);
        }

        return '<div class="widget-gallery-grid thumbview">'.$listitem->render().'</div>';
    }
}
