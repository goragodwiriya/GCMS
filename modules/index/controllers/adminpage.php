<?php
/**
 * @filesource modules/index/controllers/adminpage.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Adminpage;

use Gcms\Api as ApiController;
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
     * GET /api/index/adminpage/get
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
            // Authorization: page/module management requires can_config.
            if (!ApiController::canModify($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }
            $id = $request->get('id')->toInt();
            $owner = $request->get('owner', 'index')->filter('a-z');
            $page = \Index\Page\Model::get($id, $owner);
            if (!$page) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            // Return user details with options
            return $this->successResponse([
                'data' => $page,
                'options' => [
                    'language' => \Gcms\Controller::arrayToOptions(['' => 'All languages'] + Language::installedLanguage()),
                    'index_id' => \Index\Menu\Model::getOrderOptions($page->owner),
                    'owner' => \Index\Page\Model::getAvailableOwners()
                ]
            ], 'Page details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/index/adminpage/save
     * Save page details (create or update)
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
            // Authorization: page/module management requires can_config.
            if (!ApiController::canModify($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            // Parse input data
            $id = $request->post('id')->toInt();
            $save = $this->parseInput($request, $id);

            // Get page data
            $page = \Index\Page\Model::get($id, $save['module']['owner']);
            if (!$page) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            // Database connection
            $db = \Kotchasan\DB::create();

            // Validate
            $errors = $this->validateFields($id, $save, $page, $db);
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            $index_save = $save['index'];
            $detail_save = $save['detail'];
            $module_save = $save['module'];

            $index_save['updated_at'] = date('Y-m-d H:i:s');
            if (empty($id)) {
                // New page. `modules`.`config` is NOT NULL with no default, so a new
                // instance always stores its owner's default settings as JSON ('{}'
                // when the owner declares none).
                $module_save['config'] = \Index\Page\Model::defaultConfig($module_save['owner']);
                $class = \Index\Page\Model::settingsClass($module_save['owner']);
                if ($class && method_exists($class, 'install')) {
                    // Install method
                    $class::install($module_save);
                }
                $module_id = $db->insert('modules', $module_save);
                $index_save['member_id'] = $login->id;
                $index_save['created_at'] = $index_save['updated_at'];
                $index_save['index'] = 1;
                $index_save['module_id'] = $module_id;
                $index_id = $db->insert('index', $index_save);
                $detail_save['id'] = $index_id;
                $detail_save['module_id'] = $module_id;
                $db->insert('index_detail', $detail_save);
            } else {
                // owner cannot be changed after creation
                unset($module_save['owner']);
                // Update existing page
                $db->update('index', ['id', $page->id], $index_save);
                $db->update('modules', ['id', $page->module_id], $module_save);
                $db->update('index_detail', [
                    ['id', $page->id],
                    ['module_id', $page->module_id],
                    ['language', $page->language]
                ], $detail_save);
            }

            // Log the action
            $action_label = empty($id) ? 'Create' : 'Edit';
            \Index\Log\Model::add($id, 'index', 'Index', $action_label.' page: '.$detail_save['topic'], $login->id);

            return $this->redirectResponse('back', 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/index/adminpage/copy
     * Copy a page item to another language
     *
     * @param Request $request
     *
     * @return Response
     */
    public function copy(Request $request)
    {
        try {
            // Validate request method
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            // Authorization: page/module management requires can_config.
            if (!ApiController::canModify($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            // Parse inputs
            $id = $request->post('id')->toInt();
            $language = $request->post('language')->filter('a-z');
            if (empty($language)) {
                return $this->errorResponse('Please select a language', 400);
            }

            $db = \Kotchasan\DB::create();

            $index = $db->first('index', ['id', $id]);
            if (!$index) {
                return $this->errorResponse('No data available', 404);
            }
            if ($index->language == '') {
                return $this->errorResponse('This entry is displayed in all languages', 400);
            }
            $detail = $db->first('index_detail', [['id', $id], ['language', $index->language]]);

            // Check that target language copy doesn't already exist
            $search = $db->first('index', [
                ['module_id', $index->module_id],
                ['language', $language]
            ]);

            if (!empty($search)) {
                return $this->errorResponse('This entry is in selected language', 400);
            }

            // Keep original record intact; insert a new copy with the target language.
            // id stays as-is (FK to index.id) — only language changes.
            $newIndex = (array) $index;
            $newIndex['id'] = $db->nextId('index');
            $newIndex['language'] = $language;
            $db->insert('index', $newIndex);
            $newDetail = (array) $detail;
            $newDetail['language'] = $language;
            $newDetail['id'] = $newIndex['id'];
            $db->insert('index_detail', $newDetail);

            // Log the action
            \Index\Log\Model::add($id, 'index', 'Index', 'Copy page ID: '.$id.' to language: '.$language, $login->id);

            // Return success response
            return $this->redirectResponse('/page?id='.$newIndex['id'], 'Copied successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Parse page input from request
     *
     * @param Request $request
     * @param int $id Page ID (0 for new page)
     *
     * @return array
     */
    protected function parseInput(Request $request, $id = 0): array
    {
        $index_save = [
            'published' => $request->post('published')->toBoolean(),
            'published_date' => $request->post('published_date')->date()
        ];
        $detail_save = [
            'language' => $request->post('language')->filter('a-z'),
            'description' => $request->post('description')->textarea(),
            'detail' => str_replace(WEB_URL, '{WEBURL}', $request->post('detail')->detail()),
            'keywords' => implode(',', $request->post('keywords', [])->topic()),
            'topic' => $request->post('topic')->topic()
        ];
        if (empty($detail_save['keywords'])) {
            $detail_save['keywords'] = $request->post('topic')->topic(149);
        }
        if (empty($detail_save['description'])) {
            $detail_save['description'] = $request->post('detail')->keywords(149);
        }

        $module_save = [
            'module' => $request->post('module')->filter('a-z0-9'),
            'owner' => $request->post('owner')->filter('a-z')
        ];

        return [
            'index' => $index_save,
            'detail' => $detail_save,
            'module' => $module_save
        ];
    }

    /**
     * Validate page fields for duplicates and required fields
     *
     * @param int    $id   Page ID (0 for new page)
     * @param array  &$save Save data (may be modified by reference)
     * @param object $page  Existing page record
     * @param object $db    Database connection
     *
     * @return array Validation errors keyed by field name
     */
    protected function validateFields($id, &$save, $page, $db)
    {
        $errors = [];
        if (empty($id) && !preg_match('/[a-z]{3,}/', $save['module']['owner'])) {
            $errors['module'] = 'No data available';
        } elseif (!preg_match('/^[a-z0-9]{2,}$/', $save['module']['module'])) {
            $errors['module'] = 'Name of this module. English lowercase and number only, short. (Can not use a reserved or a duplicate name)';
        } elseif ($save['module']['owner'] === 'index' && $save['module']['module'] != 'home' && is_dir(ROOT_PATH.'modules/'.$save['module']['module'])) {
            // index cannot use module or widget names.
            $errors['module'] = 'Name of this module. English lowercase and number only, short. (Can not use a reserved or a duplicate name)';
        } elseif (empty($id) && \Index\Page\Model::isSingletonInstalled($save['module']['owner'])) {
            // This owner type is capped at one installed instance.
            $errors['owner'] = 'This module type only allows a single instance';
        } else {
            // Find duplicate module names
            $where = [
                ['M.module', $save['module']['module']],
                ['Index', 1]
            ];
            if ($id > 0) {
                $where[] = ['D.id', '!=', $id];
            }
            $query = \Kotchasan\Model::createQuery()
                ->select('D.language')
                ->from('modules M')
                ->join('index_detail D', ['D.module_id', 'M.id'])
                ->join('index I', [['I.module_id', 'M.id'], ['I.id', 'D.id']])
                ->where($where);
            foreach ($query->fetchAll() as $item) {
                if (empty($save['detail']['language']) ||
                    empty($item->language) ||
                    $item->language === $save['detail']['language']
                ) {
                    $errors['module'] = 'Module already exists';
                }
            }
        }
        // topic
        if (mb_strlen($save['detail']['topic']) < 3) {
            $errors['topic'] = 'Text displayed on the Title Bar of the browser (3 - 255 characters)';
        } elseif (empty($errors)) {
            // Find duplicate title names
            $search = $db->first('index_detail', [
                ['topic', $save['detail']['topic']],
                ['language', ['', $save['detail']['language']]]
            ]);
            if ($search && (empty($id) || $id != $search->id)) {
                $errors['module'] = 'Topic already exists';
            }
        }

        return $errors;
    }
}
