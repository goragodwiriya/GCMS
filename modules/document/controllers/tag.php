<?php
/**
 * @filesource modules/document/controllers/tag.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Tag;

use Kotchasan\ArrayTool;
use Kotchasan\Http\Request;
use Web\Gcms;

/**
 * Frontend Controller for Document Tags
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * Main controller for the module
     *
     * @param Request $request
     * @param object  $index   Module data
     *
     * @return object
     */
    public function init(Request $request, $index)
    {
        // /tag ไม่มีชื่อป้ายกำกับ = ไม่มีหน้าให้แสดง (คืน null → 404)
        if (MAIN_INIT === 'indexhtml' && isset($index->alias) && $index->alias !== '') {
            $page = max(1, $request->get('page')->toInt());
            $limit = (self::$cfg->document_cols ?? 3) * (self::$cfg->document_rows ?? 3);
            $listModel = \Document\Tag\Model::create($index);
            $listModel->updateCount($index->alias);
            $pagination = $listModel->paginate($page, $limit);
            $index = ArrayTool::replace($index, $pagination);
            // Tag listing view
            return \Document\Tag\View::create()->render($index);
        }
    }

    /**
     * URL generation function
     *
     * @param string $tag Tag name
     * @param bool   $encode (option) true=encode with rawurlencode (default true)
     *
     * @return string
     */
    public static function url($tag, $encode = true)
    {
        return Gcms::createUrl('tag', $tag, 0, 0, '', $encode);
    }
}
