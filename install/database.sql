-- GCMS — โครงสร้างฐานข้อมูลของเว็บไซต์ลูก (tenant)
--
-- ไฟล์นี้เป็นต้นทางเพียงแหล่งเดียวของโครงสร้างฐานข้อมูล ใช้ร่วมกันทั้ง
--   • ติดตั้งไซต์ใหม่            install/step4.php
--   • สร้างไซต์ลูกจาก sys-admin  modules/sysadmin/models/customer.php
--   • อัปเกรดฐานข้อมูลของไซต์เดิม modules/sysadmin/models/upgrade.php, update.php
--
-- กติกาของไฟล์นี้
--   • โครงสร้างล้วน ห้ามมี INSERT — ข้อมูลเริ่มต้นแยกตาม package ให้ตัวติดตั้งจัดการ
--   • ชื่อตารางใช้ {prefix} เสมอ ตัวติดตั้ง/ตัวอัปเกรดจะแทนที่ด้วย prefix ของแต่ละไซต์
--   • ทุกตารางเป็น InnoDB / utf8mb4 / utf8mb4_general_ci
--   • ตารางกลาง (market_*) ไม่อยู่ในไฟล์นี้ เช่น emailtemplate, language, customer
--     เพราะอยู่คนละฐานข้อมูล (การเชื่อมต่อ wsr) ดู Gcms\Sysadmin\Model
--   • ตารางที่เลิกใช้จะถูกเปลี่ยนชื่อเป็น _{prefix}_ชื่อเดิม ไม่ลบทิ้ง
--     ดู scripts/sql/deprecate_tables.php
--
-- หมายเหตุ @section ใช้จัดกลุ่มตารางตามโมดูล ตัวติดตั้งสร้างทุกตารางเสมอ
-- (ตารางเปล่าใช้พื้นที่น้อยมาก) เพื่อให้เปิดใช้โมดูลใดก็ได้ภายหลังโดยไม่ต้องแก้ฐานข้อมูล

-- =============================================================
-- @section core  —  โครงสร้างหลัก ทุกไซต์ต้องมี
-- =============================================================

