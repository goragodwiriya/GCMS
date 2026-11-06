<?php
/**
 * @filesource modules/index/controllers/database.php
 *
 * api/index/database: backup and restore of this site's tables, for the owner of the site.
 *
 * ⚠️ This installation runs one site. An import writes any row past every check of the application, so
 * it is only for someone who may already change anything here. Do not copy it into an installation
 * that serves customers: see Gcms\Database\BackupImportApi.
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Database;

use Gcms\Api as ApiController;
use Gcms\Database\BackupExportApi;
use Gcms\Database\BackupImportApi;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    use BackupExportApi, BackupImportApi;

    /**
     * Only the owner: an admin with the can_config permission could otherwise make themselves the owner
     * with one row, and a backup holds the password hashes of everyone on the site.
     *
     * @param Request $request
     * @return int|\Kotchasan\Http\Response
     */
    protected function backupActor(Request $request)
    {
        $login = $this->authenticateRequest($request);

        return ApiController::isSuperAdmin($login) ? (int) $login->id : $this->errorResponse('Permission denied', 403);
    }

    /**
     * @param string $topic
     * @param int $actor
     */
    protected function logBackup(string $topic, int $actor): void
    {
        \Index\Log\Model::add(0, 'index', 'Backup', $topic, $actor);
    }
}
