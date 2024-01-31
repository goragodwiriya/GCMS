<?php
/**
 * @filesource modules/board/views/jsonld.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Jsonld;

/**
 * generate JSON-LD
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Kotchasan\KBase
{
    /**
     * สร้างโค้ดสำหรับ JSON-LD
     *
     * @param object $index
     *
     * @return array
     */
    public static function generate($index)
    {
        $suggestedAnswer = array();
        $answerCount = 0;
        if (!empty($index->comment_items)) {
            foreach ($index->comment_items as $i => $item) {
                $detail = trim(strip_tags(str_replace(array('{', '}'), array('&#x007B;', '&#x007D;'), $item->detail)));
                if ($detail != '') {
                    $answerCount++;
                    $suggestedAnswer[] = array(
                        '@type' => 'Answer',
                        'text' => $detail,
                        'dateCreated' => date(DATE_ISO8601, $item->last_update),
                        'author' => array(
                            '@type' => 'Person',
                            'name' => $item->displayname
                        ),
                        'upvoteCount' => 0,
                        'url' => $index->canonical.'#R_'.$item->id
                    );
                }
            }
        }
        // คืนค่าข้อมูล JSON-LD
        return array(
            '@context' => 'http://schema.org',
            '@type' => 'QAPage',
            'mainEntity' => array(
                '@type' => 'Question',
                'name' => $index->topic,
                'text' => trim($index->detail) == '' ? $index->topic : $index->detail,
                'answerCount' => $answerCount,
                'upvoteCount' => $index->visited,
                'dateCreated' => date(DATE_ISO8601, $index->create_date),
                'author' => array(
                    '@type' => 'Person',
                    'name' => $index->name
                ),
                'url' => $index->canonical,
                'suggestedAnswer' => $suggestedAnswer
            )
        );
    }
}
