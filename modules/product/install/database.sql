-- ---------------------------------------------------------------------------
-- Product — ตารางของโมดูล product (ร้านค้าออนไลน์ : สินค้า ตะกร้า คำสั่งซื้อ สต็อก)
--
-- นิยามชุดเดียวกับ "@section module:product" ของ gcms.in.th ทุกคอลัมน์
--
-- product                  = สินค้า (id AUTO_INCREMENT) ตารางอื่นใช้ DB::nextId()
-- product_detail           = ชื่อ/รายละเอียดแยกภาษา language '' = ใช้กับทุกภาษา
--                            (ข้อมูลที่ย้ายมาจากโมดูลสินค้ารุ่นเดิม)
-- product_image            = รูปสินค้า ไฟล์อยู่ที่ DATA_FOLDER/product/{product_id}/{filename}
-- product_variant          = สินค้าแบบ simple มี variant แฝง 1 รายการเสมอ
--                            ตะกร้า/สต็อก/POS อ้างถึง variant_id
-- product_stock_lot        = ต้นทางของยอดคงเหลือ (FIFO) stock_qty ของ product /
--                            product_variant เป็นแค่ค่าแคช
--
-- ไซต์ GCMS 11–14 มีตาราง product / product_detail / product_price แบบเดิม
-- (product_no, picture, ราคาเก็บใน product_price) ตัวปรับรุ่นย้ายข้อมูลให้ก่อน
-- ปรับตาราง ดู modules/product/install/upgrade.php
--
-- แม่แบบอีเมลท้ายไฟล์ : payment_notify = ตอบรับเมื่อลูกค้าแจ้งชำระเงิน
-- (Gcms\Payment\Controller::notifyByEmail) ตัวแปร %ORDER_NO% %PAID%
-- %PAYMENT_METHOD% %COMMENT% — code ยาวไม่เกิน 20 ตัวอักษร (emailtemplate.code)
-- ---------------------------------------------------------------------------

-- Table: `{prefix}_product`
CREATE TABLE `{prefix}_product` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(11) unsigned NOT NULL,
  `category_id` int(11) unsigned NOT NULL DEFAULT 0,
  `sku` varchar(64) NOT NULL DEFAULT '',
  `alias` varchar(255) NOT NULL DEFAULT '',
  `product_type` enum('simple','variable') NOT NULL DEFAULT 'simple',
  `base_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `manage_stock` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `stock_qty` int(11) NOT NULL DEFAULT 0,
  `weight` decimal(10,3) NOT NULL DEFAULT 0.000,
  `featured` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `visited` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`),
  KEY `category_id` (`category_id`),
  KEY `published` (`published`),
  KEY `alias` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_detail`
-- language '' = ใช้กับทุกภาษา (ข้อมูลจากไซต์รุ่นเก่า)
CREATE TABLE `{prefix}_product_detail` (
  `id` int(11) unsigned NOT NULL,
  `module_id` int(11) unsigned NOT NULL DEFAULT 0,
  `language` varchar(2) NOT NULL DEFAULT '',
  `topic` mediumtext NOT NULL,
  `keywords` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `detail` mediumtext NOT NULL,
  PRIMARY KEY (`id`,`language`),
  KEY `module_id` (`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_image`
-- ไฟล์อยู่ที่ DATA_FOLDER/product/{product_id}/{filename}
CREATE TABLE `{prefix}_product_image` (
  `id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL DEFAULT 0,
  `module_id` int(10) unsigned NOT NULL DEFAULT 0,
  `filename` varchar(64) NOT NULL DEFAULT '',
  `sort` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_attribute`
CREATE TABLE `{prefix}_product_attribute` (
  `id` int(10) unsigned NOT NULL,
  `module_id` int(10) unsigned NOT NULL DEFAULT 0,
  `name` mediumtext NOT NULL,
  `sort` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_attribute_value`
