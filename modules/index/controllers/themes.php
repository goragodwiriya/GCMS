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
            $seen = [];

            // Personal themes first (the site's own DATA_FOLDER.themes/ —
            // DATA_FOLDER is already per-user), then shared system themes.
            // A personal theme shadows a system theme of the same slug
            // (Kotchasan\Template::init resolves personal first), so the
            // shadowed system entry is skipped to avoid a confusing duplicate.
            $sources = [
                ['glob' => \Gcms\Theme::personalDir().'*/theme.json', 'personal' => true, 'baseUrl' => WEB_URL.DATA_FOLDER.'themes/'],
                ['glob' => ROOT_PATH.'themes/*/theme.json', 'personal' => false, 'baseUrl' => WEB_URL.'themes/']
            ];

            foreach ($sources as $source) {
                foreach (glob($source['glob']) ?: [] as $file) {
                    $dir = basename(dirname($file));
                    if (isset($seen[$dir])) {
                        continue;
                    }
                    $data = json_decode(file_get_contents($file), true);
                    if (!is_array($data)) {
                        continue;
                    }
                    $seen[$dir] = true;

                    $baseUrl = $source['baseUrl'].$dir.'/';

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
                        'active' => ($dir === $currentTheme),
                        'personal' => $source['personal']
                    ];
                }
            }

            // Sort: active first, then personal themes before system, then A-Z
            usort($themes, function ($a, $b) {
                if ($a['active'] !== $b['active']) {
                    return $b['active'] ? 1 : -1;
                }
                if ($a['personal'] !== $b['personal']) {
                    return $b['personal'] ? 1 : -1;
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

            // Validate theme directory exists (personal DATA_FOLDER.themes/
            // wins over themes/ — same rule as Kotchasan\Template::init)
            $themePath = \Gcms\Theme::dir($theme);
            if ($themePath === null || !is_dir($themePath)) {
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

    /**
     * POST /api/index/themes/delete
     * Delete a personal theme (DATA_FOLDER.themes/{slug}/).
     * Shared system themes in themes/ cannot be deleted here.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function delete(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login || $login->status != 1) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $theme = $request->post('theme')->filter('a-zA-Z0-9_-');
            // Only themes in the site's own DATA_FOLDER.themes/ are deletable
            // here — shared themes under themes/ are never touched.
            if (!\Gcms\Theme::isPersonal($theme)) {
                return $this->errorResponse('Only personal themes can be deleted', 400);
            }

            if (($this->activeSkin() ?? 'default') === $theme) {
                return $this->errorResponse('Cannot delete the active theme. Switch to another theme first.', 400);
            }

            $this->removeDirRecursive(\Gcms\Theme::personalDir().$theme.'/');

            \Index\Log\Model::add(0, 'index', 'Index', 'Delete personal theme: '.$theme, $login->id);

            return $this->redirectResponse('reload', 'Theme deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Currently active skin from config.
     *
     * @return string|null
     */
    private function activeSkin()
    {
        return self::$cfg->skin ?? null;
    }

    /**
     * Recursively delete a directory.
     *
     * @param string $dir
     */
    private function removeDirRecursive(string $dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
