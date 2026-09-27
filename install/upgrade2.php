<?php
if (defined('ROOT_PATH')) {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        include ROOT_PATH.'install/upgrade1.php';
    } else {
        $error = false;
        // Database Class
        include ROOT_PATH.'install/db.php';
        // ค่าติดตั้งฐานข้อมูล
        $db_config = include ROOT_PATH.'settings/database.php';
        try {
            $db_config = $db_config['mysql'];
            // เขื่อมต่อฐานข้อมูล
            $db = new Db($db_config);
        } catch (\Exception $exc) {
            $error = true;
            echo '<h2>ความผิดพลาดในการเชื่อมต่อกับฐานข้อมูล</h2>';
            echo '<p class=warning>ไม่สามารถเชื่อมต่อกับฐานข้อมูลของคุณได้ในขณะนี้</p>';
            echo '<p>อาจเป็นไปได้ว่า</p>';
            echo '<ol>';
            echo '<li>เซิร์ฟเวอร์ของฐานข้อมูลของคุณไม่สามารถใช้งานได้ในขณะนี้</li>';
            echo '<li>ค่ากำหนดของฐานข้อมูลไม่ถูกต้อง (ตรวจสอบไฟล์ settings/database.php)</li>';
            echo '<li>ไม่พบฐานข้อมูลที่ต้องการติดตั้ง กรุณาสร้างฐานข้อมูลก่อน หรือใช้ฐานข้อมูลที่มีอยู่แล้ว</li>';
            echo '<li class="incorrect">'.$exc->getMessage().'</li>';
            echo '</ol>';
            echo '<p>หากคุณไม่สามารถดำเนินการแก้ไขข้อผิดพลาดด้วยตัวของคุณเองได้ ให้ติดต่อผู้ดูแลระบบเพื่อขอข้อมูลที่ถูกต้อง หรือ ลองติดตั้งใหม่</p>';
            echo '<p class="submit"><a href="index.php?step=1" class="btn large btn-secondary">กลับไปลองใหม่</a></p>';
        }
        if (!$error) {
            // เชื่อมต่อฐานข้อมูลสำเร็จ
            $content = ['<li class="correct">เชื่อมต่อฐานข้อมูลสำเร็จ</li>'];
            try {
                if (!isset($new_config) || !is_array($new_config)) {
                    throw new \Exception('ไม่พบค่ากำหนดเวอร์ชั่นใหม่สำหรับการปรับรุ่น');
                }

                // =========================================================
                // user
                // =========================================================
                $table_user = $db_config['prefix'].'_user';
                if (empty($config['password_key'])) {
                    // อัปเดตข้อมูลผู้ดูแลระบบ
                    $config['password_key'] = uniqid();
                }
                // ตรวจสอบการ login
                updateAdmin($db, $table_user, $_POST['username'], $_POST['password'], $config['password_key']);

                upgradeLegacyUserTable($db, $db_config['prefix'], $content);
                upgradeLegacyCategoryTable($db, $db_config['prefix'], $content);
                prepareLegacyLogsTable($db, $db_config['prefix'], $content);
                migrateLegacyTimestampColumns($db, ROOT_PATH.'install/old_database.sql', ROOT_PATH.'install/database.sql', $db_config['prefix'], $content);

                syncSchemaFromSql($db, ROOT_PATH.'install/database.sql', $db_config['prefix'], $content);
                migrateLegacyActivitiesToLogs($db, $db_config['prefix'], $content);
                dropLegacyColumnsFromSchemaDiff($db, ROOT_PATH.'install/old_database.sql', ROOT_PATH.'install/database.sql', $db_config['prefix'], $content);

                // บันทึก settings/config.php
                $config['version'] = $new_config['version'];
                $config['reversion'] = time();
                if (function_exists('imagewebp')) {
                    $config['stored_img_type'] = isset($config['stored_img_type']) ? $config['stored_img_type'] : '.jpg';
                } else {
                    $config['stored_img_type'] = '.jpg';
                }
                if (isset($new_config['default_icon'])) {
                    $config['default_icon'] = $new_config['default_icon'];
                }
                // กำหนดค่า API หากยังไม่มี
                include_once ROOT_PATH.'Kotchasan/Password.php';
                if (empty($config['api_tokens']['internal']) || empty($config['api_tokens']['external'])) {
                    $config['api_tokens'] = [
                        'internal' => \Kotchasan\Password::uniqid(40),
                        'external' => \Kotchasan\Password::uniqid(40)
                    ];
                }
                if (empty($config['api_secret'])) {
                    $config['api_secret'] = \Kotchasan\Password::uniqid();
                }
                if (empty($config['jwt_secret'])) {
                    $config['jwt_secret'] = \Kotchasan\Password::uniqid(64);
                }
                if (!isset($config['api_ips'])) {
                    $config['api_ips'] = ['0.0.0.0'];
                }
                if (!isset($config['api_cors'])) {
                    $config['api_cors'] = '*';
                }
                $f = save($config, ROOT_PATH.'settings/config.php');
                $content[] = '<li class="'.($f ? 'correct' : 'incorrect').'">บันทึก <b>config.php</b> ...</li>';
                // นำเข้าภาษา
                include ROOT_PATH.'install/language.php';
            } catch (\PDOException $exc) {
                $content[] = '<li class="incorrect">'.$exc->getMessage().'</li>';
                $error = true;
            } catch (\Exception $exc) {
                $content[] = '<li class="incorrect">'.$exc->getMessage().'</li>';
                $error = true;
            }
            if (!$error) {
                echo '<h2>ปรับรุ่นเรียบร้อย</h2>';
                echo '<p>การปรับรุ่นได้ดำเนินการเสร็จเรียบร้อยแล้ว หากคุณต้องการความช่วยเหลือในการใช้งาน คุณสามารถ ติดต่อสอบถามได้ที่ <a href="https://www.kotchasan.com" target="_blank">https://www.kotchasan.com</a></p>';
                echo '<ul>'.implode('', $content).'</ul>';
                echo '<p class=warning>กรุณาลบไดเร็คทอรี่ <em>install/</em> ออกจาก Server ของคุณ</p>';
                echo '<p>คุณควรปรับ chmod ให้ไดเร็คทอรี่ <em>datas/</em> และ <em>settings/</em> (และไดเร็คทอรี่อื่นๆที่คุณได้ปรับ chmod ไว้ก่อนการปรับรุ่น) ให้เป็น 644 ก่อนดำเนินการต่อ (ถ้าคุณได้ทำการปรับ chmod ไว้ด้วยตัวเอง)</p>';
                echo '<p class="submit"><a href="../" class="btn btn-primary large">เข้าระบบ</a></p>';
            } else {
                echo '<h2>ปรับรุ่นไม่สำเร็จ</h2>';
                echo '<p>การปรับรุ่นยังไม่สมบูรณ์ ลองตรวจสอบข้อผิดพลาดที่เกิดขึ้นและแก้ไขดู หากคุณต้องการความช่วยเหลือการติดตั้ง คุณสามารถ ติดต่อสอบถามได้ที่ <a href="https://www.kotchasan.com" target="_blank">https://www.kotchasan.com</a></p>';
                echo '<ul>'.implode('', $content).'</ul>';
                echo '<p class="submit"><a href="." class="btn btn-primary large">ลองใหม่</a></p>';
            }
        }
    }
}

