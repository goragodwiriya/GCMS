<?php
/**
 * @filesource modules/document/controllers/reply.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Reply;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Language;
use Web\Login;

/**
 * API Frontend Reply Controller
 *
 * Handles posting, editing and removing article comments (comment table)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * POST /api/document/reply/save
     * Post or edit a comment
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

            $module = \Index\Module\Model::getModuleWithConfig('document', $module_id);
            if (!$module) {
                return $this->errorResponse('Module not found', 404);
            }

            if (mb_strlen(trim($detail)) < 2) {
                return $this->formErrorResponse(['detail' => Language::get('Comment content is required')], 400);
            }

            if ($index_id === 0) {
                return $this->errorResponse('Invalid article ID', 400);
            }

            $db = \Kotchasan\DB::create();

            // Verify article exists
            $article = $db->first('index', [['id', $index_id], ['module_id', $module_id]]);
            if (!$article) {
                return $this->errorResponse('Article not found', 404);
            }

            $effectiveConfig = \Document\Category\Model::getEffectiveConfig($module->config, $module->id, (int) $article->category_id);
            $canManage = $login ? Login::checkStatus($login, $effectiveConfig, ['can_approve']) : null;
            if ($id === 0) {
                // can_reply only gates posting a new comment — editing an existing
                // one is gated below by ownership/approver instead, so a user
                // who has since lost can_reply can still edit their own past posts.
                $canPost = $login ? Login::checkStatus($login, $effectiveConfig, ['can_reply']) : null;
                $guestAllowed = $this->isGuestAllowed($effectiveConfig, 'can_reply');
                if (!$canPost && !$canManage && !$guestAllowed) {
                    return $this->errorResponse('Permission required', 403);
                }
            }

            // Guests must provide a name and a valid email — same requirement
            // as posting a new topic/reply on the board module — and cannot
            // impersonate a registered member.
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

            $actorId = $login ? (int) $login->id : 0;
            $senderName = $login
                ? (!empty($login->name) ? (string) $login->name : (!empty($login->username) ? (string) $login->username : Language::get('Member', 'Member')))
                : $guestName;
            $senderEmail = $login ? (!empty($login->email) ? (string) $login->email : '') : $guestEmail;

            // Prepare data for insert/update
            $save = [
                'detail' => str_replace(WEB_URL, '{WEBURL}', $detail),
                'sender' => $senderName,
                'email' => $senderEmail,
                'updated_at' => date('Y-m-d H:i:s')
            ];

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
                $search = $db->first('comment', $where);
                if ($search) {
                    return $this->errorResponse('Duplicate post detected', 409);
                }

                // New comment
                $save['module_id'] = $module_id;
                $save['index_id'] = $index_id;
                $save['member_id'] = $actorId;
                $save['ip'] = $request->getClientIp();
                $save['created_at'] = $save['updated_at'];

                $newId = $db->insert('comment', $save);
                if (!$newId) {
                    return $this->errorResponse('Failed to post comment', 500);
                }

                // Update article comment count and last commenter info
                \Index\Comments\Model::refreshSummary('index', 'comment', $index_id, $module_id);

                // Log
                \Index\Log\Model::add($index_id, 'document', 'Document', 'New Comment: '.$newId, $actorId);

                // Notify approvers (LINE) if configured for new comments
                $url = \Document\Index\Controller::url($module->module, (string) $article->alias, (int) $article->id).'#comment-'.$newId;
                $articleTopic = $db->first('index_detail', [['id', $index_id], ['module_id', $module_id], ['language', ['', Language::name()]]], ['topic']);
                \Index\Notify\Model::notifyModerators($effectiveConfig, 3, 'can_approve', Language::get('New comment'), $senderName, $articleTopic ? (string) $articleTopic->topic : '', $url);

                return $this->redirectResponse('reload', Language::get('Comment posted successfully'));
            } else {
                if (!$login) {
                    return $this->errorResponse('Unauthorized', 401);
                }
                // Edit comment — only approver or owner
                $existing = $db->first('comment', [['id', $id], ['module_id', $module_id]]);
                if (!$existing) {
                    return $this->errorResponse('Comment not found', 404);
                }
                if ($existing->member_id !== $login->id && !$canManage) {
                    return $this->errorResponse('Permission denied', 403);
                }

                $db->update('comment', ['id', $id], $save);

                // Log
                \Index\Log\Model::add($index_id, 'document', 'Document', 'Updated Comment: '.$id, $actorId);

                // Editing happens on its own page (document-commentwrite), not
                // inline on the article page, so send the user back to the
                // article itself rather than reloading the edit form.
                $url = \Document\Index\Controller::url($module->module, (string) $article->alias, (int) $article->id);
                return $this->redirectResponse($url, Language::get('Comment updated successfully'));
            }
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * GET /api/document/reply/get
     * Get a single comment's editable data (owner or approver only)
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
            $comment = $db->first('comment', [['id', $id], ['module_id', $module_id]]);
            if (!$comment) {
                return $this->errorResponse('Comment not found', 404);
            }

            $module = \Index\Module\Model::getModuleWithConfig('document', $module_id);
            if (!$module) {
                return $this->errorResponse('Module not found', 404);
            }
            $article = $db->first('index', ['id', $comment->index_id]);
            $effectiveConfig = \Document\Category\Model::getEffectiveConfig($module->config, $module->id, (int) ($article->category_id ?? 0));
            $canManage = Login::checkStatus($login, $effectiveConfig, ['can_approve']);
            if ($comment->member_id !== $login->id && !$canManage) {
                return $this->errorResponse('Permission denied', 403);
            }

            return $this->successResponse([
                'data' => [
                    'id' => $comment->id,
                    // untextarea(): stored content is textarea()-encoded; this
                    // JSON is consumed by JS `field.value = ...`, which does no
                    // HTML parsing — return the real characters so the textarea
                    // shows `<script>` instead of `&lt;script&gt;`.
                    'detail' => \Kotchasan\Text::untextarea(str_replace('{WEBURL}', WEB_URL, $comment->detail))
                ]
            ], 'Comment details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/document/reply/delete
     * Remove a comment (owner or approver only)
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
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $id = $request->post('id')->toInt();
            $module_id = $request->post('module_id')->toInt();

            $db = \Kotchasan\DB::create();
            $comment = $db->first('comment', [['id', $id], ['module_id', $module_id]]);
            if (!$comment) {
                return $this->errorResponse('Comment not found', 404);
            }

            $module = \Index\Module\Model::getModuleWithConfig('document', $module_id);
            if (!$module) {
                return $this->errorResponse('Module not found', 404);
            }

            $article = $db->first('index', [['id', $comment->index_id], ['module_id', $module_id]]);
            $effectiveConfig = \Document\Category\Model::getEffectiveConfig($module->config, $module->id, (int) ($article->category_id ?? 0));
            $canManage = Login::checkStatus($login, $effectiveConfig, ['can_approve']);
            if ($comment->member_id !== $login->id && !$canManage) {
                return $this->errorResponse('Permission denied', 403);
            }

            $db->delete('comment', ['id', $id]);
            if ($article) {
                \Index\Comments\Model::refreshSummary('index', 'comment', $article->id, $module_id);
            }

            // Log
            \Index\Log\Model::add($comment->index_id, 'document', 'Document', 'Deleted Comment: '.$id, (int) $login->id);

            return $this->redirectResponse('reload', Language::get('Comment deleted successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
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
}
