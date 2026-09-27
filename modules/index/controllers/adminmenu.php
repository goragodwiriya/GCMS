<?php
/**
 * @filesource modules/index/controllers/adminmenu.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Adminmenu;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Language;

/**
 * API Admin Menu Controller
 *
 * Handles single menu item CRUD endpoints
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/index/adminmenu/get
     * Get menu details by ID
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
            // Authorization: super-admin, or a staff account with the can_config permission (and not in demo mode)
            if (!ApiController::canModify($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            $id = $request->get('id')->toInt();
            $parent = $request->get('parent')->topic();
            $menu = \Index\Menu\Model::get($id, $parent);
            if (!$menu) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $menu->menu_url = str_replace('{WEBURL}', WEB_URL, (string) $menu->menu_url);

            // Return user details with options
            return $this->successResponse([
                'data' => $menu,
                'options' => [
                    'language' => \Gcms\Controller::arrayToOptions(['' => 'All languages'] + Language::installedLanguage()),
                    'index_id' => \Index\Menu\Model::getOrderOptions($menu->parent)
                ]
            ], 'User details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/index/adminmenu/save
     * Save menu item (create or update)
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
                return $this->errorResponse('Unauthorized', 401);
            }
            // Authorization: super-admin, or a staff account with the
            // can_config permission (and not in demo mode) — matches the
            // old system's Login::checkPermission($login, 'can_config').
            if (!ApiController::canModify($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            // Parse input data
            $id = $request->post('id')->toInt();
            $parent = $request->post('parent')->topic(); // e.g. '0_MAINMENU'
            $type = $request->post('type')->toInt(); // 0/1/2/3
            $action = $request->post('menu_action')->toInt(); // 0/1/2
            $afterId = $request->post('menu_order')->toInt(); // ID of preceding sibling, 0 = first
            // index_id is either a plain `index.id` integer (real installed
            // page) or a "sys:xxx"/"{owner}:xxx" virtual target string (see
            // Index\Menu\Model::virtualTargets()) — resolve which one first.
            $indexIdRaw = $request->post('index_id')->filter('a-z0-9_:');
            $virtualUrl = \Index\Menu\Model::resolveVirtualTarget($indexIdRaw);
            $indexId = $virtualUrl !== null ? 0 : (int) $indexIdRaw;

            // Validate required fields
            $errors = [];
            $menuText = $request->post('menu_text')->topic();
            if (empty($menuText)) {
                $errors['menu_text'] = 'Please fill in';
            }
            $validParents = ['0_MAINMENU', '1_SIDEMENU', '2_BOTTOMMENU'];
            if (!in_array($parent, $validParents, true)) {
                $errors['parent'] = 'Invalid value';
            }
            $accesskey = $request->post('accesskey')->filter('a-z0-9');
            if ($accesskey !== '' && !preg_match('/^[a-z0-9]$/', $accesskey)) {
                $errors['accesskey'] = 'Must be a single lowercase letter or number';
            }
            if ($action === 1 && $indexId <= 0 && $virtualUrl === null) {
                $errors['index_id'] = 'Please select a module';
            } elseif ($action === 2 && $request->post('menu_url')->url() === '') {
                $errors['menu_url'] = 'Please fill in';
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            // Derive level from type
            // type 0 = front page (level 0), 1 = top-level (level 0), 2 = 1st sub (level 1), 3 = 2nd sub (level 2)
            if ($type === 2) {
                $level = 1;
            } elseif ($type === 3) {
                $level = 2;
            } else {
                $level = 0;
            }

            // Derive index_id / menu_url / menu_target from action
            if ($action === 1) {
                // Link to an installed module (menu_url stays empty, the
                // public renderer builds the URL from index_id — see
                // Index\Menu\View::createItem()), or a virtual target
                // (search/login/... or a module-registered target), whose
                // resolved URL is used directly since it has no index_id.
                $menuUrl = $virtualUrl ?? '';
                $menuTarget = $request->post('menu_target')->filter('a-z_');
            } elseif ($action === 2) {
                // External / custom URL
                $indexId = 0;
                $menuUrl = $request->post('menu_url')->url();
                $menuTarget = $request->post('menu_target')->filter('a-z_');
            } else {
                // No link (separator / group header)
                $indexId = 0;
                $menuUrl = '';
                $menuTarget = '';
            }

            // Build save data
            $save = [
                'index_id' => $indexId,
                'parent' => $parent,
                'level' => $level,
                'language' => $request->post('language')->filter('a-z'),
                'menu_text' => $menuText,
                'menu_tooltip' => $request->post('menu_tooltip')->topic(),
                'accesskey' => $accesskey,
                'alias' => $request->post('alias')->topic(),
                'published' => $request->post('published')->toInt(),
                'menu_url' => str_replace(WEB_URL, '{WEBURL}', $menuUrl),
                'menu_target' => $menuTarget,
                'icon' => $request->post('icon')->filter('a-z0-9_\-')
            ];

            $db = \Kotchasan\DB::create();

            // Verify the existing record when updating
            $existing = null;
            if ($id > 0) {
                $existing = $db->first('menus', ['id', $id]);
                if (!$existing) {
                    return $this->errorResponse('No data available', 404);
                }
            }

            // Build ordered sibling ID list (excluding current item)
            $siblings = $db->select('menus', ['parent', $parent], ['orderBy' => 'menu_order'], ['id']);
            $siblingIds = array_column((array) $siblings, 'id');
            $siblingIds = array_values(array_filter($siblingIds, fn($sid) => $sid !== $id));

            // Determine insert position (after $afterId; 0 = insert at start)
            if ($afterId === 0) {
                $insertPos = 0;
            } else {
                $pos = array_search($afterId, $siblingIds);
                $insertPos = $pos !== false ? $pos + 1 : count($siblingIds);
            }

            // Assign ID for new items
            if ($id === 0) {
                $id = $db->nextId('menus');
                $save['id'] = $id;
            }

            // Splice current item into its new position
            array_splice($siblingIds, $insertPos, 0, [$id]);
            $save['menu_order'] = $insertPos + 1;

            // Persist the menu item
            if ($existing !== null) {
                $db->update('menus', ['id', $id], $save);
            } else {
                $db->insert('menus', $save);
            }

            // Resequence all other siblings to keep menu_order consecutive
            foreach ($siblingIds as $pos => $sid) {
                if ($sid !== $id) {
                    $db->update('menus', ['id', $sid], ['menu_order' => $pos + 1]);
                }
            }

            // Log the action
            $action_label = $existing !== null ? 'Edit' : 'Create';
            \Index\Log\Model::add($id, 'index', 'Index', $action_label.' menu: '.$id, $login->id);

            return $this->redirectResponse('back', 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/index/adminmenu/menus
     * Get menu options by parent for dropdowns
     *
     * @param Request $request
     *
     * @return Response
     */
    public function menus(Request $request)
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
            // Authorization: super-admin, or a staff account with the
            // can_config permission (and not in demo mode) — matches the
            // old system's Login::checkPermission($login, 'can_config').
            if (!ApiController::canModify($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            // Get menus by parent
            $parent = $request->post('parent')->topic();
            $options = [
                'orderBy' => 'menu_order'
            ];
            $menus = \Kotchasan\DB::create()->select('menus', ['parent', $parent], $options, ['id', 'level', 'menu_text', 'menu_tooltip']);

            // Format result with indentation based on level
            $result = [];
            foreach ($menus as $row) {
                $text = '';
                for ($j = 0; $j < $row->level; ++$j) {
                    $text .= "\xE2\x80\x83";
                }
                $result['O_'.$row->id] = (empty($text) ? '' : $text.'↳ ').(empty($row->menu_text) ? (empty($row->menu_tooltip) ? '---' : $row->menu_tooltip) : $row->menu_text).(empty($row->language) ? '' : ' ['.$row->language.']');
            }

            // Return formatted menu options
            return $this->successResponse($result, 'Menus retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/index/adminmenu/copy
     * Copy a menu item to another language
     *
     * @param Request $request
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
            // Authorization: super-admin, or a staff account with the
            // can_config permission (and not in demo mode) — matches the
            // old system's Login::checkPermission($login, 'can_config').
            if (!ApiController::canModify($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            // Parse inputs
            $id = $request->post('id')->toInt();
            $language = $request->post('language')->filter('a-z');
            if (empty($language)) {
                return $this->errorResponse('Please select a language', 400);
            }

            $db = \Kotchasan\DB::create();

            $menu = $db->first('menus', ['id', $id]);
            if (!$menu) {
                return $this->errorResponse('No data available', 404);
            }
            if ($menu->language == '') {
                return $this->errorResponse('This entry is displayed in all languages', 400);
            }

            // Check that target language copy doesn't already exist
            $search = $db->first('menus', [
                ['index_id', $menu->index_id],
                ['parent', $menu->parent],
                ['level', $menu->level],
                ['language', $language]
            ]);

            if (!empty($search)) {
                return $this->errorResponse('This entry is in selected language', 400);
            }

            // Keep original record intact; insert a new copy with the target language
            $newMenu = (array) $menu;
            unset($newMenu['id']);
            $newMenu['language'] = $language;
            // Append at end of siblings (give it the highest menu_order)
            $siblings = $db->select('menus', ['parent', $menu->parent], ['orderBy' => ['menu_order' => 'DESC']], ['menu_order']);
            $newMenu['menu_order'] = empty($siblings) ? 1 : ($siblings[0]->menu_order + 1);
            $newMenu['id'] = $db->nextId('menus');
            $db->insert('menus', $newMenu);

            // Log the action
            \Index\Log\Model::add($id, 'index', 'Index', 'Copy menu ID: '.$id.' to language: '.$language, $login->id);

            // Return success response
            return $this->redirectResponse('/menu?id='.$newMenu['id'], 'Copied successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