CREATE TABLE `{prefix}_product_attribute_value` (
  `id` int(10) unsigned NOT NULL,
  `attribute_id` int(10) unsigned NOT NULL DEFAULT 0,
  `module_id` int(10) unsigned NOT NULL DEFAULT 0,
  `value` mediumtext NOT NULL,
  `sort` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `attribute_id` (`attribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_variant`
-- สินค้าแบบ simple มี variant แฝง 1 รายการเสมอ เพื่อให้ตะกร้า/สต็อก/POS อ้างถึงได้
CREATE TABLE `{prefix}_product_variant` (
  `id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL DEFAULT 0,
  `module_id` int(10) unsigned NOT NULL DEFAULT 0,
  `sku` varchar(64) NOT NULL DEFAULT '',
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sale_price` decimal(12,2) DEFAULT NULL,
  `stock_qty` int(11) NOT NULL DEFAULT 0,
  `weight` decimal(10,3) NOT NULL DEFAULT 0.000,
  `published` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `image_id` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_variant_value`
CREATE TABLE `{prefix}_product_variant_value` (
  `variant_id` int(10) unsigned NOT NULL DEFAULT 0,
  `attribute_id` int(10) unsigned NOT NULL DEFAULT 0,
  `attribute_value_id` int(10) unsigned NOT NULL DEFAULT 0,
  `module_id` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`variant_id`,`attribute_id`),
  KEY `attribute_value_id` (`attribute_value_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_cart`
CREATE TABLE `{prefix}_product_cart` (
  `id` int(10) unsigned NOT NULL,
  `module_id` int(10) unsigned NOT NULL DEFAULT 0,
  `member_id` int(11) NOT NULL DEFAULT 0,
  `guest_token` varchar(64) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `guest_token` (`guest_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_cart_item`
CREATE TABLE `{prefix}_product_cart_item` (
  `id` int(10) unsigned NOT NULL,
  `cart_id` int(10) unsigned NOT NULL DEFAULT 0,
  `module_id` int(10) unsigned NOT NULL DEFAULT 0,
  `product_id` int(10) unsigned NOT NULL DEFAULT 0,
  `variant_id` int(10) unsigned NOT NULL DEFAULT 0,
  `qty` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `cart_id` (`cart_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_order`
CREATE TABLE `{prefix}_product_order` (
  `id` int(10) unsigned NOT NULL,
  `module_id` int(10) unsigned NOT NULL DEFAULT 0,
  `order_no` varchar(20) NOT NULL DEFAULT '',
  `member_id` int(11) NOT NULL DEFAULT 0,
  `guest_token` varchar(64) NOT NULL DEFAULT '',
  `channel` enum('online','pos') NOT NULL DEFAULT 'online',
  `order_status` tinyint(2) unsigned NOT NULL DEFAULT 1,
  `payment_status` enum('unpaid','pending_verify','paid','failed') NOT NULL DEFAULT 'unpaid',
  `payment_method` enum('bank_transfer','cod','pos') NOT NULL DEFAULT 'bank_transfer',
  `payment_date` datetime DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cust_name` varchar(255) NOT NULL DEFAULT '',
  `cust_phone` varchar(50) NOT NULL DEFAULT '',
  `cust_email` varchar(255) NOT NULL DEFAULT '',
  `ship_address` varchar(255) NOT NULL DEFAULT '',
  `ship_address2` varchar(255) NOT NULL DEFAULT '',
  `ship_province` varchar(64) NOT NULL DEFAULT '',
  `ship_zipcode` varchar(10) NOT NULL DEFAULT '',
  `cust_tax_id` varchar(13) NOT NULL DEFAULT '',
  `cust_company` varchar(255) NOT NULL DEFAULT '',
  `shipping_method_id` int(10) unsigned NOT NULL DEFAULT 0,
  `note` mediumtext DEFAULT NULL,
  `created_by` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`),
  KEY `order_no` (`order_no`),
  KEY `member_id` (`member_id`),
  KEY `order_status` (`order_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_order_item`
-- ชื่อสินค้า/ราคา ณ วันสั่งซื้อ (snapshot) ไม่ผูกกับข้อมูลสินค้าปัจจุบัน
CREATE TABLE `{prefix}_product_order_item` (
  `id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned NOT NULL DEFAULT 0,
  `module_id` int(10) unsigned NOT NULL DEFAULT 0,
  `product_id` int(10) unsigned NOT NULL DEFAULT 0,
  `variant_id` int(10) unsigned NOT NULL DEFAULT 0,
  `product_name` varchar(255) NOT NULL DEFAULT '',
  `variant_label` varchar(255) NOT NULL DEFAULT '',
  `sku` varchar(64) NOT NULL DEFAULT '',
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `qty` int(11) NOT NULL DEFAULT 1,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cost_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_order_status_history`
CREATE TABLE `{prefix}_product_order_status_history` (
  `id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned NOT NULL DEFAULT 0,
  `module_id` int(10) unsigned NOT NULL DEFAULT 0,
  `order_status` tinyint(2) unsigned NOT NULL DEFAULT 0,
  `note` mediumtext DEFAULT NULL,
  `changed_by` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_payment`
CREATE TABLE `{prefix}_product_payment` (
  `id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned NOT NULL DEFAULT 0,
  `module_id` int(10) unsigned NOT NULL DEFAULT 0,
  `method` enum('bank_transfer','cod','pos') NOT NULL DEFAULT 'bank_transfer',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `slip_image` varchar(64) NOT NULL DEFAULT '',
  `bank_account` varchar(100) NOT NULL DEFAULT '',
  `paid_at` datetime DEFAULT NULL,
  `verified_by` int(11) NOT NULL DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `ref` varchar(64) NOT NULL DEFAULT '',
  `note` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_shipment`
CREATE TABLE `{prefix}_product_shipment` (
  `id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned NOT NULL DEFAULT 0,
  `module_id` int(10) unsigned NOT NULL DEFAULT 0,
  `shipping_method_id` int(10) unsigned NOT NULL DEFAULT 0,
  `tracking_no` varchar(64) NOT NULL DEFAULT '',
  `status` enum('preparing','shipped','delivered') NOT NULL DEFAULT 'preparing',
  `shipped_at` datetime DEFAULT NULL,
  `note` mediumtext DEFAULT NULL,
  `created_by` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_shipping_method`
CREATE TABLE `{prefix}_product_shipping_method` (
  `id` int(10) unsigned NOT NULL,
  `module_id` int(10) unsigned NOT NULL DEFAULT 0,
  `name` mediumtext NOT NULL,
  `calc_type` enum('flat','by_weight','free') NOT NULL DEFAULT 'flat',
  `base_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `rate_per_kg` decimal(12,2) NOT NULL DEFAULT 0.00,
  `free_over` decimal(12,2) DEFAULT NULL,
  `published` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `sort` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_stock_lot`
-- ต้นทางของยอดคงเหลือ (FIFO) product/product_variant.stock_qty เป็นแค่ค่าแคช
CREATE TABLE `{prefix}_product_stock_lot` (
  `id` int(10) unsigned NOT NULL,
  `module_id` int(10) unsigned NOT NULL DEFAULT 0,
  `product_id` int(10) unsigned NOT NULL DEFAULT 0,
  `variant_id` int(10) unsigned NOT NULL DEFAULT 0,
  `qty_in` int(11) NOT NULL DEFAULT 0,
  `qty_remaining` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `received_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ref` varchar(64) NOT NULL DEFAULT '',
  `note` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `variant_fifo` (`variant_id`,`qty_remaining`,`received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_product_stock_movement`
CREATE TABLE `{prefix}_product_stock_movement` (
  `id` int(10) unsigned NOT NULL,
  `module_id` int(10) unsigned NOT NULL DEFAULT 0,
  `product_id` int(10) unsigned NOT NULL DEFAULT 0,
  `variant_id` int(10) unsigned NOT NULL DEFAULT 0,
  `lot_id` int(10) unsigned NOT NULL DEFAULT 0,
  `type` enum('in','out','adjust','return') NOT NULL DEFAULT 'in',
  `qty` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ref_type` enum('order','pos','receipt','manual') NOT NULL DEFAULT 'manual',
  `ref_id` int(10) unsigned NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `variant_id` (`variant_id`),
  KEY `ref` (`ref_type`,`ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- แม่แบบอีเมลของโมดูล (ตัวปรับรุ่นเพิ่มเฉพาะ code + language ที่ยังไม่มี)
INSERT INTO `{prefix}_emailtemplate` (`module`, `email_id`, `code`, `language`, `from_email`, `copy_to`, `name`, `subject`, `detail`) VALUES ('product', 1, 'payment_notify', 'th', '', '', 'ตอบรับการแจ้งชำระเงิน', '[%WEBTITLE%] ได้รับแจ้งการชำระเงิน คำสั่งซื้อ %ORDER_NO%', '<div style="font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.8;color:#333;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #ddd"><div style="background:#3b5998;color:#fff;padding:12px 20px;font-size:16px;font-weight:bold">[%WEBTITLE%] ได้รับแจ้งการชำระเงิน คำสั่งซื้อ %ORDER_NO%</div><div style="padding:20px">เราได้รับแจ้งการชำระเงินสำหรับคำสั่งซื้อ <strong>%ORDER_NO%</strong> เรียบร้อยแล้ว<br><br>จำนวนเงิน : <strong>%PAID%</strong><br>ช่องทางการชำระเงิน : %PAYMENT_METHOD%<br>หมายเหตุ : %COMMENT%<br><br>ผู้ดูแลจะตรวจสอบการชำระเงิน แล้วแจ้งสถานะของคำสั่งซื้อให้ทราบอีกครั้ง<p style="margin:20px 0"><a href="%BUTTON_URL%" style="display:inline-block;background:#3b5998;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">%BUTTON_LABEL%</a></p></div><div style="padding:12px 20px;color:#999;font-size:12px;border-top:1px solid #eee">อีเมลนี้ส่งจากระบบอัตโนมัติของ <a href="%WEBURL%">%WEBTITLE%</a> เมื่อ %TIME% กรุณาอย่าตอบกลับ</div></div></div>');
INSERT INTO `{prefix}_emailtemplate` (`module`, `email_id`, `code`, `language`, `from_email`, `copy_to`, `name`, `subject`, `detail`) VALUES ('product', 1, 'payment_notify', 'en', '', '', 'Payment notification received', '[%WEBTITLE%] Payment notification received for order %ORDER_NO%', '<div style="font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.8;color:#333;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #ddd"><div style="background:#3b5998;color:#fff;padding:12px 20px;font-size:16px;font-weight:bold">[%WEBTITLE%] Payment notification received for order %ORDER_NO%</div><div style="padding:20px">We have received your payment notification for order <strong>%ORDER_NO%</strong>.<br><br>Amount : <strong>%PAID%</strong><br>Payment method : %PAYMENT_METHOD%<br>Note : %COMMENT%<br><br>The administrator will verify the payment and let you know the status of your order.<p style="margin:20px 0"><a href="%BUTTON_URL%" style="display:inline-block;background:#3b5998;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">%BUTTON_LABEL%</a></p></div><div style="padding:12px 20px;color:#999;font-size:12px;border-top:1px solid #eee">This email was sent automatically by <a href="%WEBURL%">%WEBTITLE%</a> on %TIME%. Please do not reply.</div></div></div>');
