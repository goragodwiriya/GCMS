-- ---------------------------------------------------------------------------
-- E-Document — ตารางของโมดูล edocument (ระบบส่งเอกสารอิเล็กทรอนิกส์)
--
-- โครงสร้างเดียวกับตารางของ GCMS รุ่นเดิมทุกคอลัมน์ ไซต์ที่ย้ายมาจึงใช้ตารางเดิม
-- ได้ทันทีโดยไม่ต้องแปลงข้อมูล
--
-- edocument.reciever  = สถานะสมาชิกที่ดาวน์โหลดได้ เป็น JSON เช่น [-1,0]
--                       (แถวเก่าอาจเป็น PHP serialize ดู Edocument\Setup\Model::parseReciever)
-- edocument.file      = ชื่อไฟล์ใน DATA_FOLDER/edocument/ (varchar 20)
-- edocument_download  = ประวัติการดาวน์โหลด หนึ่งแถวต่อเอกสารต่อสมาชิก
--                       ผู้มาเยือนทุกคนใช้แถวเดียวกัน (member_id = 0)
-- ---------------------------------------------------------------------------

CREATE TABLE `{prefix}_edocument` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(11) unsigned NOT NULL,
  `sender_id` int(11) unsigned NOT NULL,
  `reciever` text NOT NULL,
  `last_update` int(11) unsigned NOT NULL,
  `downloads` int(11) unsigned NOT NULL,
  `document_no` varchar(20) NOT NULL,
  `detail` text NOT NULL,
  `topic` varchar(50) NOT NULL,
  `ext` varchar(4) NOT NULL,
  `size` double unsigned NOT NULL,
  `file` varchar(20) NOT NULL,
  `ip` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `{prefix}_edocument_download` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(10) unsigned NOT NULL,
  `document_id` int(10) unsigned NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `downloads` int(10) unsigned NOT NULL,
  `last_update` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
