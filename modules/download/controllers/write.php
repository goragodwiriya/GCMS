<?php
/**
 * @filesource modules/download/controllers/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Download\Write;

use Download\Category\Model as CategoryModel;
use Gcms\Api as ApiController;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API Download write controller
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/download/write/get
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

            $module = \Index\Module\Model::getModuleWithConfig('download', $request->get('module_id')->toInt());
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $module->config = \Download\Settings\Model::normalizeConfig($module->config);
            $isModerator = \Web\Login::checkStatus($login, $module->config, ['moderator']) ? true : false;

            if (!\Web\Login::checkStatus($login, $module->config, ['can_upload', 'moderator'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $item = \Download\Write\Model::get($request->get('id')->toInt(), $module->id);
            if (!$item) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            if ($item->id > 0 && !$isModerator && (int) $item->member_id !== (int) $login->id) {
                return $this->errorResponse('Permission required', 403);
            }

            $item->is_owner = $item->id === 0 || (int) $item->member_id === (int) $login->id;
            $item->upload_size_text = \Kotchasan\Text::formatFileSize((int) $item->upload_size);
            $item->file_typies_text = '.'.implode(', .', (array) $item->file_typies);

            $item->file_url = '';
            if (!empty($item->file)) {
                $path = \Download\Setup\Model::toFilePath($item->file);
                if ($path && is_file($path)) {
                    $item->file_url = WEB_URL.DATA_FOLDER.\Download\Write\Model::normalizeFilePath($item->file);
                }
            }

            return $this->successResponse([
                'data' => $item,
                'options' => [
                    'category_id' => CategoryModel::toOptions($module->id, true),
                    'user_status' => array_merge([
                        ['value' => '-1', 'text' => '{LNG_Guest}']
                    ], \Gcms\Controller::getUserStatusOptions())
                ]
            ], 'Download details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/download/write/save
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

            $module = \Index\Module\Model::getModuleWithConfig('download', $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $module->config = \Download\Settings\Model::normalizeConfig($module->config);
            $isModerator = \Web\Login::checkStatus($login, $module->config, ['moderator']) ? true : false;
            if (!\Web\Login::checkStatus($login, $module->config, ['can_upload', 'moderator'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $item = \Download\Write\Model::get($request->post('id')->toInt(), $module->id);
            if (!$item) {
                return $this->errorResponse('No data available', 404);
            }
            if ($item->id > 0 && !$isModerator && (int) $item->member_id !== (int) $login->id) {
                return $this->errorResponse('Permission required', 403);
            }

            $save = [
                'name' => $request->post('name')->topic(),
                'category_id' => $request->post('category_id')->toInt(),
                'detail' => $request->post('detail')->topic(),
                'file' => \Download\Write\Model::normalizeFilePath($request->post('file_path')->toString())
            ];
            if ($save['category_id'] === 0) {
                $save['category_id'] = null;
            }

            $reciever = json_decode($request->post('reciever')->toJson(), true);
            if (!is_array($reciever)) {
                $reciever = [];
            }
            $save['reciever'] = array_values(array_unique(array_map('intval', $reciever)));

            $errors = [];
            if (empty($save['reciever'])) {
                $errors['reciever'] = Language::replace('Please select :name at least one item', [':name' => 'Recipient']);
            }
            if (trim($save['detail']) === '') {
                $errors['detail'] = 'Please fill in';
            }

            $db = \Kotchasan\DB::create();

            foreach ($request->getUploadedFiles() as $field => $file) {
                if ($field !== 'file') {
                    continue;
                }

                if ($file->hasUploadFile()) {
                    if (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'download/')) {
                        $errors['file'] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'download/');
                    } elseif (!$file->validFileExt($module->config->file_typies)) {
                        $errors['file'] = 'The type of file is invalid';
                    } elseif ($file->getSize() > (int) $module->config->upload_size) {
                        $errors['file'] = 'The file size larger than the limit';
                    } else {
                        $save['ext'] = strtolower((string) $file->getClientFileExt());
                        $save['file'] = 'download/'.uniqid('dl_', true).'.'.$save['ext'];
                        while (file_exists(ROOT_PATH.DATA_FOLDER.$save['file'])) {
                            $save['file'] = 'download/'.uniqid('dl_', true).'.'.$save['ext'];
                        }
                        $file->moveTo(ROOT_PATH.DATA_FOLDER.$save['file']);
                        $save['size'] = (int) $file->getSize();

                        if ($save['name'] === '') {
                            $clientFilename = (string) $file->getClientFilename();
                            $save['name'] = pathinfo($clientFilename, PATHINFO_FILENAME);
                        }

                        if (!empty($item->file) && $item->file !== $save['file']) {
                            $oldPath = \Download\Setup\Model::toFilePath($item->file);
                            if ($oldPath && is_file($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                    }
                } elseif ($file->hasError()) {
                    $errors['file'] = Language::get($file->getErrorMessage());
                }
            }

            if (empty($save['file'])) {
                $errors['file'] = 'Please select file';
            }

            if (empty($errors) && !isset($save['size'])) {
                $path = \Download\Setup\Model::toFilePath($save['file']);
                if ($path && is_file($path)) {
                    $save['size'] = (int) filesize($path);
                    if (empty($save['ext'])) {
                        $save['ext'] = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
                    }
                } else {
                    $errors['file'] = 'Please select file';
                }
            }

            if ($save['name'] === '' && !empty($save['file'])) {
                $save['name'] = pathinfo($save['file'], PATHINFO_FILENAME);
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            $save['reciever'] = json_encode($save['reciever'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($item->id === 0) {
                $save['module_id'] = (int) $module->id;
                $save['downloads'] = 0;
                $save['member_id'] = (int) $login->id;
                $save['created_at'] = date('Y-m-d H:i:s');
                $item->id = $db->insert('download', $save);
            } else {
                $db->update('download', ['id', (int) $item->id], $save);
            }

            \Index\Log\Model::add($item->id, 'download', 'Download', 'Save Download ID : '.$item->id, $login->id);

            return $this->redirectResponse('back', 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
