<?php
/**
 * @filesource modules/index/controllers/adminmaintenance.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Adminmaintenance;

use Gcms\Api as ApiController;
use Gcms\Config;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Language;

/**
 * API Admin Page Controller
 *
 * Handles single page item CRUD endpoints
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/index/adminmaintenance/get
     * Get page details by ID
     *
     * @param Request $request
     *
     * @return Response
     */
    public function get(Request $request)
    {
        try {
            // Validate request method
            ApiController::validateMethod($request, 'GET');

            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            // Authorization: maintenance/site config requires can_config.
            if (!ApiController::canModify($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $language = $request->get('language', Language::name())->filter('a-z');
            // maintenance detail
            $template = ROOT_PATH.DATA_FOLDER.'maintenance.'.$language.'.html';
            if (is_file($template)) {
                $template = str_replace('{WEBURL}', WEB_URL, file_get_contents($template));
            } else {
                $template = '<p style="padding: 20px; text-align: center; font-weight: bold;">Website Temporarily Closed for Maintenance, Please try again in a few minutes.<br>ปิดปรับปรุงเว็บไซต์ชั่วคราวเพื่อบำรุงรักษา กรุณาลองใหม่ในอีกสักครู่</p>';
            }

            // Return user details with options
            return $this->successResponse([
                'data' => [
                    'language' => $language,
                    'detail' => $template,
                    'show_maintenance' => isset(self::$cfg->show_maintenance) ? self::$cfg->show_maintenance : 0
                ],
                'options' => [
                    'language' => \Gcms\Controller::arrayToOptions(Language::installedLanguage())
                ]
            ], 'Page details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/index/adminmaintenance/save
     * Save maintenance content and show_maintenance setting
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

            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }
            // Authorization: maintenance/site config requires can_config.
            if (!ApiController::canModify($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            // Parse input data
            $save = $this->parseInput($request);

            // Write maintenance HTML file
            $filePath = ROOT_PATH.DATA_FOLDER.'maintenance.'.$save['language'].'.html';
            if (file_put_contents($filePath, $save['detail']) === false) {
                return $this->errorResponse('Failed to save maintenance file', 500);
            }

            // Load and update config
            $config = Config::load(ROOT_PATH.'settings/config.php');
            $config->show_maintenance = $save['show_maintenance'];

            if (Config::save($config, ROOT_PATH.'settings/config.php')) {
                // Log
                \Index\Log\Model::add(0, 'index', 'Index', 'Save Intro Settings', $login->id);

                // Reload page
                return $this->redirectResponse('reload', 'Saved successfully');
            }

            return $this->errorResponse('Failed to save settings', 500);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Parse page input from request
     *
     * @param Request $request
     *
     * @return array
     */
    protected function parseInput(Request $request): array
    {
        return [
            'language' => $request->post('language')->filter('a-z'),
            'show_maintenance' => $request->post('show_maintenance')->toBoolean(),
            'detail' => str_replace(['&#x007B;', '&#x007D;', WEB_URL], ['{', '}', '{WEBURL}'], $request->post('detail')->detail())
        ];
    }
}
