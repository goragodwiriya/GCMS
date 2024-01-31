<?php exit;?>
CREATE TABLE IF NOT EXISTS `{prefix}_friends` (`id` int(11) NOT NULL auto_increment,`module_id` int(11) NOT NULL,`member_id` int(11) NOT NULL,`create_date` int(11) NOT NULL,`pin` tinyint(1) NOT NULL DEFAULT '0',`topic` varchar(255) collate utf8_unicode_ci NOT NULL,`province_id` tinyint(3) NOT NULL,`ip` varchar(50) collate utf8_unicode_ci NOT NULL,PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
DELETE FROM `{prefix}_language` WHERE `owner` = 'friends';
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`, `en`) VALUES ('Do not post inappropriate messages. Found guilty disabled members immediately.','text','friends','0','ห้ามโพสต์ข้อความไม่เหมาะสม พบเห็นการกระทำผิดระงับการใช้งานสมาชิกทันที','');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`, `en`) VALUES ('Fill out a short message','text','friends','0','กรอกข้อความสั้นๆ','');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`, `en`) VALUES ('Limit the number of posts per day (Zero means unlimited)','text','friends','0','จำกัดจำนวนการโพสต์ต่อวัน (0 หมายถึง ไม่จำกัด)','');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`, `en`) VALUES ('You can post a maximum of %COUNT% times per day','text','friends','0','สามารถโพสต์ได้ไม่เกิน %COUNT% ครั้ง ต่อวัน','');
