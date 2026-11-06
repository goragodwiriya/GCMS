<?php
/**
 * @filesource modules/index/controllers/mailtemplate.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\MailTemplate;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Language;
use Kotchasan\Validator;

/**
 * API Admin Mail Template Controller
 *
 * แก้ไขแม่แบบอีเมลของเว็บนี้ (ตาราง emailtemplate) และเพิ่มแม่แบบในภาษาอื่น (copy)
 * แม่แบบถูกเลือกด้วย code + language (Gcms\EmailTemplate) หนึ่ง code มีได้ภาษาละหนึ่งแถว
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/index/mailtemplate/get
     * Get mail template details by ID
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

            // Authorization check
            if (!ApiController::hasPermission($login, ['can_config'])) {
                return $this->errorResponse('No data available', 404);
            }

            $email = \Index\Mailtemplate\Model::get($request->get('id')->toInt());
            if (!$email) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            // Return template details with language options
            return $this->successResponse([
                'data' => $email,
                'options' => [
                    'language' => \Gcms\Controller::arrayToOptions(Language::installedLanguage())
                ]
            ], 'Mail template details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/index/mailtemplate/save
     * Save mail template details
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

            // Authorization check
            if (!ApiController::canModify($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            // Load existing record
            $id = $request->post('id')->toInt();
            $email = \Index\Mailtemplate\Model::get($id);
            if (!$email) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            // Parse input
            $save = $this->parseInput($request);

            // Validate
            $errors = $this->validateFields($save);
            if (empty($errors['language']) && \Index\Mailtemplate\Model::exists($email, $save['language'], $id)) {
                // ย้ายไปภาษาที่มีแม่แบบ code นี้อยู่แล้ว = สองแถวชนกัน
                $errors['language'] = 'This entry is in selected language';
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            \Kotchasan\DB::create()->update('emailtemplate', [['id', $id]], [
                'name' => $save['name'],
                'from_email' => $save['from_email'],
                'copy_to' => implode(',', $save['copy_to']),
                'subject' => $save['subject'],
                'language' => $save['language'],
                'detail' => $save['detail'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Log
            \Index\Log\Model::add($id, 'index', 'Index', 'Edit Mail template: '.$email->code.' ('.$save['language'].')', $login->id);

            return $this->redirectResponse('back', 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/index/mailtemplate/copy
     * เพิ่มแม่แบบนี้ในภาษาอื่น (สำเนาจากภาษาปัจจุบัน แล้วไปแก้ไขฉบับใหม่)
     *
     * @param Request $request
     *
     * @return Response
     */
    public function copy(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            if (!ApiController::canModify($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $id = $request->post('id')->toInt();
            $language = $request->post('language')->filter('a-z');
            if (!isset(Language::installedLanguage()[$language])) {
                return $this->errorResponse('Please select a language', 400);
            }

            $db = \Kotchasan\DB::create();
            $email = $db->first('emailtemplate', [['id', $id]]);
            if (!$email) {
                return $this->errorResponse('No data available', 404);
            }
            if (\Index\Mailtemplate\Model::exists($email, $language)) {
                return $this->errorResponse('This entry is in selected language', 400);
            }

            // สำเนาทั้งแถว (code, module, email_id, ผู้ส่ง, สำเนาถึง, เนื้อหา) เปลี่ยนแค่ภาษา
            $copy = (array) $email;
            unset($copy['id']);
            $copy['language'] = $language;
            $copy['updated_at'] = date('Y-m-d H:i:s');
            $newId = $db->insert('emailtemplate', $copy);

            \Index\Log\Model::add($newId, 'index', 'Index', 'Copy Mail template: '.$email->code.' to language: '.$language, $login->id);

            return $this->redirectResponse('/mailtemplate?id='.$newId, 'Copied successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Parse mail template input from request
     *
     * @param Request $request
     *
     * @return array
     */
    protected function parseInput(Request $request): array
    {
        // copy_to มาจากช่อง tags เป็น copy_to[] (เรียก API ตรงอาจส่งมาเป็นข้อความคั่นด้วย ,)
        $copy_to = $request->post('copy_to', [])->email();
        if (!is_array($copy_to)) {
            $copy_to = explode(',', (string) $copy_to);
        }

        return [
            'name' => $request->post('name')->topic(),
            'from_email' => $request->post('from_email')->email(),
            'copy_to' => array_values(array_filter(array_map('trim', $copy_to))),
            'subject' => $request->post('subject')->topic(),
            'language' => $request->post('language')->filter('a-z'),
            'detail' => str_replace(WEB_URL, '{WEBURL}', $request->post('detail')->detail())
        ];
    }

    /**
     * Validate mail template fields
     *
     * @param array $save Parsed input data
     *
     * @return array Validation errors keyed by field name
     */
    protected function validateFields($save): array
    {
        $errors = [];

        // name, subject and detail are required
        foreach (['name', 'subject', 'detail'] as $key) {
            if ($save[$key] === '') {
                $errors[$key] = 'Please fill in';
            }
        }

        // language is required and must be an installed language
        if (!isset(Language::installedLanguage()[$save['language']])) {
            $errors['language'] = 'Please select';
        }

        // Validate from_email format
        if ($save['from_email'] !== '' && !Validator::email($save['from_email'])) {
            $errors['from_email'] = 'Invalid email';
        }

        // Validate each copy_to address
        foreach ($save['copy_to'] as $item) {
            if (!Validator::email($item)) {
                $errors['copy_to'] = 'Invalid email';
                break;
            }
        }

        return $errors;
    }
}
