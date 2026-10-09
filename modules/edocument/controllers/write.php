<?php
/**
 * @filesource modules/edocument/controllers/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Edocument\Write;

use Gcms\Api as ApiController;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API E-Document write controller
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/edocument/write/get
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $module = $this->getModule($request->get('module_id')->toInt());
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            if (!\Web\Login::checkStatus($login, $module->config, ['can_upload', 'moderator'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $item = \Edocument\Write\Model::get($request->get('id')->toInt(), $module);
            if (!$item) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $isModerator = \Web\Login::checkStatus($login, $module->config, ['moderator']) ? true : false;
            if ($item->id > 0 && !$isModerator && $item->sender_id !== (int) $login->id) {
                return $this->errorResponse('Permission required', 403);
            }

            $item->file_url = empty($item->file) ? '' : \Edocument\Setup\Model::toFileUrl($item->file);
            $item->file_name = $item->file_url === '' ? '' : $item->topic.'.'.$item->ext;
            $item->send_mail = $item->id === 0 && $module->config->send_mail ? 1 : 0;
            $item->file_typies = $module->config->file_typies;
            $item->file_typies_text = '.'.implode(', .', $module->config->file_typies);
            $item->upload_size_text = \Kotchasan\Text::formatFileSize((int) $module->config->upload_size);

            return $this->successResponse([
                'data' => $item,
                'options' => [
                    'user_status' => array_merge([
                        ['value' => '-1', 'text' => '{LNG_Guest}']
                    ], \Gcms\Controller::getUserStatusOptions())
                ]
            ], 'E-Document details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/edocument/write/save
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = $this->getModule($request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            if (!\Web\Login::checkStatus($login, $module->config, ['can_upload', 'moderator']) || !ApiController::isNotDemoMode($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            $item = \Edocument\Write\Model::get($request->post('id')->toInt(), $module);
            if (!$item) {
                return $this->errorResponse('No data available', 404);
            }

            $isModerator = \Web\Login::checkStatus($login, $module->config, ['moderator']) ? true : false;
            if ($item->id > 0 && !$isModerator && $item->sender_id !== (int) $login->id) {
                return $this->errorResponse('Permission required', 403);
            }

            $save = [
                'document_no' => $request->post('document_no')->topic(),
                'topic' => $request->post('topic')->topic(),
                'detail' => $request->post('detail')->textarea()
            ];

            $reciever = json_decode($request->post('reciever')->toJson(), true);
            if (!is_array($reciever)) {
                $reciever = [];
            }
            $reciever = array_values(array_unique(array_map('intval', $reciever)));

            $errors = [];
            if (mb_strlen($save['document_no']) > 20) {
                $errors['document_no'] = Language::replace('Must not exceed :n characters', [':n' => 20]);
            } elseif ($save['document_no'] !== '' && \Edocument\Write\Model::documentNoExists($save['document_no'], $item->id)) {
                $errors['document_no'] = Language::replace('This :name already exist', [':name' => Language::get('Document number')]);
            }
            if (mb_strlen($save['topic']) > 50) {
                $errors['topic'] = Language::replace('Must not exceed :n characters', [':n' => 50]);
            }
            if (empty($reciever)) {
                $errors['reciever'] = Language::replace('Please select :name at least one item', [':name' => Language::get('Recipient')]);
            }
            if (trim($save['detail']) === '') {
                $errors['detail'] = 'Please fill in';
            }

            $file = $request->getUploadedFiles()['file'] ?? null;
            $hasUpload = $file && $file->hasUploadFile();
            if ($hasUpload) {
                if (!$file->validFileExt($module->config->file_typies)) {
                    $errors['file'] = 'The type of file is invalid';
                } elseif ($file->getSize() > (int) $module->config->upload_size) {
                    $errors['file'] = 'The file size larger than the limit';
                }
            } elseif ($file && $file->hasError()) {
                $errors['file'] = Language::get($file->getErrorMessage());
            } elseif ($item->id === 0) {
                $errors['file'] = 'Please select file';
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            if ($hasUpload) {
                $dir = ROOT_PATH.\Edocument\Setup\Model::uploadDir();
                if (!File::makeDirectory($dir)) {
                    return $this->formErrorResponse([
                        'file' => Language::replace('Directory %s cannot be created or is read-only.', \Edocument\Setup\Model::uploadDir())
                    ], 422);
                }

                $save['ext'] = strtolower((string) $file->getClientFileExt());
                // `edocument`.`file` is varchar(15) on many legacy sites:
                // 10 random hex chars + '.' + ext (max 4)
                do {
                    $save['file'] = bin2hex(random_bytes(5)).'.'.$save['ext'];
                } while (file_exists($dir.$save['file']));
                $file->moveTo($dir.$save['file']);
                $save['size'] = (int) $file->getSize();

                if ($save['topic'] === '') {
                    $save['topic'] = mb_substr(pathinfo((string) $file->getClientFilename(), PATHINFO_FILENAME), 0, 50);
                }

                if (!empty($item->file) && $item->file !== $save['file']) {
                    $oldPath = \Edocument\Setup\Model::toFilePath($item->file);
                    if ($oldPath && is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            }

            if ($save['topic'] === '') {
                $save['topic'] = $item->topic;
            }

            $save['reciever'] = json_encode($reciever);
            $save['last_update'] = time();

            $db = \Kotchasan\DB::create();
            if ($item->id === 0) {
                // Explicit ID: some legacy `edocument` tables have no AUTO_INCREMENT
                $item->id = $db->nextId('edocument');
                $save['id'] = $item->id;
                $save['module_id'] = (int) $module->id;
                $save['sender_id'] = (int) $login->id;
                $save['downloads'] = 0;
                $save['ip'] = $request->getClientIp();
                $db->insert('edocument', $save);
            } else {
                $db->update('edocument', ['id', $item->id], $save);
            }

            if ($save['document_no'] === '') {
                // No number given: generate one from the module format and the real ID
                $save['document_no'] = \Edocument\Write\Model::documentNo($module->config->format_no, $item->id);
                $db->update('edocument', ['id', $item->id], ['document_no' => $save['document_no']]);
            }

            \Index\Log\Model::add($item->id, 'edocument', 'Save', 'Save E-Document ID : '.$item->id, $login->id);

            $message = 'Saved successfully';
            if ($request->post('send_mail')->toBoolean()) {
                \Edocument\Write\Model::notify($module, $save, $reciever);
                $message = 'Save and email completed';
            }

            return $this->redirectResponse('/edocument-setup?module_id='.$module->id, $message, 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Load an e-document module with normalized config.
     *
     * @param int $module_id
     *
     * @return object|null
     */
    private function getModule($module_id)
    {
        $module = \Index\Module\Model::getModuleWithConfig('edocument', $module_id);
        if (!$module) {
            return null;
        }
        $module->config = \Edocument\Settings\Model::normalizeConfig($module->config);

        return $module;
    }
}
