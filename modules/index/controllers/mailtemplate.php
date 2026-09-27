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
 * Handles single mail template item CRUD endpoints
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
            if (!ApiController::isAdmin($login) && !ApiController::hasPermission($login, 'can_config')) {
                return $this->errorResponse('No data available', 404);
            }

            $id = $request->get('id')->toInt();
            if (empty($id)) {
                return $this->errorResponse('No data available', 404);
            }

            $email = \Index\Mailtemplate\Model::get($id);
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
            if (!ApiController::isAdmin($login) && !ApiController::hasPermission($login, 'can_config')) {
                return $this->errorResponse('Permission required', 403);
            }

            $id = $request->post('id')->toInt();
            if (empty($id)) {
                return $this->errorResponse('No data available', 404);
            }

            // Load existing record
            $email = \Index\Mailtemplate\Model::get($id);
            if (!$email) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            // Parse input
            $save = $this->parseInput($request);

            // Validate
            $errors = $this->validateFields($id, $save, $email);
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            $db = \Gcms\Sysadmin\Model::createDB();

            // If language changed, check if a record in the target language already exists
            if ($id > 0 && $save['language'] !== $email->language) {
                $existing = $db->first('emailtemplate', [
                    ['email_id', $email->email_id],
                    ['module', $email->module],
                    ['language', $save['language']]
                ]);
                if ($existing) {
                    return $this->formErrorResponse(['language' => Language::get('This entry is in selected language')], 400);
                }
            }

            // Build update data
            $data = [
                'from_email' => $save['from_email'],
                'copy_to' => implode(',', $save['copy_to']),
                'subject' => $save['subject'],
                'language' => $save['language'],
                'detail' => $save['detail'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($id === 0 || $email->customer_id === 0) {
                // For new record, we need to set email_id and module
                $data['customer_id'] = CUSTOMER_ID;
                $data['email_id'] = $email->email_id;
                $data['name'] = $email->name;
                $data['module'] = $email->module;
                $db->insert('emailtemplate', $data);
            } else {
                $db->update('emailtemplate', ['id', $id], $data);
            }

            // Log
            \Index\Log\Model::add($id, 'index', 'Index', 'Edit Mail template: '.$save['subject'], $login->id);

            return $this->redirectResponse('back', 'Saved successfully', 200, 1000);
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
        return [
            'from_email' => $request->post('from_email')->email(),
            'copy_to' => $request->post('copy_to', [])->email(),
            'subject' => $request->post('subject')->topic(),
            'language' => $request->post('language')->filter('a-z'),
            'detail' => str_replace(WEB_URL, '{WEBURL}', $request->post('detail')->detail()),
        ];
    }

    /**
     * Validate mail template fields
     *
     * @param int    $id   Template ID
     * @param array  $save Parsed input data
     * @param object $email Existing template record
     *
     * @return array Validation errors keyed by field name
     */
    protected function validateFields($id, $save, $email): array
    {
        $errors = [];

        // subject is required
        if (empty($save['subject'])) {
            $errors['subject'] = 'Please fill in';
        }

        // language is required
        if (empty($save['language'])) {
            $errors['language'] = 'Please select';
        }

        // Validate from_email format
        if (!empty($save['from_email']) && !Validator::email($save['from_email'])) {
            $errors['from_email'] = 'Invalid email';
        }

        // Validate each copy_to address
        if (!empty($save['copy_to'])) {
            foreach ($save['copy_to'] as $item) {
                if (!empty($item) && !Validator::email($item)) {
                    $errors['copy_to'] = 'Invalid email';
                    break;
                }
            }
        }

        return $errors;
    }
}
