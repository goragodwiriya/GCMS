<?php
/**
 * @filesource modules/index/controllers/database.php
 *
 * Database Import/Export API controller.
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Database;

use Gcms\Api as ApiController;
use Gcms\Database\ImportExportService;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;

class Controller extends ApiController
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function tables(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $login = $this->requireConfigPermission($request);
            if (!$login) {
                return $this->errorResponse('Permission denied', 403);
            }

            $service = new ImportExportService();
            $tables = $service->detectTables();

            return $this->successResponse([
                'prefix' => $service->getPrefix(),
                'table_prefix' => $service->getTablePrefixWithUnderscore(),
                'tables' => $tables
            ], 'Database tables loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function export(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);
            $login = $this->requireConfigPermission($request);
            if (!$login) {
                return $this->errorResponse('Permission denied', 403);
            }

            $service = new ImportExportService();
            $scope = $request->post('scope')->filter('a-z');
            $batchSize = max(100, min(5000, $request->post('batch_size', 500)->toInt()));

            $selected = $this->decodeTables($request->post('tables')->toString());
            $tables = $scope === 'selected' ? $service->sanitizeSelectedTables($selected) : array_map(static function ($item) {
                return $item['name'];
            }, $service->detectTables());

            $tableModes = $this->decodeTableModes($request->post('table_modes')->toString());
            $tableModes = $service->sanitizeTableModes($tableModes, $tables);

            if (empty($tableModes)) {
                return $this->errorResponse('No table selected for export', 422);
            }

            $tmpFile = tempnam(sys_get_temp_dir(), 'gcms-db-');
            if ($tmpFile === false) {
                return $this->errorResponse('Unable to create temporary backup file', 500);
            }
            $sqlFile = $tmpFile.'.sql';
            if (!@rename($tmpFile, $sqlFile)) {
                $sqlFile = $tmpFile;
            }

            $timestamp = date('Y-m-d-His');
            $filename = 'database-backup-'.$timestamp.'.sql';

            $service->exportSqlByTableModes($sqlFile, $tableModes, $batchSize);
            $content = file_get_contents($sqlFile);
            @unlink($sqlFile);

            if ($content === false) {
                return $this->errorResponse('Unable to read generated backup file', 500);
            }

            \Index\Log\Model::add(0, 'index', 'Backup', 'Export DB backup (per-table mode)', $login->id);

            return (new Response())
                ->setNoCacheHeaders()
                ->download($content, $filename, 'application/sql');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function import(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);
            $login = $this->requireConfigPermission($request);
            if (!$login) {
                return $this->errorResponse('Permission denied', 403);
            }

            $service = new ImportExportService();
            $format = $this->normalizeFormat($request->post('format')->filter('a-z'));
            $scope = $request->post('scope')->filter('a-z');
            $overwrite = $request->post('overwrite')->toBoolean();
            $confirmOverwrite = $request->post('confirm_overwrite')->toBoolean();

            if ($overwrite && !$confirmOverwrite) {
                return $this->errorResponse('Please confirm overwrite operation before importing', 422);
            }

            $selected = $this->decodeTables($request->post('tables')->toString());
            $tables = $scope === 'selected' ? $service->sanitizeSelectedTables($selected) : array_map(static function ($item) {
                return $item['name'];
            }, $service->detectTables());

            $tableModes = $this->decodeTableModes($request->post('table_modes')->toString());
            $tableModes = $service->sanitizeTableModes($tableModes, $tables);

            if (empty($tableModes)) {
                return $this->errorResponse('Please select structure or data for at least one table', 422);
            }

            $uploadedFiles = $request->getUploadedFiles();
            $file = $uploadedFiles['backup_file'] ?? null;
            if (!is_object($file) || !method_exists($file, 'hasUploadFile') || !$file->hasUploadFile()) {
                return $this->errorResponse('Backup file is required', 422);
            }

            $filePath = $file->getTempFileName();
            if (!is_string($filePath) || $filePath === '' || !is_file($filePath)) {
                return $this->errorResponse('Uploaded file is invalid', 422);
            }

            if ($format === 'sql') {
                $result = $service->importSql($filePath, $tableModes, $tables, $overwrite);
            } else {
                $result = $service->importJson($filePath, $tableModes, $tables, $overwrite);
            }

            \Index\Log\Model::add(0, 'index', 'Backup', 'Import DB backup ('.$format.')', $login->id);

            return $this->successResponse(['stats' => $result['stats'] ?? []], 'Import completed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    private function requireConfigPermission(Request $request)
    {
        $login = $this->authenticateRequest($request);
        if (!$login) {
            return null;
        }
        if (!ApiController::canModify($login, ['can_config'])) {
            return null;
        }

        return $login;
    }

    /**
     * @param string $format
     * @return mixed
     */
    private function normalizeFormat(string $format): string
    {
        if (!in_array($format, ['sql', 'json'], true)) {
            return 'sql';
        }

        return $format;
    }

    /**
     * @param string $raw
     * @return mixed
     */
    private function decodeTables(string $raw): array
    {
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $decoded), static function ($table) {
            return $table !== '';
        }));
    }

    /**
     * @param string $raw
     * @return mixed
     */
    private function decodeTableModes(string $raw): array
    {
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $result = [];
        foreach ($decoded as $table => $mode) {
            if (!is_array($mode)) {
                continue;
            }
            $tableName = trim((string) $table);
            if ($tableName === '') {
                continue;
            }
            $result[$tableName] = [
                'structure' => !empty($mode['structure']),
                'data' => !empty($mode['data'])
            ];
        }

        return $result;
    }
}
