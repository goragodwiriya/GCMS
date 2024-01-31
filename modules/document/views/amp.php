<?php
/**
 * @filesource modules/document/views/amp.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Amp;

use Gcms\Gcms;
use Kotchasan\Date;
use Kotchasan\Grid;
use Kotchasan\Http\Request;
use Kotchasan\Template;

/**
 * แสดงหน้าสำหรับ Amp
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงหน้าสำหรับ Amp
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // ค่าที่ส่งมา
        $index->id = $request->get('id')->toInt();
        $index->alias = $request->get('alias')->text();
        // อ่านรายการที่เลือก
        $index = \Document\View\Model::get($request, $index);
        if ($index && $index->published) {
            // login
            $login = $request->session('login', array('id' => 0, 'status' => -1, 'email' => '', 'password' => ''))->all();
            // แสดงความคิดเห็นได้
            $canReply = !empty($index->can_reply);
            // สถานะสมาชิกที่สามารถเปิดดูกระทู้ได้
            $canView = Gcms::canConfig($login, $index, 'can_view');
            if ($canView || $index->viewing == 1) {
                // รูปภาพ
                $dir = DATA_FOLDER.'document/';
                $imagedir = ROOT_PATH.$dir;
                if (!empty($index->picture) && is_file($imagedir.$index->picture)) {
                    $size = @getimagesize($imagedir.$index->picture);
                    if ($size) {
                        $index->image = array(
                            '@type' => 'ImageObject',
                            'url' => WEB_URL.$dir.$index->picture,
                            'width' => $size[0],
                            'height' => $size[1]
                        );
                    }
                }
                // URL ของหน้า
                $index->canonical = \Document\Index\Controller::url($index->module, $index->alias, $index->id, false);
                // แสดงความคิดเห็นได้ จากการตั้งค่าโมดูล
                $canReply = !empty($index->can_reply);
                if ($canReply) {
                    // query รายการแสดงความคิดเห็น
                    $index->comment_items = \Index\Comment\Model::get($index);
                    // /document/printcommentitem.html
                    $listitem = Grid::create('document', $index->module, 'printcommentitem');
                    // รายการแสดงความคิดเห็น
                    foreach ($index->comment_items as $no => $item) {
                        $item->detail = Gcms::showDetail(str_replace(array('{', '}'), array('&#x007B;', '&#x007D;'), nl2br($item->detail)), $canView, true);
                        $listitem->add(array(
                            '/{DETAIL}/' => $item->detail,
                            '/{DISPLAYNAME}/' => $item->displayname,
                            '/{DATE}/' => Date::format($item->last_update),
                            '/{IP}/' => Gcms::showip($item->ip, $login),
                            '/{NO}/' => $no + 1
                        ));
                    }
                }
                // เนื้อหา
                $index->detail = Gcms::showDetail(str_replace(array('&#x007B;', '&#x007D;'), array('{', '}'), $index->detail), $canView, true);
                // JSON-LD
                Gcms::$view->setJsonLd(\Document\Jsonld\View::generate($index));
                // คืนค่า
                return (object) array(
                    // /document/amp.html
                    'content' => Template::create('document', $index->module, 'amp')->render(),
                    'canonical' => $index->canonical,
                    'topic' => $index->topic,
                    'detail' => $index->detail,
                    'commentlist' => isset($listitem) ? $listitem->render() : '',
                    'date' => Date::format($index->create_date),
                    'comments' => number_format($index->comments),
                    'visited' => number_format($index->visited),
                    'displayname' => $index->displayname,
                    'picture' => isset($index->image) ? $index->image['url'] : ''
                );
            }
        }
        // 404
        return createClass('Index\Error\Controller')->init('document');
    }
}
