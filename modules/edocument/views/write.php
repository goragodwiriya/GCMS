<?php
/**
 * @filesource modules/edocument/views/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Write;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Mime;
use Kotchasan\Template;
use Kotchasan\Text;

/**
 * module=editprofile&tab=edocumenterite
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * อัปโหลดเอกสาร
     *
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public function render(Request $request, $index)
    {
        if (Login::isMember()) {
            // ตรวจสอบโมดูลและอ่านข้อมูลโมดูล
            $index = \Edocument\Write\Model::get($request->request('id')->toInt(), $index);
            if ($index) {
                // กลุ่มผู้รับ
                $reciever = array();
                foreach (array(-1 => '{LNG_Guest}') + self::$cfg->member_status as $key => $value) {
                    $sel = in_array($key, $index->reciever) ? ' checked' : '';
                    $sel .= $key == -1 ? ' id=reciever' : '';
                    $reciever[] = '<label><input type=checkbox value='.$key.$sel.' name=reciever[]>&nbsp;'.$value.'</label>';
                }
                $modules = array();
                foreach ($index->modules as $module_id => $topic) {
                    $sel = $module_id == $index->module_id ? ' selected' : '';
                    $modules[] = '<option value='.$module_id.$sel.'>'.$topic.'</option>';
                }
                // title
                $title = Language::get($index->id == 0 ? 'Add New' : 'Edit');
                // /edocument/write.html
                $template = Template::create('edocument', $index->module->module, 'write');
                $template->add(array(
                    '/{TITLE}/' => $title,
                    '/{NO}/' => $index->document_no,
                    '/{TOPIC}/' => isset($index->topic) ? $index->topic : '',
                    '/{DETAIL}/' => isset($index->detail) ? $index->detail : '',
                    '/{TOKEN}/' => $request->createToken(),
                    '/{ACCEPT}/' => Mime::getAccept($index->module->file_typies),
                    '/{GROUPS}/' => implode('', $reciever),
                    '/{ID}/' => $index->id,
                    '/{MODULES}/' => implode('', $modules),
                    '/{SENDMAIL}/' => $index->id == 0 && $index->module->send_mail ? 'checked' : ''
                ));
                Gcms::$view->setContentsAfter(array(
                    '/:type/' => implode(', ', $index->module->file_typies),
                    '/:size/' => Text::formatFileSize($index->module->upload_size)
                ));
                // คืนค่า
                $index->topic = $index->module->topic.' - '.$title;
                $index->detail = $template->render();
                // คืนค่า HTML
                return $index;
            }
        }
        // not member
        return null;
    }
}
