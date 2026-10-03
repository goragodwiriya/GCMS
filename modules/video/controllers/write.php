<?php
/**
 * @filesource modules/video/controllers/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Video\Write;

use Gcms\Api as ApiController;
use Kotchasan\Curl;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API Video CRUD Controller — ports gcms241021 Video\Admin\Write onto the
 * current API convention (get/save, thumbnail from YouTube).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/video/write/get
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

            $module = \Index\Module\Model::getModuleWithConfig('video', $request->get('module_id')->toInt());
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_write'])) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $item = \Video\Write\Model::get($request->get('id')->toInt(), $module->id);
            if (!$item) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            return $this->successResponse($item, 'Video item retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/video/write/save
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

            $module = \Index\Module\Model::getModuleWithConfig('video', $request->post('module_id')->toInt());
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_write'])) {
                return $this->errorResponse('No data available', 404);
            }

            $id = $request->post('id')->toInt();
            $item = \Video\Write\Model::get($id, $module->id);
            if (!$item) {
                return $this->errorResponse('No data available', 404);
            }

            $save = [
                'module_id' => $module->id,
                'youtube' => $request->post('youtube')->topic(),
                'topic' => $request->post('topic')->topic(),
                'description' => $request->post('description')->textarea()
            ];

            $errors = [];
            if (!preg_match('/^[a-zA-Z0-9_\-]{11}$/', $save['youtube'])) {
                $errors['youtube'] = Language::get('Enter the ID of the video from Youtube 11 characters eg 17IKhjQWT9M (Without a complete URL)');
            } else {
                // ตรวจสอบรายการซ้ำในโมดูลเดียวกัน
                $search = \Kotchasan\Model::createQuery()
                    ->from('video')
                    ->where([
                        ['youtube', $save['youtube']],
                        ['module_id', $module->id]
                    ])
                    ->first();
                if ($search && ($item->id === 0 || $item->id != $search->id)) {
                    $errors['youtube'] = Language::replace('This :name already exist', [':name' => Language::get('Video')]);
                }
            }

            if (empty($errors)) {
                // อ่านข้อมูลวีดีโอจาก YouTube API (ถ้ามี Google API Key)
                $thumbnailUrl = 'https://img.youtube.com/vi/'.$save['youtube'].'/hqdefault.jpg';
                $apiKey = $module->config->google_api_key ?? '';
                if ($apiKey !== '') {
                    $url = 'https://www.googleapis.com/youtube/v3/videos?part=snippet,statistics&id='.$save['youtube'].'&key='.$apiKey;
                    $feed = (new Curl())->referer(WEB_URL)->get($url);
                    $datas = json_decode($feed);
                    if (isset($datas->error)) {
                        $errors['youtube'] = strip_tags($datas->error->message);
                    } elseif (empty($datas->items)) {
                        $errors['youtube'] = Language::get('Video not found');
                    } else {
                        $snippet = $datas->items[0]->snippet;
                        if ($save['topic'] === '') {
                            $save['topic'] = trim($snippet->title);
                        }
                        if ($save['description'] === '') {
                            $save['description'] = trim($snippet->description);
                        }
                        $save['views'] = (int) $datas->items[0]->statistics->viewCount;
                        if (isset($snippet->thumbnails->standard)) {
                            $thumbnailUrl = $snippet->thumbnails->standard->url;
                        } elseif (isset($snippet->thumbnails->high)) {
                            $thumbnailUrl = $snippet->thumbnails->high->url;
                        }
                    }
                }

                if (empty($errors) && $save['topic'] === '') {
                    $errors['topic'] = 'Please fill in';
                }

                if (empty($errors)) {
                    // บันทึกรูป thumbnail ของวีดีโอ
                    $dir = ROOT_PATH.DATA_FOLDER.'video/';
                    if (!File::makeDirectory($dir)) {
                        $errors['youtube'] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'video/');
                    } else {
                        $thumbnail = (new Curl())->referer(WEB_URL)->get($thumbnailUrl);
                        if (!empty($thumbnail)) {
                            $f = @fopen($dir.$save['youtube'].'.jpg', 'wb');
                            if ($f) {
                                fwrite($f, $thumbnail);
                                fclose($f);
                            }
                        }
                    }
                }
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            $db = \Kotchasan\DB::create();
            $save['last_update'] = time();
            if ($item->id > 0) {
                $db->update('video', ['id', $item->id], $save);
                $save['id'] = $item->id;
            } else {
                $save['views'] = $save['views'] ?? 0;
                $save['id'] = $db->insert('video', $save);
            }

            \Index\Log\Model::add($save['id'], 'video', 'Video', 'Save Video: '.$save['topic'], $login->id);

            return $this->redirectResponse('back', 'Saved successfully');
        } catch (\Kotchasan\ApiException $e) {
            return $this->errorResponse($e->getMessage(), (int) $e->getCode() ?: 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
