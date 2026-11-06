-- ---------------------------------------------------------------------------
-- install/database.sql — ตารางของ GCMS (ติดตั้งแบบเว็บไซต์เดี่ยว)
--
-- ใช้ร่วมกันทั้งการติดตั้งใหม่ (install/step5.php, install/cli-fresh.php) และ
-- การปรับรุ่น (install/upgrade2.php) — ตัวปรับรุ่นอ่านนิยามตารางจากไฟล์นี้
-- แล้วปรับฐานเดิมให้ตรง จึงห้ามมีนิยามตารางเดียวกันสองชุด
--
-- ลำดับที่ตัวติดตั้งรัน : install/core.sql → ไฟล์นี้ →
-- modules/*/install/database.sql (ดู schemaFiles() ใน install/common.php)
-- ข้อมูลตัวอย่างของแต่ละประเภทเว็บไซต์อยู่ที่ install/seeds/<type>/seed.sql ไม่ใช่ที่นี่
--
-- กติกาของไฟล์นี้
--   • ตารางแกน (user, user_meta, user_session, login_attempt, logs, language,
--     number, migration) อยู่ใน install/core.sql ห้ามนิยามซ้ำที่นี่
--   • ยกเว้น category — GCMS ใช้หมวดหมู่แบบของตัวเอง (id, module_id, config,
--     icon, published) คนละแบบกับ core.sql นิยามข้างล่างจึง "ทับ" ของ core.sql
--     (ตัวติดตั้งใส่ DROP TABLE IF EXISTS ก่อนทุก CREATE ตัวที่อยู่หลังจึงชนะ
--     และตัวปรับรุ่นใช้นิยามตัวสุดท้ายของแต่ละตารางเช่นกัน)
--   • ชื่อตารางใช้ {prefix} เสมอ · InnoDB / utf8mb4 ทุกตาราง
--   • หนึ่งคำสั่งต่อหนึ่งบรรทัดสำหรับ INSERT (ห้ามขึ้นบรรทัดใหม่ในข้อความ)
--     เพราะ sqlCommands() แยกคำสั่งด้วย ";\n"
--   • ห้ามใช้ UNIQUE ... USING HASH กับคอลัมน์ TEXT (ใช้ได้เฉพาะ MariaDB
--     MySQL 8 ติดตั้งไม่ผ่าน)
-- ---------------------------------------------------------------------------

-- ---------------------------------------------------------------------------
-- เนื้อหาหลัก (โมดูล index / document)
-- ---------------------------------------------------------------------------
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

-- หมวดหมู่ของทุกโมดูล — topic/detail/icon/config เก็บเป็น JSON แยกตามภาษา
-- type = 'category' (document, board ...) หรือ 'department' (personnel)
-- module_id = 0 คือหมวดระดับเว็บไซต์ (Gcms\Category เช่นแผนกของสมาชิก) ซึ่งเก็บ
-- แถวละภาษาได้ UNIQUE จึงรวม language ด้วย (หมวดของโมดูลใช้ language = '' เสมอ)
-- ⚠️ ทับนิยาม category ของ install/core.sql (ดูหัวไฟล์)
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
  UNIQUE KEY `type` (`module_id`,`type`,`category_id`,`language`) USING BTREE,
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
  `count` int(11) NOT NULL DEFAULT 0,
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

-- config เป็น JSON (ระบบรุ่นเก่าเก็บเป็น PHP serialize ตัวปรับรุ่นแปลงให้)
CREATE TABLE `{prefix}_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner` varchar(20) NOT NULL,
  `module` varchar(64) NOT NULL,
  `config` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `owner` (`owner`),
  KEY `module` (`module`)
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
-- ไฟล์โลโก้ (`logo`) อยู่ที่ DATA_FOLDER/image/ ที่เดียวกับ GCMS 11 · publish_start/publish_end
-- เป็น UNIX timestamp (0 = ไม่กำหนดขอบเขต)
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

-- แม่แบบอีเมล — Gcms\EmailTemplate::get() ค้นด้วย code + language
-- ตัวแปรในข้อความเขียนแบบ %NAME% (ดู Gcms\EmailTemplate::render())
CREATE TABLE `{prefix}_emailtemplate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(20) NOT NULL,
  `email_id` int(11) NOT NULL DEFAULT 0,
  `code` varchar(20) NOT NULL DEFAULT '',
  `language` varchar(2) NOT NULL,
  `from_email` mediumtext NOT NULL,
  `copy_to` mediumtext NOT NULL,
  `name` mediumtext NOT NULL,
  `subject` mediumtext NOT NULL,
  `detail` mediumtext NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `code` (`code`,`language`),
  KEY `module` (`module`,`email_id`)
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

-- ---------------------------------------------------------------------------
-- โมดูล event — ปฏิทินกิจกรรม
-- โค้ดเรียกตารางนี้ว่า 'event' ผ่านการแมปใน settings/database.php
-- ('tables' => ['event' => 'eventcalendar'])
-- ---------------------------------------------------------------------------
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

-- ---------------------------------------------------------------------------
-- โมดูล gallery — รูปอยู่ที่ DATA_FOLDER/gallery/{album_id}/{image}
-- ---------------------------------------------------------------------------
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

-- ---------------------------------------------------------------------------
-- โมดูล personnel — department = category.category_id ของหมวด type 'department'
-- ---------------------------------------------------------------------------
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

-- ---------------------------------------------------------------------------
-- โมดูล portfolio — id มาจาก DB::nextId() (AUTO_INCREMENT ไว้กันพลาด)
-- ---------------------------------------------------------------------------
CREATE TABLE `{prefix}_portfolio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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

-- ---------------------------------------------------------------------------
-- ข้อมูลเริ่มต้น : แม่แบบอีเมลที่ Index\Email\Model ส่ง
-- (สร้างจากสคริปต์ ห้ามขึ้นบรรทัดใหม่ในข้อความ — ตัวปรับรุ่นเพิ่มเฉพาะ
--  code + language ที่ไซต์ยังไม่มี ไม่ทับแม่แบบที่ผู้ดูแลแก้ไว้)
-- ---------------------------------------------------------------------------
INSERT INTO `{prefix}_emailtemplate` (`module`, `email_id`, `code`, `language`, `from_email`, `copy_to`, `name`, `subject`, `detail`) VALUES ('member', 11, 'registration', 'th', '', '', 'ตอบรับการสมัครสมาชิก', 'ยินดีต้อนรับสู่ %WEBTITLE%', '<div style="font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.8;color:#333;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #ddd"><div style="background:#3b5998;color:#fff;padding:12px 20px;font-size:16px;font-weight:bold">ยินดีต้อนรับสู่ %WEBTITLE%</div><div style="padding:20px">เรียนคุณ %NAME%<br><br>ขอบคุณที่สมัครสมาชิกกับเรา บัญชีของคุณถูกสร้างเรียบร้อยแล้ว<br><br>ชื่อผู้ใช้ : <strong>%USERNAME%</strong><br>รหัสผ่าน : <strong>%PASSWORD%</strong><p style="margin:20px 0"><a href="%BUTTON_URL%" style="display:inline-block;background:#3b5998;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">%BUTTON_LABEL%</a></p>ถ้าระบบกำหนดให้ยืนยันอีเมล กรุณาคลิกปุ่มด้านบนเพื่อยืนยันก่อนเข้าระบบ</div><div style="padding:12px 20px;color:#999;font-size:12px;border-top:1px solid #eee">อีเมลนี้ส่งจากระบบอัตโนมัติของ <a href="%WEBURL%">%WEBTITLE%</a> เมื่อ %TIME% กรุณาอย่าตอบกลับ</div></div></div>');
INSERT INTO `{prefix}_emailtemplate` (`module`, `email_id`, `code`, `language`, `from_email`, `copy_to`, `name`, `subject`, `detail`) VALUES ('member', 11, 'registration', 'en', '', '', 'Welcome new member', 'Welcome to %WEBTITLE%', '<div style="font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.8;color:#333;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #ddd"><div style="background:#3b5998;color:#fff;padding:12px 20px;font-size:16px;font-weight:bold">Welcome to %WEBTITLE%</div><div style="padding:20px">Dear %NAME%<br><br>Thank you for signing up. Your account has been created.<br><br>Username : <strong>%USERNAME%</strong><br>Password : <strong>%PASSWORD%</strong><p style="margin:20px 0"><a href="%BUTTON_URL%" style="display:inline-block;background:#3b5998;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">%BUTTON_LABEL%</a></p>If email verification is required, please click the button above before logging in.</div><div style="padding:12px 20px;color:#999;font-size:12px;border-top:1px solid #eee">This email was sent automatically by <a href="%WEBURL%">%WEBTITLE%</a> on %TIME%. Please do not reply.</div></div></div>');
INSERT INTO `{prefix}_emailtemplate` (`module`, `email_id`, `code`, `language`, `from_email`, `copy_to`, `name`, `subject`, `detail`) VALUES ('member', 12, 'activation', 'th', '', '', 'ยืนยันที่อยู่อีเมล', 'ยืนยันอีเมลของคุณที่ %WEBTITLE%', '<div style="font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.8;color:#333;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #ddd"><div style="background:#3b5998;color:#fff;padding:12px 20px;font-size:16px;font-weight:bold">ยืนยันอีเมลของคุณที่ %WEBTITLE%</div><div style="padding:20px">เรียนคุณ %NAME%<br><br>กรุณายืนยันที่อยู่อีเมลของคุณโดยคลิกปุ่มด้านล่าง<p style="margin:20px 0"><a href="%ACTIVATE_URL%" style="display:inline-block;background:#3b5998;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">ยืนยันอีเมล</a></p>หรือคัดลอกลิงก์นี้ไปเปิดในเบราว์เซอร์ <a href="%ACTIVATE_URL%">%ACTIVATE_URL%</a></div><div style="padding:12px 20px;color:#999;font-size:12px;border-top:1px solid #eee">อีเมลนี้ส่งจากระบบอัตโนมัติของ <a href="%WEBURL%">%WEBTITLE%</a> เมื่อ %TIME% กรุณาอย่าตอบกลับ</div></div></div>');
INSERT INTO `{prefix}_emailtemplate` (`module`, `email_id`, `code`, `language`, `from_email`, `copy_to`, `name`, `subject`, `detail`) VALUES ('member', 12, 'activation', 'en', '', '', 'Verify email address', 'Verify your email at %WEBTITLE%', '<div style="font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.8;color:#333;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #ddd"><div style="background:#3b5998;color:#fff;padding:12px 20px;font-size:16px;font-weight:bold">Verify your email at %WEBTITLE%</div><div style="padding:20px">Dear %NAME%<br><br>Please verify your email address by clicking the button below.<p style="margin:20px 0"><a href="%ACTIVATE_URL%" style="display:inline-block;background:#3b5998;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">Verify email</a></p>Or copy this link into your browser <a href="%ACTIVATE_URL%">%ACTIVATE_URL%</a></div><div style="padding:12px 20px;color:#999;font-size:12px;border-top:1px solid #eee">This email was sent automatically by <a href="%WEBURL%">%WEBTITLE%</a> on %TIME%. Please do not reply.</div></div></div>');
INSERT INTO `{prefix}_emailtemplate` (`module`, `email_id`, `code`, `language`, `from_email`, `copy_to`, `name`, `subject`, `detail`) VALUES ('member', 13, 'account_approved', 'th', '', '', 'บัญชีได้รับการอนุมัติ', 'บัญชีของคุณที่ %WEBTITLE% ได้รับการอนุมัติแล้ว', '<div style="font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.8;color:#333;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #ddd"><div style="background:#3b5998;color:#fff;padding:12px 20px;font-size:16px;font-weight:bold">บัญชีของคุณที่ %WEBTITLE% ได้รับการอนุมัติแล้ว</div><div style="padding:20px">เรียนคุณ %NAME%<br><br>บัญชีของคุณได้รับการอนุมัติเรียบร้อยแล้ว สามารถเข้าระบบได้ทันที<p style="margin:20px 0"><a href="%LOGIN_URL%" style="display:inline-block;background:#3b5998;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">เข้าระบบ</a></p></div><div style="padding:12px 20px;color:#999;font-size:12px;border-top:1px solid #eee">อีเมลนี้ส่งจากระบบอัตโนมัติของ <a href="%WEBURL%">%WEBTITLE%</a> เมื่อ %TIME% กรุณาอย่าตอบกลับ</div></div></div>');
INSERT INTO `{prefix}_emailtemplate` (`module`, `email_id`, `code`, `language`, `from_email`, `copy_to`, `name`, `subject`, `detail`) VALUES ('member', 13, 'account_approved', 'en', '', '', 'Account approved', 'Your account at %WEBTITLE% has been approved', '<div style="font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.8;color:#333;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #ddd"><div style="background:#3b5998;color:#fff;padding:12px 20px;font-size:16px;font-weight:bold">Your account at %WEBTITLE% has been approved</div><div style="padding:20px">Dear %NAME%<br><br>Your account has been approved. You can log in now.<p style="margin:20px 0"><a href="%LOGIN_URL%" style="display:inline-block;background:#3b5998;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">Log in</a></p></div><div style="padding:12px 20px;color:#999;font-size:12px;border-top:1px solid #eee">This email was sent automatically by <a href="%WEBURL%">%WEBTITLE%</a> on %TIME%. Please do not reply.</div></div></div>');
INSERT INTO `{prefix}_emailtemplate` (`module`, `email_id`, `code`, `language`, `from_email`, `copy_to`, `name`, `subject`, `detail`) VALUES ('member', 14, 'password_reset', 'th', '', '', 'ขอรหัสผ่านใหม่', '[%WEBTITLE%] ขอรหัสผ่านใหม่', '<div style="font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.8;color:#333;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #ddd"><div style="background:#3b5998;color:#fff;padding:12px 20px;font-size:16px;font-weight:bold">[%WEBTITLE%] ขอรหัสผ่านใหม่</div><div style="padding:20px">มีการขอตั้งรหัสผ่านใหม่สำหรับบัญชี <strong>%EMAIL%</strong><br><br>คลิกปุ่มด้านล่างเพื่อตั้งรหัสผ่านใหม่ ลิงก์นี้ใช้ได้ภายใน %EXPIRY_MINUTES% นาที<p style="margin:20px 0"><a href="%RESET_URL%" style="display:inline-block;background:#3b5998;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">ตั้งรหัสผ่านใหม่</a></p>ถ้าคุณไม่ได้ขอตั้งรหัสผ่านใหม่ ไม่ต้องทำอะไร รหัสผ่านเดิมยังใช้ได้ตามปกติ</div><div style="padding:12px 20px;color:#999;font-size:12px;border-top:1px solid #eee">อีเมลนี้ส่งจากระบบอัตโนมัติของ <a href="%WEBURL%">%WEBTITLE%</a> เมื่อ %TIME% กรุณาอย่าตอบกลับ</div></div></div>');
INSERT INTO `{prefix}_emailtemplate` (`module`, `email_id`, `code`, `language`, `from_email`, `copy_to`, `name`, `subject`, `detail`) VALUES ('member', 14, 'password_reset', 'en', '', '', 'Password reset request', '[%WEBTITLE%] Password reset request', '<div style="font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.8;color:#333;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #ddd"><div style="background:#3b5998;color:#fff;padding:12px 20px;font-size:16px;font-weight:bold">[%WEBTITLE%] Password reset request</div><div style="padding:20px">A password reset was requested for <strong>%EMAIL%</strong><br><br>Click the button below to set a new password. This link expires in %EXPIRY_MINUTES% minutes.<p style="margin:20px 0"><a href="%RESET_URL%" style="display:inline-block;background:#3b5998;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">Reset password</a></p>If you did not request this, you can ignore this email. Your current password still works.</div><div style="padding:12px 20px;color:#999;font-size:12px;border-top:1px solid #eee">This email was sent automatically by <a href="%WEBURL%">%WEBTITLE%</a> on %TIME%. Please do not reply.</div></div></div>');
INSERT INTO `{prefix}_emailtemplate` (`module`, `email_id`, `code`, `language`, `from_email`, `copy_to`, `name`, `subject`, `detail`) VALUES ('member', 15, 'admin_new_member', 'th', '', '', 'แจ้งผู้ดูแลเมื่อมีสมาชิกใหม่', '[%WEBTITLE%] มีสมาชิกใหม่รอการอนุมัติ', '<div style="font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.8;color:#333;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #ddd"><div style="background:#3b5998;color:#fff;padding:12px 20px;font-size:16px;font-weight:bold">[%WEBTITLE%] มีสมาชิกใหม่รอการอนุมัติ</div><div style="padding:20px">มีผู้สมัครสมาชิกใหม่ที่ %WEBTITLE% รอการตรวจสอบและอนุมัติ<p style="margin:20px 0"><a href="%ADMIN_URL%" style="display:inline-block;background:#3b5998;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">ตรวจสอบสมาชิก</a></p></div><div style="padding:12px 20px;color:#999;font-size:12px;border-top:1px solid #eee">อีเมลนี้ส่งจากระบบอัตโนมัติของ <a href="%WEBURL%">%WEBTITLE%</a> เมื่อ %TIME% กรุณาอย่าตอบกลับ</div></div></div>');
INSERT INTO `{prefix}_emailtemplate` (`module`, `email_id`, `code`, `language`, `from_email`, `copy_to`, `name`, `subject`, `detail`) VALUES ('member', 15, 'admin_new_member', 'en', '', '', 'New member notification (admin)', '[%WEBTITLE%] New member awaiting approval', '<div style="font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.8;color:#333;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #ddd"><div style="background:#3b5998;color:#fff;padding:12px 20px;font-size:16px;font-weight:bold">[%WEBTITLE%] New member awaiting approval</div><div style="padding:20px">A new member has signed up at %WEBTITLE% and is awaiting approval.<p style="margin:20px 0"><a href="%ADMIN_URL%" style="display:inline-block;background:#3b5998;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">Review members</a></p></div><div style="padding:12px 20px;color:#999;font-size:12px;border-top:1px solid #eee">This email was sent automatically by <a href="%WEBURL%">%WEBTITLE%</a> on %TIME%. Please do not reply.</div></div></div>');
