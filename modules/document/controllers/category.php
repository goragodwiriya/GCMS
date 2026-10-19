<?php
/**
 * @filesource modules/document/controllers/category.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Category;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Language;

/**
 * API Single Category Controller for Document module
 *
 * Handles a single document category: GET data for form, POST save, POST removeIcon.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/document/category/get
     *
     * Returns one category record (or defaults for a new record) plus
     * the installed language list and allowed image types.
     *
     * Query params:
     *   id        – category.id; 0 or omitted = new record
     *   module_id – required
     *
     * @param Request $request
     *
     * @return Response
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login || !ApiController::isAdmin($login)) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $id = $request->get('id')->toInt();
            $module_id = $request->get('module_id')->toInt();

            $module = \Index\Module\Model::getModuleWithConfig('document', $module_id);
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $category = \Document\Category\Model::get($id, $module_id);
            if (!$category) {
                return $this->errorResponse('No data available', 404);
            }

            $detail = [];
            foreach ((array) $category->detail as $lng => $text) {
                $detail[$lng] = \Kotchasan\Text::untextarea((string) $text);
            }
            $category->detail = $detail;

            $moduleConfig = is_object($module->config ?? null) ? (array) $module->config : [];
            $categoryConfig = is_object($category->config ?? null) ? (array) $category->config : (is_array($category->config ?? null) ? $category->config : []);

            $category->can_reply = isset($categoryConfig['can_reply']) && is_array($categoryConfig['can_reply'])
                ? $categoryConfig['can_reply']
                : (isset($moduleConfig['can_reply']) && is_array($moduleConfig['can_reply']) ? $moduleConfig['can_reply'] : [1]);
            $category->published = isset($categoryConfig['published']) ? (int) $categoryConfig['published'] : ((int) $category->published === 1 ? 1 : 0);

            $category->img_typies = implode(', ', self::$cfg->img_typies ?? ['jpg', 'jpeg', 'png', 'webp']);
            $category->languages = \Gcms\Controller::arrayToOptions(Language::installedLanguage());
            $userStatus = \Gcms\Controller::getUserStatusOptions();
            $userStatusWithGuest = array_merge([
                ['value' => '-1', 'text' => '{LNG_Guest}']
            ], $userStatus);
            $category->options = [
                'user_status' => $userStatus,
                'user_status_with_guest' => $userStatusWithGuest
            ];

            // Convert icon paths to data-files format per language
            $icons = (array) $category->icon;
            $defaultIcon = new \stdClass();
            foreach ($icons as $lng => $url) {
                if ($url) {
                    $defaultIcon->$lng = [['url' => $url, 'name' => basename($url)]];
                }
            }
            $category->default_icon = $defaultIcon;
            unset($category->icon);
            unset($category->config);

            return $this->successResponse($category, 'Category details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/document/category/save
     *
     * Saves one category. Handles topic/detail JSON per language and
     * optional icon file upload per language.
     *
     * Form fields:
     *   id                   – category.id (0 = new record)
     *   module_id            – required
     *   published            – 1 or 0
     *   topic[<lng>]         – category name per language
     *   detail[<lng>]        – description per language
     *   default_icon[<lng>]  – optional uploaded icon file per language
     *
     * @param Request $request
     *
     * @return Response
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login || !ApiController::isAdmin($login)) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = \Index\Module\Model::getModuleWithConfig('document', $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('Permission required', 403);
            }

            $id = $request->post('id')->toInt();
            $module_id = $module->id;
            $category_id = $request->post('category_id')->toInt();
            $published = $request->post('published')->toBoolean();
            // Collect topic and description arrays keyed by language
            $topicRaw = $request->post('topic', [])->topic();
            $detailRaw = $request->post('detail', [])->textarea();

            $errors = [];

            $topics = [];
            $details = [];
            foreach (Language::installedLanguage() as $lng => $label) {
                if (empty($topicRaw[$lng])) {
                    $errors['topic_'.$lng] = 'Please fill in';
                } else {
                    $topics[$lng] = $topicRaw[$lng];
                }
                if (empty($detailRaw[$lng])) {
                    $errors['detail_'.$lng] = 'Please fill in';
                } else {
                    $details[$lng] = $detailRaw[$lng];
                }
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            $duplicate = \Kotchasan\Model::createQuery()
                ->selectCount()
                ->from('category')
                ->where([
                    ['module_id', $module_id],
                    ['category_id', $category_id],
                    ['type', 'category'],
                    ['id', '!=', $id]
                ])
                ->first();
            if ($duplicate && (int) $duplicate->count > 0) {
                return $this->formErrorResponse([
                    'category_id' => Language::replace('This :name already exist', [':name' => 'ID'])
                ], 422);
            }

            $db = \Kotchasan\DB::create();

            $canReply = $request->post('can_reply', [])->toInt();
            $canReply[] = 1;

            $config = [
                'published' => $published,
                'can_reply' => array_values(array_unique(array_map('intval', $canReply)))
            ];

            // Load existing icon JSON paths (to preserve languages not being re-uploaded)
            $iconPaths = [];
            if ($id > 0) {
                $iconPaths = \Document\Category\Model::getRawIcons($db, $id);
            }

            if ($id > 0) {
                $newId = $id;
            } else {
                $newId = $db->nextId('category');
            }

            foreach ($request->getUploadedFiles() as $field => $file) {
                if (preg_match('/^default_icon\[([a-z]{2})\]$/', $field, $matches)) {
                    $icon = DATA_FOLDER.'document/cat_'.$matches[1].'_'.$newId.self::$cfg->stored_img_type;
                    try {
                        $file->resizeImage(self::$cfg->img_typies, ROOT_PATH, $icon, self::$cfg->stored_img_size);
                        $iconPaths[$matches[1]] = $icon;
                    } catch (\Exception $exc) {
                        $errors['default_icon_'.$matches[1]] = Language::get($exc->getMessage());
                    }
                }
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            $data = [
                'id' => $newId,
                'category_id' => $category_id,
                'topic' => json_encode($topics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'detail' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'icon' => json_encode($iconPaths, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'config' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ];

            // Save the record (insert or update)
            \Document\Category\Model::save($db, $id, $module_id, $data);

            // Log
            \Index\Log\Model::add($newId, 'document', 'Document', 'Save Category: '.$data['category_id'], $login->id);

            // Redirect back to the form with a success message
            return $this->redirectResponse('back', 'Saved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/document/category/removeIcon
     *
     * Removes the icon for one language from a category record.
     *
     * Form fields:
     *   module_id – required
     *   url       – full URL of the icon file
     *   language  – language code (e.g. 'th', 'en')
     *
     * @param Request $request
     *
     * @return Response
     */
    public function removeIcon(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login || !ApiController::isAdmin($login)) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $module = \Index\Module\Model::getModuleWithConfig('document', $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('Permission required', 403);
            }

            $id = 0;
            $lng = '';
            $url = $request->post('url')->url();
            if (preg_match('/document\/cat_([a-z]{2})_(\d+)\./', $url, $matches)) {
                $lng = $matches[1];
                $id = (int) $matches[2];
            }

            if ($id === 0 || $lng === '') {
                return $this->errorResponse('Invalid URL format', 400);
            }

            $db = \Kotchasan\DB::create();

            $icons = \Document\Category\Model::getRawIcons($db, $id);
            if (empty($icons)) {
                return $this->errorResponse('No data available', 404);
            }

            if (isset($icons[$lng])) {
                $path = ROOT_PATH.$icons[$lng];
                if (file_exists($path)) {
                    @unlink($path);

                    // Log
                    \Index\Log\Model::add($id, 'document', 'Document', 'Remove Category Icon: '.$icons[$lng], $login->id);
                }

                unset($icons[$lng]);

                $db->update('category', ['id', $id], [
                    'icon' => json_encode($icons, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                ]);
            }

            // Return success even if the icon was already missing, since the end result is the same (icon removed)
            return $this->successResponse([], 'Icon removed');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