/**
 * Prepare legacy user rows and indexes before syncing the target schema.
 * This keeps old values usable when token/social/timestamp columns change type.
 *
 * @param Db $db
 * @param string $prefix
 * @param array $content
 */
function upgradeLegacyUserTable($db, $prefix, &$content)
{
    $table_user = $prefix.'_user';
    if (!$db->tableExists($table_user)) {
        return;
    }

    foreach (['username', 'token', 'id_card', 'phone', 'activatecode', 'line_uid', 'telegram_id', 'status'] as $index_name) {
        if ($db->indexExists($table_user, $index_name)) {
            $db->query("ALTER TABLE `$table_user` DROP INDEX `$index_name`");
        }
    }

    if ($db->fieldExists($table_user, 'phone2') && !$db->fieldExists($table_user, 'phone1')) {
        $db->query("ALTER TABLE `$table_user` ADD `phone1` VARCHAR(20) NULL DEFAULT NULL");
        $db->query("UPDATE `$table_user` SET `phone1` = `phone2` WHERE (`phone1` IS NULL OR `phone1` = '') AND `phone2` IS NOT NULL AND `phone2` != ''");
        $content[] = '<li class="correct">user: ย้ายข้อมูล phone2 -> phone1</li>';
    } elseif ($db->fieldExists($table_user, 'phone2') && $db->fieldExists($table_user, 'phone1')) {
        $db->query("UPDATE `$table_user` SET `phone1` = `phone2` WHERE (`phone1` IS NULL OR `phone1` = '') AND `phone2` IS NOT NULL AND `phone2` != ''");
    }

    if ($db->fieldExists($table_user, 'create_date') && !$db->fieldExists($table_user, 'created_at')) {
        $db->query("ALTER TABLE `$table_user` ADD `created_at` DATETIME NULL DEFAULT NULL");
        $db->query("UPDATE `$table_user` SET `created_at` = CASE WHEN `create_date` IS NULL OR `create_date` = 0 THEN NOW() ELSE FROM_UNIXTIME(`create_date`) END");
        $content[] = '<li class="correct">user: ย้ายข้อมูล create_date -> created_at</li>';
    } elseif ($db->fieldExists($table_user, 'create_date') && $db->fieldExists($table_user, 'created_at')) {
        $db->query("UPDATE `$table_user` SET `created_at` = CASE WHEN `create_date` IS NULL OR `create_date` = 0 THEN NOW() ELSE FROM_UNIXTIME(`create_date`) END WHERE `created_at` IS NULL");
    }

    if ($db->fieldExists($table_user, 'social')) {
        if ($db->isColumnType($table_user, 'social', 'tinyint')) {
            $db->query("ALTER TABLE `$table_user` CHANGE `social` `social` VARCHAR(32) NULL DEFAULT NULL");
        }
        $db->query("UPDATE `$table_user` SET `social` = 'user' WHERE `social` IS NULL OR `social` = '' OR `social` = '0' OR `social` = 0");
        $db->query("UPDATE `$table_user` SET `social` = 'facebook' WHERE `social` = '1' OR `social` = 1");
        $db->query("UPDATE `$table_user` SET `social` = 'google' WHERE `social` = '2' OR `social` = 2");
        $db->query("UPDATE `$table_user` SET `social` = 'line' WHERE `social` = '3' OR `social` = 3");
        $db->query("UPDATE `$table_user` SET `social` = 'telegram' WHERE `social` = '4' OR `social` = 4");
    }

    foreach (['name' => "''", 'sex' => "''"] as $column_name => $default_value) {
        if ($db->fieldExists($table_user, $column_name)) {
            $db->query("UPDATE `$table_user` SET `$column_name` = $default_value WHERE `$column_name` IS NULL");
        }
    }
    if ($db->fieldExists($table_user, 'country')) {
        $db->query("UPDATE `$table_user` SET `country` = 'TH' WHERE `country` IS NULL OR `country` = ''");
    }
    foreach (['phone', 'id_card'] as $column_name) {
        if ($db->fieldExists($table_user, $column_name)) {
            $db->query("UPDATE `$table_user` SET `$column_name` = NULL WHERE `$column_name` = ''");
        }
    }

    $content[] = '<li class="correct">user อัปเกรดพร้อมสำหรับ database.sql</li>';
}

