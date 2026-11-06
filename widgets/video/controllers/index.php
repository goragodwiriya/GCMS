<?php
/**
 * @filesource widgets/video/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Video\Controllers;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Widget: latest videos from a video module.
 *
 * {WIDGET_VIDEO module=clip;count=6}
 *
 * Backward-compatible single embed: when `module` is an 11-char YouTube id
 * (e.g. {WIDGET_VIDEO module=dQw4w9WgXcQ}) it renders that one video instead,
 * same as the previous single-embed widget. Mirrors
 * widgets/gallery/controllers/index.php's shape.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * No automatic position injection; rendered via the {WIDGET_VIDEO} tag.
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
     * Display Widget
     *
     * @param array $query_string  module=name|youtubeId, count=N
     *
     * @return string
     */
    public function get($query_string)
    {
        $module = empty($query_string['module']) ? 'video' : $query_string['module'];

        // Single-embed fallback: an 11-char YouTube id that is not an installed module
        if (preg_match('/^[a-zA-Z0-9_\-]{11}$/', $module) && !Gcms::$module->findByModule($module)) {
            return '<div class="youtube"><iframe src="https://www.youtube.com/embed/'.$module.'?wmode=transparent" allowfullscreen title="Youtube"></iframe></div>';
        }

        // Latest videos from a video module instance
        $index = Gcms::$module->findByModule($module);
        if (!$index) {
            return '';
        }

        $limit = isset($query_string['count']) ? (int) $query_string['count'] : 6;
        $limit = max(1, min(20, $limit));

        $videos = \Widgets\Video\Models\Index::getLatest($index->module_id, $limit);
        if (empty($videos)) {
            return '';
        }

        $listitem = Template::createFromFile(ROOT_PATH.'widgets/video/views/item.html');
        foreach ($videos as $video) {
            if (is_file(ROOT_PATH.DATA_FOLDER.'video/'.$video->youtube.'.jpg')) {
                $picture = WEB_URL.DATA_FOLDER.'video/'.$video->youtube.'.jpg';
            } else {
                $picture = WEB_URL.'images/no-image.webp';
            }
            $listitem->add([
                '/{ID}/' => (int) $video->id,
                '/{TOPIC}/' => Text::htmlspecialchars($video->topic),
                '/{PICTURE}/' => $picture,
                '/{YOUTUBE}/' => $video->youtube,
                '/{VIEWS}/' => number_format((int) $video->views)
            ]);
        }

        return '<div class="widget-video-latest ggrid thumbview">'.$listitem->render().'</div>';
    }
}
