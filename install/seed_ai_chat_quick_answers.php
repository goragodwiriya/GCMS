<?php
/**
 * Seed default AI chat quick answers during installation only.
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

/**
 * @param Db    $db
 * @param string $prefix
 * @param array  $config
 */
function seedAiChatQuickAnswers($db, $prefix, array $config = [])
{
    $table = preg_replace('/[^a-zA-Z0-9_]+/', '', $prefix).'_ai_chat_quick_answers';
    if (!$db->tableExists($table)) {
        return;
    }

    $existing = $db->search($table, [], 1);
    if (is_array($existing) && !empty($existing)) {
        return;
    }

    if (!class_exists('Gcms\\Chat\\AiChatAdminContent', false)) {
        spl_autoload_register(function ($class) {
            if (strncmp('Gcms\\', $class, 5) !== 0) {
                return;
            }
            $file = ROOT_PATH.str_replace('\\', '/', $class).'.php';
            if (is_file($file)) {
                require $file;
            }
        });
    }

    $company = isset($config['company']) && is_array($config['company']) ? $config['company'] : [];
    $replace = [
        ':site_name' => trim((string) ($config['web_title'] ?? 'เว็บไซต์')),
        ':web_description' => trim((string) ($config['web_description'] ?? '')),
        ':company_name' => trim((string) ($company['name'] ?? '')),
        ':company_phone' => trim((string) ($company['phone'] ?? '')),
        ':company_email' => trim((string) ($company['email'] ?? '')),
        ':company_address' => trim((string) ($company['address'] ?? ''))
    ];

    $timestamp = date('Y-m-d H:i:s');
    foreach (\Gcms\Chat\AiChatAdminContent::quickAnswerSamples() as $sample) {
        $db->insert($table, [
            'title' => (string) ($sample['title'] ?? ''),
            'keywords' => (string) ($sample['keywords'] ?? ''),
            'match_mode' => (string) ($sample['match_mode'] ?? 'contains'),
            'answer_text' => strtr((string) ($sample['answer_text'] ?? ''), $replace),
            'sort_order' => (int) ($sample['sort_order'] ?? 0),
            'published' => !empty($sample['published']) ? 1 : 0,
            'created_at' => $timestamp,
            'updated_at' => $timestamp
        ]);
    }
}