/**
 * Add legacy defaults required by the current category schema.
 * Old installations did not have type/language columns.
 *
 * @param Db $db
 * @param string $prefix
 * @param array $content
 */
function upgradeLegacyCategoryTable($db, $prefix, &$content)
{
    $table_category = $prefix.'_category';
    if (!$db->tableExists($table_category)) {
        return;
    }

    if (!$db->fieldExists($table_category, 'type')) {
        $db->query("ALTER TABLE `$table_category` ADD `type` VARCHAR(20) NOT NULL DEFAULT 'category'");
        $content[] = '<li class="correct">category: เพิ่ม type เริ่มต้น</li>';
    }
    if (!$db->fieldExists($table_category, 'language')) {
        $db->query("ALTER TABLE `$table_category` ADD `language` VARCHAR(2) NOT NULL DEFAULT ''");
        $content[] = '<li class="correct">category: เพิ่ม language เริ่มต้น</li>';
    }

    $db->query("UPDATE `$table_category` SET `type` = 'category' WHERE `type` IS NULL OR `type` = ''");
    $db->query("UPDATE `$table_category` SET `type` = 'car_accessory' WHERE `type` = 'car_accessories'");
    $db->query("UPDATE `$table_category` SET `language` = '' WHERE `language` IS NULL");
    if ($db->fieldExists($table_category, 'category_id')) {
        $db->query("UPDATE `$table_category` SET `category_id` = 0 WHERE `category_id` IS NULL");
    }

    $content[] = '<li class="correct">category อัปเกรดพร้อมสำหรับ database.sql</li>';
}

/**
 * Rename legacy access logs away from the target logs table name.
 * Old installs stored access logs in _logs, while the new schema uses that name for activity logs.
 *
 * @param Db $db
 * @param string $prefix
 * @param array $content
 */
function prepareLegacyLogsTable($db, $prefix, &$content)
{
    $table_logs = $prefix.'_logs';
    if (!$db->tableExists($table_logs) || $db->fieldExists($table_logs, 'source')) {
        return;
    }
    if (!$db->fieldExists($table_logs, 'time') || !$db->fieldExists($table_logs, 'session_id')) {
        return;
    }

    $backup_table = findAvailableLegacyTableName($db, $prefix.'_logs_access_legacy');
    $db->query("RENAME TABLE `$table_logs` TO `$backup_table`");
    $content[] = '<li class="correct">logs: เก็บ access log เดิมไว้ที่ '.$backup_table.'</li>';
}

/**
 * @param Db $db
 * @param string $base_name
 *
 * @return string
 */
function findAvailableLegacyTableName($db, $base_name)
{
    $table_name = $base_name;
    $suffix = 1;
    while ($db->tableExists($table_name)) {
        $table_name = $base_name.'_'.$suffix;
        ++$suffix;
    }

    return $table_name;
}

/**
 * Migrate known legacy timestamp columns before syncing the target schema.
 * This preserves values when old installs stored timestamps as UNIX integers.
 *
 * @param Db $db
 * @param string $legacy_schema_file
 * @param string $schema_file
 * @param string $prefix
 * @param array $content
 */
function migrateLegacyTimestampColumns($db, $legacy_schema_file, $schema_file, $prefix, &$content)
{
    $legacy_schema = loadSchemaFromSql($legacy_schema_file, $prefix);
    $schema = loadSchemaFromSql($schema_file, $prefix);

    foreach ($schema['tables'] as $table_name => $table) {
        if (!$db->tableExists($table_name) || empty($legacy_schema['tables'][$table_name]['columns'])) {
            continue;
        }

        foreach (['create_date' => 'created_at', 'last_update' => 'updated_at'] as $legacy_column => $target_column) {
            if (isset($legacy_schema['tables'][$table_name]['columns'][$legacy_column]) && isset($table['columns'][$target_column])) {
                migrateLegacyColumnToSchema($db, $table_name, $legacy_column, $target_column, $table['columns'][$target_column], $content);
            }
        }

        if (isset($legacy_schema['tables'][$table_name]['columns']['comment_date']) && isset($table['columns']['comment_date'])) {
            $legacy_definition = $legacy_schema['tables'][$table_name]['columns']['comment_date'];
            $target_definition = $table['columns']['comment_date'];
            if (parseColumnDefinition($legacy_definition)['type'] !== parseColumnDefinition($target_definition)['type']) {
                migrateLegacyColumnToSchema($db, $table_name, 'comment_date', 'comment_date', $target_definition, $content);
            }
        }
    }
}

