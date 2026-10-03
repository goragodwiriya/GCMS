<?php
/**
 * @filesource modules/document/models/document.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Write;

use Kotchasan\Language;

/**
 * Document (Article) Model — admin form data
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get article data for the admin form
     * Returns new article defaults when id = 0
     *
     * @param int $id
     * @param int $module_id
     *
     * @return object|null
     */
    public static function get($id, $module_id)
    {
        if (empty($id)) {
            $index = (object) [
                'id' => 0,
                'module_id' => $module_id,
                'tags' => [],
                'published' => 1,
                'show_news' => 1,
                'published_date' => date('Y-m-d'),
                'category_id' => '',
                'languages' => array_keys(Language::installedLanguage()),
                'picture' => [
                    [
                        'url' => WEB_URL.'images/no-image.webp',
                        'name' => 'Choose file'
                    ]
                ]
            ];

            foreach ($index->languages as $lng) {
                $index->topic[$lng] = '';
                $index->description[$lng] = '';
                $index->keywords[$lng] = '';
                $index->detail[$lng] = '';
            }

            return $index;
        }

        $index = static::createQuery()
            ->select()
            ->from('index')
            ->where([
                ['id', $id],
                ['module_id', $module_id],
                ['index', 0]
            ])
            ->first();

        // Not found
        if (!$index) {
            return null;
        }

        $details = static::createQuery()
            ->select()
            ->from('index_detail')
            ->where([
                ['id', $index->id],
                ['module_id', $index->module_id]
            ])
            ->fetchAll();

        $index->languages = array_keys(Language::installedLanguage());

        foreach ($index->languages as $lng) {
            $index->topic[$lng] = '';
            $index->description[$lng] = '';
            $index->keywords[$lng] = '';
            $index->detail[$lng] = '';
        }

        foreach ($details as $detail) {
            $lng = $detail->language ?: $index->languages[0];
            $index->topic[$lng] = $detail->topic;
            $index->description[$lng] = $detail->description;
            $index->keywords[$lng] = $detail->keywords;
            $index->detail[$lng] = str_replace('{WEBURL}', WEB_URL, $detail->detail);
        }

        if (!empty($index->picture) && file_exists(ROOT_PATH.DATA_FOLDER.'document/'.$index->picture)) {
            $index->picture = [
                [
                    'url' => WEB_URL.DATA_FOLDER.'document/'.$index->picture,
                    'name' => $index->picture
                ]
            ];
        } else {
            $index->picture = [
                [
                    'url' => WEB_URL.'images/no-image.webp',
                    'name' => 'Choose file'
                ]
            ];
        }

        $query = static::createQuery()
            ->select('tag')
            ->from('index_tag')
            ->where(['index_id', $index->id]);
        foreach ($query->fetchAll() as $item) {
            $index->tags[] = $item->tag;
        }

        $index->show_news = (int) $index->show_news;

        return $index;
    }
}
