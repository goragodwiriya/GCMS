<?php
/**
 * @filesource modules/event/controllers/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Event\Write;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API Event CRUD Controller — ports gcms241021 Event\Admin\Write onto the
 * current API convention (get/save). begin/end are stored as DATETIME.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/event/write/get
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = \Index\Module\Model::getModuleWithConfig('event', $request->get('module_id')->toInt());
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_write'])) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $item = \Event\Write\Model::get($request->get('id')->toInt(), $module->id);
            if (!$item) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            return $this->successResponse($item, 'Event item retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/event/write/save
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = \Index\Module\Model::getModuleWithConfig('event', $request->post('module_id')->toInt());
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_write'])) {
                return $this->errorResponse('No data available', 404);
            }

            $id = $request->post('id')->toInt();
            $item = \Event\Write\Model::get($id, $module->id);
            if (!$item) {
                return $this->errorResponse('No data available', 404);
            }

            $beginDate = $request->post('begin_date')->date();
            $beginTime = $request->post('begin_time')->time(true) ?: '00:00:00';

            $save = [
                'module_id' => $module->id,
                'topic' => $request->post('topic')->topic(255),
                'color' => $request->post('color')->filter('#a-fA-F0-9'),
                'keywords' => $request->post('keywords')->keywords(255),
                'description' => $request->post('description')->description(255),
                'detail' => $request->post('detail')->detail(),
                'published' => $request->post('published')->toBoolean() ? 1 : 0,
                'begin_date' => $beginDate.' '.$beginTime,
                'published_date' => $request->post('published_date')->date()
            ];

            if ($request->post('forever')->toBoolean()) {
                $save['end_date'] = null;
            } else {
                $toTime = $request->post('to_time')->time(true) ?: '00:00:00';
                $save['end_date'] = $beginDate.' '.$toTime;
            }

            if ($save['keywords'] === '') {
                $save['keywords'] = $request->post('topic')->keywords(255);
            }
            if ($save['description'] === '') {
                $save['description'] = $request->post('detail')->description(255);
            }

            $errors = [];
            if (mb_strlen($save['topic']) < 3) {
                $errors['topic'] = 'Title or topic 3 to 255 characters';
            }
            if ($save['detail'] === '') {
                $errors['detail'] = 'Please fill in';
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            $db = \Kotchasan\DB::create();
            $save['last_update'] = time();
            $save['updated_at'] = date('Y-m-d H:i:s');
            if ($item->id > 0) {
                $db->update('event', ['id', $item->id], $save);
                $save['id'] = $item->id;
            } else {
                $save['member_id'] = $login->id;
                $save['created_at'] = $save['updated_at'];
                $save['id'] = $db->insert('event', $save);
            }

            \Index\Log\Model::add($save['id'], 'event', 'Event', 'Save Event: '.$save['topic'], $login->id);

            return $this->redirectResponse('back', 'Saved successfully');
        } catch (\Kotchasan\ApiException $e) {
            return $this->errorResponse($e->getMessage(), (int) $e->getCode() ?: 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