/**
 * Copy a legacy column into its target schema definition, optionally renaming it.
 *
 * @param Db $db
 * @param string $table_name
 * @param string $source_column
 * @param string $target_column
 * @param string $target_definition
 * @param array $content
 */
function migrateLegacyColumnToSchema($db, $table_name, $source_column, $target_column, $target_definition, &$content)
{
    $columns = getTableColumns($db, $table_name);
    if (!isset($columns[$source_column])) {
        return;
    }

    $target_exists = isset($columns[$target_column]);
    if ($source_column === $target_column && !$target_exists) {
        return;
    }
    if ($source_column === $target_column && !columnNeedsSync($columns[$source_column], $target_definition)) {
        return;
    }

    $copy_expression = buildLegacyColumnCopyExpression($source_column, $columns[$source_column], $target_definition);
    if ($source_column === $target_column) {
        $temp_column = '__upgrade2_'.$target_column;
        if (!$db->fieldExists($table_name, $temp_column)) {
            $db->query("ALTER TABLE `$table_name` ADD `$temp_column` $target_definition");
        }
        $db->query("UPDATE `$table_name` SET `$temp_column` = $copy_expression");
        $db->query("ALTER TABLE `$table_name` DROP COLUMN `$source_column`");
        $db->query("ALTER TABLE `$table_name` CHANGE `$temp_column` `$target_column` $target_definition");
    } else {
        if (!$target_exists) {
            $db->query("ALTER TABLE `$table_name` ADD `$target_column` $target_definition");
        }
        $target_type = parseColumnDefinition($target_definition)['type'];
        $empty_target = isCharacterColumnType($target_type) ? "(`$target_column` IS NULL OR `$target_column` = '')" : "`$target_column` IS NULL";
        $db->query("UPDATE `$table_name` SET `$target_column` = $copy_expression WHERE $empty_target");
        $db->query("ALTER TABLE `$table_name` DROP COLUMN `$source_column`");
    }

    $content[] = '<li class="correct">'.$table_name.': ย้ายข้อมูล '.$source_column.' -> '.$target_column.'</li>';
}

/**
 * Build a SQL expression for copying a legacy column into the target type.
 *
 * @param string $source_column
 * @param array $source_definition
 * @param string $target_definition
 *
 * @return string
 */
function buildLegacyColumnCopyExpression($source_column, $source_definition, $target_definition)
{
    $target = parseColumnDefinition($target_definition);
    $source_type = normalizeSqlFragment($source_definition['Type']);

    if (preg_match('/^(datetime|timestamp)\b/i', $target['type'])) {
        $fallback = $target['nullable'] ? 'NULL' : 'CURRENT_TIMESTAMP';
        if (isNumericColumnType($source_type)) {
            return "CASE WHEN `$source_column` IS NULL OR `$source_column` = 0 THEN $fallback ELSE FROM_UNIXTIME(`$source_column`) END";
        }

        return "CASE WHEN `$source_column` IS NULL OR `$source_column` = '' THEN $fallback ELSE `$source_column` END";
    }
    if (preg_match('/^date\b/i', $target['type'])) {
        $fallback = $target['nullable'] ? 'NULL' : 'CURRENT_DATE';
        if (isNumericColumnType($source_type)) {
            return "CASE WHEN `$source_column` IS NULL OR `$source_column` = 0 THEN $fallback ELSE DATE(FROM_UNIXTIME(`$source_column`)) END";
        }

        return "CASE WHEN `$source_column` IS NULL OR `$source_column` = '' THEN $fallback ELSE DATE(`$source_column`) END";
    }

    return "`$source_column`";
}

/**
 * @param string $column_type
 *
 * @return bool
 */
function isNumericColumnType($column_type)
{
    return preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|float|double)\b/i', $column_type) === 1;
}

/**
 * Remove columns that existed only in old_database.sql after the new schema is in place.
 * This keeps the upgraded tables aligned with install/database.sql while leaving unknown custom columns alone.
 *
 * @param Db $db
 * @param string $legacy_schema_file
 * @param string $schema_file
 * @param string $prefix
 * @param array $content
 */
function dropLegacyColumnsFromSchemaDiff($db, $legacy_schema_file, $schema_file, $prefix, &$content)
{
    $legacy_schema = loadSchemaFromSql($legacy_schema_file, $prefix);
    $schema = loadSchemaFromSql($schema_file, $prefix);

    foreach ($legacy_schema['tables'] as $table_name => $table) {
        if (!$db->tableExists($table_name) || empty($schema['tables'][$table_name]['columns'])) {
            continue;
        }

        foreach ($table['columns'] as $column_name => $definition) {
            if (!isset($schema['tables'][$table_name]['columns'][$column_name]) && $db->fieldExists($table_name, $column_name)) {
                $db->query("ALTER TABLE `$table_name` DROP COLUMN `$column_name`");
                $content[] = '<li class="correct">'.$table_name.': ลบคอลัมน์เดิม '.$column_name.'</li>';
            }
        }
    }
}

/**
 * Copy legacy activity rows into the new logs table.
 * The old schema stored these events in _activities instead of _logs.
 *
 * @param Db $db
 * @param string $prefix
 * @param array $content
 */
