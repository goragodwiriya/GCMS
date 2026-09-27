<?php
/**
 * @filesource modules/document/controllers/settings.php
 *
 * Document Settings Controller
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Settings;

use Gcms\Api as ApiController;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * Document Settings Controller
 *
 * GET  /api/document/settings/get
 * POST /api/document/settings/save
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * Parse JSON array status list and optionally force member(1).
     *
     * @param Request $request
     * @param string  $key
     * @param bool    $includeMember
     *
     * @return array
     */
    private function parseStatusList(Request $request, string $key, bool $includeMember = false): array
    {
        $statuses = $includeMember ? [1] : [];
        $posted = json_decode($request->post($key)->toJson(), true);
        if (is_array($posted)) {
            foreach ($posted as $status) {
                $s = (int) $status;
                if ((!$includeMember || $s !== 1) && !in_array($s, $statuses, true)) {
                    $statuses[] = $s;
                }
            }
        }
        return $statuses;
    }

    /**
     * `modules`.`config` for a new document instance — the same values the
     * settings form (get()) falls back to when a key is missing, stored so
     * a freshly created module never runs with rows/cols/can_view unset
     * (Index\Page\Model::defaultConfig() writes this on creation).
     *
     * @return array
     */
    public static function defaultSettings()
    {
        return [
            'published' => 1,
            'sort' => 0,
            'rows' => 3,
            'cols' => 3,
            'style' => 'iconview',
            'new_date' => 604800,
            'viewing' => 0,
            'category_display' => 'iconview',
            'category_cols' => 3,
            'notifications' => [],
            'can_write' => [1],
            'can_reply' => [],
            'can_view' => [-1, 0, 1],
            'can_approve' => [1],
            'moderator' => [1],
            'can_config' => [1]
        ];
    }

    /**
     * GET /api/document/settings/get
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function get(Request $request)
    {
        try {
            // Validate request method (GET request doesn't need CSRF token)
            ApiController::validateMethod($request, 'GET');

            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login || !ApiController::isAdmin($login)) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            // Load module configuration
            $module = \Index\Module\Model::getModuleWithConfig('document', $request->get('module_id')->toInt());
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $config = $module->config;

            $memberOnlyList = Language::get('MEMBER_ONLY_LIST');
            $publisheds = Language::get('PUBLISHEDS');
            $documentNotifications = Language::get('DOCUMENT_NOTIFICATIONS');
            $userStatus = \Gcms\Controller::getUserStatusOptions();
            $userStatusWithGuest = array_merge([['value' => -1, 'text' => '{LNG_Guest}']], $userStatus);

            $lineNotifications = [];
            if (isset($config->line_notifications) && is_array($config->line_notifications)) {
                $lineNotifications = $config->line_notifications;
            } elseif (isset($config->notifications) && is_array($config->notifications)) {
                $lineNotifications = $config->notifications;
            }

            $style = isset($config->style) ? (string) $config->style : 'iconview';
            if (!in_array($style, ['listview', 'iconview', 'thumbview'], true)) {
                $style = 'iconview';
            }

            $categoryDisplay = isset($config->category_display) ? (string) $config->category_display : 'iconview';
            if (!in_array($categoryDisplay, ['', 'listview', 'iconview', 'thumbview'], true)) {
                $categoryDisplay = 'iconview';
            }

            $documentCols = isset(self::$cfg->document_cols) ? (int) self::$cfg->document_cols : (isset(self::$cfg->document_cols) ? (int) self::$cfg->document_cols : 3);
            $documentRows = isset(self::$cfg->document_rows) ? (int) self::$cfg->document_rows : (isset(self::$cfg->document_rows) ? (int) self::$cfg->document_rows : 3);

            $response = [
                'module_id' => $module->id,
                // General settings
                'published' => isset($config->published) ? (int) $config->published : 1,
                'sort' => isset($config->sort) ? (int) $config->sort : 0,
                'rows' => isset($config->rows) ? (int) $config->rows : 3,
                'cols' => isset($config->cols) ? (int) $config->cols : 3,
                'style' => $style,
                'new_date' => isset($config->new_date) ? (int) floor(((int) $config->new_date) / 86400) : 7,
                'viewing' => isset($config->viewing) ? (int) $config->viewing : 0,
                'category_display' => $categoryDisplay,
                'category_cols' => isset($config->category_cols) ? (int) $config->category_cols : 3,
                'document_cols' => $documentCols,
                'document_rows' => $documentRows,

                // Multi-selects
                'line_notifications' => $lineNotifications,
                'can_write' => isset($config->can_write) && is_array($config->can_write) ? $config->can_write : [1],
                'can_reply' => isset($config->can_reply) && is_array($config->can_reply) ? $config->can_reply : [],
                'can_view' => isset($config->can_view) && is_array($config->can_view) ? $config->can_view : [-1, 1],
                'can_approve' => isset($config->can_approve) && is_array($config->can_approve) ? $config->can_approve : [1],
                'can_config' => isset($config->can_config) && is_array($config->can_config) ? $config->can_config : [1],

                // Default icon
                'default_icon' => [],
                'document_icon' => [],

                // Other
                'permissions' => \Document\Init\Controller::initPermission(),
                'options' => [
                    'publisheds' => is_array($publisheds) ? \Gcms\Controller::arrayToOptions($publisheds) : [
                        ['value' => 0, 'text' => '{LNG_Do not show}'],
                        ['value' => 1, 'text' => '{LNG_Show}']
                    ],
                    'member_only_list' => is_array($memberOnlyList) ? \Gcms\Controller::arrayToOptions($memberOnlyList) : [],
                    'document_notifications' => is_array($documentNotifications) ? \Gcms\Controller::arrayToOptions($documentNotifications) : [],
                    'user_status' => $userStatus,
                    'user_status_with_guest' => $userStatusWithGuest
                ]
            ];

            if (isset($config->default_icon) && file_exists(ROOT_PATH.$config->default_icon)) {
                $response['default_icon'] = [
                    [
                        'url' => WEB_URL.$config->default_icon,
                        'name' => basename($config->default_icon)
                    ]
                ];
            } else {
                $response['default_icon'] = [
                    [
                        'url' => WEB_URL.'images/no-image.webp',
                        'name' => 'Choose file'
                    ]
                ];
            }

            if (isset($config->document_icon) && file_exists(ROOT_PATH.$config->document_icon)) {
                $response['document_icon'] = [
                    [
                        'url' => WEB_URL.$config->document_icon,
                        'name' => basename($config->document_icon)
                    ]
                ];
            } else {
                $response['document_icon'] = [
                    [
                        'url' => WEB_URL.'images/no-image.webp',
                        'name' => 'Choose file'
                    ]
                ];
            }

            // Return response
            return $this->successResponse($response, 'Document settings loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/document/settings/save
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            ApiController::validateCsrfToken($request);

            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            // Authorization for saving
            if (!ApiController::canModify($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            // Load module configuration
            $module = \Index\Module\Model::getModuleWithConfig('document', $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $config = $module->config;

            // published
            $config->published = $request->post('published')->toBoolean();

            // sort
            $config->sort = $request->post('sort')->toInt();
            $config->cols = min(4, max(1, $request->post('cols')->toInt()));
            $config->rows = max(1, $request->post('rows')->toInt());
            $style = $request->post('style', 'iconview')->filter('a-z');
            $config->style = in_array($style, ['listview', 'iconview', 'thumbview'], true) ? $style : 'iconview';
            $config->new_date = max(0, $request->post('new_date')->toInt()) * 86400;
            $config->viewing = $request->post('viewing')->toInt();

            // category_display
            $categoryDisplay = $request->post('category_display')->filter('a-z');
            $allowedDisplay = ['', 'listview', 'iconview', 'thumbview'];
            $config->category_display = in_array($categoryDisplay, $allowedDisplay, true) ? $categoryDisplay : 'iconview';
            $config->category_cols = min(4, max(1, $request->post('category_cols')->toInt()));

            // Global document/tag listing settings
            $documentCols = $request->post('document_cols')->toInt();
            if ($documentCols < 1) {
                $documentCols = $request->post('document_cols')->toInt();
            }
            $documentRows = $request->post('document_rows')->toInt();
            if ($documentRows < 1) {
                $documentRows = $request->post('document_rows')->toInt();
            }

            // line notifications
            $lineNotifications = json_decode($request->post('line_notifications')->toJson(), true);
            $config->line_notifications = is_array($lineNotifications) ? array_map('intval', $lineNotifications) : [];
            // Keep alias for upgraded flows
            $config->notifications = $config->line_notifications;

            // Role settings
            $config->can_write = $this->parseStatusList($request, 'can_write', true);
            $config->can_reply = $this->parseStatusList($request, 'can_reply', false);
            $config->can_view = $this->parseStatusList($request, 'can_view', true);
            $config->can_approve = $this->parseStatusList($request, 'can_approve', true);
            $config->can_config = $this->parseStatusList($request, 'can_config', true);

            // default_icon
            $errors = [];
            if (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'document/')) {
                $errors['default_icon'] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'document/');
            }
            foreach ($request->getUploadedFiles() as $item => $file) {
                // Name of file to upload
                if ($item === 'default_icon' || $item === 'document_icon') {
                    if ($file->hasUploadFile()) {
                        try {
                            $target = $item === 'default_icon' ? 'default' : 'document';
                            $config->{$item} = DATA_FOLDER.'document/'.$target.'-'.$module->id.self::$cfg->stored_img_type;
                            $file->resizeImage(self::$cfg->img_typies, ROOT_PATH, $config->{$item}, self::$cfg->stored_img_size);
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

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            if (\Index\Module\Model::updateConfig($module->id, $config)) {
                // Log
                \Index\Log\Model::add(0, 'document', 'Document', 'Save Document Settings', $login->id);

                // Reload page
                return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
            }
        } catch (\Kotchasan\ApiException $e) {
            // Keep original HTTP code (e.g. 403 CSRF, 405 method)
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
        // Error save settings
        return $this->errorResponse('Failed to save settings', 500);
    }
}
