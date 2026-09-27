<?php
/**
 * @filesource modules/board/controllers/settings.php
 *
 * Board Settings Controller
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Settings;

use Gcms\Api as ApiController;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * Board Settings Controller
 *
 * GET  /api/board/settings/get
 * POST /api/board/settings/save
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
     * `modules`.`config` for a new board instance — the values get() falls
     * back to, stored on creation (Index\Page\Model::defaultConfig()) so the
     * listing never calls paginate() with list_per_page unset.
     *
     * @return array
     */
    public static function defaultSettings()
    {
        return [
            'list_per_page' => 20,
            'new_date' => 604800,
            'viewing' => 0,
            'category_display' => 'iconview',
            'category_cols' => 3,
            'img_law' => 0,
            'img_upload_type' => ['jpg', 'jpeg', 'png', 'webp'],
            'line_notifications' => [],
            'can_post' => [1],
            'can_reply' => [1],
            'can_view' => [-1, 0, 1],
            'moderator' => [1],
            'can_config' => [1]
        ];
    }

    /**
     * GET /api/board/settings/get
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
            $module = \Index\Module\Model::getModuleWithConfig('board', $request->get('module_id')->toInt());
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $config = $module->config;

            $lineNotifications = [];
            if (isset($config->line_notifications) && is_array($config->line_notifications)) {
                $lineNotifications = $config->line_notifications;
            } elseif (isset($config->notifications) && is_array($config->notifications)) {
                $lineNotifications = $config->notifications;
            }

            $imgUploadType = isset($config->img_upload_type) && is_array($config->img_upload_type) ? $config->img_upload_type : ['jpg', 'jpeg', 'webp'];

            $memberOnlyList = Language::get('MEMBER_ONLY_LIST');
            $imgLaw = Language::get('IMG_LAW');
            $userStatus = \Gcms\Controller::getUserStatusOptions();
            $userStatusWithGuest = array_merge([['value' => -1, 'text' => '{LNG_Guest}']], $userStatus);

            $categoryDisplay = isset($config->category_display) ? (string) $config->category_display : 'iconview';
            if (!in_array($categoryDisplay, ['', 'listview', 'iconview', 'thumbview'], true)) {
                $categoryDisplay = 'iconview';
            }

            $response = [
                'module_id' => $module->id,
                // General settings
                'list_per_page' => isset($config->list_per_page) ? (int) $config->list_per_page : 20,
                'new_date' => isset($config->new_date) ? (int) floor(((int) $config->new_date) / 86400) : 7,
                'viewing' => isset($config->viewing) ? (int) $config->viewing : 0,
                'category_display' => $categoryDisplay,
                'category_cols' => isset($config->category_cols) ? (int) $config->category_cols : 3,
                'img_law' => isset($config->img_law) ? (int) $config->img_law : 0,

                // Upload flags
                'img_upload_type_jpg' => in_array('jpg', $imgUploadType, true),
                'img_upload_type_jpeg' => in_array('jpeg', $imgUploadType, true),
                'img_upload_type_gif' => in_array('gif', $imgUploadType, true),
                'img_upload_type_png' => in_array('png', $imgUploadType, true),
                'img_upload_type_webp' => in_array('webp', $imgUploadType, true),

                // Multi-selects
                'line_notifications' => $lineNotifications,
                'can_post' => isset($config->can_post) && is_array($config->can_post) ? $config->can_post : [1],
                'can_reply' => isset($config->can_reply) && is_array($config->can_reply) ? $config->can_reply : [1],
                'can_view' => isset($config->can_view) && is_array($config->can_view) ? $config->can_view : [-1, 1],
                'moderator' => isset($config->moderator) && is_array($config->moderator) ? $config->moderator : [1],
                'can_config' => isset($config->can_config) && is_array($config->can_config) ? $config->can_config : [1],

                // Default icon
                'default_icon' => [],

                // Other
                'permissions' => \Board\Init\Controller::initPermission(),
                'options' => [
                    'user_status' => $userStatus,
                    'user_status_with_guest' => $userStatusWithGuest,
                    'member_only_list' => is_array($memberOnlyList) ? \Gcms\Controller::arrayToOptions($memberOnlyList) : [],
                    'img_law' => is_array($imgLaw) ? \Gcms\Controller::arrayToOptions($imgLaw) : []
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

            // Return response
            return $this->successResponse($response, 'Board settings loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/board/settings/save
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
            $module = \Index\Module\Model::getModuleWithConfig('board', $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $config = $module->config;

            // list_per_page
            $config->list_per_page = max(1, $request->post('list_per_page')->toInt());

            // new_date (days -> seconds)
            $config->new_date = max(0, $request->post('new_date')->toInt()) * 86400;
            $config->viewing = $request->post('viewing')->toInt();

            // category_display
            $categoryDisplay = $request->post('category_display')->filter('a-z');
            $allowedDisplay = ['', 'listview', 'iconview', 'thumbview'];
            $config->category_display = in_array($categoryDisplay, $allowedDisplay, true) ? $categoryDisplay : 'iconview';
            $config->category_cols = min(4, max(1, $request->post('category_cols')->toInt()));

            // Upload settings
            $config->img_upload_type = $request->post('img_upload_type', [])->filter('a-z');
            $config->img_law = $request->post('img_law')->toInt() === 1 ? 1 : 0;

            // line notifications
            $lineNotifications = json_decode($request->post('line_notifications')->toJson(), true);
            $config->line_notifications = is_array($lineNotifications) ? array_map('intval', $lineNotifications) : [];
            // Keep alias for upgraded flows
            $config->notifications = $config->line_notifications;

            // Role settings
            $config->can_post = $this->parseStatusList($request, 'can_post', true);
            $config->can_reply = $this->parseStatusList($request, 'can_reply', false);
            $config->can_view = $this->parseStatusList($request, 'can_view', true);
            $config->moderator = $this->parseStatusList($request, 'moderator', true);
            $config->can_config = $this->parseStatusList($request, 'can_config', true);

            // default_icon
            $errors = [];
            if (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'board/')) {
                $errors['default_icon'] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'board/');
            }
            foreach ($request->getUploadedFiles() as $item => $file) {
                // Name of file to upload
                if ($item === 'default_icon') {
                    if ($file->hasUploadFile()) {
                        try {
                            $config->default_icon = DATA_FOLDER.'board/default-'.$module->id.self::$cfg->stored_img_type;
                            $file->resizeImage(self::$cfg->img_typies, ROOT_PATH, $config->default_icon, self::$cfg->stored_img_size);
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
                \Index\Log\Model::add(0, 'board', 'Board', 'Save Board Settings', $login->id);

                // Reload page
                return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
            }
        } catch (\Kotchasan\ApiException $e) {
            // Keep original HTTP code (e.g. 403 CSRF, 405 method)
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400, $e);
        }
        // Error save settings
        return $this->errorResponse('Failed to save settings', 500);
    }
}
