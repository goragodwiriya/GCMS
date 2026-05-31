<?php
/**
 * @filesource modules/gallery/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Gallery\Index;

use Kotchasan\ArrayTool;
use Kotchasan\Http\Request;

/**
 * Controller หลัก สำหรับแสดง frontend ของ Gallery
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Web\Controller
{
    /**
     * Controller หลักของโมดูล
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function init(Request $request, $index)
    {
        if (MAIN_INIT === 'indexhtml') {
            $alias = $request->get('alias')->topic();
            if (preg_match('/^id\/([0-9]+)$/', $alias, $matches)) {
                // หน้าแสดงรูปในอัลบัม
                $index->id = (int) $matches[1];
                // อ่านข้อมูลโมดูล Index
                $index = \Gallery\Index\Model::getAlbum($index);
                if ($index !== false) {
                    return \Gallery\Index\View::create()->renderAlbum($index);
                }
            } elseif ($request->get('id')->exists()) {
                // หน้าแสดงรูปในอัลบัม
                $index->id = $request->get('id')->toInt();
                // อ่านข้อมูลโมดูล Index
                $index = \Gallery\Index\Model::getAlbum($index);
                if ($index !== false) {
                    return \Gallery\Index\View::create()->renderAlbum($index);
                }
            } else {
                // หน้ารวมอัลบัม
                $listModel = \Gallery\Lists\Model::create(['module_id' => $index->module_id]);
                $page = max(1, $request->get('page')->toInt());
                $limit = $request->get('limit')->toInt();
                $limit = $limit > 0 ? $limit : 15;
                $pagination = $listModel->paginate($page, $limit);
                $index = ArrayTool::replace($index, $pagination);
                // listting gallery
                return \Gallery\Index\View::create()->render($index);
            }
        }
        // ไม่พบหน้าที่เรียก
        return \Index\Error\Controller::create()->init('gallery');
    }

    /**
     * สร้าง URL สำหรับโมดูล gallery
     *
     * @param string $module
     * @param int    $id
     *
     * @return string
     */
    public static function url($module, $id)
    {
        return WEB_URL.$module.'/id/'.$id;
    }
}