function migrateLegacyActivitiesToLogs($db, $prefix, &$content)
{
    $table_activities = $prefix.'_activities';
    $table_logs = $prefix.'_logs';
    if (!$db->tableExists($table_activities) || !$db->tableExists($table_logs)) {
        return;
    }
    foreach (['id', 'src_id', 'module', 'action', 'create_date', 'reason', 'member_id', 'topic', 'datas'] as $column_name) {
        if (!$db->fieldExists($table_activities, $column_name)) {
            return;
        }
    }
    foreach (['id', 'src_id', 'source', 'created_at', 'reason', 'member_id', 'topic', 'datas'] as $column_name) {
        if (!$db->fieldExists($table_logs, $column_name)) {
            return;
        }
    }

    $before_count = getTableRowCount($db, $table_logs);
    $db->query(
        "INSERT INTO `$table_logs` (`id`, `src_id`, `source`, `created_at`, `reason`, `member_id`, `topic`, `datas`)
        SELECT A.`id`, A.`src_id`, LEFT(CASE WHEN A.`module` IS NULL OR A.`module` = '' THEN 'legacy' ELSE A.`module` END, 20),
               A.`create_date`, A.`reason`, A.`member_id`, A.`topic`,
               CASE
                   WHEN A.`action` IS NULL OR A.`action` = '' THEN A.`datas`
                   WHEN A.`datas` IS NULL OR A.`datas` = '' THEN CONCAT('legacy_action=', A.`action`)
                   ELSE CONCAT('legacy_action=', A.`action`, '\n', A.`datas`)
               END
        FROM `$table_activities` A
        LEFT JOIN `$table_logs` L ON L.`id` = A.`id`
        WHERE L.`id` IS NULL"
    );
    $after_count = getTableRowCount($db, $table_logs);
    if ($after_count > $before_count) {
        $content[] = '<li class="correct">logs: ย้ายข้อมูลจาก activities '.($after_count - $before_count).' รายการ</li>';
    }

    $backup_table = findAvailableLegacyTableName($db, $prefix.'_activities_legacy');
    $db->query("RENAME TABLE `$table_activities` TO `$backup_table`");
    $content[] = '<li class="correct">activities: เก็บตารางเดิมไว้ที่ '.$backup_table.'</li>';
}

/**
 * @param Db $db
 * @param string $table_name
 *
 * @return int
 */
function getTableRowCount($db, $table_name)
{
    if (!$db->tableExists($table_name)) {
        return 0;
    }

    $result = $db->customQuery("SELECT COUNT(*) AS `count` FROM `$table_name`", true);

    return empty($result) ? 0 : (int) $result[0]['count'];
}

/**
 * Sync tables, columns, indexes, and AUTO_INCREMENT clauses from install/database.sql.
 * The upgrader reads the current database schema first and only applies missing or mismatched parts.
 *
 * @param Db $db
 * @param string $schema_file
 * @param string $prefix
 * @param array $content
 */
function syncSchemaFromSql($db, $schema_file, $prefix, &$content)
{
    $schema = loadSchemaFromSql($schema_file, $prefix);
    $schema_defaults = getUpgradeSchemaDefaults($db);
    $content[] = '<li class="correct">ฐานข้อมูล: ใช้ ENGINE='.strtoupper($schema_defaults['engine']).', CHARSET='.$schema_defaults['charset'].', COLLATE='.$schema_defaults['collation'].'</li>';
    foreach ($schema['tables'] as $table_name => $table) {
        if (!$db->tableExists($table_name)) {
            $db->query($table['sql']);
            $content[] = '<li class="correct">'.$table_name.': สร้างตารางจาก database.sql</li>';
        }
    }
    foreach ($schema['tables'] as $table_name => $table) {
        if (!$db->tableExists($table_name)) {
            continue;
        }
        syncTableOptionsFromSchema($db, $table_name, $table['options'], $schema_defaults, $content);
        syncTableColumnsFromSchema($db, $table_name, $table['columns'], $schema_defaults, $content);
    }
    foreach ($schema['alters'] as $table_name => $clauses) {
        if (!$db->tableExists($table_name)) {
            continue;
        }
        foreach ($clauses as $clause) {
            syncTableAlterClauseFromSchema($db, $table_name, $clause, $content);
        }
    }
}

/**
 * Parse CREATE TABLE and ALTER TABLE statements from install/database.sql.
 *
 * @param string $schema_file
 * @param string $prefix
 *
 * @return array
 */
function loadSchemaFromSql($schema_file, $prefix)
{
    /**
     * @var array
     */
    static $cache = [];

    $cache_key = $schema_file.'|'.$prefix;
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $sql = @file_get_contents($schema_file);
    if ($sql === false) {
        throw new \Exception('ไม่สามารถอ่านไฟล์ install/database.sql ได้');
    }
    if (strpos($sql, '{prefix}') !== false) {
        $sql = str_replace('{prefix}', $prefix, $sql);
    } else {
        $sql = preg_replace('/`oas_([a-z0-9_]+)`/i', '`'.$prefix.'_$1`', $sql);
    }

    $schema = [
        'tables' => [],
        'alters' => []
    ];

    if (preg_match_all('/CREATE TABLE\s+`([^`]+)`\s*\((.*?)\)\s*(ENGINE=.*?);/is', $sql, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $table_name = $match[1];
            $columns = [];
            foreach (preg_split('/\R/', trim($match[2])) as $line) {
                $line = trim(rtrim($line, ','));
                if ($line === '' || $line[0] !== '`') {
                    continue;
                }
                if (preg_match('/^`([^`]+)`\s+(.+)$/', $line, $column_match)) {
                    $columns[$column_match[1]] = $column_match[2];
                }
            }
            $schema['tables'][$table_name] = [
                'sql' => trim($match[0]),
                'columns' => $columns,
                'options' => parseTableOptionsFromSql($match[3])
            ];
        }
    }

    if (preg_match_all('/ALTER TABLE\s+`([^`]+)`\s*(.*?);/is', $sql, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $table_name = $match[1];
            if (!isset($schema['alters'][$table_name])) {
                $schema['alters'][$table_name] = [];
            }
            foreach (preg_split('/\R/', trim($match[2])) as $line) {
                $line = trim(rtrim($line, ','));
                if ($line !== '') {
                    $schema['alters'][$table_name][] = $line;
                }
            }
        }
    }

    $cache[$cache_key] = $schema;

    return $schema;
}

