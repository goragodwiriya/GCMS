<?php
/**
 * @filesource modules/gallery/controllers/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Gallery\Write;

use Gcms\Api as ApiController;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Language;

/**
 * API Gallery Album Controller
 * Handle create / edit album and upload images
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/gallery/write/get
     * Get album details by ID
     *
     * @param Request $request
     *
     * @return Response
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            // Authenticate request
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            // Load module configuration
            $module = \Index\Module\Model::getModuleWithConfig('gallery', $request->get('module_id')->toInt());
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_upload'])) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            // Get album data
            $id = $request->get('id')->toInt();
            $album = \Gallery\Write\Model::get($id, $module->id);
            if (!$album) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $album->img_typies = implode(', ', self::$cfg->img_typies ?? ['jpg', 'jpeg', 'png', 'webp']);

            return $this->successResponse($album, 'Album details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/gallery/write/save
     * Save album (create or update)
     *
     * @param Request $request
     *
     * @return Response
     */
    public function save(Request $request)
    {
        try {
            // Validate request method
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            // Authenticate request
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            // Load module configuration
            $module = \Index\Module\Model::getModuleWithConfig('gallery', $request->post('module_id')->toInt());
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_upload'])) {
                return $this->errorResponse('No data available', 404);
            }

            // Get album data
            $album = \Gallery\Write\Model::get($request->post('id')->toInt(), $module->id);
            if (!$album) {
                return $this->errorResponse('No data available', 404);
            }

            $save = [
                'module_id' => $album->module_id,
                'topic' => $request->post('topic')->topic(),
                'detail' => $request->post('detail')->textarea(),
                'published_date' => $request->post('published_date')->date()
            ];

            $errors = [];
            if (empty($save['topic'])) {
                $errors['topic'] = 'Please fill in';
            }

            $db = \Kotchasan\DB::create();

            if (empty($errors)) {
                if ($album->id > 0) {
                    $save['id'] = $album->id;
                } else {
                    $save['id'] = $db->nextId('gallery_album');
                }

                // Handle cover image
                $dir = ROOT_PATH.DATA_FOLDER.'gallery/';
                $albumDir = $dir.$save['id'].'/';
                $count = $album->count;
                $images = [];

                foreach ($request->getUploadedFiles() as $item => $file) {
                    if (preg_match('/images\[[0-9]+\]/', $item)) {
                        if (!File::makeDirectory($dir) || !File::makeDirectory($albumDir)) {
                            $errors['images'] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'gallery/');
                        } elseif ($file->hasUploadFile()) {
                            try {
                                $image = uniqid().self::$cfg->stored_img_type;
                                $file->resizeImage(self::$cfg->img_typies, $albumDir, $image, self::$cfg->stored_img_size);
                                $images[$count] = [
                                    'image' => $image
                                ];
                                $count++;
                            } catch (\Exception $exc) {
                                // Unable to upload
                                $errors['images'] = $exc->getMessage();
                            }
                        }
                    }
                }
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            $save['updated_at'] = date('Y-m-d H:i:s');
            $save['count'] = $count;
            if ($album->id > 0) {
                $db->update('gallery_album', ['id', $save['id']], $save);
            } else {
                $save['visited'] = 0;
                $db->insert('gallery_album', $save);
            }
            foreach ($images as $sort => $img) {
                $db->insert('gallery_image', [
                    'album_id' => $save['id'],
                    'module_id' => $save['module_id'],
                    'image' => $img['image'],
                    'count' => $sort
                ]);
            }

            // Log
            \Index\Log\Model::add($save['id'], 'gallery', 'Gallery', 'Save Album: '.$save['topic'], $login->id);

            // Redirect to album page
            if ($album->id === 0) {
                return $this->redirectResponse('reload', 'Saved successfully');
            } else {
                return $this->redirectResponse('back', 'Saved successfully');
            }
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/album/action
     *
     * @param Request $request
     *
     * @return Response
     */
    public function action(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            // Authenticate request
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Load module configuration
            $module = \Index\Module\Model::getModuleWithConfig('gallery', $request->post('module_id')->toInt());
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_upload'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $action = $request->request('action')->filter('a-z_0-9');
            if ($action === 'sort') {
                return $this->handleSort($request, $login, $module->id);
            } elseif ($action === 'delete') {
                return $this->handleDelete($request, $login, $module->id);
            }
            return $this->errorResponse('Invalid action: '.$action, 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Handle edit action
     */
    private function handleSort(Request $request, $login, $moduleId)
    {
        // Get order data from request
        $order = $request->post('order', [])->toJson();
        $orderData = json_decode($order, true);
        if (empty($orderData)) {
            return $this->errorResponse('Invalid data', 400);
        }

        // Extract album ID and file order from the order data
        $files = [];
        $count = 0;
        foreach ($orderData as $item) {
            if (preg_match('/\/gallery\/(\d+)\/(.+)$/', $item['url'], $matches)) {
                $albumId = (int) $matches[1];
                $files[$matches[2]] = $count;
                $count++;
            }
        }

        // Get images for the album
        $images = \Gallery\Setup\Model::images($albumId, $moduleId);
        if (empty($images)) {
            return $this->errorResponse('Invalid data', 400);
        }

        // Create database connection
        $db = \Kotchasan\DB::create();

        foreach ($files as $file => $count) {
            if (isset($images[$file])) {
                // Update count for each image in the database
                $db->update('gallery_image', ['id' => $images[$file]], ['count' => $count]);
            }
        }
        // Update album's updated_at and count
        $db->update('gallery_album', ['id' => $albumId], ['count' => $count, 'updated_at' => date('Y-m-d H:i:s')]);

        // Log the sorting action
        \Index\Log\Model::add($albumId, 'gallery', 'Gallery', 'Sort album images ID:'.$albumId, $login->id);
    }

    /**
     * Handle edit action
     * Remove Image
     */
    private function handleDelete(Request $request, $login, $moduleId)
    {
        $url = $request->post('url')->url();
        if (!preg_match('/\/gallery\/(\d+)\/(.+)$/', $url, $matches)) {
            return $this->errorResponse('Invalid data', 400);
        }

        $albumId = (int) $matches[1];
        $image = $matches[2];
        // Get images for the album
        $images = \Gallery\Setup\Model::images($albumId, $moduleId);
        if (empty($images)) {
            return $this->errorResponse('Invalid data', 400);
        }

        // Delete image file from server
        if (file_exists(ROOT_PATH.DATA_FOLDER.'gallery/'.$albumId.'/'.$image)) {
            @unlink(ROOT_PATH.DATA_FOLDER.'gallery/'.$albumId.'/'.$image);
        }

        // Create database connection
        $db = \Kotchasan\DB::create();

        // Delete image record from database
        if (isset($images[$image])) {
            $db->delete('gallery_image', ['id' => $images[$image]]);
            unset($images[$image]);
            $count = 0;
            foreach ($images as $id) {
                $db->update('gallery_image', ['id', $id], ['count' => $count]);
                $count++;
            }
            // Update album's updated_at and count
            $db->update('gallery_album', ['id' => $albumId], ['count' => $count, 'updated_at' => date('Y-m-d H:i:s')]);

            // Log the sorting action
            \Index\Log\Model::add($albumId, 'gallery', 'Gallery', 'Delete image from album ID:'.$albumId, $login->id);
        }
    }
}