-- Table: `{prefix}_index`
CREATE TABLE `{prefix}_index` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `index` tinyint(1) NOT NULL DEFAULT 0,
  `module_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `language` varchar(2) NOT NULL DEFAULT '',
  `sender` varchar(50) DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `visited` int(11) NOT NULL DEFAULT 0,
  `visited_today` int(11) NOT NULL DEFAULT 0,
  `comments` smallint(3) NOT NULL DEFAULT 0,
  `comment_id` int(11) NOT NULL DEFAULT 0,
  `commentator` varchar(50) DEFAULT NULL,
  `commentator_id` int(11) NOT NULL DEFAULT 0,
  `comment_date` datetime DEFAULT NULL,
  `picture` mediumtext DEFAULT NULL,
  `can_reply` tinyint(1) NOT NULL DEFAULT 0,
  `show_news` mediumtext NOT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `published_date` date NOT NULL,
  `alias` varchar(255) DEFAULT NULL,
  `page` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`),
  KEY `index` (`index`),
  KEY `module_id` (`module_id`),
  KEY `idx_article_list` (`module_id`,`index`,`published`,`published_date`,`id`),
  KEY `idx_category` (`category_id`,`module_id`,`published`,`published_date`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_index_detail`
CREATE TABLE `{prefix}_index_detail` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `language` varchar(2) NOT NULL,
  `topic` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `detail` mediumtext NOT NULL,
  `keywords` varchar(255) NOT NULL,
  PRIMARY KEY (`id`,`language`) USING BTREE,
  KEY `module_id` (`module_id`),
  FULLTEXT KEY `topic` (`topic`,`detail`),
  FULLTEXT KEY `topic_2` (`topic`),
  FULLTEXT KEY `detail` (`detail`),
  FULLTEXT KEY `ft_search` (`topic`,`description`,`detail`,`keywords`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_index_tag`
CREATE TABLE `{prefix}_index_tag` (
  `index_id` int(11) NOT NULL,
  `tag` varchar(64) NOT NULL,
  PRIMARY KEY (`index_id`,`tag`) USING BTREE,
  KEY `idx_tag` (`tag`,`index_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_category`
CREATE TABLE `{prefix}_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `config` mediumtext NOT NULL,
  `topic` mediumtext NOT NULL,
  `detail` mediumtext NOT NULL,
  `icon` mediumtext NOT NULL,
  `published` enum('0','1') NOT NULL DEFAULT '1',
  `type` varchar(20) NOT NULL,
  `language` varchar(2) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `type` (`module_id`,`type`,`category_id`) USING BTREE,
  KEY `category_id` (`category_id`),
  KEY `module_id` (`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_comment`
CREATE TABLE `{prefix}_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `index_id` int(11) NOT NULL,
  `detail` mediumtext NOT NULL,
  `sender` varchar(50) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `ip` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `detail` (`detail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_tags`
CREATE TABLE `{prefix}_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` mediumtext NOT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag` (`tag`) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_menus`
CREATE TABLE `{prefix}_menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `index_id` int(11) NOT NULL,
  `level` smallint(2) NOT NULL,
  `language` varchar(2) NOT NULL,
  `menu_text` varchar(100) NOT NULL,
  `menu_tooltip` varchar(100) NOT NULL,
  `accesskey` varchar(1) NOT NULL,
  `menu_order` int(11) NOT NULL,
  `menu_url` varchar(255) NOT NULL,
  `menu_target` varchar(6) NOT NULL,
  `alias` varchar(20) NOT NULL,
  `published` enum('0','1','2','3') NOT NULL DEFAULT '1',
  `icon` varchar(20) DEFAULT NULL,
  `parent` enum('0_MAINMENU','1_SIDEMENU','2_BOTTOMMENU','') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lang_parent_order` (`language`,`menu_order`),
  KEY `parent` (`menu_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_modules`
CREATE TABLE `{prefix}_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner` varchar(20) NOT NULL,
  `module` varchar(64) NOT NULL,
  `config` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `owner` (`owner`),
  KEY `module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_user`
CREATE TABLE `{prefix}_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `password` varchar(255) NOT NULL,
  `token` varchar(512) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `sex` varchar(1) NOT NULL,
  `username` varchar(50) NOT NULL,
  `salt` varchar(32) NOT NULL,
  `id_card` varchar(13) NOT NULL,
  `birthday` date NOT NULL,
  `website` varchar(255) NOT NULL,
  `company` varchar(64) NOT NULL,
  `icon` varchar(24) NOT NULL,
  `visited` int(11) NOT NULL,
  `pm` mediumtext NOT NULL,
  `unread` mediumtext NOT NULL,
  `lastvisited` int(11) NOT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `ban` int(11) NOT NULL,
  `ban_count` int(11) NOT NULL,
  `point` int(11) NOT NULL,
  `post` int(11) NOT NULL,
  `reply` int(11) NOT NULL,
  `address` varchar(64) NOT NULL,
  `address2` varchar(64) NOT NULL,
  `provinceID` smallint(3) NOT NULL,
  `province` varchar(64) NOT NULL,
  `zipcode` varchar(5) NOT NULL,
  `country` varchar(2) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `phone2` varchar(20) NOT NULL,
  `activatecode` varchar(64) DEFAULT NULL,
  `status` tinyint(1) NOT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `tax_id` varchar(13) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `permission` mediumtext DEFAULT NULL,
  `line_uid` varchar(33) DEFAULT NULL,
  `notification` varchar(163) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL,
  `token_version` int(11) NOT NULL,
  `displayname` varchar(50) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `phone1` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `telegram_id` varchar(20) DEFAULT NULL,
  `social` enum('user','facebook','google','line','telegram') DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `line_uid` (`line_uid`),
  KEY `username` (`username`),
  KEY `phone` (`phone`),
  KEY `id_card` (`id_card`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_user_meta`
-- Key/value extras of a member (department, signature, ...). Index\Auth\Model
-- LEFT JOINs this table on every login / getUserById / getUserByToken, so it
-- must exist in every tenant database even when it holds no rows — a missing
-- table fails the whole query and nobody on that site can log in.
CREATE TABLE `{prefix}_user_meta` (
  `value` varchar(10) NOT NULL,
  `name` varchar(20) NOT NULL,
  `member_id` int(11) NOT NULL,
  KEY `member_id` (`member_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_user_session`
-- Registry of open sessions. One account may hold several rows, which is what
-- allows logging in from several devices at once.
-- Index\Auth\Model::getUserByToken() decides from this table whether a token is
-- still valid, so it must always exist — without it nobody can log in at all.
CREATE TABLE `{prefix}_user_session` (
  `sid` varchar(32) NOT NULL,
  `member_id` int(11) NOT NULL,
  `expires_at` int(11) NOT NULL DEFAULT 0,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `fingerprint` varchar(64) DEFAULT NULL,
  `last_event` varchar(20) DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  PRIMARY KEY (`sid`),
  KEY `member_id` (`member_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_login_attempt`
-- Failed login counter (Gcms\LoginAttempt), the brute-force guard.
-- Without this table every method swallows its error into error_log and
-- isLocked() returns false, which silently disables brute-force protection
-- site-wide with no warning of any kind.
CREATE TABLE `{prefix}_login_attempt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL DEFAULT '',
  `ip_address` varchar(45) NOT NULL DEFAULT '',
  `user_agent` varchar(500) NOT NULL DEFAULT '',
  `attempted_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`,`attempted_at`),
  KEY `idx_user_time` (`username`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_logs`
CREATE TABLE `{prefix}_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `src_id` int(11) NOT NULL,
  `module` varchar(20) NOT NULL,
  `action` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `reason` mediumtext DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `topic` mediumtext NOT NULL,
  `datas` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `src_id` (`src_id`),
  KEY `module` (`module`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_counter`
CREATE TABLE `{prefix}_counter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `counter` int(11) NOT NULL,
  `visited` int(11) NOT NULL,
  `pages_view` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_textlink`
-- Widget Textlinks: กลุ่มเมนูข้อความ/เมนูรูปภาพ (widgets/textlinks)
-- ชื่อกลุ่มคือคอลัมน์ `name` เลือกกลุ่มที่จะแสดงตอนแทรก widget ลง section
CREATE TABLE `{prefix}_textlink` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `type` varchar(11) NOT NULL DEFAULT 'text',
  `text` mediumtext DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `url` mediumtext DEFAULT NULL,
  `target` varchar(6) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `width` int(11) NOT NULL DEFAULT 0,
  `height` int(11) NOT NULL DEFAULT 0,
  `published` smallint(1) NOT NULL DEFAULT 1,
  `link_order` smallint(2) NOT NULL DEFAULT 0,
  `publish_start` int(11) NOT NULL DEFAULT 0,
  `publish_end` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`,`published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- @section module:board  —  โมดูลกระดานข่าว/เว็บบอร์ด
-- =============================================================

-- Table: `{prefix}_board_q`
CREATE TABLE `{prefix}_board_q` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `sender` varchar(50) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `visited` smallint(6) DEFAULT NULL,
  `comments` smallint(3) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `commentator` varchar(50) DEFAULT NULL,
  `commentator_id` int(11) DEFAULT NULL,
  `comment_date` datetime DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `can_reply` tinyint(1) NOT NULL DEFAULT 1,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `pin` tinyint(1) NOT NULL DEFAULT 0,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `topic` varchar(64) NOT NULL,
  `detail` mediumtext NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `topic` (`topic`),
  FULLTEXT KEY `detail` (`detail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_board_r`
CREATE TABLE `{prefix}_board_r` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `index_id` int(11) NOT NULL,
  `detail` mediumtext NOT NULL,
  `sender` varchar(50) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `picture` mediumtext DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `detail` (`detail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- @section module:download  —  โมดูลดาวน์โหลด
-- =============================================================

-- Table: `{prefix}_download`
CREATE TABLE `{prefix}_download` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `detail` varchar(200) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `name` varchar(50) NOT NULL,
  `ext` varchar(5) NOT NULL,
  `size` int(50) NOT NULL,
  `file` varchar(255) NOT NULL,
  `downloads` int(11) NOT NULL,
  `reciever` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- @section module:video  —  โมดูลวิดีโอ
-- =============================================================

-- Table: `{prefix}_video`
CREATE TABLE `{prefix}_video` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `youtube` varchar(11) NOT NULL,
  `topic` mediumtext NOT NULL,
  `description` mediumtext NOT NULL,
  `views` int(11) NOT NULL,
  `last_update` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- @section module:event  —  โมดูลปฏิทินกิจกรรม
-- =============================================================

-- Table: `{prefix}_eventcalendar`
CREATE TABLE `{prefix}_eventcalendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `topic` varchar(64) NOT NULL,
  `detail` mediumtext NOT NULL,
  `description` varchar(149) NOT NULL,
  `keywords` varchar(149) NOT NULL,
  `member_id` int(11) unsigned NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `begin_date` datetime NOT NULL,
  `color` varchar(11) NOT NULL,
  `published` tinyint(1) unsigned NOT NULL,
  `published_date` date NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `last_update` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- @section module:gallery  —  โมดูลอัลบั้มภาพ
-- =============================================================

-- Table: `{prefix}_gallery_album`
CREATE TABLE `{prefix}_gallery_album` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `topic` varchar(255) NOT NULL,
  `detail` mediumtext NOT NULL,
  `last_update` int(11) NOT NULL,
  `count` int(11) NOT NULL,
  `visited` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `published_date` date DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_gallery_image`
CREATE TABLE `{prefix}_gallery_image` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `album_id` int(10) NOT NULL,
  `image` varchar(255) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `count` int(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_cover` (`album_id`,`count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- @section module:personnel  —  โมดูลบุคลากร
-- =============================================================

-- Table: `{prefix}_personnel`
CREATE TABLE `{prefix}_personnel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL DEFAULT '',
  `detail` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL,
  `picture` varchar(20) DEFAULT NULL,
  `order` tinyint(2) DEFAULT 0,
  `department` int(11) NOT NULL,
  `level` int(10) NOT NULL DEFAULT 0,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`),
  KEY `sort` (`level`),
  KEY `published` (`published`),
  KEY `department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- @section module:portfolio  —  โมดูลผลงาน
-- =============================================================

-- Table: `{prefix}_portfolio`
CREATE TABLE `{prefix}_portfolio` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `keywords` varchar(255) NOT NULL,
  `detail` mediumtext NOT NULL,
  `created_at` int(11) NOT NULL,
  `image` varchar(15) NOT NULL,
  `url` varchar(255) NOT NULL,
  `published` enum('0','1') NOT NULL DEFAULT '1',
  `visited` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- @section module:product  —  โมดูลร้านค้า (สินค้า ตะกร้า คำสั่งซื้อ สต็อก)
-- =============================================================
-- ไซต์รุ่นเก่ามีตาราง product/product_detail/product_price แบบเดิม
-- (product_no, picture, ราคาเก็บใน product_price) ตัวอัปเกรดย้ายข้อมูลให้
-- ดู productLegacyMigration() ใน modules/sysadmin/models/upgrade.php
-- ตารางอื่นนอกจาก product ใช้ DB::nextId() ไม่ใช่ AUTO_INCREMENT

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

-- =============================================================
-- @section module:aichat  —  ผู้ช่วย AI Chat
-- =============================================================

-- Table: `{prefix}_ai_chat_handoffs`
-- คิวส่งต่อแชทให้เจ้าหน้าที่ (Gcms\Chat\HandoffStore)
-- insert() ไม่ส่ง id มาเอง คอลัมน์ id จึงต้องเป็น AUTO_INCREMENT
CREATE TABLE `{prefix}_ai_chat_handoffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel` varchar(20) NOT NULL,
  `conversation_id` varchar(100) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `user_id` int(11) DEFAULT NULL,
  `requester_name` varchar(150) DEFAULT NULL,
  `requester_email` varchar(255) DEFAULT NULL,
  `requester_phone` varchar(50) DEFAULT NULL,
  `requester_username` varchar(100) DEFAULT NULL,
  `message` mediumtext NOT NULL,
  `history_json` mediumtext DEFAULT NULL,
  `source_json` mediumtext DEFAULT NULL,
  `notifications_json` mediumtext DEFAULT NULL,
  `requester_notifications_json` mediumtext DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `accepted_by` int(11) DEFAULT NULL,
  `accepted_by_name` varchar(150) DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `closed_by` int(11) DEFAULT NULL,
  `closed_by_name` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `channel` (`channel`),
  KEY `conversation_id` (`conversation_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_ai_chat_quick_answers`
-- ชุดคำตอบสำเร็จรูปของแชทบอท (Gcms\Chat\QuickAnswerRepository)
CREATE TABLE `{prefix}_ai_chat_quick_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `keywords` mediumtext NOT NULL,
  `match_mode` varchar(20) NOT NULL DEFAULT 'contains',
  `answer_text` mediumtext NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `published_sort` (`published`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- =============================================================
-- @section module:grade  —  ผลการเรียน สมุดคะแนน ทะเบียนนักเรียน
-- =============================================================
-- grade_student = ทะเบียนนักเรียน (ใช้ร่วมกับ elearning) เลขประชาชนเก็บเป็น
--   HMAC-SHA256 (key grade_id_key ใน datas/config/{prefix}.php) + 4 หลักท้ายเท่านั้น
-- grade_course  = รายวิชาที่ครูสร้างเอง 1 แถวต่อวิชา/กลุ่มเรียน/ภาค (teacher_id = เจ้าของ)
-- grade_score   = 1 แถวต่อนักเรียนต่อรายวิชา คอลัมน์คะแนนตามหน้าบันทึกผลการเรียนของ SGS
--   s = JSON {"s1":..,"s18":..}  คะแนนเต็มของแต่ละช่องอยู่ใน grade_course.structure
-- grade_log     = ประวัติการค้นหาผลของผู้ปกครอง ใช้นับการลองผิด (แยกจาก login_attempt)

-- Table: `{prefix}_grade_student`
CREATE TABLE `{prefix}_grade_student` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_code` varchar(20) NOT NULL DEFAULT '',
  `id_hash` char(64) NOT NULL,
  `id_last4` varchar(4) NOT NULL DEFAULT '',
  `id_type` tinyint(1) NOT NULL DEFAULT 0,
  `prefix` varchar(50) NOT NULL DEFAULT '',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL DEFAULT '',
  `birthday` date NOT NULL,
  `class` varchar(20) NOT NULL DEFAULT '',
  `room` varchar(10) NOT NULL DEFAULT '',
  `number` smallint(5) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_hash` (`id_hash`),
  KEY `student_code` (`student_code`),
  KEY `class_room` (`class`,`room`,`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_grade_term`
CREATE TABLE `{prefix}_grade_term` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` smallint(4) NOT NULL,
  `term` tinyint(1) NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  `entry_open` tinyint(1) NOT NULL DEFAULT 1,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `publish_at` datetime DEFAULT NULL,
  `live_scores` tinyint(1) NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_term` (`year`,`term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_grade_course`
CREATE TABLE `{prefix}_grade_course` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `term_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 1,
  `credit` decimal(3,1) NOT NULL DEFAULT 0.0,
  `hours` smallint(5) NOT NULL DEFAULT 0,
  `class` varchar(20) NOT NULL DEFAULT '',
  `group_name` varchar(50) NOT NULL DEFAULT '',
  `structure` text DEFAULT NULL,
  `scale` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `term_teacher` (`term_id`,`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_grade_score`
CREATE TABLE `{prefix}_grade_score` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class` varchar(20) NOT NULL DEFAULT '',
  `room` varchar(10) NOT NULL DEFAULT '',
  `number` smallint(5) NOT NULL DEFAULT 0,
  `s` text DEFAULT NULL,
  `mid` decimal(5,2) DEFAULT NULL,
  `final` decimal(5,2) DEFAULT NULL,
  `total` decimal(5,2) DEFAULT NULL,
  `percent` decimal(5,2) DEFAULT NULL,
  `grade` varchar(4) NOT NULL DEFAULT '',
  `grade_remedial` varchar(4) NOT NULL DEFAULT '',
  `grade_repeat` varchar(4) NOT NULL DEFAULT '',
  `remark` varchar(100) NOT NULL DEFAULT '',
  `attr` varchar(100) DEFAULT NULL,
  `rtw` varchar(100) DEFAULT NULL,
  `updated_by` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_student` (`course_id`,`student_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_grade_log`
CREATE TABLE `{prefix}_grade_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_hash` char(64) NOT NULL DEFAULT '',
  `ip` varchar(50) NOT NULL DEFAULT '',
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `hash_time` (`id_hash`,`created_at`),
  KEY `ip_time` (`ip`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- @section module:elearning  —  บทเรียนออนไลน์ แบบทดสอบ เกียรติบัตร
-- =============================================================
-- elearning_course      = รายวิชา/หลักสูตร (owner_id = ครูเจ้าของ) access 0=ทุกคน 1=นักเรียนในทะเบียน 2=สมาชิก
-- elearning_lesson      = บทเรียน (เนื้อหา HTML + วิดีโอ YouTube/Google Drive + ไฟล์แนบ)
-- elearning_quiz        = แบบทดสอบ kind 1=ก่อนเรียน 2=ระหว่างเรียน 3=หลังเรียน
-- elearning_question    = ข้อสอบ type 1=ตอบข้อเดียว 2=ถูก/ผิด 3=หลายคำตอบ; indicator = ตัวชี้วัด
-- elearning_learner     = ผู้เรียนต่อรายวิชา (student_id=grade_student.id, member_id=user.id, หรือบุคคลทั่วไป)
-- elearning_attempt     = การทำแบบทดสอบ 1 ครั้ง (served = ลำดับข้อ/ตัวเลือกที่ส่งให้ ตรวจคำตอบฝั่ง server)
-- elearning_progress    = บทเรียนที่เรียนจบแล้ว
-- elearning_certificate = เกียรติบัตรที่ออกแล้ว (cert_no ใช้ตรวจสอบสาธารณะ)

-- Table: `{prefix}_elearning_course`
CREATE TABLE `{prefix}_elearning_course` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `code` varchar(20) NOT NULL DEFAULT '',
  `class` varchar(20) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `cover` varchar(50) DEFAULT NULL,
  `access` tinyint(1) NOT NULL DEFAULT 0,
  `sequential` tinyint(1) NOT NULL DEFAULT 0,
  `cert_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `cert_rule` text DEFAULT NULL,
  `cert_template` text DEFAULT NULL,
  `grade_course_id` int(11) NOT NULL DEFAULT 0,
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`),
  KEY `owner_id` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_elearning_lesson`
CREATE TABLE `{prefix}_elearning_lesson` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `order` smallint(5) NOT NULL DEFAULT 0,
  `title` varchar(150) NOT NULL,
  `content` mediumtext DEFAULT NULL,
  `video_url` varchar(255) NOT NULL DEFAULT '',
  `attachments` text DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_order` (`course_id`,`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_elearning_quiz`
CREATE TABLE `{prefix}_elearning_quiz` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL DEFAULT 0,
  `kind` tinyint(1) NOT NULL DEFAULT 2,
  `title` varchar(150) NOT NULL,
  `settings` text DEFAULT NULL,
  `grade_column` varchar(10) NOT NULL DEFAULT '',
  `order` smallint(5) NOT NULL DEFAULT 0,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_elearning_question`
CREATE TABLE `{prefix}_elearning_question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `order` smallint(5) NOT NULL DEFAULT 0,
  `type` tinyint(1) NOT NULL DEFAULT 1,
  `question` text NOT NULL,
  `choices` text DEFAULT NULL,
  `answer` varchar(255) NOT NULL DEFAULT '',
  `explain` text DEFAULT NULL,
  `indicator` varchar(30) NOT NULL DEFAULT '',
  `score` decimal(4,1) NOT NULL DEFAULT 1.0,
  PRIMARY KEY (`id`),
  KEY `quiz_id` (`quiz_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_elearning_learner`
CREATE TABLE `{prefix}_elearning_learner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL DEFAULT 0,
  `member_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(150) NOT NULL,
  `org` varchar(150) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `token_hash` char(64) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_seen` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_student` (`course_id`,`student_id`),
  KEY `course_member` (`course_id`,`member_id`),
  KEY `token_hash` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_elearning_attempt`
CREATE TABLE `{prefix}_elearning_attempt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `learner_id` int(11) NOT NULL,
  `served` text NOT NULL,
  `answers` text DEFAULT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `total` decimal(6,2) DEFAULT NULL,
  `percent` decimal(5,2) DEFAULT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL,
  `ip` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `quiz_learner` (`quiz_id`,`learner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_elearning_progress`
CREATE TABLE `{prefix}_elearning_progress` (
  `learner_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `completed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`learner_id`,`lesson_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: `{prefix}_elearning_certificate`
CREATE TABLE `{prefix}_elearning_certificate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `learner_id` int(11) NOT NULL,
  `cert_no` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `percent` decimal(5,2) DEFAULT NULL,
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cert_no` (`cert_no`),
  KEY `course_learner` (`course_id`,`learner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