/**
 * Parse ENGINE / CHARSET / COLLATE table options.
 *
 * @param string $options_sql
 *
 * @return array
 */
function parseTableOptionsFromSql($options_sql)
{
    $options = [];
    if (preg_match('/ENGINE\s*=\s*([a-z0-9_]+)/i', $options_sql, $match)) {
        $options['engine'] = strtolower($match[1]);
    }
    if (preg_match('/DEFAULT\s+CHARSET\s*=\s*([a-z0-9_]+)/i', $options_sql, $match)) {
        $options['charset'] = strtolower($match[1]);
    }
    if (preg_match('/COLLATE\s*=\s*([a-z0-9_]+)/i', $options_sql, $match)) {
        $options['collation'] = strtolower($match[1]);
    }

    return $options;
}

/**
 * Resolve the preferred engine/charset/collation for upgrades.
 * Prioritize Thai-aware utf8mb4 collation when the server supports it.
 *
 * @param Db $db
 *
 * @return array
 */
function getUpgradeSchemaDefaults($db)
{
    /**
     * @var array|null
     */
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $cache = [
        'engine' => 'innodb',
        'charset' => 'utf8mb4',
        'collation' => resolveSupportedUpgradeCollation($db, [
            'utf8mb4_unicode_ci',
            'utf8mb4_general_ci'
        ])
    ];

    return $cache;
}

/**
 * Pick the first available collation from the preferred list.
 *
 * @param Db $db
 * @param array $collations
 *
 * @return string
 */
function resolveSupportedUpgradeCollation($db, $collations)
{
    foreach ($collations as $collation) {
        $result = $db->customQuery("SHOW COLLATION LIKE '$collation'", true);
        if (!empty($result)) {
            return strtolower($result[0]['Collation']);
        }
    }

    throw new \Exception('Server นี้ไม่รองรับ utf8mb4 collation ที่ต้องใช้สำหรับการปรับรุ่น');
}

/**
 * Ensure table engine / charset / collation matches database.sql.
 *
 * @param Db $db
 * @param string $table_name
 * @param array $target_options
 * @param array $schema_defaults
 * @param array $content
 */
function syncTableOptionsFromSchema($db, $table_name, $target_options, $schema_defaults, &$content)
{
    $status = getTableStatus($db, $table_name);
    if (empty($status)) {
        return;
    }

    $sql = [];
    if (!empty($schema_defaults['engine']) && !empty($status['Engine']) && strtolower($status['Engine']) !== $schema_defaults['engine']) {
        $sql[] = 'ENGINE='.$schema_defaults['engine'];
    }

    if (!empty($schema_defaults['charset'])) {
        $current_collation = empty($status['Collation']) ? '' : strtolower($status['Collation']);
        $target_collation = empty($schema_defaults['collation']) ? '' : $schema_defaults['collation'];
        $needs_convert = false;
        if ($target_collation !== '') {
            $needs_convert = $current_collation !== $target_collation;
        } else {
            $needs_convert = $current_collation === '' || strpos($current_collation, $schema_defaults['charset'].'_') !== 0;
        }
        if ($needs_convert) {
            $convert = 'CONVERT TO CHARACTER SET '.$schema_defaults['charset'];
            if ($target_collation !== '') {
                $convert .= ' COLLATE '.$target_collation;
            }
            $sql[] = $convert;
        }
    }

    if (!empty($sql)) {
        $db->query("ALTER TABLE `$table_name` ".implode(', ', $sql));
        $content[] = '<li class="correct">'.$table_name.': ปรับ ENGINE/CHARSET/COLLATE เป็น '.strtoupper($schema_defaults['engine']).' / '.$schema_defaults['charset'].' / '.$schema_defaults['collation'].'</li>';
    }
}

/**
 * Ensure columns from database.sql exist and match the target definition.
 *
 * @param Db $db
 * @param string $table_name
 * @param array $target_columns
 * @param array $schema_defaults
 * @param array $content
 */
function syncTableColumnsFromSchema($db, $table_name, $target_columns, $schema_defaults, &$content)
{
    $current_columns = getTableColumns($db, $table_name);
    foreach ($target_columns as $column_name => $definition) {
        $definition = applyUpgradeSchemaDefaultsToColumn($definition, $schema_defaults);
        if (!isset($current_columns[$column_name])) {
            $db->query("ALTER TABLE `$table_name` ADD `$column_name` $definition");
            $content[] = '<li class="correct">'.$table_name.': เพิ่มคอลัมน์ '.$column_name.'</li>';
            $current_columns = getTableColumns($db, $table_name);
            continue;
        }
        if (columnNeedsSync($current_columns[$column_name], $definition)) {
            $db->query("ALTER TABLE `$table_name` MODIFY `$column_name` $definition");
            $content[] = '<li class="correct">'.$table_name.': ปรับคอลัมน์ '.$column_name.'</li>';
            $current_columns = getTableColumns($db, $table_name);
        }
    }
}

