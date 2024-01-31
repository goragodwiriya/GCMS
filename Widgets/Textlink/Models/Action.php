<?php
/**
 * @filesource Widgets/Textlink/Models/Action.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Textlink\Models;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * Textlink Action
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Action extends \Kotchasan\Model
{
    /**
     * @param Request $request
     */
    public function get(Request $request)
    {
        // referer, session, admin, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::adminAccess()) {
            if (Login::notDemoMode($login)) {
                // ค่าที่ส่งมา
                $action = $request->post('action')->toString();
                $id = $request->post('id')->filter('0-9,');
                $value = $request->post('val')->toString();
                if ($action == 'delete') {
                    // ลบ
                    $query = $this->db()->createQuery()
                        ->select('id', 'logo')
                        ->from('textlink')
                        ->where(array('id', explode(',', $id)));
                    $ids = array();
                    foreach ($query->execute() as $item) {
                        $ids[] = $item->id;
                        if ($item->logo != '' && is_file(ROOT_PATH.DATA_FOLDER.'image/'.$item->logo)) {
                            unlink(ROOT_PATH.DATA_FOLDER.'image/'.$item->logo);
                        }
                    }
                    $this->db()->createQuery()->delete('textlink', array('id', $ids))->execute();
                    // คืนค่า JSON
                    echo json_encode(array('location' => 'reload'));
                } elseif ($action == 'move') {
                    // sort link
                    $max = 1;
                    $query = $this->db()->createQuery()->update('textlink');
                    foreach (explode(',', $request->post('data')->filter('0-9,')) as $i) {
                        $query->set(array('link_order' => $max))->where((int) $i)->execute();
                        ++$max;
                    }
                } elseif ($action == 'styles') {
                    // เลือกรูปแบบ
                    $styles = include ROOT_PATH.'Widgets/Textlink/styles.php';
                    // template
                    if ($value == 'custom') {
                        $textlink = $this->db()->createQuery()->from('textlink')->where((int) $id)->first('template');
                        if ($textlink) {
                            echo $textlink->template;
                        }
                    } elseif (isset($styles[$value])) {
                        echo $styles[$value];
                    }
                } elseif ($action == 'published') {
                    // เผยแพร่
                    $query = $this->db()->createQuery()->where((int) $id);
                    $textlink = $query->from('textlink')->first('id', 'published');
                    if ($textlink) {
                        $published = $textlink->published == 0 ? 1 : 0;
                        $this->db()->update($this->getTableName('textlink'), $textlink->id, array('published' => $published));
                        // คืนค่าเป็น JSON
                        echo json_encode(array(
                            'title' => Language::get('PUBLISHEDS', null, $published),
                            'class' => 'icon-published'.$published,
                            'elem' => 'published_'.$textlink->id
                        ));
                    }
                } elseif (preg_match('/^published_([0-1])$/', $action, $match)) {
                    // เผยแพร่ (multi)
                    $this->db()->createQuery()
                        ->update('textlink')
                        ->set(array('published' => (int) $match[1]))
                        ->where(array('id', explode(',', $id)))
                        ->execute();
                }
            }
        }
    }
}
