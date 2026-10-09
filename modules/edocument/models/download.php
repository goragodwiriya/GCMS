<?php
/**
 * @filesource modules/edocument/models/download.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Edocument\Download;

/**
 * E-Document download action model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get one document by ID with its module config and the
     * member's download record (download_id 0 = not downloaded yet).
     *
     * @param int $id
     * @param int $member_id 0 = guest
     *
     * @return object|null
     */
    public static function get($id, $member_id)
    {
        $search = static::createQuery()
            ->select('D.id', 'D.module_id', 'D.topic', 'D.ext', 'D.file', 'D.size', 'D.downloads', 'D.reciever', 'M.config', 'N.id download_id', 'N.downloads member_downloads')
            ->from('edocument D')
            ->join('modules M', [['M.id', 'D.module_id'], ['M.owner', 'edocument']])
            ->join('edocument_download N', [['N.document_id', 'D.id'], ['N.member_id', (int) $member_id]], 'LEFT')
            ->where(['D.id', (int) $id])
            ->first();

        if (!$search) {
            return null;
        }

        $search->config = \Edocument\Settings\Model::normalizeConfig(json_decode((string) $search->config));
        $search->reciever = \Edocument\Setup\Model::parseReciever($search->reciever);
        $search->download_id = (int) $search->download_id;

        return $search;
    }

    /**
     * Count one download: the per-member record and the document total.
     *
     * @param object $document from get()
     * @param int    $member_id 0 = guest
     *
     * @return int new document total
     */
    public static function record($document, $member_id)
    {
        $db = \Kotchasan\DB::create();
        $now = time();

        if ($document->download_id === 0) {
            // Explicit ID: some legacy tables have no AUTO_INCREMENT
            $db->insert('edocument_download', [
                'id' => $db->nextId('edocument_download'),
                'module_id' => (int) $document->module_id,
                'document_id' => (int) $document->id,
                'member_id' => (int) $member_id,
                'downloads' => 1,
                'last_update' => $now
            ]);
        } else {
            $db->update('edocument_download', ['id', $document->download_id], [
                'downloads' => (int) $document->member_downloads + 1,
                'last_update' => $now
            ]);
        }

        $downloads = (int) $document->downloads + 1;
        $db->update('edocument', ['id', (int) $document->id], ['downloads' => $downloads]);

        return $downloads;
    }
}
