<?php
/**
 * @filesource modules/edocument/models/download.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Download;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * อ่านข้อมูลโมดูล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ดาวน์โหลดไฟล์
     *
     * @param Request $request
     *
     * @return object
     */
    public function action(Request $request)
    {
        if ($request->initSession() && $request->isReferer()) {
            // ค่าที่ส่งมา
            if (preg_match('/^(icon\-)?(download|downloading|delete)\s([0-9]+)$/', $request->post('id')->toString(), $match)) {
                $action = $request->post('action')->toString();
                // login
                $login = Login::isMember();
                $login = $login ? array('id' => (int) $login['id'], 'status' => $login['status']) : array('id' => 0, 'status' => -1);
                // ไฟล์ดาวน์โหลด
                $download = $this->get((int) $match[3], $login['id']);
                $ret = array();
                if ($download) {
                    if ($action == 'download' || $action == 'downloading') {
                        // ตรวจสอบข้อมูล
                        if (!$download || !is_file(ROOT_PATH.DATA_FOLDER.'edocument/'.$download->file)) {
                            $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
                        } elseif (!in_array($login['status'], $download->reciever)) {
                            $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
                        } elseif ($action === 'download') {
                            $ret['confirm'] = Language::get('Do you want to download the file ?');
                        } elseif ($action === 'downloading') {
                            // อัปเดตดาวน์โหลด
                            $save = array(
                                'last_update' => time(),
                                'downloads' => $download->downloads + 1
                            );
                            if ($download->download_id == 0) {
                                $save['module_id'] = $download->module_id;
                                $save['document_id'] = $download->id;
                                $save['member_id'] = $login['id'];
                                $this->db()->insert($this->getTableName('edocument_download'), $save);
                            } else {
                                $this->db()->update($this->getTableName('edocument_download'), (int) $download->download_id, $save);
                            }
                            // URL สำหรับดาวน์โหลด
                            $id = uniqid();
                            $_SESSION[$id] = array(
                                'file' => ROOT_PATH.DATA_FOLDER.'edocument/'.$download->file,
                                'name' => $download->download_action == 1 ? '' : $download->topic.'.'.$download->ext,
                                'mime' => $download->download_action == 1 ? \Kotchasan\Mime::get($download->ext) : 'application/octet-stream'
                            );
                            // คืนค่า URL สำหรับดาวน์โหลด
                            $ret['target'] = $download->download_action;
                            $ret['href'] = WEB_URL.'modules/edocument/filedownload.php?id='.$id;
                        }
                    } elseif ($action === 'delete') {
                        // ลบ
                        if ($login['id'] == $download->sender_id || \Gcms\Gcms::canConfig($login, $download, 'moderator')) {
                            @unlink(ROOT_PATH.DATA_FOLDER.'edocument/'.$download->file);
                            $this->db()->delete($this->getTableName('edocument'), (int) $download->id);
                            $this->db()->delete($this->getTableName('edocument_download'), array('document_id', (int) $download->id), 0);
                        } else {
                            $action = '';
                        }
                    }
                    $ret['id'] = $download->id;
                    $ret['action'] = $action;
                }
                // คืนค่าเป็น JSON
                echo json_encode($ret);
            }
        }
    }

    /**
     * ตรวจสอบไฟล์ที่เลือก
     *
     * @param int $id
     * @param int $login_id
     *
     * @return object
     */
    public function get($id, $login_id)
    {
        $search = $this->db()->createQuery()
            ->from('edocument D')
            ->join('modules M', 'INNER', array('M.id', 'D.module_id'))
            ->join('edocument_download N', 'LEFT', array(array('N.document_id', 'D.id'), array('N.member_id', $login_id)))
            ->where(array('D.id', $id))
            ->cacheOn()
            ->toArray()
            ->first('D.*', 'N.id download_id', 'N.downloads', 'M.config');
        if ($search) {
            $config = @unserialize($search['config']);
            unset($search['config']);
            foreach ($config as $key => $value) {
                $search[$key] = $value;
            }
            $reciever = @unserialize($search['reciever']);
            $search['reciever'] = is_array($reciever) ? $reciever : array();
            return (object) $search;
        }
        return null;
    }
}
