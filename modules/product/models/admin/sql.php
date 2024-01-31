<?php exit;?>
CREATE TABLE IF NOT EXISTS `{prefix}_product` (`id` int(11) NOT NULL auto_increment,`category_id` int(11) NOT NULL,`module_id` int(11) NOT NULL,`product_no` varchar(20) collate utf8_unicode_ci NOT NULL,`picture` varchar(20) collate utf8_unicode_ci NOT NULL,`alias` varchar(64) collate utf8_unicode_ci NOT NULL,`last_update` int(11) NOT NULL,`published` tinyint(1) NOT NULL DEFAULT '1',`visited` int(11) NOT NULL,PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE IF NOT EXISTS `{prefix}_product_detail` (`id` int(11) NOT NULL,`language` varchar(2) collate utf8_unicode_ci NOT NULL,`topic` text collate utf8_unicode_ci NOT NULL,`keywords` varchar(149) collate utf8_unicode_ci NOT NULL,`description` varchar(149) collate utf8_unicode_ci NOT NULL,`detail` text collate utf8_unicode_ci NOT NULL,PRIMARY KEY (`id`,`language`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE IF NOT EXISTS `{prefix}_product_price` (`id` int(11) NOT NULL auto_increment,`price` text collate utf8_unicode_ci NOT NULL,`net` text collate utf8_unicode_ci NOT NULL,PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DELETE FROM `{prefix}_language` WHERE `owner` = 'product';
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`, `en`) VALUES ('Contact information','text','product','0','ติดต่อสอบถาม','');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`, `en`) VALUES ('Images shown in the catalog of products','text','product','0','รูปภาพแสดงในหน้าแคตตาล็อกของสินค้า','');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`, `en`) VALUES ('Net Price','text','product','0','ราคาสุทธิ','');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`, `en`) VALUES ('Pictures displayed at the product details page','text','product','0','รูปภาพแสดงในหน้ารายละเอียดของสินค้า','');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`, `en`) VALUES ('Product','text','product','0','สินค้า','');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`, `en`) VALUES ('The full price of the product (Display only)','text','product','0','ราคาเต็มของสินค้า (ใช้แสดงผลเท่านั้น)','');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`, `en`) VALUES ('The net price of the product, after deducting discounts','text','product','0','ราคาสุทธิของสินค้า หลังจากหักส่วนลดแล้ว','');
