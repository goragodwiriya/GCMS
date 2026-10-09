<?php
/**
 * modules/product/install/upgrade.php — ย้ายสินค้าของโมดูล product รุ่นเดิม (GCMS 11–14)
 * มาอยู่ในโครงสร้างของโมดูล product ปัจจุบัน
 *
 * install/upgrade2.php เรียกไฟล์นี้ให้เอง (gcmsModuleHooks) หลัง gcmsBeforeSync() และ
 * "ก่อน" ปรับตารางตามสคีมา (gcmsSyncSchema) ใน closure ของตัวเอง ตัวแปรที่มองเห็นมีแค่
 * $db (install/db.php), $prefix, $schema, $content, $config — ตอนนี้ last_update ของรุ่นเดิมถูกย้ายเป็น
 * updated_at แล้ว แต่ตาราง/คอลัมน์ใหม่ยังไม่ถูกสร้าง การย้ายจึงเพิ่มเฉพาะส่วนที่ต้องเขียน
 * (ตาราง product_image / product_variant และคอลัมน์ไม่กี่ตัว) ที่เหลือ gcmsSyncSchema() ปรับให้
 *
 * รุ่นเดิมเก็บ รหัสสินค้าใน product_no, รูปเดียวใน picture (ไฟล์ที่
 * DATA_FOLDER/product/{picture}) และราคาแยกไว้ในตาราง product_price เป็น
 * serialize หรือ JSON ({"THB":499} / a:1:{s:3:"THB";d:299;}) โดย price = ราคาเต็ม
 * net = ราคาขายจริง โมดูลใหม่อ่านจาก sku, product_image, base_price และ
 * product_variant แทน ถ้าไม่ย้าย หน้าสินค้าจะแสดงราคา 0 ไม่มีรูป และหยิบใส่ตะกร้าไม่ได้
 *
 *   sku                    <- product_no
 *   base_price, variant    <- net ของ product_price (ถ้า net ว่างใช้ price)
 *   manage_stock = 0       รุ่นเดิมไม่มีระบบสต็อก ถ้าเปิดไว้สินค้าทุกชิ้นจะ "หมด"
 *   created_at             <- last_update (ที่ถูกย้ายเป็น updated_at แล้ว — รุ่นเดิมไม่มีวันที่สร้าง)
 *   product_image          <- picture + คัดลอกไฟล์ไป DATA_FOLDER/product/{id}/
 *   product_variant        variant แฝงของสินค้าแบบ simple (ตะกร้า/POS/สต็อก อ้างถึง)
 *   product_detail         + module_id (โมดูลใหม่ join ด้วยคอลัมน์นี้)
 *   modules.config         can_write -> can_manage, currency_unit -> currency
 *
 * ไม่ลบข้อมูลของใคร : คอลัมน์ product_no / picture คงไว้ตามเดิม (ตัวปรับรุ่นให้มี
 * ค่าเริ่มต้นเอง) ไฟล์รูปเดิมถูกคัดลอก ไม่ได้ย้าย ตาราง product_price ซึ่งโมดูลใหม่
 * ไม่ใช้แล้ว (ราคาเต็มไม่มีที่เก็บในโครงสร้างใหม่) ถูกเปลี่ยนชื่อเป็น product_price_legacy
 *
 * รันซ้ำได้ — สินค้าที่ "ยังไม่มี variant" คือสินค้าที่ยังไม่ได้ย้าย (สินค้าที่สร้างจาก
 * โมดูลใหม่มี variant เสมอ) และ variant ถูกเพิ่มเป็นขั้นสุดท้ายของสินค้าแต่ละตัว
 * รอบที่ค้างไว้จึงทำต่อได้โดยไม่ทำซ้ำ ทั้งหมดทำงานเฉพาะตาราง product ที่ยังมี
 * คอลัมน์ของรุ่นเดิม (product_no หรือ picture) เท่านั้น
 */
if (!defined('ROOT_PATH')) {
    exit;
}

