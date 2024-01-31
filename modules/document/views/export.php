<?php
/**
 * @filesource modules/document/views/export.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Export;

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
        // อ่านรายการที่เลือก
        $index = \Document\View\Model::get($request, (object) array('id' => $request->get('id')->toInt()));
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
                $index->image_src = '';
                if (!empty($index->picture) && is_file($imagedir.$index->picture)) {
                    $index->image_src = WEB_URL.$dir.$index->picture;
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
                        $item->detail = Gcms::showDetail(str_replace(array('{WEBURL}', '{', '}'), array(WEB_URL, '&#x007B;', '&#x007D;'), nl2br($item->detail)), $canView, true);
                        $listitem->add(array(
                            '/{DETAIL}/' => $item->detail,
                            '/{DISPLAYNAME}/' => $item->displayname,
                            '/{DATE}/' => $item->last_update,
                            '/{IP}/' => Gcms::showip($item->ip, $login),
                            '/{NO}/' => $no + 1
                        ));
                    }
                }
                // เนื้อหา
                $index->detail = Gcms::showDetail(str_replace(array('{WEBURL}', '{', '}'), array(WEB_URL, '&#x007B;', '&#x007D;'), $index->detail), $canView, true);
                $replace = array(
                    '/{COMMENTLIST}/' => isset($listitem) ? $listitem->render() : '',
                    '/{TOPIC}/' => $index->topic,
                    '/{DETAIL}/' => $index->detail,
                    '/{DATE}/' => $index->create_date,
                    '/<IMAGE>(.*)<\/IMAGE>/s' => empty($index->image_src) ? '' : '$1',
                    '/{IMG}/' => $index->image_src,
                    '/{DISPLAYNAME}/' => $index->displayname,
                    '/{URL}/' => $index->canonical
                );
                // /document/print.html

                return Template::create('document', $index->module, 'print')->add($replace)->render();
            }
        }
        return false;
    }

    /**
     * ส่งออกเป็นไฟล์ PDF
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function pdf(Request $request, $index)
    {
        // อ่านรายการที่เลือก
        $index = \Document\View\Model::get($request, (object) array('id' => $request->get('id')->toInt()));
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
                        $index->picture = WEB_URL.$dir.$index->picture;
                        $index->pictureWidth = $size[0];
                        $index->pictureHeight = $size[1];
                    } else {
                        $index->picture = '';
                    }
                } else {
                    $index->picture = '';
                }
                // URL ของหน้า
                $index->canonical = \Document\Index\Controller::url($index->module, $index->alias, $index->id, false);
                // แสดงความคิดเห็นได้ จากการตั้งค่าโมดูล
                $canReply = !empty($index->can_reply);
                if ($canReply) {
                    // query รายการแสดงความคิดเห็น
                    $index->comment_items = \Index\Comment\Model::get($index);
                    // document/printcommentitem.html
                    $listitem = Grid::create('document', $index->module, 'printcommentitem');
                    // รายการแสดงความคิดเห็น
                    foreach ($index->comment_items as $no => $item) {
                        $item->detail = Gcms::showDetail(str_replace(array('{WEBURL}', '{', '}'), array(WEB_URL, '&#x007B;', '&#x007D;'), nl2br($item->detail)), $canView, true);
                        $listitem->add(array(
                            '/{DETAIL}/' => $item->detail,
                            '/{DISPLAYNAME}/' => $item->displayname,
                            '/{DATE}/' => $item->last_update,
                            '/{IP}/' => Gcms::showip($item->ip, $login),
                            '/{NO}/' => $no + 1
                        ));
                    }
                }
                // เนื้อหา
                $index->detail = Gcms::showDetail(str_replace(array('{WEBURL}', '{', '}'), array(WEB_URL, '&#x007B;', '&#x007D;'), $index->detail), $canView, true);
                $replace = array(
                    '/{COMMENTLIST}/' => isset($listitem) ? $listitem->render() : '',
                    '/{TOPIC}/' => $index->topic,
                    '/{DETAIL}/' => $index->detail,
                    '/{DATE}/' => $index->create_date,
                    '/<IMAGE>(.*)<\/IMAGE>/s' => empty($index->picture) ? '' : '$1',
                    '/{IMG}/' => $index->picture,
                    '/{DISPLAYNAME}/' => $index->displayname,
                    '/{URL}/' => $index->canonical,
                    '/{WEBURL}/' => WEB_URL
                );
                $content = $this->renderHTML(Template::create('document', $index->module, 'print')->add($replace)->render());
                $pdf = new \Kotchasan\Pdf();
                $pdf->AddPage();
                $pdf->WriteHTML($content);
                $pdf->Output();
                // คืนค่าสำเร็จ

                return true;
            }
        }
    }
}
