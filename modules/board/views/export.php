<?php
/**
 * @filesource ฺboard/views/export.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Export;

use Gcms\Gcms;
use Kotchasan\Grid;
use Kotchasan\Http\Request;
use Kotchasan\Template;

/**
 * แสดงหน้าสำหรับพิมพ์
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงหน้าสำหรับพิมพ์
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function printer(Request $request, $index)
    {
        // ค่าที่ส่งมา
        $index->id = $request->get('id', $request->get('id')->toInt())->toInt();
        // อ่านรายการที่เลือก
        $index = \Board\View\Model::get($request, $index);
        if ($index) {
            // login
            $login = $request->session('login', array('id' => 0, 'status' => -1, 'email' => '', 'password' => ''))->all();
            // แสดงความคิดเห็นได้
            $canReply = !empty($index->can_reply);
            // สถานะสมาชิกที่สามารถเปิดดูกระทู้ได้
            $canView = Gcms::canConfig($login, $index, 'can_view');
            // dir ของรูปภาพอัปโหลด
            $imagedir = ROOT_PATH.DATA_FOLDER.'board/';
            $imageurl = WEB_URL.DATA_FOLDER.'board/';
            // รูปภาพ
            if (!empty($index->picture) && is_file($imagedir.$index->picture)) {
                $index->image_src = $imageurl.$index->picture;
            }
            if ($canView || $index->viewing == 1) {
                if ($canReply) {
                    // /board/printcommentitem.html
                    $listitem = Grid::create('board', $index->module, 'printcommentitem');
                    // รายการแสดงความคิดเห็น
                    foreach (\Index\Comment\Model::get($index, 'board_r') as $no => $item) {
                        // รูปภาพของความคิดเห็น
                        $picture = $item->picture != '' && is_file($imagedir.$item->picture) ? '<figure><img src="'.$imageurl.$item->picture.'" alt="'.$index->topic.'"></figure>' : '';
                        $listitem->add(array(
                            '/{DETAIL}/' => $picture.Gcms::showDetail(str_replace(array('{WEBURL}', '{', '}'), array(WEB_URL, '&#x007B;', '&#x007D;'), nl2br($item->detail)), $canView, true),
                            '/{DISPLAYNAME}/' => $item->displayname,
                            '/{DATE}/' => $item->last_update,
                            '/{IP}/' => Gcms::showip($item->ip, $login),
                            '/{NO}/' => $no + 1
                        ));
                    }
                }
                // รูปภาพในกระทู้
                $picture = empty($index->image_src) ? '' : '<figure><img src="'.$index->image_src.'" alt="'.$index->topic.'"></figure>';
                // เนื้อหา
                $detail = Gcms::showDetail(str_replace(array('{WEBURL}', '{', '}'), array(WEB_URL, '&#x007B;', '&#x007D;'), nl2br($index->detail)), $canView, true);
                $replace = array(
                    '/{COMMENTLIST}/' => isset($listitem) ? $listitem->render() : '',
                    '/{TOPIC}/' => $index->topic,
                    '/{DETAIL}/' => $picture.$detail,
                    '/{DATE}/' => $index->create_date,
                    '/{URL}/' => \Board\Index\Controller::url($index->module, $index->id),
                    '/{DISPLAYNAME}/' => $index->name
                );
                // /board/print.html

                return Template::create('board', $index->module, 'print')->add($replace)->render();
            }
        }
        return false;
    }
}
