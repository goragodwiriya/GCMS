<?php
/**
 * @filesource modules/index/controllers/aichatquickanswer.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Aichatquickanswer;

use Gcms\Api as ApiController;
use Gcms\Chat\QuickAnswerRepository;
use Kotchasan\Http\Request;

/**
 * AI chat quick-answer form actions.
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function get(Request $request)
    {
        ApiController::validateMethod($request, 'GET');

        $login = $this->authenticateRequest($request);
        if (!$login) {
            return $this->errorResponse('Unauthorized', 401);
        }
        if (!ApiController::canModify($login, ['can_use_ai_chat'])) {
            return $this->errorResponse('Forbidden', 403);
        }

        $row = [
            'id' => 0,
            'title' => '',
            'keywords' => '',
            'match_mode' => 'contains',
            'answer_text' => '',
            'sort_order' => $this->nextSortOrder((new QuickAnswerRepository())->all(false)),
            'published' => 1,
            'table_id' => 'aiChatQuickAnswers'
        ];

        return $this->successResponse([
            'data' => (object) $row,
            'actions' => [
                [
                    'type' => 'modal',
                    'action' => 'show',
                    'template' => 'ai-chat/quick-answer.html',
                    'title' => 'Add Quick Answer',
                    'titleClass' => 'icon-new'
                ]
            ]
        ], 'AI chat quick answer loaded');
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function save(Request $request)
    {
        ApiController::validateMethod($request, 'POST');
        $this->validateCsrfToken($request);

        $login = $this->authenticateRequest($request);
        if (!$login) {
            return $this->errorResponse('Unauthorized', 401);
        }
        if (!ApiController::canModify($login, ['can_use_ai_chat'])) {
            return $this->errorResponse('Forbidden', 403);
        }

        $repository = new QuickAnswerRepository();
        $items = $repository->all(false);
        $id = $request->post('id')->toInt();
        $title = trim((string) $request->post('title')->topic());
        $keywords = trim(str_replace(["\r\n", "\r"], "\n", $request->post('keywords')->textarea()));
        $answerText = trim(str_replace(["\r\n", "\r"], "\n", $request->post('answer_text')->textarea()));
        $matchMode = $request->post('match_mode')->filter('a-z_') === 'exact' ? 'exact' : 'contains';
        $sortOrder = $request->post('sort_order')->toInt();
        $published = $request->post('published')->toInt() === 1 ? 1 : 0;

        $errors = [];
        if ($keywords === '') {
            $errors['keywords'] = 'Keywords are required';
        }
        if ($answerText === '') {
            $errors['answer_text'] = 'Answer is required';
        }
        if (!empty($errors)) {
            return $this->formErrorResponse($errors, 422);
        }

        $row = [
            'title' => $title,
            'keywords' => $keywords,
            'match_mode' => $matchMode,
            'answer_text' => $answerText,
            'sort_order' => $sortOrder > 0 ? $sortOrder : $this->nextSortOrder($items),
            'published' => $published
        ];

        $index = $this->findItemIndex($items, $id);
        if ($index >= 0) {
            $items[$index] = array_merge($items[$index], $row);
        } else {
            $items[] = $row;
        }

        if (!$repository->saveMany($items)) {
            return $this->errorResponse('Unable to save quick answer', 500);
        }

        return $this->successResponse([
            'actions' => [
                [
                    'type' => 'notification',
                    'level' => 'success',
                    'message' => 'Saved successfully'
                ],
                [
                    'type' => 'redirect',
                    'url' => 'reload',
                    'target' => 'table',
                    'tableId' => 'aiChatQuickAnswers'
                ],
                [
                    'type' => 'modal',
                    'action' => 'close'
                ]
            ]
        ], 'Saved successfully');
    }

    /**
     * @param array $items
     * @param int   $id
     *
     * @return int
     */
    private function findItemIndex(array $items, int $id): int
    {
        foreach ($items as $index => $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
                return $index;
            }
        }

        return -1;
    }

    /**
     * @param array $items
     *
     * @return int
     */
    private function nextSortOrder(array $items): int
    {
        $max = 0;
        foreach ($items as $item) {
            $max = max($max, (int) ($item['sort_order'] ?? 0));
        }

        return $max + 1;
    }
}