/**
 * Normalize text column definitions to the chosen upgrade charset/collation.
 *
 * @param string $definition
 * @param array $schema_defaults
 *
 * @return string
 */
function applyUpgradeSchemaDefaultsToColumn($definition, $schema_defaults)
{
    $definition = trim(preg_replace('/\s+character set\s+[a-z0-9_]+/i', '', $definition));
    $definition = trim(preg_replace('/\s+collate\s+[a-z0-9_]+/i', '', $definition));

    $parsed = parseColumnDefinition($definition);
    if (!isCharacterColumnType($parsed['type'])) {
        return $definition;
    }

    if (preg_match('/^(.*?)(\s+(?:not null|null|default|auto_increment)\b.*)?$/i', $definition, $match)) {
        $type = trim($match[1]);
        $suffix = empty($match[2]) ? '' : ' '.trim($match[2]);

        return $type.' CHARACTER SET '.$schema_defaults['charset'].' COLLATE '.$schema_defaults['collation'].$suffix;
    }

    return $definition.' CHARACTER SET '.$schema_defaults['charset'].' COLLATE '.$schema_defaults['collation'];
}

/**
 * Determine whether a column type supports charset/collation attributes.
 *
 * @param string $type
 *
 * @return bool
 */
function isCharacterColumnType($type)
{
    return preg_match('/^(char|varchar|tinytext|text|mediumtext|longtext|enum|set)\b/i', $type) === 1;
}

/**
 * Ensure an ALTER TABLE clause from database.sql is applied when needed.
 *
 * @param Db $db
 * @param string $table_name
 * @param string $clause
 * @param array $content
 */
function syncTableAlterClauseFromSchema($db, $table_name, $clause, &$content)
{
    if (preg_match('/^ADD PRIMARY KEY\s*\((.+?)\)(?:\s+USING\s+[A-Z]+)?$/i', $clause, $match)) {
        $indexes = getTableIndexes($db, $table_name);
        $target_columns = normalizeIndexColumnsSql($match[1]);
        if (!isset($indexes['PRIMARY'])) {
            $db->query("ALTER TABLE `$table_name` $clause");
            $content[] = '<li class="correct">'.$table_name.': เพิ่ม PRIMARY KEY</li>';
        } elseif (normalizeCurrentIndexColumns($indexes['PRIMARY']) !== $target_columns) {
            $db->query("ALTER TABLE `$table_name` DROP PRIMARY KEY, $clause");
            $content[] = '<li class="correct">'.$table_name.': ปรับ PRIMARY KEY ให้ตรงกับ database.sql</li>';
        }

        return;
    }

    if (preg_match('/^ADD\s+(UNIQUE KEY|FULLTEXT KEY|KEY) `([^`]+)`\s*\((.+?)\)(?:\s+USING\s+([A-Z]+))?$/i', $clause, $match)) {
        syncNamedIndexFromSchema($db, $table_name, $match[2], $match[3], strtoupper($match[1]), $clause, $content, empty($match[4]) ? '' : strtoupper($match[4]));

        return;
    }

    if (preg_match('/^MODIFY `([^`]+)`\s+(.+)$/i', $clause, $match)) {
        $columns = getTableColumns($db, $table_name);
        if (!isset($columns[$match[1]]) || columnNeedsSync($columns[$match[1]], $match[2])) {
            $db->query("ALTER TABLE `$table_name` $clause");
            $content[] = '<li class="correct">'.$table_name.': ปรับ '.$match[1].' ตาม database.sql</li>';
        }
    }
}

/**
 * Ensure a named index matches database.sql.
 *
 * @param Db $db
 * @param string $table_name
 * @param string $index_name
 * @param string $columns_sql
 * @param string $index_mode
 * @param string $clause
 * @param array $content
 * @param string $using
 */
function syncNamedIndexFromSchema($db, $table_name, $index_name, $columns_sql, $index_mode, $clause, &$content, $using = '')
{
    $indexes = getTableIndexes($db, $table_name);
    $target_columns = normalizeIndexColumnsSql($columns_sql);
    $target_unique = $index_mode === 'UNIQUE KEY';
    $target_type = $index_mode === 'FULLTEXT KEY' ? 'FULLTEXT' : ($using === '' ? 'BTREE' : $using);
    if (!isset($indexes[$index_name])) {
        $db->query("ALTER TABLE `$table_name` $clause");
        $content[] = '<li class="correct">'.$table_name.': เพิ่ม index '.$index_name.'</li>';

        return;
    }

    $current_unique = (int) $indexes[$index_name][0]['Non_unique'] === 0;
    $current_type = strtoupper((string) ($indexes[$index_name][0]['Index_type'] ?? 'BTREE'));
    $current_columns = normalizeCurrentIndexColumns($indexes[$index_name]);
    if ($current_unique !== $target_unique || $current_type !== $target_type || $current_columns !== $target_columns) {
        $db->query("ALTER TABLE `$table_name` DROP INDEX `$index_name`, $clause");
        $content[] = '<li class="correct">'.$table_name.': ปรับ index '.$index_name.'</li>';
    }
}

/**
 * Load column metadata for a table.
 *
 * @param Db $db
 * @param string $table_name
 *
 * @return array
 */
