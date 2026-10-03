<?php
/**
 * @filesource modules/personnel/controllers/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Personnel\Write;

use Gcms\Api as ApiController;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Language;

/**
 * API Personnel CRUD Controller
 * Handle create / edit personnel and upload photo
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/personnel/write/get
     * Get personnel detail by ID
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            // Authenticate request
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Load module configuration
            $module = \Index\Module\Model::getModuleWithConfig('personnel', $request->get('module_id')->toInt());
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_manage'])) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            // Get personnel by ID
            $person = \Personnel\Write\Model::get($request->get('id')->toInt(), $module->id);
            if (!$person) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $person->img_typies = implode(', ', self::$cfg->img_typies ?? ['jpg', 'jpeg', 'png', 'webp']);
            $person->options = [
                'department' => \Personnel\Category\Model::toOptions($module->id, 'department'),
                'level' => \Personnel\Category\Model::levelOptions()
            ];

            return $this->successResponse($person, 'Personnel detail retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/personnel/write/save
     * Save personnel (create or update)
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            // Authenticate request
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            // Load module configuration
            $module = \Index\Module\Model::getModuleWithConfig('personnel', $request->post('module_id')->toInt());
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_manage'])) {
                return $this->errorResponse('No data available', 404);
            }

            // Get personnel by ID
            $person = \Personnel\Write\Model::get($request->post('id')->toInt(), $module->id);
            if (!$person) {
                return $this->errorResponse('No data available', 404);
            }

            $save = [
                'module_id' => $person->module_id,
                'name' => $request->post('name')->topic(),
                'department' => $request->post('department')->toInt(),
                'position' => $request->post('position')->topic(),
                'phone' => $request->post('phone')->topic(),
                'email' => $request->post('email')->email(),
                'level' => $request->post('level')->toInt(),
                'published' => $request->post('published')->toBoolean(),
                'detail' => $request->post('detail')->textarea(),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $errors = [];
            if (empty($save['name'])) {
                $errors['name'] = 'Please fill in';
            }
            if (empty($save['department'])) {
                $errors['department'] = 'Please select';
            }

            $db = \Kotchasan\DB::create();

            if (empty($errors)) {
                if ($person->id > 0) {
                    $save['id'] = $person->id;
                } else {
                    $save['id'] = $db->nextId('personnel');
                }

                // File storage directory
                $dir = ROOT_PATH.DATA_FOLDER.'personnel/';
                // Upload file
                foreach ($request->getUploadedFiles() as $item => $file) {
                    // Name of file to upload
                    if ($item === 'image') {
                        if (!File::makeDirectory($dir)) {
                            // The directory cannot be created.
                            $errors[$item] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.$item.'/');
                        } elseif ($file->hasUploadFile()) {
                            try {
                                $save['picture'] = uniqid().self::$cfg->stored_img_type;
                                $file->resizeImage(self::$cfg->img_typies, $dir, $save['picture'], self::$cfg->stored_img_size);
                                if ($save['picture'] !== $person->picture) {
                                    $path = ROOT_PATH.DATA_FOLDER.'personnel/'.$person->picture;
                                    if (file_exists($path) && is_file($path)) {
                                        unlink($path);
                                    }
                                }
                            } catch (\Exception $exc) {
                                // Unable to upload
                                $errors[$item] = Language::get($exc->getMessage());
                            }
                        } elseif ($err = $file->getErrorMessage()) {
                            // Upload error
                            $errors[$item] = $err;
                        }
                    }
                }
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            if ($person->id > 0) {
                // Update
                $db->update('personnel', ['id', $save['id']], $save);
            } else {
                // Create
                $save['created_at'] = date('Y-m-d H:i:s');
                $db->insert('personnel', $save);
            }

            // Log
            \Index\Log\Model::add($save['id'], 'personnel', 'Personnel', 'Save Personnel: '.$save['name'], $login->id);

            // Redirect to personnel list page
            return $this->redirectResponse('back', 'Saved successfully');

            return $this->formErrorResponse($errors, 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/personnel/write/remoove-image
     * Remove featured image from article
     *
     * @param Request $request
     *
     * @return Response
     */
    public function removeImage(Request $request)
    {
        try {
            // Validate request method
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $module_id = $request->post('module_id')->toInt();
            $id = $request->post('id')->toInt();

            // Load module configuration
            $module = \Index\Module\Model::getModuleWithConfig('personnel', $module_id);
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_manage'])) {
                return $this->errorResponse('No data available', 404);
            }

            // Get article image
            $db = \Kotchasan\DB::create();
            $person = $db->first('personnel', [['id', $id], ['module_id', $module_id]], ['picture']);
            if ($person) {
                $path = ROOT_PATH.DATA_FOLDER.'personnel/'.$person->picture;
                if (file_exists($path) && is_file($path)) {
                    // remove picture
                    unlink($path);
                    // update database
                    $db->update('personnel', [['id', $id], ['module_id', $module_id]], ['picture' => '']);
                }

                // Log
                \Index\Log\Model::add($module_id, 'personnel', 'Personnel', 'Delete image ID:'.$id, $login->id);
            }
            return $this->successResponse([], 'Image deleted');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
