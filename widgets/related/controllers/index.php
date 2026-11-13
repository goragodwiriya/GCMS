<?php
/**
 * @filesource widgets/related/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Related\Controllers;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Widget แสดงบทความที่เกี่ยวข้อง
 * อ่าน tags จากตาราง index_tag แล้วจับคู่บทความที่มี tag ตรงกัน
 *
 * การใช้งานในเทมเพลต:
 *   {WIDGET_RELATED module={MODULE};id={ID}}
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * Resolve thumbnail URL for an article
     *
     * @param object $article
     * @param object $index   Module index
     *
     * @return string
     */
    private function thumb($article, $index)
    {
        if (!empty($article->picture) && file_exists(ROOT_PATH.DATA_FOLDER.'document/'.$article->picture)) {
            return WEB_URL.DATA_FOLDER.'document/'.$article->picture;
        }
        if (!empty($index->default_icon) && file_exists(ROOT_PATH.$index->default_icon)) {
            return WEB_URL.$index->default_icon;
        }
        return WEB_URL.'images/no-image.webp';
    }

    /**
     * แสดงผล Widget
     *
     * @param array $query_string  module=<module_name>;id=<article_id>
     *
     * @return string
     */
    public function get($query_string)
    {
        if (empty($query_string['module']) || empty($query_string['id'])) {
            return '';
        }

        $index = Gcms::$module->findByModule($query_string['module']);
        if (!$index) {
            return '';
        }

        $articles = \Widgets\Related\Models\Index::getRelated($query_string['id'], $query_string['count'] ?? 4);

        if (empty($articles)) {
            return '';
        }

        // item template
        $listitem = Template::createFromFile('widgets/related/views/item.html');
        foreach ($articles as $article) {
            $listitem->add([
                '/{URL}/' => \Document\Index\Controller::url($index->module, $article->alias, $article->id),
                '/{THUMB}/' => $this->thumb($article, $index),
                '/{TOPIC}/' => Text::htmlspecialchars($article->topic),
                '/{DATE}/' => $article->published_date,
                '/{DESCRIPTION}/' => !empty($article->description) ? Text::htmlspecialchars($article->description) : '',
                '/{BADGET}/' => ''
            ]);
        }

        return '<div class="widget-related thumbview">'.$listitem->render().'</div>';
    }
}
