<?php
/**
 * @filesource modules/edocument/controllers/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Edocument\Settings;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API E-Document Settings Controller
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * `modules`.`config` for a new e-document instance. Index\Page\Model
     * looks for defaultSettings() on the Settings *Controller* first.
     *
     * @return array
     */
    public static function defaultSettings()
    {
        return \Edocument\Settings\Model::defaultSettings();
    }

    /**
     * GET /api/edocument/settings/get
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
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = \Index\Module\Model::getModuleWithConfig('edocument', $request->get('module_id')->toInt());
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $config = \Edocument\Settings\Model::normalizeConfig($module->config);
            if (!self::canConfig($login, $config)) {
                return $this->errorResponse('Permission required', 403);
            }

            return $this->successResponse([
                'module_id' => (int) $module->id,
                'format_no' => $config->format_no,
                'send_mail' => (string) $config->send_mail,
                'file_typies' => implode(',', $config->file_typies),
                'upload_size' => (string) $config->upload_size,
                'download_action' => (string) $config->download_action,
                'list_per_page' => (int) $config->list_per_page,
                'can_upload' => $config->can_upload,
                'moderator' => $config->moderator,
                'can_config' => $config->can_config,
                'options' => [
                    'user_status' => \Gcms\Controller::getUserStatusOptions(),
                    'upload_sizes' => $this->uploadSizeOptions()
                ]
            ], 'E-Document settings loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/edocument/settings/save
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

            $module = \Index\Module\Model::getModuleWithConfig('edocument', $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $config = \Edocument\Settings\Model::normalizeConfig($module->config);
            if (!self::canConfig($login, $config) || !ApiController::isNotDemoMode($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            $errors = [];
            $typies = [];
            $rawTypies = strtolower((string) $request->post('file_typies')->toString());
            foreach (preg_split('/\s*,\s*/', $rawTypies) as $typ) {
                if ($typ !== '' && preg_match('/^[a-z0-9]{2,4}$/', $typ)) {
                    $typies[$typ] = $typ;
                }
            }
            if (empty($typies)) {
                $errors['file_typies'] = 'Please select at least one item';
            }

            $formatNo = $request->post('format_no')->topic();
            if ($formatNo !== '' && !self::isValidFormatNo($formatNo)) {
                $errors['format_no'] = 'Invalid format';
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            $config->format_no = $formatNo;
            $config->send_mail = $request->post('send_mail')->toBoolean() ? 1 : 0;
            $config->file_typies = array_values($typies);
            $config->upload_size = max(1024, $request->post('upload_size')->toInt());
            $config->download_action = $request->post('download_action')->toBoolean() ? 1 : 0;
            $config->list_per_page = min(100, max(1, $request->post('list_per_page')->toInt()));

            $config->can_upload = $this->parseStatusArray($request, 'can_upload');
            $config->moderator = $this->parseStatusArray($request, 'moderator');
            $config->can_config = $this->parseStatusArray($request, 'can_config');

            if (\Index\Module\Model::updateConfig($module->id, $config)) {
                \Index\Log\Model::add(0, 'edocument', 'Save', 'Save E-Document Settings ID : '.$module->id, $login->id);

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
     * Can the user change this module's settings.
     *
     * @param object $login
     * @param object $config normalized module config
     *
     * @return bool
     */
    public static function canConfig($login, $config)
    {
        return ApiController::isAdmin($login) || \Web\Login::checkStatus($login, $config, ['can_config']);
    }

    /**
     * Document number format as used by Number::printf() (e.g. E-%04d,
     * %Y-%03d). It must contain the ID (two IDs give two numbers) and fit
     * `edocument`.`document_no` (varchar 20).
     *
     * @param string $format
     *
     * @return bool
     */
    public static function isValidFormatNo($format)
    {
        try {
            $sample = \Kotchasan\Number::printf($format, 9999);
            $other = \Kotchasan\Number::printf($format, 9998);
        } catch (\Throwable $e) {
            return false;
        }

        return $sample !== $other && mb_strlen($sample) <= 20;
    }

    /**
     * Parse posted status array. Admin (status 1) is always included.
     *
     * @param Request $request
     * @param string  $field
     *
     * @return array
     */
    private function parseStatusArray(Request $request, $field)
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

        if (!in_array(1, $statuses, true)) {
            $statuses[] = 1;
        }

        return $statuses;
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
