<?php
/**
 * @filesource modules/board/controllers/topic.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Topic;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Language;
use Web\Login;

/**
 * API Frontend Topic Controller
 *
 * Handles new topic submission from the frontend form
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/board/topic/get
     * Get topic data for editing and get user information (frontend)
     *
     * @param Request $request
     * @return mixed
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            // Authenticate request
            $login = $this->authenticateRequest($request);

            $id = $request->get('id')->toInt();
            // board_module=forum-write
            // NOTE: cannot be named "module" — that key is reserved by the
            // API router (Kotchasan\ApiController) to resolve the dispatched
            // controller, and a client-supplied ?module= would hijack routing.
            $module = $request->get('board_module')->filter('a-z\-');
            $module = \Index\Module\Model::getModuleWithConfig('board', 0, $module);
            if (!$module) {
                return $this->errorResponse('Module not found', 404);
            }

            $category_id = $request->get('category_id')->toInt();
            $effectiveConfig = \Board\Category\Model::getEffectiveConfig($module->config, $module->id, $category_id);

            if ($id === 0) {
                // New topic — must be allowed to post before returning the empty form
                $canPost = $login ? Login::checkStatus($login, $effectiveConfig, ['can_post']) : null;
                $guestAllowed = $this->isGuestAllowed($effectiveConfig, 'can_post');
                if (!$canPost && !$guestAllowed) {
                    return $this->errorResponse('Permission required', 403);
                }

                $page = \Board\Write\Model::get(0);
                $page->module_id = $module->id;
                $page->category_id = $category_id;
                $page->member_id = $login ? $login->id : 0;
                $page->sender = $login ? (!empty($login->name) ? (string) $login->name : '') : '';
                $page->email = $login ? (!empty($login->username) ? (string) $login->username : '') : '';
            } else {
                // Existing topic
                if (!$login) {
                    return $this->errorResponse('Unauthorized', 401);
                }

                $page = \Board\Write\Model::get($id);
                if (!$page || $page->module_id !== $module->id) {
                    return $this->errorResponse('Topic not found', 404);
                }

                // Only the original author or a moderator may access the edit form
                $effectiveConfig = \Board\Category\Model::getEffectiveConfig($module->config, $module->id, $page->category_id);
                $canModerate = Login::checkStatus($login, $effectiveConfig, ['moderator']);
                if ($page->member_id !== $login->id && !$canModerate) {
                    return $this->errorResponse('Permission denied', 403);
                }
            }

            // getEffectiveConfig() always returns an array
            $uploadTypes = isset($effectiveConfig['img_upload_type']) && is_array($effectiveConfig['img_upload_type']) ? $effectiveConfig['img_upload_type'] : [];
            $imgLaw = $effectiveConfig['img_law'] ?? 0;
            // Shape matches the existing-file input convention (see board/template/settings.html "default_icon")
            $page->picture = empty($page->picture) ? [] : [[
                'url' => WEB_URL.DATA_FOLDER.'board/'.$page->picture,
                'name' => $page->picture
            ]];
            // untextarea(): stored content is textarea()-encoded; this JSON is
            // consumed by JS `field.value = ...`, which does no HTML parsing —
            // return the real characters so the textarea shows `<script>`
            // instead of `&lt;script&gt;`. (The server-rendered write form goes
            // through Board\Write\Controller instead and must stay encoded,
            // since there the content is embedded in HTML.)
            $page->detail = \Kotchasan\Text::untextarea($page->detail);

            return $this->successResponse([
                'data' => $page,
                'options' => [
                    'category_id' => \Document\Category\Model::toOptions($module->id, true)
                ],
                'upload' => [
                    'enabled' => !empty($uploadTypes),
                    'types' => $uploadTypes,
                    'law_text' => Language::get('IMG_LAW', '', $imgLaw)
                ]
            ], 'Article details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/board/topic/remove-picture
     * Remove the attached image from a topic (owner or moderator only)
     *
     * @param Request $request
     *
     * @return Response
     */
    public function removePicture(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $id = $request->post('id')->toInt();
            $module_id = $request->post('module_id')->toInt();

            $db = \Kotchasan\DB::create();
            $topic = $db->first('board_q', [['id', $id], ['module_id', $module_id]]);
            if (!$topic) {
                return $this->errorResponse('Topic not found', 404);
            }

            $module = \Index\Module\Model::getModuleWithConfig('board', $module_id);
            if (!$module) {
                return $this->errorResponse('Module not found', 404);
            }
            $effectiveConfig = \Board\Category\Model::getEffectiveConfig($module->config, $module->id, (int) $topic->category_id);
            $canModerate = Login::checkStatus($login, $effectiveConfig, ['moderator']);
            if ($topic->member_id !== $login->id && !$canModerate) {
                return $this->errorResponse('Permission denied', 403);
            }

            if (!empty($topic->picture)) {
                $path = ROOT_PATH.DATA_FOLDER.'board/'.$topic->picture;
                if (is_file($path)) {
                    unlink($path);
                }
                $db->update('board_q', ['id', $id], ['picture' => '']);

                // Log
                \Index\Log\Model::add($id, 'board', 'Board', 'Removed topic image: '.$id, (int) $login->id);
            }

            return $this->successResponse([], 'Image removed');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/board/topic/save
     * Save a new topic (frontend)
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

            $id = $request->post('id')->toInt();
            $module_id = $request->post('module_id')->toInt();
            $module = \Index\Module\Model::getModuleWithConfig('board', $module_id);
            if (!$module) {
                return $this->errorResponse('Module not found', 404);
            }

            $category_id = $request->post('category_id')->toInt();
            $effectiveConfig = \Board\Category\Model::getEffectiveConfig($module->config, $module->id, $category_id);

            if ($id === 0) {
                // can_post only gates creating a new topic — editing an existing
                // one is gated below by ownership/moderator instead, so a user
                // who has since lost can_post can still edit their own past posts.
                $canPost = $login ? Login::checkStatus($login, $effectiveConfig, ['can_post']) : null;
                $guestAllowed = $this->isGuestAllowed($effectiveConfig, 'can_post');
                if (!$canPost && !$guestAllowed) {
                    return $this->errorResponse('Permission required', 403);
                }
            }

            // Prepare data for insert/update
            $save = [
                'category_id' => $category_id,
                'topic' => $request->post('topic')->topic(),
                'detail' => str_replace(WEB_URL, '{WEBURL}', $request->post('detail')->textarea()),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $error = [];
            $senderName = '';
            if ($login) {
                $senderName = !empty($login->name) ? (string) $login->name : (!empty($login->username) ? (string) $login->username : Language::get('Member', 'Member'));
            } else {
                $sender = $request->post('sender')->topic();
                if (empty($sender)) {
                    $error['sender'] = 'Please fill in';
                }
                $email = $request->post('email')->email();
                if (empty($email)) {
                    $error['email'] = 'Please fill in';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error['email'] = 'Invalid email';
                }
                // Prevent a guest from posting under a name/email that already
                // belongs to a registered member (identity impersonation).
                if (empty($error) && \Index\Auth\Model::isMemberIdentity($sender, $email)) {
                    $error['email'] = Language::get('This name or email is already registered. Please log in to post.');
                }
                $senderName = $sender;
            }
            if (mb_strlen($save['topic']) < 3) {
                $error['topic'] = 'Please specify topic title (at least 3 characters).';
            }
            if (mb_strlen(trim($save['detail'])) < 5) {
                $error['detail'] = 'Please specify topic content (at least 5 characters).';
            }

            if (!empty($error)) {
                return $this->formErrorResponse($error, 400);
            }

            //  Database connection
            $db = \Kotchasan\DB::create();
            $actorId = $login ? (int) $login->id : 0;

            if ($id === 0) {
                // Check for duplicate posts within 1 day.
                $where = [
                    ['topic', $save['topic']],
                    ['updated_at', '>', date('Y-m-d H:i:s', time() - 86400)]
                ];
                if ($login) {
                    $where[] = ['member_id', $login->id];
                } else {
                    $where[] = ['ip', $request->getClientIp()];
                }
                $search = $db->first('board_q', $where);
                if ($search) {
                    return $this->errorResponse('Duplicate post detected', 409);
                }

                // Handle image upload (validate before writing anything to the DB)
                $this->processPictureUpload($request, $effectiveConfig, $module->id, 0, null, $save, $error);
                if (!empty($error)) {
                    return $this->formErrorResponse($error, 422);
                }

                $save['module_id'] = $module_id;
                $save['member_id'] = $actorId;
                $save['ip'] = $request->getClientIp();
                $save['created_at'] = $save['updated_at'];
                $save['visited'] = 0;
                $save['comments'] = 0;
                $save['comment_id'] = 0;
                $save['commentator_id'] = 0;
                $save['commentator'] = null;
                $save['comment_date'] = null;
                $save['published'] = 1;
                $save['pin'] = 0;
                $save['locked'] = 0;
                $this->applyOptionalIdentityFields($db, 'board_q', $save, $login, $request);

                $newId = $db->insert('board_q', $save);
                if (!$newId) {
                    return $this->errorResponse(Language::get('Failed to save'), 500);
                }

                // Log
                \Index\Log\Model::add($newId, 'board', 'Board', 'New Topic: '.$save['topic'], $actorId);

                $url = \Board\Index\Controller::url($module->module, $category_id, $newId);

                // Notify moderators (LINE) if configured for new topics
                \Index\Notify\Model::notifyModerators($effectiveConfig, 1, 'moderator', Language::get('New topic'), $senderName, $save['topic'], $url);
            } else {
                if (!$login) {
                    return $this->errorResponse('Unauthorized', 401);
                }
                // Verify topic exists for update (filter by id AND module_id)
                $search = $db->first('board_q', [['id', $id], ['module_id', $module_id]]);
                if (!$search) {
                    return $this->errorResponse('Topic not found', 404);
                }

                // Only the original author or a moderator may edit
                $canModerate = Login::checkStatus($login, $effectiveConfig, ['moderator']);
                if ($search->member_id !== $login->id && !$canModerate) {
                    return $this->errorResponse('Permission denied', 403);
                }

                // A locked topic can only be edited by a moderator, even by its own author
                if ($search->locked && !$canModerate) {
                    return $this->errorResponse('Topic is locked', 403);
                }

                // Handle image upload (validate before writing anything to the DB)
                $this->processPictureUpload($request, $effectiveConfig, $module->id, $id, $search->picture, $save, $error);
                if (!empty($error)) {
                    return $this->formErrorResponse($error, 422);
                }

                $db->update('board_q', ['id', $id], $save);

                // Log
                \Index\Log\Model::add($id, 'board', 'Board', 'Updated Topic: '.$save['topic'], $actorId);

                $url = \Board\Index\Controller::url($module->module, $category_id, $id);
            }

            // Redirect to topic page after posting
            return $this->redirectResponse($url, Language::get($id === 0 ? 'Topic created successfully' : 'Topic updated successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Validate and store an uploaded topic image (field name "picture"),
     * or remove the existing one when "remove_picture" is posted.
     * Only runs when the module's img_upload_type config is non-empty.
     * Populates $save['picture'] on success, or $errors['picture'] on failure.
     *
     * @param Request $request
     * @param array    $effectiveConfig Category-aware module config (array)
     * @param int      $moduleId        Module ID (used for the stored filename)
     * @param int      $id              Topic ID (0 = new topic)
     * @param string|null $existingPicture Current stored filename, if any
     * @param array    $save            Save data, passed by reference
     * @param array    $errors          Errors, passed by reference
     */
    private function processPictureUpload(Request $request, array $effectiveConfig, $moduleId, $id, $existingPicture, array &$save, array &$errors): void
    {
        $uploadTypes = isset($effectiveConfig['img_upload_type']) && is_array($effectiveConfig['img_upload_type']) ? $effectiveConfig['img_upload_type'] : [];
        \Index\Upload\Model::processImage(
            $request,
            'picture',
            $uploadTypes,
            ROOT_PATH.DATA_FOLDER.'board/',
            $id > 0 ? 'board-'.$moduleId.'-'.$id : '',
            $existingPicture,
            $id > 0 && $request->post('remove_picture')->toBoolean(),
            $save,
            $errors
        );
    }

    /**
     * Check whether guest status (-1) is allowed for a permission key.
     */
    private function isGuestAllowed($config, string $key): bool
    {
        if (is_object($config) && property_exists($config, $key)) {
            $allowed = $config->{$key};
        } elseif (is_array($config) && array_key_exists($key, $config)) {
            $allowed = $config[$key];
        } else {
            return false;
        }

        if (is_array($allowed)) {
            foreach ($allowed as $status) {
                if ((int) $status === -1) {
                    return true;
                }
            }
        }

        return (int) $allowed === -1;
    }

    /**
     * Add identity columns when the current DB schema contains them (legacy compatibility).
     */
    private function applyOptionalIdentityFields($db, string $table, array &$save, $login, Request $request): void
    {
        $guestName = trim($request->post('sender')->topic());
        $legacyEmail = trim($request->post('board_email')->email());
        $guestEmail = trim($request->post('email')->email());
        if ($guestEmail === '' && $legacyEmail !== '') {
            $guestEmail = $legacyEmail;
        }
        if ($guestName === '' && $legacyEmail !== '') {
            $guestName = $legacyEmail;
        }
        if ($guestName === '') {
            $guestName = Language::get('Guest', 'Guest');
        }

        $sender = $guestName;
        $email = $guestEmail;
        if ($login) {
            $sender = !empty($login->name) ? (string) $login->name : (!empty($login->username) ? (string) $login->username : Language::get('Member', 'Member'));
            $email = !empty($login->email) ? (string) $login->email : '';
        }

        if ($db->fieldExists($table, 'sender')) {
            $save['sender'] = $sender;
        }
        if ($db->fieldExists($table, 'email')) {
            $save['email'] = $email;
        }
    }
}
