<?php
/**
 * @filesource modules/document/controllers/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Write;

use Gcms\Api as ApiController;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Language;
use Kotchasan\Text;
use Web\Gcms;

/**
 * API Document Controller
 *
 * Handles single article CRUD + image upload
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/document/write/get
     * Get article details by ID
     *
     * @param Request $request
     *
     * @return Response
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            // Authenticate request
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            // Load module configuration
            $module = \Index\Module\Model::getModuleWithConfig('document', $request->get('module_id')->toInt());
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_write', 'can_approve'])) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            // Get article data
            $id = $request->get('id')->toInt();
            $page = \Document\Write\Model::get($id, $module->id);
            if (!$page) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $page->img_typies = implode(', ', self::$cfg->img_typies ?? ['jpg', 'jpeg', 'png', 'webp']);
            $page->tab = $request->get('tab', $page->languages[0])->filter('a-z');

            return $this->successResponse([
                'data' => $page,
                'options' => [
                    'category_id' => \Document\Category\Model::toOptions($module->id, true),
                    'tags' => \Widgets\Tags\Models\Settings::toOptions()
                ]
            ], 'Article details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/document/write/save
     * Save article (create or update)
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

            // Load module configuration
            $module = \Index\Module\Model::getModuleWithConfig('document', $request->post('module_id')->toInt());
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_write', 'can_approve'])) {
                return $this->errorResponse('No data available', 404);
            }

            // Get page data
            $page = \Document\Write\Model::get($request->post('id')->toInt(), $module->id);
            if (!$page) {
                return $this->errorResponse('No data available', 404);
            }

            $db = \Kotchasan\DB::create();

            // Parse input data
            $save = $this->parseInput($request);
            // Validate
            $errors = $this->validateFields($page->id, $save, $page, $db);
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            $index_save = $save['index'];
            $detail_save = $save['detail'];
            $tags = $save['tags'];

            if (empty($page->id)) {
                $index_save['id'] = $db->nextId('index');
            } else {
                $index_save['id'] = $page->id;
            }

            // File storage directory
            $dir = ROOT_PATH.DATA_FOLDER.'document/';
            // Upload file
            foreach ($request->getUploadedFiles() as $item => $file) {
                // Name of file to upload
                if ($item === 'picture') {
                    if (!File::makeDirectory($dir)) {
                        // The directory cannot be created.
                        $errors[$item] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.$item.'/');
                    } elseif ($file->hasUploadFile()) {
                        try {
                            $index_save[$item] = 'picture-'.$page->module_id.'-'.$index_save['id'].self::$cfg->stored_img_type;
                            $file->resizeImage(self::$cfg->img_typies, $dir, $index_save[$item], self::$cfg->stored_img_size);
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

            $index_save['updated_at'] = date('Y-m-d H:i:s');
            if (empty($page->id)) {
                // New article
                $index_save['module_id'] = $page->module_id;
                $index_save['index'] = 0;
                $index_save['member_id'] = $login->id;
                $index_save['created_at'] = $index_save['updated_at'];
                $index_save['sender'] = '';
                $index_save['email'] = '';
                $index_save['ip'] = $request->getClientIp();
                $index_save['commentator'] = '';
                $db->insert('index', $index_save);
            } else {
                // Update existing article
                $db->update('index', ['id', $page->id], $index_save);
            }

            // Save details
            $db->delete('index_detail', [
                ['id', $index_save['id']],
                ['module_id', $page->module_id]
            ], 0);
            foreach ($detail_save as $detail) {
                $detail['id'] = $index_save['id'];
                $detail['module_id'] = $page->module_id;
                $db->insert('index_detail', $detail);
            }

            // Save tags
            $db->delete('index_tag', ['index_id', $index_save['id']], 0);
            foreach ($tags as $tag) {
                $db->insert('index_tag', [
                    'index_id' => $index_save['id'],
                    'tag' => $tag
                ]);
            }
            \Widgets\Tags\Models\Settings::ensureExists($tags);

            // Log
            \Index\Log\Model::add($index_save['id'], 'document', 'Document', (empty($page->id) ? 'Create' : 'Edit').' article: '.$index_save['id'], $login->id);

            // Redirect back to article page
            return $this->redirectResponse('back', 'Saved successfully', 200, 1000);
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
        $index_save = [
            'alias' => Gcms::aliasName($request->post('alias')->toString()),
            'category_id' => $request->post('category_id')->toInt(),
            'published' => $request->post('published')->toBoolean(),
            'published_date' => $request->post('published_date')->date(),
            'show_news' => $request->post('show_news')->toBoolean()
        ];
        if (empty($index_save['category_id'])) {
            $index_save['category_id'] = null;
        }
        // trim and remove empty values
        $tagsRaw = json_decode($request->post('tags', [])->toJson(), true);
        $tags = [];
        foreach ($tagsRaw as $key => $tag) {
            $tag = Text::topic($tag);
            if ($tag !== '') {
                $tags[] = $tag;
            }
        }
        // Normalize tags: unique and limit to maximum 5 tags
        $tags = array_values(array_unique($tags));
        if (count($tags) > 5) {
            $tags = array_slice($tags, 0, 5);
        }

        $description = $request->post('description', [])->description();
        $detail = $request->post('detail', [])->detail();
        $keywords = $request->post('keywords', [])->keywords();
        $topic = $request->post('topic', [])->topic();

        $details = [];
        $languages = Language::installedLanguage();
        foreach ($languages as $lng => $label) {
            if (isset($description[$lng]) && $description[$lng] !== '') {
                $details[$lng]['description'] = $description[$lng];
            }
            if (isset($detail[$lng]) && $detail[$lng] !== '') {
                $details[$lng]['detail'] = $detail[$lng];
                if (empty($details[$lng]['description'])) {
                    $details[$lng]['description'] = Text::description($detail[$lng], 255);
                }
            }
            if (isset($keywords[$lng]) && $keywords[$lng] !== '') {
                $details[$lng]['keywords'] = $keywords[$lng];
            }
            if (isset($topic[$lng]) && $topic[$lng] !== '') {
                $details[$lng]['topic'] = $topic[$lng];
                if (empty($index_save['alias'])) {
                    $index_save['alias'] = Gcms::aliasName($topic[$lng]);
                }
                if (empty($details[$lng]['keywords'])) {
                    $details[$lng]['keywords'] = $topic[$lng];
                }
            }
        }

        $multiLanguage = count($details) > 1;
        $detail_save = [];
        foreach ($details as $lng => $data) {
            $detail_save[] = [
                'language' => $multiLanguage ? $lng : '',
                'topic' => $data['topic'] ?? '',
                'description' => $data['description'] ?? '',
                'detail' => str_replace(WEB_URL, '{WEBURL}', $data['detail'] ?? ''),
                'keywords' => $data['keywords'] ?? ''
            ];
        }

        return [
            'index' => $index_save,
            'detail' => $detail_save,
            'tags' => $tags
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
        if (empty($save['detail'])) {
            $errors['topic_'.$page->languages[0]] = 'Please fill in';
        }
        if (empty($save['index']['alias'])) {
            $errors['alias'] = 'Please fill in';
        } else {
            $search = $db->first('index', ['alias', $save['index']['alias']], ['id']);
            if ($search && $search->id != $id) {
                $errors['alias'] = 'Already exists';
            }
        }
        if (empty($save['index']['published_date'])) {
            $errors['published_date'] = 'Please select';
        }

        return $errors;
    }

    /**
     * POST /api/document/write/remove-image
     * Remove featured image from article
     *
     * @param Request $request
     *
     * @return Response
     */
    public function removeImage(Request $request)
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

            $module_id = $request->post('module_id')->toInt();
            $id = $request->post('id')->toInt();

            // Load module configuration
            $module = \Index\Module\Model::getModuleWithConfig('document', $module_id);
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_write', 'can_approve'])) {
                return $this->errorResponse('No data available', 404);
            }

            // Get article image
            $db = \Kotchasan\DB::create();
            $doc = $db->first('index', [['id', $id], ['module_id', $module_id]], ['picture']);
            if ($doc && !empty($doc->picture)) {
                $path = ROOT_PATH.DATA_FOLDER.'document/'.$doc->picture;
                if (file_exists($path)) {
                    unlink($path);
                }
                $db->update('index', [['id', $id], ['module_id', $module_id]], ['picture' => '']);

                // Log
                \Index\Log\Model::add($module_id, 'document', 'Document', 'Delete image ID:'.$id, $login->id);
            }

            return $this->successResponse([], 'Image deleted');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
