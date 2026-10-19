<?php
/**
 * @filesource modules/index/controllers/themes.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Themes;

use Gcms\Api as ApiController;
use Gcms\Config;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;

/**
 * API Themes Controller
 *
 * Lists available themes and allows switching the active theme.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/index/themes
     * List all available themes with metadata and screenshots.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            // Validate request method
            ApiController::validateMethod($request, 'GET');

            // Authentication check (admin only)
            $login = $this->authenticateRequest($request);
            if (!$login || $login->status != 1) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $currentTheme = self::$cfg->skin ?? 'default';
            $themes = [];

            // ธีมใน themes/ เท่านั้น — ตำแหน่งเดียวที่ Kotchasan\Template::init() แสดงผลได้
            // (ดู Gcms\Theme) ธีมที่ไม่มี index.html ใช้เป็นโครงหน้าเว็บไม่ได้ ไม่แสดง
            foreach (glob(ROOT_PATH.'themes/*/theme.json') ?: [] as $file) {
                $dir = basename(dirname($file));
                if (!\Gcms\Theme::exists($dir)) {
                    continue;
                }
                $data = json_decode(file_get_contents($file), true);
                if (!is_array($data)) {
                    continue;
                }

                $baseUrl = \Gcms\Theme::url($dir);

                // Resolve screenshot URL
                $screenshotUrl = null;
                $screenshotField = $data['screenshot'] ?? null;
                if ($screenshotField && file_exists(dirname($file).'/'.$screenshotField)) {
                    $screenshotUrl = $baseUrl.$screenshotField;
                }
                // Fallback: try common image filenames
                if (!$screenshotUrl) {
                    foreach (['screenshot.png', 'screenshot.svg', 'screenshot.jpg', 'screenshot.webp'] as $candidate) {
                        if (file_exists(dirname($file).'/'.$candidate)) {
                            $screenshotUrl = $baseUrl.$candidate;
                            break;
                        }
                    }
                }

                $themes[] = [
                    'name' => $dir,
                    'label' => $data['name'] ?? $dir,
                    'description' => $data['description'] ?? '',
                    'author' => $data['author'] ?? '',
                    'author_url' => $data['author_url'] ?? '',
                    'version' => $data['version'] ?? '1.0',
                    'screenshot' => $screenshotUrl,
                    'colors' => $data['colors'] ?? [],
                    'active' => ($dir === $currentTheme)
                ];
            }

            // Sort: active first, then A-Z
            usort($themes, function ($a, $b) {
                if ($a['active'] !== $b['active']) {
                    return $b['active'] ? 1 : -1;
                }
                return strcmp($a['name'], $b['name']);
            });

            return $this->successResponse([
                'themes' => $themes,
                'current_theme' => $currentTheme
            ], 'Themes retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/index/themes/select
     * Activate a theme by saving its name to config.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function select(Request $request)
    {
        try {
            // Validate request method
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            // Authentication check (admin only)
            $login = $this->authenticateRequest($request);
            if (!$login || $login->status != 1) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Get theme name from POST
            $theme = $request->post('theme')->filter('a-zA-Z0-9_-');
            if (empty($theme)) {
                return $this->errorResponse('Theme name is required', 400);
            }

            // ต้องเป็นธีมใน themes/ ที่หน้าเว็บแสดงได้ (Kotchasan\Template::init)
            if (!\Gcms\Theme::exists($theme)) {
                return $this->errorResponse('Theme not found', 404);
            }

            // Save to config
            $config = Config::load(ROOT_PATH.'settings/config.php');
            $config->skin = $theme;

            if (Config::save($config, ROOT_PATH.'settings/config.php')) {
                // Log the change
                \Index\Log\Model::add(0, 'index', 'Index', 'Change Theme to: '.$theme, $login->id);

                return $this->redirectResponse('reload', 'Theme activated successfully');
            }

            return $this->errorResponse('Failed to save theme settings', 500);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
