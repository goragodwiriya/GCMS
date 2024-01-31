<?php
/**
 * @filesource modules/video/models/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Video\Admin\Write;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\ArrayTool;
use Kotchasan\File;
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
     * อ่านข้อมูลรายการที่เลือก
     *
     * @param int $module_id ของโมดูล
     * @param int $id        ID
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($module_id, $id)
    {
        // model
        $model = new static;
        $query = $model->db()->createQuery();
        if (empty($id)) {
            // ใหม่ ตรวจสอบโมดูล
            $query->select('0 id', 'M.id module_id', 'M.owner', 'M.module', 'M.config')
                ->from('modules M')
                ->where(array(
                    array('M.id', $module_id),
                    array('M.owner', 'video')
                ));
        } else {
            // แก้ไข ตรวจสอบรายการที่เลือก
            $query->select('A.*', 'M.owner', 'M.module', 'M.config')
                ->from('video A')
                ->join('modules M', 'INNER', array(array('M.id', 'A.module_id'), array('M.owner', 'video')))
                ->where(array('A.id', $id));
        }
        $result = $query->limit(1)->toArray()->execute();
        if (count($result) == 1) {
            $result = ArrayTool::unserialize($result[0]['config'], $result[0], empty($id));
            unset($result['config']);
            return (object) $result;
        }
        return null;
    }

    /**
     * บันทึก
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // referer, token, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::notDemoMode($login)) {
                // ค่าที่ส่งมา
                $save = array(
                    'youtube' => $request->post('write_youtube')->topic(),
                    'topic' => $request->post('write_topic')->topic(),
                    'description' => $request->post('write_description')->description()
                );
                $id = $request->post('write_id')->toInt();
                // ตรวจสอบรายการที่เลือก
                $index = self::get($request->post('module_id')->toInt(), $id);
                if ($index && Gcms::canConfig($login, $index, 'can_write')) {
                    if ($save['youtube'] == '') {
                        $ret['ret_write_youtube'] = 'this';
                    } else {
                        // ตรวจสอบรายการซ้ำ
                        $search = $this->db()->first($this->getTableName('video'), array(
                            array('youtube', $save['youtube']),
                            array('module_id', (int) $index->module_id)
                        ));
                        if ($search && ($id == 0 || $id != $search->id)) {
                            $ret['ret_write_youtube'] = Language::replace('This :name already exist', array(':name' => Language::get('Video')));
                        } else {
                            $ret['ret_write_youtube'] = '';
                            // get youtube data
                            $url = 'https://www.googleapis.com/youtube/v3/videos?part=snippet,statistics&id='.$save['youtube'].(empty($index->google_api_key) ? '' : '&key='.$index->google_api_key);
                            if (function_exists('curl_init') && $ch = @curl_init()) {
                                curl_setopt($ch, CURLOPT_URL, $url);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                $feed = curl_exec($ch);
                                curl_close($ch);
                            } else {
                                $feed = file_get_contents($url);
                            }
                            $datas = json_decode($feed);
                            if (isset($datas->{'error'})) {
                                $ret['alert'] = $datas->{'error'}->{'message'};
                            } else {
                                $items = $datas->{'items'};
                                if (count($items) == 0) {
                                    $ret['ret_write_youtube'] = Language::get('Video not found');
                                } else {
                                    $error = false;
                                    $item = $items[0]->{'snippet'};
                                    $save['topic'] = trim($item->{'title'});
                                    $save['description'] = trim($item->{'description'});
                                    $save['views'] = (int) $items[0]->{'statistics'}->{'viewCount'};
                                    // video thumbnail
                                    if (isset($item->{'thumbnails'}->{'standard'})) {
                                        $url = $item->{'thumbnails'}->{'standard'}->{'url'};
                                    } else {
                                        $url = $item->{'thumbnails'}->{'high'}->{'url'};
                                    }
                                    if (function_exists('curl_init') && $ch = @curl_init()) {
                                        curl_setopt($ch, CURLOPT_URL, $url);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                        $thumbnail = curl_exec($ch);
                                        curl_close($ch);
                                    } else {
                                        $thumbnail = file_get_contents($url);
                                    }
                                    if (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'video/')) {
                                        // ไดเรคทอรี่ไม่สามารถสร้างได้
                                        $ret['alert'] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'video/');
                                        $error = true;
                                    } else {
                                        $f = @fopen(ROOT_PATH.DATA_FOLDER.'video/'.$save['youtube'].'.jpg', 'w');
                                        if (!$f) {
                                            // ไดเร็คทอรี่ไม่สามารถเขียนได้
                                            $ret['alert'] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'video/'.$save['youtube'].'.jpg');
                                            $error = true;
                                        } else {
                                            fwrite($f, $thumbnail);
                                            fclose($f);
                                            $ret['imgIcon'] = WEB_URL.DATA_FOLDER.'video/'.$save['youtube'].'.jpg?'.time();
                                        }
                                    }
                                    if (!$error) {
                                        // save
                                        $save['last_update'] = time();
                                        if ($id == 0) {
                                            // ใหม่
                                            $save['module_id'] = $index->module_id;
                                            $save['views'] = 0;
                                            $id = $this->db()->insert($this->getTableName('video'), $save);
                                        } else {
                                            // แก้ไข
                                            $this->db()->update($this->getTableName('video'), $id, $save);
                                        }
                                        // คืนค่า
                                        $ret['alert'] = Language::get('Saved successfully');
                                        $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'video-setup', 'mid' => $index->module_id));
                                        // เคลียร์
                                        $request->removeToken();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
