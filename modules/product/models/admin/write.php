<?php
/**
 * @filesource modules/product/models/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Product\Admin\Write;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\ArrayTool;
use Kotchasan\Database\Sql;
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
                $tab = false;
                // details
                $details = array();
                $alias_topic = '';
                $languages = Language::installedLanguage();
                foreach ($languages as $lng) {
                    $topic = $request->post('topic_'.$lng)->topic();
                    $alias = Gcms::aliasName($request->post('topic_'.$lng)->toString());
                    $keywords = implode(',', $request->post('keywords_'.$lng, array())->topic());
                    $description = $request->post('description_'.$lng)->description();
                    if (!empty($topic)) {
                        $save = array();
                        $save['topic'] = $topic;
                        $save['keywords'] = empty($keywords) ? $request->post('topic_'.$lng)->keywords(255) : $keywords;
                        $save['description'] = empty($description) ? $request->post('details_'.$lng)->description(255) : $description;
                        $save['detail'] = $request->post('details_'.$lng)->detail();
                        $save['language'] = $lng;
                        $details[$lng] = $save;
                        $alias_topic = empty($alias_topic) ? $alias : $alias_topic;
                    }
                }
                $save = array(
                    'alias' => Gcms::aliasName($request->post('alias')->toString()),
                    'product_no' => $request->post('product_no')->topic(),
                    'published' => $request->post('published')->toBoolean()
                );
                // id ที่แก้ไข
                $id = $request->post('id')->toInt();
                $module_id = $request->post('module_id')->toInt();
                // ตรวจสอบข้อมูลที่เลือก คืนค่า ID ใหม่หากเป็นรายการใหม่
                $index = self::get($module_id, $id, true);
                if (empty($index) || !Gcms::canConfig($login, $index, 'can_write')) {
                    $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
                } else {
                    // ตรวจสอบข้อมูลที่กรอก
                    if (empty($details)) {
                        $lng = reset($languages);
                        $ret['ret_topic_'.$lng] = 'this';
                        $tab = !$tab ? 'detail_'.$lng : $tab;
                    } else {
                        foreach ($details as $lng => $values) {
                            if (mb_strlen($values['topic']) < 3) {
                                $ret['ret_topic_'.$lng] = 'this';
                                $tab = !$tab ? 'detail_'.$lng : $tab;
                            }
                        }
                    }
                    // มีข้อมูลมาภาษาเดียวให้แสดงในทุกภาษา
                    if (count($details) == 1) {
                        foreach ($details as $i => $item) {
                            $details[$i]['language'] = '';
                        }
                    }
                    // alias
                    if ($save['alias'] == '') {
                        $save['alias'] = $alias_topic;
                    }
                    // ค้นหาชื่อเรื่องซ้ำ
                    $search = $this->db()->first($this->getTableName('product'), array('alias', $save['alias']));
                    if ($search && ($id == 0 || $id != $search->id)) {
                        $ret['ret_alias'] = Language::replace('This :name already exist', array(':name' => Language::get('Alias')));
                        $tab = !$tab ? 'options' : $tab;
                    }
                    if (empty($ret)) {
                        // อัปโหลดไฟล์
                        foreach ($request->getUploadedFiles() as $item => $file) {
                            /* @var $file UploadedFile */
                            if ($file->hasUploadFile()) {
                                if (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'product/')) {
                                    // ไดเรคทอรี่ไม่สามารถสร้างได้
                                    $ret['ret_'.$item] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'product/');
                                    $tab = !$tab ? 'options' : $tab;
                                } else {
                                    // อัปโหลด
                                    $save[$item] = $index->id.'.jpg';
                                    try {
                                        // thumbnail
                                        $file->resizeImage($index->img_typies, ROOT_PATH.DATA_FOLDER.'product/', 'thumb_'.$save[$item], $index->thumb_width);
                                        // image
                                        $file->resizeImage($index->img_typies, ROOT_PATH.DATA_FOLDER.'product/', $save[$item], $index->image_width);
                                    } catch (\Exception $exc) {
                                        // ไม่สามารถอัปโหลดได้
                                        $ret['ret_'.$item] = Language::get($exc->getMessage());
                                        $tab = !$tab ? 'options' : $tab;
                                    }
                                }
                            }
                        }
                    }
                    if (empty($ret)) {
                        $save['last_update'] = time();
                        if (empty($id)) {
                            // ใหม่
                            $save['id'] = $index->id;
                            $save['module_id'] = $index->module_id;
                            $save['visited'] = 0;
                            $index->id = $this->db()->insert($this->getTableName('product'), $save);
                        } else {
                            // แก้ไข
                            $this->db()->update($this->getTableName('product'), $index->id, $save);
                        }
                        // ตาราง product_detail
                        $table = $this->getTableName('product_detail');
                        // ลบรายละเอียดเดิมออก
                        $this->db()->delete($table, array('id', $index->id), 0);
                        // บันทึกรายละเอียดใหม่
                        foreach ($details as $item) {
                            $item['id'] = $index->id;
                            $this->db()->insert($table, $item);
                        }
                        // ตาราง product_price
                        $table = $this->getTableName('product_price');
                        // ลบราคาเดิมออก
                        $this->db()->delete($table, array('id', $index->id), 0);
                        // บันทึกราคา
                        $this->db()->insert($table, array(
                            'id' => $index->id,
                            'price' => serialize(array($index->currency_unit => $request->post('price')->toDouble())),
                            'net' => serialize(array($index->currency_unit => $request->post('net')->toDouble()))
                        ));
                        // ส่งค่ากลับ
                        $ret['alert'] = Language::get('Saved successfully');
                        $ret['location'] = $request->getUri()->postBack('index.php', array('mid' => $index->module_id, 'module' => 'product-setup'));
                        // เคลียร์
                        $request->removeToken();
                    } elseif ($tab) {
                        $ret['tab'] = $tab;
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

    /**
     * อ่านสินค้าที่ $id
     *
     * @param int  $module_id ของโมดูล
     * @param int  $id        ID ของบทความ
     * @param bool $new_id    true คืนค่า ID ของบทความรายการใหม่ (สำหรับการบันทึก), false คืนค่า ID หากเป็นรายการใหม่
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($module_id, $id, $new_id)
    {
        // model
        $model = new static;
        $query = $model->db()->createQuery();
        if (empty($id)) {
            // ใหม่ ตรวจสอบโมดูล
            $query->select(Sql::NEXT('id', $model->getTableName('product'), null, 'id'), 'M.id module_id', 'M.owner', 'M.module', 'M.config')
                ->from('modules M')
                ->where(array(
                    array('M.id', $module_id),
                    array('M.owner', 'product')
                ));
        } else {
            // แก้ไข ตรวจสอบรายการที่เลือก
            $query->select('P.*', 'R.price', 'R.net', 'M.owner', 'M.module', 'M.config')
                ->from('product P')
                ->join('modules M', 'INNER', array(array('M.id', 'P.module_id'), array('M.owner', 'product')))
                ->join('product_price R', 'LEFT', array('R.id', 'P.id'))
                ->where(array('P.id', $id));
        }
        $result = $query->limit(1)->toArray()->execute();
        if (count($result) == 1) {
            $result = ArrayTool::unserialize($result[0]['config'], $result[0], empty($id));
            unset($result['config']);
            if ($id == 0) {
                $result['product_no'] = sprintf($result['product_no'], $result['id']);
                if (!$new_id) {
                    $result['id'] = 0;
                }
                $result['published'] = 1;
                $result['price'] = array($result['currency_unit'] => 0);
                $result['net'] = array($result['currency_unit'] => 0);
            } else {
                $result['price'] = self::getPrice($result['price'], $result['currency_unit']);
                $result['net'] = self::getPrice($result['net'], $result['currency_unit']);
            }
            return (object) $result;
        }
        return null;
    }

    /**
     * ราคาสินค้า
     *
     * @param string $price
     * @param string $currency_unit
     *
     * @return array array('THB' => 0, 'USD' => 0)
     */
    public static function getPrice($price, $currency_unit)
    {
        $values = @unserialize($price);
        if (!is_array($values)) {
            $values = array($currency_unit => (float) $price);
        }
        return $values;
    }

    /**
     * อ่านรายละเอียด (detail) ของบทความตามภาษา
     *
     * @param int    $module_id
     * @param int    $id
     * @param string $lng
     *
     * @return array
     */
    public static function details($module_id, $id, $lng)
    {
        $result = array();
        if (is_int($module_id) && $module_id > 0) {
            // model
            $model = new static;
            $query = $model->db()
                ->createQuery()
                ->select('language', 'topic', 'keywords', 'description', 'detail')
                ->from('product_detail')
                ->where(array('id', $id))
                ->toArray();
            foreach ($query->execute() as $i => $item) {
                $item['language'] = ($i == 0 && $item['language'] == '') ? $lng : $item['language'];
                $result[$item['language']] = (object) $item;
            }
        }
        return $result;
    }
}
