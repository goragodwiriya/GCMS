<?php
/**
 * @filesource modules/board/controllers/reply.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Reply;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Language;
use Web\Login;

/**
 * API Frontend Reply Controller
 *
 * Handles posting, editing replies (board_r)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * POST /api/board/reply/save
     * Post or edit a reply
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

            $module_id = $request->post('module_id')->toInt();
            $detail = $request->post('detail')->textarea();
            $index_id = $request->post('index_id')->toInt();
            $id = $request->post('id')->toInt();

            $module = \Index\Module\Model::getModuleWithConfig('board', $module_id);
            if (!$module) {
                return $this->errorResponse('Module not found', 404);
            }

            if (mb_strlen(trim($detail)) < 2) {
                return $this->formErrorResponse(['detail' => Language::get('Reply content is required')], 400);
            }

            if ($index_id === 0) {
                return $this->errorResponse('Invalid topic ID', 400);
            }

            $db = \Kotchasan\DB::create();

            // Verify topic exists
            $topic = $db->first('board_q', [['id', $index_id], ['module_id', $module->id]]);
            if (!$topic) {
                return $this->errorResponse('Topic not found', 404);
            }

            $effectiveConfig = \Board\Category\Model::getEffectiveConfig($module->config, $module->id, (int) $topic->category_id);
            $canManage = $login ? Login::checkStatus($login, $effectiveConfig, ['moderator']) : null;
            if ($id === 0) {
                // can_reply only gates posting a new reply — editing an existing
                // one is gated below by ownership/moderator instead, so a user
                // who has since lost can_reply can still edit their own past posts.
                $canPost = $login ? Login::checkStatus($login, $effectiveConfig, ['can_reply']) : null;
                $guestAllowed = $this->isGuestAllowed($effectiveConfig, 'can_reply');
                if (!$canPost && !$canManage && !$guestAllowed) {
                    return $this->errorResponse('Permission required', 403);
                }
            }

            if ($topic->locked && !$canManage) {
                return $this->errorResponse('Topic is locked', 403);
            }

            // Guests must provide a name and a valid email — same requirement
            // as posting a new topic — and cannot impersonate a registered member.
            $error = [];
            $guestName = '';
            $guestEmail = '';
            if (!$login) {
                $guestName = trim($request->post('sender')->topic());
                if (empty($guestName)) {
                    $error['sender'] = 'Please fill in';
                }
                $guestEmail = trim($request->post('email')->email());
                if (empty($guestEmail)) {
                    $error['email'] = 'Please fill in';
                } elseif (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
                    $error['email'] = 'Invalid email';
                }
                if (empty($error) && \Index\Auth\Model::isMemberIdentity($guestName, $guestEmail)) {
                    $error['email'] = Language::get('This name or email is already registered. Please log in to post.');
                }
            }
            if (!empty($error)) {
                return $this->formErrorResponse($error, 400);
            }

            // Prepare data for insert/update
            $save = [
                'detail' => str_replace(WEB_URL, '{WEBURL}', $detail),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $actorId = $login ? (int) $login->id : 0;
            $senderName = $login
                ? (!empty($login->name) ? (string) $login->name : (!empty($login->username) ? (string) $login->username : Language::get('Member', 'Member')))
                : $guestName;

            if ($id === 0) {
                // Check for duplicate posts within 1 day.
                $where = [
                    ['detail', $detail],
                    ['updated_at', '>', date('Y-m-d H:i:s', time() - 86400)]
                ];
                if ($login) {
                    $where[] = ['member_id', $login->id];
                } else {
                    $where[] = ['ip', $request->getClientIp()];
                }
                $search = $db->first('board_r', $where);
                if ($search) {
                    return $this->errorResponse('Duplicate post detected', 409);
                }

                // Handle image upload (validate before writing anything to the DB)
                $this->processPictureUpload($request, $effectiveConfig, $module->id, 0, null, $save, $error);
                if (!empty($error)) {
                    return $this->formErrorResponse($error, 422);
                }

                // New reply
                $save['module_id'] = $module_id;
                $save['index_id'] = $index_id;
                $save['member_id'] = $actorId;
                $save['ip'] = $request->getClientIp();
                $this->applyOptionalIdentityFields($db, 'board_r', $save, $login, $request);

                $newId = $db->insert('board_r', $save);
                if (!$newId) {
                    return $this->errorResponse('Failed to post reply', 500);
                }

                // Update topic comment count and last reply info
                \Board\Action\Model::refreshTopicCommentSummary($index_id, $module_id);

                // Log
                \Index\Log\Model::add($index_id, 'board', 'Board', 'New Reply: '.$newId, $actorId);

                // Notify moderators (LINE) if configured for new replies
                $url = \Board\Index\Controller::url($module->module, (int) $topic->category_id, $index_id);
                \Index\Notify\Model::notifyModerators($effectiveConfig, 2, 'moderator', Language::get('New reply'), $senderName, $topic->topic, $url);

                return $this->redirectResponse('reload', Language::get('Reply posted successfully'));
            } else {
                if (!$login) {
                    return $this->errorResponse('Unauthorized', 401);
                }
                // Edit reply — only manager or owner
                $existing = $db->first('board_r', [['id', $id], ['module_id', $module_id]]);
                if (!$existing) {
                    return $this->errorResponse('Reply not found', 404);
                }
                if ($existing->member_id !== $login->id && !$canManage) {
                    return $this->errorResponse('Permission denied', 403);
                }

                // Handle image upload (validate before writing anything to the DB)
                $this->processPictureUpload($request, $effectiveConfig, $module->id, $id, $existing->picture, $save, $error);
                if (!empty($error)) {
                    return $this->formErrorResponse($error, 422);
                }

                $db->update('board_r', ['id', $id], $save);

                // Log
                \Index\Log\Model::add($index_id, 'board', 'Board', 'Updated Reply: '.$id, $actorId);

                // Editing happens on its own page (board-replywrite), not inline
                // on the topic page, so send the user back to the topic itself
                // rather than reloading the edit form.
                $url = \Board\Index\Controller::url($module->module, (int) $topic->category_id, $index_id);
                return $this->redirectResponse($url, Language::get('Reply updated successfully'));
            }
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * GET /api/board/reply/get
     * Get a single reply's editable data (owner or moderator only)
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
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $id = $request->get('id')->toInt();
            $module_id = $request->get('module_id')->toInt();

            $db = \Kotchasan\DB::create();
            $reply = $db->first('board_r', [['id', $id], ['module_id', $module_id]]);
            if (!$reply) {
                return $this->errorResponse('Reply not found', 404);
            }

            $module = \Index\Module\Model::getModuleWithConfig('board', $module_id);
            if (!$module) {
                return $this->errorResponse('Module not found', 404);
            }
            $topic = $db->first('board_q', ['id', $reply->index_id]);
            $effectiveConfig = \Board\Category\Model::getEffectiveConfig($module->config, $module->id, (int) ($topic->category_id ?? 0));
            $canModerate = Login::checkStatus($login, $effectiveConfig, ['moderator']);
            if ($reply->member_id !== $login->id && !$canModerate) {
                return $this->errorResponse('Permission denied', 403);
            }

            return $this->successResponse([
                'data' => [
                    'id' => $reply->id,
                    // untextarea(): stored content is textarea()-encoded; this
                    // JSON is consumed by JS `field.value = ...`, which does no
                    // HTML parsing — return the real characters so the textarea
                    // shows `<script>` instead of `&lt;script&gt;`.
                    'detail' => \Kotchasan\Text::untextarea(str_replace('{WEBURL}', WEB_URL, $reply->detail)),
                    'picture' => empty($reply->picture) ? [] : [[
                        'url' => WEB_URL.DATA_FOLDER.'board/'.$reply->picture,
                        'name' => $reply->picture
                    ]]
                ]
            ], 'Reply details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/board/reply/remove-picture
     * Remove the attached image from a reply (owner or moderator only)
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
            $reply = $db->first('board_r', [['id', $id], ['module_id', $module_id]]);
            if (!$reply) {
                return $this->errorResponse('Reply not found', 404);
            }

            $module = \Index\Module\Model::getModuleWithConfig('board', $module_id);
            if (!$module) {
                return $this->errorResponse('Module not found', 404);
            }
            $topic = $db->first('board_q', ['id', $reply->index_id]);
            $effectiveConfig = \Board\Category\Model::getEffectiveConfig($module->config, $module->id, (int) ($topic->category_id ?? 0));
            $canModerate = Login::checkStatus($login, $effectiveConfig, ['moderator']);
            if ($reply->member_id !== $login->id && !$canModerate) {
                return $this->errorResponse('Permission denied', 403);
            }

            if (!empty($reply->picture)) {
                $path = ROOT_PATH.DATA_FOLDER.'board/'.$reply->picture;
                if (is_file($path)) {
                    unlink($path);
                }
                $db->update('board_r', ['id', $id], ['picture' => '']);

                // Log
                \Index\Log\Model::add($id, 'board', 'Board', 'Removed reply image: '.$id, (int) $login->id);
            }

            return $this->successResponse([], 'Image removed');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Validate and store an uploaded reply image (field name "picture"),
     * or remove the existing one when "remove_picture" is posted.
     * Only runs when the module's img_upload_type config is non-empty.
     * Populates $save['picture'] on success, or $errors['picture'] on failure.
     *
     * @param Request     $request
     * @param array       $effectiveConfig Category-aware module config (array)
     * @param int         $moduleId        Module ID (used for the stored filename)
     * @param int         $id              Reply ID (0 = new reply)
     * @param string|null $existingPicture Current stored filename, if any
     * @param array       $save            Save data, passed by reference
     * @param array       $errors          Errors, passed by reference
     */
    private function processPictureUpload(Request $request, array $effectiveConfig, $moduleId, $id, $existingPicture, array &$save, array &$errors): void
    {
        $uploadTypes = isset($effectiveConfig['img_upload_type']) && is_array($effectiveConfig['img_upload_type']) ? $effectiveConfig['img_upload_type'] : [];
        \Index\Upload\Model::processImage(
            $request,
            'picture',
            $uploadTypes,
            ROOT_PATH.DATA_FOLDER.'board/',
            $id > 0 ? 'board-reply-'.$moduleId.'-'.$id : '',
            $existingPicture,
            $id > 0 && $request->post('remove_picture')->toBoolean(),
            $save,
            $errors
        );
    }

    /**
     * Check whether guest status (-1) is allowed for a permission key.
     *
     * @param object|array $config
     * @param string $key
     *
     * @return bool
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
     *
     * @param \Kotchasan\DB $db
     * @param string $table Table name
     * @param array $save Save data, passed by reference
     * @param object|null $login Logged-in user object, or null for guests
     * @param Request $request Request object (used to get guest name/email)
     */
    private function applyOptionalIdentityFields($db, string $table, array &$save, $login, Request $request): void
    {
        $guestName = trim($request->post('sender')->topic());
        $legacyEmail = trim($request->post('reply_email')->email());
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
