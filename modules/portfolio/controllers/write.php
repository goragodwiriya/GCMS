<?php
/**
 * @filesource modules/portfolio/controllers/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Portfolio\Write;

use Gcms\Api as ApiController;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API Portfolio CRUD Controller — mirrors Personnel\Write\Controller's
 * shape (get/save/remove-image, single image upload).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/portfolio/write/get
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $module = \Index\Module\Model::getModuleWithConfig('portfolio', $request->get('module_id')->toInt());
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_write'])) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $item = \Portfolio\Write\Model::get($request->get('id')->toInt(), $module->id);
            if (!$item) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $item->img_typies = implode(', ', self::$cfg->img_typies ?? ['jpg', 'jpeg', 'png', 'webp']);

            return $this->successResponse($item, 'Portfolio item retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/portfolio/write/save
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

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = \Index\Module\Model::getModuleWithConfig('portfolio', $request->post('module_id')->toInt());
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_write'])) {
                return $this->errorResponse('No data available', 404);
            }

            $item = \Portfolio\Write\Model::get($request->post('id')->toInt(), $module->id);
            if (!$item) {
                return $this->errorResponse('No data available', 404);
            }

            $keywords = [];
            foreach ($request->post('keywords', [])->topic() as $keyword) {
                if ($keyword !== '') {
                    $keywords[$keyword] = $keyword;
                }
            }

            $save = [
                'module_id' => $item->module_id,
                'title' => $request->post('title')->topic(),
                'keywords' => implode(',', $keywords),
                'detail' => $request->post('detail')->detail(),
                'url' => $request->post('url')->url(),
                'published' => $request->post('published')->toBoolean() ? '1' : '0'
            ];

            $errors = [];
            if (mb_strlen($save['title']) < 3) {
                $errors['title'] = 'Please fill in';
            }
            if ($save['detail'] === '') {
                $errors['detail'] = 'Please fill in';
            }

            $db = \Kotchasan\DB::create();

            if (empty($errors)) {
                $save['id'] = $item->id > 0 ? $item->id : $db->nextId('portfolio');

                $dir = ROOT_PATH.DATA_FOLDER.'portfolio/';
                foreach ($request->getUploadedFiles() as $field => $file) {
                    if ($field !== 'image') {
                        continue;
                    }
                    if (!File::makeDirectory($dir)) {
                        $errors[$field] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'portfolio/');
                    } elseif ($file->hasUploadFile()) {
                        try {
                            $name = $save['id'].self::$cfg->stored_img_type;
                            $file->resizeImage(self::$cfg->img_typies, $dir, $name, self::$cfg->stored_img_size);
                            $save['image'] = $name;
                        } catch (\Exception $exc) {
                            $errors[$field] = Language::get($exc->getMessage());
                        }
                    } elseif ($err = $file->getErrorMessage()) {
                        $errors[$field] = $err;
                    }
                }
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            if ($item->id > 0) {
                $db->update('portfolio', ['id', $save['id']], $save);
            } else {
                $save['visited'] = 0;
                $save['created_at'] = time();
                $db->insert('portfolio', $save);
            }

            \Index\Log\Model::add($save['id'], 'portfolio', 'Portfolio', 'Save Portfolio: '.$save['title'], $login->id);

            return $this->redirectResponse('back', 'Saved successfully');
        } catch (\Kotchasan\ApiException $e) {
            return $this->errorResponse($e->getMessage(), (int) $e->getCode() ?: 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/portfolio/write/remove-image
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function removeImage(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $moduleId = $request->post('module_id')->toInt();
            $module = \Index\Module\Model::getModuleWithConfig('portfolio', $moduleId);
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_write'])) {
                return $this->errorResponse('No data available', 404);
            }

            $id = $request->post('id')->toInt();
            $db = \Kotchasan\DB::create();
            $item = $db->first('portfolio', [['id', $id], ['module_id', $moduleId]], ['id', 'image']);
            if ($item) {
                if ($item->image !== '' && is_file(ROOT_PATH.DATA_FOLDER.'portfolio/'.$item->image)) {
                    unlink(ROOT_PATH.DATA_FOLDER.'portfolio/'.$item->image);
                }
                $db->update('portfolio', ['id', $item->id], ['image' => '']);
                \Index\Log\Model::add($moduleId, 'portfolio', 'Portfolio', 'Delete image ID:'.$id, $login->id);
            }

            return $this->successResponse([], 'Image deleted');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