$_pd_product = $prefix.'_product';
if ($db->tableExists($_pd_product) && $db->fieldExists($_pd_product, 'module_id')
    && ($db->fieldExists($_pd_product, 'product_no') || $db->fieldExists($_pd_product, 'picture'))
) {
    $_pd_detail = $prefix.'_product_detail';
    $_pd_image = $prefix.'_product_image';
    $_pd_variant = $prefix.'_product_variant';
    $_pd_data = (defined('DATA_FOLDER') ? DATA_FOLDER : 'datas/').'product/';

    // ตารางที่การย้ายต้องเขียน — นิยามของตัวติดตั้ง (database.sql ข้าง ๆ)
    foreach ([$_pd_image, $_pd_variant] as $_pd_table) {
        if (!$db->tableExists($_pd_table) && isset($schema['tables'][$_pd_table]['create'])) {
            $db->query($schema['tables'][$_pd_table]['create']);
            $content[] = '<li class="correct">'.$_pd_table.': สร้างตารางใหม่</li>';
        }
    }

    // คอลัมน์ของโครงสร้างใหม่ที่การย้ายต้องเขียน (นิยามเดียวกับ database.sql)
    foreach ([
        'sku' => "varchar(64) NOT NULL DEFAULT ''",
        'base_price' => 'decimal(12,2) NOT NULL DEFAULT 0.00',
        'manage_stock' => 'tinyint(1) unsigned NOT NULL DEFAULT 1',
        'created_at' => 'datetime NOT NULL DEFAULT current_timestamp()'
    ] as $_pd_column => $_pd_definition) {
        if (!$db->fieldExists($_pd_product, $_pd_column)) {
            $db->query("ALTER TABLE `$_pd_product` ADD `$_pd_column` $_pd_definition");
            $content[] = '<li class="correct">'.$_pd_product.': เพิ่มคอลัมน์ '.$_pd_column.'</li>';
        }
    }
    if ($db->tableExists($_pd_detail)) {
        if (!$db->fieldExists($_pd_detail, 'module_id')) {
            $db->query("ALTER TABLE `$_pd_detail` ADD `module_id` int(11) unsigned NOT NULL DEFAULT 0 AFTER `id`");
            $content[] = '<li class="correct">'.$_pd_detail.': เพิ่มคอลัมน์ module_id</li>';
        }
        $_pd_n = (int) $db->query("UPDATE `$_pd_detail` D INNER JOIN `$_pd_product` P ON P.`id` = D.`id` SET D.`module_id` = P.`module_id` WHERE D.`module_id` = 0");
        if ($_pd_n > 0) {
            $content[] = '<li class="correct">'.$_pd_detail.': กำหนด module_id '.$_pd_n.' รายการ</li>';
        }
    }

    // ราคาของรุ่นเดิม — ตารางที่ถูกเปลี่ยนชื่อไปแล้วในรอบก่อน (รอบที่ค้าง) ก็ยังอ่านได้
    $_pd_prices = [];
    $_pd_price_table = '';
    foreach ([$prefix.'_product_price', $prefix.'_product_price_legacy'] as $_pd_table) {
        if ($db->tableExists($_pd_table) && $db->fieldExists($_pd_table, 'net')) {
            $_pd_price_table = $_pd_table;
            break;
        }
    }
    if ($_pd_price_table !== '') {
        foreach ((array) $db->customQuery("SELECT `id`, `price`, `net` FROM `$_pd_price_table`", true) as $_pd_row) {
            // ราคาขายจริง (net) ก่อน ถ้าว่างใช้ราคาเต็ม — ค่าแรกที่เป็นตัวเลขของสกุลเงินใดก็ได้
            foreach (['net', 'price'] as $_pd_column) {
                $_pd_value = trim((string) $_pd_row[$_pd_column]);
                if (is_numeric($_pd_value)) {
                    $_pd_values = [$_pd_value];
                } elseif (substr($_pd_value, 0, 2) === 'a:') {
                    $_pd_values = @unserialize($_pd_value, ['allowed_classes' => false]);
                } else {
                    $_pd_values = json_decode($_pd_value, true);
                }
                $_pd_amount = 0.0;
                foreach (is_array($_pd_values) ? $_pd_values : [] as $_pd_value) {
                    if (is_numeric($_pd_value)) {
                        $_pd_amount = (float) $_pd_value;
                        break;
                    }
                }
                if ($_pd_amount > 0) {
                    $_pd_prices[(int) $_pd_row['id']] = $_pd_amount;
                    break;
                }
            }
        }
    }

    // สินค้าที่ยังไม่ได้ย้าย = ยังไม่มี variant
    $_pd_has_no = $db->fieldExists($_pd_product, 'product_no');
    $_pd_has_picture = $db->fieldExists($_pd_product, 'picture');
    $_pd_rows = $db->customQuery(
        'SELECT P.`id`, P.`module_id`, P.`published`, '
        .($_pd_has_no ? 'P.`product_no`' : "'' AS `product_no`").', '
        .($_pd_has_picture ? 'P.`picture`' : "'' AS `picture`")
        ." FROM `$_pd_product` P LEFT JOIN `$_pd_variant` V ON V.`product_id` = P.`id`"
        .' WHERE V.`id` IS NULL ORDER BY P.`id`',
        true
    );
    if (!empty($_pd_rows)) {
        // created_at : วันที่แก้ไขล่าสุดของรุ่นเดิม (gcmsBeforeSync ย้าย last_update เป็น updated_at
        // ไว้แล้ว — last_update ยังอ่านได้ถ้าไฟล์นี้ถูกเรียกก่อนหน้านั้น)
        if ($db->fieldExists($_pd_product, 'last_update')) {
            $_pd_created = 'IF(`last_update` > 0, FROM_UNIXTIME(`last_update`), `created_at`)';
        } elseif ($db->fieldExists($_pd_product, 'updated_at')) {
            $_pd_created = 'COALESCE(`updated_at`, `created_at`)';
        } else {
            $_pd_created = '`created_at`';
        }
        // updated_at ที่มี ON UPDATE อยู่แล้ว (รันซ้ำหลังปรับตาราง) ต้องไม่ถูกเลื่อนเป็นตอนนี้
        $_pd_keep = $db->fieldExists($_pd_product, 'updated_at') ? ', `updated_at` = `updated_at`' : '';
        $_pd_sku = $_pd_has_no ? ", `sku` = IF(`sku` = '', LEFT(`product_no`, 64), `sku`)" : '';

        $_pd_max = $db->customQuery("SELECT MAX(`id`) AS `m` FROM `$_pd_image`");
        $_pd_image_id = empty($_pd_max) ? 0 : (int) $_pd_max[0]->m;
        $_pd_max = $db->customQuery("SELECT MAX(`id`) AS `m` FROM `$_pd_variant`");
        $_pd_variant_id = empty($_pd_max) ? 0 : (int) $_pd_max[0]->m;

        $_pd_count = ['product' => 0, 'price' => 0, 'image' => 0, 'variant' => 0];
        foreach ($_pd_rows as $_pd_row) {
            $_pd_id = (int) $_pd_row['id'];
            $_pd_module_id = (int) $_pd_row['module_id'];
            $_pd_amount = isset($_pd_prices[$_pd_id]) ? $_pd_prices[$_pd_id] : 0.0;
            $_pd_price = number_format($_pd_amount, 2, '.', '');

            $db->query("UPDATE `$_pd_product` SET `manage_stock` = 0, `created_at` = $_pd_created$_pd_sku$_pd_keep"
                .($_pd_amount > 0 ? ", `base_price` = $_pd_price" : '')." WHERE `id` = $_pd_id");
            ++$_pd_count['product'];
            if ($_pd_amount > 0) {
                ++$_pd_count['price'];
            }

            // รูปเดียวของรุ่นเดิม -> รูปหลักของสินค้า (ไฟล์เดิมยังอยู่ที่เดิม)
            $_pd_picture = trim((string) $_pd_row['picture']);
            if ($_pd_picture !== '' && preg_match('/^[a-zA-Z0-9_\-\.]+$/', $_pd_picture)
                && is_file(ROOT_PATH.$_pd_data.$_pd_picture)
                && !$db->first($_pd_image, ['product_id' => $_pd_id])
            ) {
                $_pd_dir = ROOT_PATH.$_pd_data.$_pd_id.'/';
                if (!is_dir($_pd_dir)) {
                    makeDirectory($_pd_dir);
                }
                if (is_file($_pd_dir.$_pd_picture) || (is_dir($_pd_dir) && @copy(ROOT_PATH.$_pd_data.$_pd_picture, $_pd_dir.$_pd_picture))) {
                    $db->insert($_pd_image, [
                        'id' => ++$_pd_image_id,
                        'product_id' => $_pd_id,
                        'module_id' => $_pd_module_id,
                        'filename' => $_pd_picture,
                        'sort' => 0,
                        'is_primary' => 1
                    ]);
                    ++$_pd_count['image'];
                } else {
                    $content[] = '<li class="warning">'.$_pd_product.': คัดลอกรูป '.$_pd_data.$_pd_picture
                        .' ไปที่ '.$_pd_data.$_pd_id.'/ ไม่ได้ (สินค้า '.$_pd_id.' ยังไม่มีรูป ตั้งค่าสิทธิ์โฟลเดอร์แล้วปรับรุ่นซ้ำได้)</li>';
                }
            }

            // variant แฝงเป็นขั้นสุดท้าย — สินค้าที่มี variant แล้วถือว่าย้ายเสร็จ
            $db->insert($_pd_variant, [
                'id' => ++$_pd_variant_id,
                'product_id' => $_pd_id,
                'module_id' => $_pd_module_id,
                'sku' => mb_substr((string) $_pd_row['product_no'], 0, 64),
                'price' => $_pd_price,
                'sale_price' => null,
                'stock_qty' => 0,
                'weight' => 0,
                'published' => empty($_pd_row['published']) ? 0 : 1,
                'image_id' => 0
            ]);
            ++$_pd_count['variant'];
        }
        $content[] = '<li class="correct">'.$_pd_product.': ย้ายสินค้าของรุ่นเดิม '.$_pd_count['product'].' รายการ'
            .' (ราคา '.$_pd_count['price'].', รูป '.$_pd_count['image'].', variant '.$_pd_count['variant'].')</li>';
    }

    // สิทธิ์ของโมดูลรุ่นเดิม can_write -> can_manage ที่โมดูลใหม่ตรวจ
    // config อาจยังเป็น serialize (ตัวปรับรุ่นแปลงเป็น JSON ทีหลัง) — เขียนกลับเป็น JSON
    $_pd_modules = $prefix.'_modules';
    if ($db->tableExists($_pd_modules)) {
        foreach ((array) $db->customQuery("SELECT `id`, `config` FROM `$_pd_modules` WHERE `owner` = 'product'", true) as $_pd_row) {
            $_pd_value = trim((string) $_pd_row['config']);
            $_pd_module_config = substr($_pd_value, 0, 2) === 'a:' ? @unserialize($_pd_value, ['allowed_classes' => false]) : json_decode($_pd_value, true);
            if (!is_array($_pd_module_config) || isset($_pd_module_config['can_manage']) || !isset($_pd_module_config['can_write'])) {
                continue;
            }
            $_pd_module_config['can_manage'] = $_pd_module_config['can_write'];
            if (!isset($_pd_module_config['currency']) && !empty($_pd_module_config['currency_unit'])) {
                $_pd_module_config['currency'] = $_pd_module_config['currency_unit'];
            }
            $db->update($_pd_modules, ['id' => $_pd_row['id']], ['config' => json_encode($_pd_module_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            $content[] = '<li class="correct">'.$_pd_modules.': สิทธิ์จัดการสินค้า (can_write -> can_manage) ของโมดูล '.(int) $_pd_row['id'].'</li>';
        }
    }

    // product_price — โมดูลใหม่ไม่ใช้แล้ว ราคาขายย้ายไป base_price/variant แล้ว
    // ราคาเต็ม (price) ไม่มีที่เก็บ จึงเปลี่ยนชื่อตารางเก็บไว้ ไม่ลบ
    $_pd_table = $prefix.'_product_price';
    if ($db->tableExists($_pd_table) && !$db->tableExists($_pd_table.'_legacy')) {
        $_pd_max = $db->customQuery("SELECT COUNT(*) AS `c` FROM `$_pd_table`");
        $_pd_n = empty($_pd_max) ? 0 : (int) $_pd_max[0]->c;
        $db->query("RENAME TABLE `$_pd_table` TO `{$_pd_table}_legacy`");
        if (function_exists('noteRowsMoved')) {
            noteRowsMoved($_pd_table, $_pd_table.'_legacy', $_pd_n);
        }
        $content[] = '<li class="correct">'.$_pd_table.': เปลี่ยนชื่อเป็น '.$_pd_table.'_legacy (ราคาย้ายไปที่สินค้าแล้ว เก็บตารางเดิมไว้ '.$_pd_n.' แถว)</li>';
    }
}