function getTableColumns($db, $table_name)
{
    $columns = [];
    foreach ($db->customQuery("SHOW FULL COLUMNS FROM `$table_name`", true) as $column) {
        $columns[$column['Field']] = $column;
    }

    return $columns;
}

/**
 * Load index metadata for a table.
 *
 * @param Db $db
 * @param string $table_name
 *
 * @return array
 */
function getTableIndexes($db, $table_name)
{
    $indexes = [];
    foreach ($db->customQuery("SHOW INDEX FROM `$table_name`", true) as $index) {
        if (!isset($indexes[$index['Key_name']])) {
            $indexes[$index['Key_name']] = [];
        }
        $indexes[$index['Key_name']][] = $index;
    }

    return $indexes;
}

/**
 * Load table status metadata.
 *
 * @param Db $db
 * @param string $table_name
 *
 * @return array
 */
function getTableStatus($db, $table_name)
{
    $escaped = str_replace("'", "''", $table_name);
    $result = $db->customQuery("SHOW TABLE STATUS LIKE '$escaped'", true);

    return empty($result) ? [] : $result[0];
}

/**
 * Check whether a column differs from the target SQL definition.
 *
 * @param array $current_column
 * @param string $target_definition
 *
 * @return bool
 */
function columnNeedsSync($current_column, $target_definition)
{
    $target = parseColumnDefinition($target_definition);
    if (normalizeSqlFragment($current_column['Type']) !== $target['type']) {
        return true;
    }
    if ($target['collation'] !== '' && strtolower((string) $current_column['Collation']) !== $target['collation']) {
        return true;
    }
    if (($current_column['Null'] === 'YES') !== $target['nullable']) {
        return true;
    }
    $current_default = normalizeDefaultValue($current_column['Default']);
    $target_default = $target['default']['specified'] ? $target['default']['value'] : null;
    if ($current_default !== $target_default) {
        return true;
    }

    $current_auto_increment = stripos((string) $current_column['Extra'], 'auto_increment') !== false;

    return $current_auto_increment !== $target['auto_increment'];
}

/**
 * Parse a column definition body from database.sql.
 *
 * @param string $definition
 *
 * @return array
 */
function parseColumnDefinition($definition)
{
    $definition = trim($definition);
    $type_definition = preg_replace('/\s+character set\s+[a-z0-9_]+/i', '', $definition);
    $type_definition = preg_replace('/\s+collate\s+[a-z0-9_]+/i', '', $type_definition);

    $type = $type_definition;
    if (preg_match('/^(.*?)(?=\s+(?:not null|null|default|auto_increment)\b|$)/i', $type_definition, $match)) {
        $type = $match[1];
    }

    $default_specified = false;
    $default_value = null;
    if (preg_match('/\bdefault\s+(.+?)(?=\s+auto_increment\b|$)/i', $definition, $match)) {
        $default_specified = true;
        $default_value = normalizeDefaultValue($match[1]);
    }

    return [
        'type' => normalizeSqlFragment($type),
        'collation' => preg_match('/\bcollate\s+([a-z0-9_]+)/i', $definition, $match) ? strtolower($match[1]) : '',
        'nullable' => !preg_match('/\bnot null\b/i', $definition),
        'default' => [
            'specified' => $default_specified,
            'value' => $default_value
        ],
        'auto_increment' => preg_match('/\bauto_increment\b/i', $definition) === 1
    ];
}

/**
 * Normalize defaults from SHOW FULL COLUMNS / database.sql.
 *
 * @param mixed $value
 *
 * @return mixed
 */
function normalizeDefaultValue($value)
{
    if ($value === null) {
        return null;
    }

    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    if (strcasecmp($value, 'null') === 0) {
        return null;
    }
    if (($value[0] === "'" && substr($value, -1) === "'") || ($value[0] === '"' && substr($value, -1) === '"')) {
        $value = substr($value, 1, -1);
    }
    if ($value === "''" || $value === '""') {
        return '';
    }

    return $value;
}

/**
 * Normalize an index column list from database.sql.
 *
 * @param string $columns_sql
 *
 * @return string
 */
function normalizeIndexColumnsSql($columns_sql)
{
    $normalized = [];
    foreach (explode(',', $columns_sql) as $column_sql) {
        $column_sql = normalizeSqlFragment($column_sql);
        $column_sql = preg_replace('/\s+asc$/', '', $column_sql);
        $normalized[] = $column_sql;
    }

    return implode(', ', $normalized);
}

/**
 * Normalize current index columns from SHOW INDEX metadata.
 *
 * @param array $index_rows
 *
 * @return string
 */
function normalizeCurrentIndexColumns($index_rows)
{
    usort($index_rows, function ($a, $b) {
        return (int) $a['Seq_in_index'] <=> (int) $b['Seq_in_index'];
    });

    $normalized = [];
    foreach ($index_rows as $row) {
        $column_sql = '`'.strtolower($row['Column_name']).'`';
        if (isset($row['Collation']) && strtoupper((string) $row['Collation']) === 'D') {
            $column_sql .= ' desc';
        }
        $normalized[] = $column_sql;
    }

    return implode(', ', $normalized);
}

/**
 * Normalize SQL fragments for simple comparisons.
 *
 * @param string $sql
 *
 * @return string
 */
function normalizeSqlFragment($sql)
{
    return strtolower(trim(preg_replace('/\s+/', ' ', $sql)));
}
