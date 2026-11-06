<?php
/**
 * @filesource modules/download/controllers/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Download\Settings;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API Download Settings Controller
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * `modules`.`config` for a new download instance. Index\Page\Model
     * looks for defaultSettings() on the Settings *Controller* first, so
     * without this the Model's defaults were never stored at creation.
     *
     * @return array
     */
    public static function defaultSettings()
    {
        return \Download\Settings\Model::defaultSettings();
    }

    /**
     * GET /api/download/settings/get
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
            if (!$login || !ApiController::isAdmin($login)) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = \Index\Module\Model::getModuleWithConfig('download', $request->get('module_id')->toInt());
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $config = \Download\Settings\Model::normalizeConfig($module->config);
            $response = [
                'module_id' => (int) $module->id,
                'file_typies' => implode(',', (array) $config->file_typies),
                'upload_size' => (int) $config->upload_size,
                'list_per_page' => (int) $config->list_per_page,
                'sort' => (int) $config->sort,
                'can_download' => $config->can_download,
                'can_upload' => $config->can_upload,
                'moderator' => $config->moderator,
                'can_config' => $config->can_config,
                'permissions' => \Download\Init\Controller::initPermission(),
                'options' => [
                    'user_status' => array_merge([
                        ['value' => '-1', 'text' => '{LNG_Guest}']
                    ], \Gcms\Controller::getUserStatusOptions()),
                    'upload_sizes' => $this->uploadSizeOptions()
                ]
            ];

            return $this->successResponse($response, 'Download settings loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/download/settings/save
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
            if (!ApiController::canModify($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $module = \Index\Module\Model::getModuleWithConfig('download', $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $config = \Download\Settings\Model::normalizeConfig($module->config);

            $errors = [];
            $typies = [];
            $rawTypies = strtolower((string) $request->post('file_typies')->toString());
            foreach (preg_split('/\s*,\s*/', $rawTypies) as $typ) {
                if ($typ !== '' && preg_match('/^[a-z0-9]{2,10}$/', $typ)) {
                    $typies[$typ] = $typ;
                }
            }
            if (empty($typies)) {
                $errors['file_typies'] = 'Please select at least one item';
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            $config->file_typies = array_values($typies);
            $config->upload_size = max(1024, $request->post('upload_size')->toInt());
            $config->list_per_page = min(100, max(1, $request->post('list_per_page')->toInt()));
            $config->sort = min(2, max(0, $request->post('sort')->toInt()));

            $config->can_download = $this->parseStatusArray($request, 'can_download', [-1, 0, 1], false);
            $config->can_upload = $this->parseStatusArray($request, 'can_upload', [1], true);
            $config->moderator = $this->parseStatusArray($request, 'moderator', [1], true);
            $config->can_config = $this->parseStatusArray($request, 'can_config', [1], true);

            if (\Index\Module\Model::updateConfig($module->id, $config)) {
                \Index\Log\Model::add(0, 'download', 'Download', 'Save Download Settings', $login->id);

                return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
            }

            return $this->errorResponse('Failed to save settings', 500);
        } catch (\Kotchasan\ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Parse posted status array.
     *
     * @param Request $request
     * @param string  $field
     * @param array   $fallback
     * @param bool    $forceAdmin
     *
     * @return array
     */
    private function parseStatusArray(Request $request, $field, array $fallback, $forceAdmin)
    {
        $statuses = [];
        $raw = json_decode($request->post($field)->toJson(), true);
        if (!is_array($raw)) {
            $raw = [];
        }

        foreach ($raw as $status) {
            $status = (int) $status;
            if (!in_array($status, $statuses, true)) {
                $statuses[] = $status;
            }
        }

        if ($forceAdmin && !in_array(1, $statuses, true)) {
            $statuses[] = 1;
        }

        return empty($statuses) ? $fallback : $statuses;
    }

    /**
     * Upload size options for select controls.
     *
     * @return array
     */
    private function uploadSizeOptions()
    {
        $maxSize = \Kotchasan\Http\UploadedFile::getUploadSize(true);

        $options = [];
        foreach ([2, 4, 6, 8, 16, 32, 64, 128, 256, 512, 1024, 2048] as $mb) {
            $bytes = $mb * 1048576;
            if ($bytes > $maxSize) {
                break;
            }
            $options[] = [
                'value' => (string) $bytes,
                'text' => \Kotchasan\Text::formatFileSize($bytes)
            ];
        }

        return $options;
    }
}
