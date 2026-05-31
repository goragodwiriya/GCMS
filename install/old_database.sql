-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 14, 2026 at 09:02 PM
-- Server version: 10.4.34-MariaDB
-- PHP Version: 7.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `service_demo`
--

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_activities`
--

CREATE TABLE `{prefix}_activities` (
  `id` int(11) NOT NULL,
  `src_id` int(11) NOT NULL,
  `module` varchar(20) NOT NULL,
  `action` varchar(20) NOT NULL,
  `create_date` datetime NOT NULL,
  `reason` text DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `topic` text NOT NULL,
  `datas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_board_q`
--

CREATE TABLE `{prefix}_board_q` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `sender` varchar(50) NOT NULL,
  `member_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip` varchar(50) NOT NULL,
  `create_date` int(11) UNSIGNED NOT NULL,
  `last_update` int(11) NOT NULL,
  `visited` smallint(6) DEFAULT NULL,
  `comments` smallint(3) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `commentator` varchar(50) DEFAULT NULL,
  `commentator_id` int(11) DEFAULT NULL,
  `comment_date` int(11) DEFAULT NULL,
  `picture` text DEFAULT NULL,
  `pictureW` int(11) DEFAULT NULL,
  `pictureH` int(11) DEFAULT NULL,
  `hassubpic` smallint(3) DEFAULT NULL,
  `can_reply` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `published` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `pin` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `locked` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `related` varchar(149) DEFAULT NULL,
  `topic` varchar(64) NOT NULL,
  `detail` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_board_r`
--

CREATE TABLE `{prefix}_board_r` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `index_id` int(11) NOT NULL,
  `detail` text NOT NULL,
  `sender` varchar(50) NOT NULL,
  `member_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip` varchar(50) NOT NULL,
  `last_update` int(11) NOT NULL,
  `picture` text DEFAULT NULL,
  `pictureW` int(11) DEFAULT NULL,
  `pictureH` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_category`
--

CREATE TABLE `{prefix}_category` (
  `id` int(11) UNSIGNED NOT NULL,
  `module_id` int(11) UNSIGNED NOT NULL,
  `category_id` int(11) UNSIGNED NOT NULL,
  `group_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `config` text DEFAULT NULL,
  `c1` int(11) UNSIGNED DEFAULT NULL,
  `c2` int(11) UNSIGNED DEFAULT NULL,
  `topic` text NOT NULL,
  `detail` text DEFAULT NULL,
  `icon` text DEFAULT NULL,
  `published` enum('0','1') NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_comment`
--

CREATE TABLE `{prefix}_comment` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `index_id` int(11) NOT NULL,
  `detail` text NOT NULL,
  `sender` varchar(50) NOT NULL,
  `member_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip` varchar(50) NOT NULL,
  `last_update` int(11) NOT NULL,
  `picture` text DEFAULT NULL,
  `pictureW` int(11) DEFAULT NULL,
  `pictureH` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_counter`
--

CREATE TABLE `{prefix}_counter` (
  `id` int(11) NOT NULL,
  `counter` int(11) NOT NULL,
  `visited` int(11) NOT NULL,
  `pages_view` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `date` date NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_download`
--

CREATE TABLE `{prefix}_download` (
  `id` int(11) UNSIGNED NOT NULL,
  `module_id` int(11) UNSIGNED NOT NULL,
  `category_id` smallint(5) UNSIGNED DEFAULT NULL,
  `member_id` int(11) UNSIGNED NOT NULL,
  `detail` varchar(200) NOT NULL,
  `last_update` int(11) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `ext` varchar(5) NOT NULL,
  `size` int(11) UNSIGNED NOT NULL,
  `file` varchar(255) NOT NULL,
  `downloads` int(11) UNSIGNED NOT NULL,
  `reciever` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_edocument`
--

CREATE TABLE `{prefix}_edocument` (
  `id` int(11) UNSIGNED NOT NULL,
  `module_id` int(11) UNSIGNED NOT NULL,
  `sender_id` int(11) UNSIGNED NOT NULL,
  `reciever` text NOT NULL,
  `last_update` int(11) UNSIGNED NOT NULL,
  `downloads` int(11) UNSIGNED NOT NULL,
  `document_no` varchar(20) NOT NULL,
  `detail` text NOT NULL,
  `topic` varchar(50) NOT NULL,
  `ext` varchar(4) NOT NULL,
  `size` double UNSIGNED NOT NULL,
  `file` varchar(15) NOT NULL,
  `ip` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_edocument_download`
--

CREATE TABLE `{prefix}_edocument_download` (
  `id` int(10) UNSIGNED NOT NULL,
  `module_id` int(10) UNSIGNED NOT NULL,
  `document_id` int(10) UNSIGNED NOT NULL,
  `member_id` int(10) UNSIGNED NOT NULL,
  `downloads` int(10) UNSIGNED NOT NULL,
  `last_update` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_emailtemplate`
--

CREATE TABLE `{prefix}_emailtemplate` (
  `id` int(10) UNSIGNED NOT NULL,
  `module` varchar(20) NOT NULL,
  `email_id` int(10) UNSIGNED NOT NULL,
  `language` varchar(2) NOT NULL,
  `from_email` text NOT NULL,
  `copy_to` text NOT NULL,
  `name` text NOT NULL,
  `subject` text NOT NULL,
  `detail` text NOT NULL,
  `last_update` int(11) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_eventcalendar`
--

CREATE TABLE `{prefix}_eventcalendar` (
  `id` int(11) UNSIGNED NOT NULL,
  `module_id` int(11) UNSIGNED NOT NULL,
  `topic` varchar(64) NOT NULL,
  `detail` text NOT NULL,
  `description` varchar(149) NOT NULL,
  `keywords` varchar(149) NOT NULL,
  `member_id` int(11) UNSIGNED NOT NULL,
  `create_date` datetime DEFAULT NULL,
  `last_update` int(11) UNSIGNED NOT NULL,
  `begin_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `color` varchar(11) NOT NULL,
  `published` tinyint(1) UNSIGNED NOT NULL,
  `published_date` date DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_friends`
--

CREATE TABLE `{prefix}_friends` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `create_date` int(11) UNSIGNED NOT NULL,
  `pin` tinyint(1) NOT NULL DEFAULT 0,
  `topic` varchar(255) NOT NULL,
  `province_id` tinyint(3) UNSIGNED NOT NULL,
  `ip` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_gallery`
--

CREATE TABLE `{prefix}_gallery` (
  `id` int(11) UNSIGNED NOT NULL,
  `module_id` int(11) UNSIGNED NOT NULL,
  `album_id` int(11) UNSIGNED NOT NULL,
  `image` varchar(15) NOT NULL,
  `last_update` int(11) UNSIGNED NOT NULL,
  `count` int(11) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_gallery_album`
--

CREATE TABLE `{prefix}_gallery_album` (
  `id` int(11) UNSIGNED NOT NULL,
  `module_id` int(11) UNSIGNED NOT NULL,
  `topic` varchar(64) NOT NULL,
  `detail` varchar(200) NOT NULL,
  `last_update` int(11) UNSIGNED NOT NULL,
  `count` int(11) UNSIGNED NOT NULL,
  `visited` int(11) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_index`
--

CREATE TABLE `{prefix}_index` (
  `id` int(11) UNSIGNED NOT NULL,
  `index` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `module_id` int(11) UNSIGNED NOT NULL,
  `category_id` int(11) UNSIGNED DEFAULT NULL,
  `language` varchar(2) NOT NULL DEFAULT '',
  `sender` varchar(50) NOT NULL DEFAULT '',
  `member_id` int(11) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip` varchar(50) NOT NULL,
  `create_date` int(11) UNSIGNED NOT NULL,
  `last_update` int(11) UNSIGNED NOT NULL,
  `visited` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `visited_today` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `comments` smallint(3) UNSIGNED NOT NULL DEFAULT 0,
  `comment_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `commentator` varchar(50) DEFAULT NULL,
  `commentator_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `comment_date` int(11) DEFAULT NULL,
  `picture` text DEFAULT NULL,
  `pictureW` int(11) DEFAULT NULL,
  `pictureH` int(11) DEFAULT NULL,
  `hassubpic` smallint(3) DEFAULT NULL,
  `can_reply` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `show_news` text DEFAULT NULL,
  `published` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `pin` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `locked` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `published_date` date NOT NULL,
  `alias` varchar(64) DEFAULT NULL,
  `page` varchar(20) DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_index_detail`
--

CREATE TABLE `{prefix}_index_detail` (
  `id` int(11) UNSIGNED NOT NULL,
  `module_id` int(11) UNSIGNED NOT NULL,
  `language` varchar(2) NOT NULL,
  `topic` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `detail` text NOT NULL,
  `keywords` varchar(255) NOT NULL,
  `relate` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_language`
--

CREATE TABLE `{prefix}_language` (
  `id` int(11) UNSIGNED NOT NULL,
  `key` text NOT NULL,
  `ja` text DEFAULT NULL,
  `th` text DEFAULT NULL,
  `en` text DEFAULT NULL,
  `owner` varchar(20) NOT NULL,
  `type` varchar(5) NOT NULL,
  `js` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_logs`
--

CREATE TABLE `{prefix}_logs` (
  `time` datetime NOT NULL,
  `ip` varchar(32) NOT NULL,
  `session_id` varchar(32) NOT NULL,
  `referer` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `url` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_menus`
--

CREATE TABLE `{prefix}_menus` (
  `id` int(11) UNSIGNED NOT NULL,
  `index_id` int(11) UNSIGNED NOT NULL,
  `parent` varchar(20) NOT NULL,
  `level` smallint(2) UNSIGNED NOT NULL,
  `language` varchar(2) NOT NULL,
  `menu_text` varchar(100) NOT NULL,
  `menu_tooltip` varchar(100) NOT NULL,
  `accesskey` varchar(1) NOT NULL,
  `menu_order` int(11) UNSIGNED NOT NULL,
  `menu_url` varchar(255) NOT NULL,
  `menu_target` varchar(6) NOT NULL,
  `alias` varchar(20) NOT NULL,
  `published` enum('0','1','2','3') NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_modules`
--

CREATE TABLE `{prefix}_modules` (
  `id` int(11) UNSIGNED NOT NULL,
  `owner` varchar(20) NOT NULL,
  `module` varchar(64) NOT NULL,
  `config` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_personnel`
--

CREATE TABLE `{prefix}_personnel` (
  `id` int(11) UNSIGNED NOT NULL,
  `module_id` int(11) UNSIGNED NOT NULL,
  `category_id` int(11) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `position` varchar(100) NOT NULL,
  `detail` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `picture` varchar(15) NOT NULL,
  `order` tinyint(2) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_portfolio`
--

CREATE TABLE `{prefix}_portfolio` (
  `id` int(11) UNSIGNED NOT NULL,
  `module_id` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `keywords` varchar(255) NOT NULL,
  `detail` text NOT NULL,
  `create_date` int(11) NOT NULL,
  `image` varchar(20) NOT NULL,
  `url` varchar(255) NOT NULL,
  `published` enum('0','1') NOT NULL DEFAULT '1',
  `visited` int(11) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_product`
--

CREATE TABLE `{prefix}_product` (
  `id` int(11) UNSIGNED NOT NULL,
  `category_id` int(11) UNSIGNED NOT NULL,
  `module_id` int(11) UNSIGNED NOT NULL,
  `product_no` varchar(20) NOT NULL,
  `picture` varchar(20) NOT NULL,
  `alias` varchar(64) NOT NULL,
  `last_update` int(11) UNSIGNED NOT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `visited` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_product_detail`
--

CREATE TABLE `{prefix}_product_detail` (
  `id` int(11) UNSIGNED NOT NULL,
  `language` varchar(2) NOT NULL,
  `topic` text NOT NULL,
  `keywords` varchar(149) NOT NULL,
  `description` varchar(149) NOT NULL,
  `detail` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_product_price`
--

CREATE TABLE `{prefix}_product_price` (
  `id` int(11) UNSIGNED NOT NULL,
  `price` text NOT NULL,
  `net` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_tags`
--

CREATE TABLE `{prefix}_tags` (
  `id` int(11) NOT NULL,
  `tag` text NOT NULL,
  `count` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_textlink`
--

CREATE TABLE `{prefix}_textlink` (
  `id` int(11) NOT NULL,
  `text` text NOT NULL,
  `url` text NOT NULL,
  `publish_start` int(11) NOT NULL,
  `publish_end` int(11) NOT NULL,
  `logo` text NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `type` varchar(11) NOT NULL,
  `name` varchar(11) NOT NULL,
  `published` smallint(1) NOT NULL DEFAULT 1,
  `link_order` smallint(2) NOT NULL,
  `last_preview` int(11) UNSIGNED DEFAULT NULL,
  `description` varchar(49) NOT NULL,
  `template` text DEFAULT NULL,
  `target` varchar(6) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_user`
--

CREATE TABLE `{prefix}_user` (
  `id` int(11) UNSIGNED NOT NULL,
  `password` varchar(50) NOT NULL,
  `token` varchar(50) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `displayname` varchar(50) DEFAULT NULL,
  `sex` varchar(1) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `salt` varchar(32) NOT NULL,
  `id_card` varchar(13) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `company` varchar(64) DEFAULT NULL,
  `icon` varchar(24) DEFAULT NULL,
  `create_date` int(11) UNSIGNED NOT NULL,
  `visited` int(11) UNSIGNED DEFAULT NULL,
  `lastvisited` int(11) UNSIGNED DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `ban` int(11) DEFAULT NULL,
  `point` int(11) DEFAULT NULL,
  `post` int(11) UNSIGNED DEFAULT NULL,
  `reply` int(11) UNSIGNED DEFAULT NULL,
  `address` varchar(64) DEFAULT NULL,
  `address2` varchar(64) DEFAULT NULL,
  `provinceID` smallint(3) UNSIGNED DEFAULT NULL,
  `province` varchar(64) DEFAULT NULL,
  `zipcode` varchar(5) DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `phone2` varchar(20) DEFAULT NULL,
  `activatecode` varchar(32) NOT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL,
  `social` tinyint(4) NOT NULL,
  `session_id` varchar(32) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `permission` text DEFAULT NULL,
  `line_uid` varchar(33) DEFAULT NULL,
  `notification` varchar(200) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_useronline`
--

CREATE TABLE `{prefix}_useronline` (
  `member_id` int(11) NOT NULL,
  `displayname` varchar(50) DEFAULT NULL,
  `session` varchar(32) NOT NULL,
  `time` int(11) NOT NULL,
  `ip` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_video`
--

CREATE TABLE `{prefix}_video` (
  `id` int(11) UNSIGNED NOT NULL,
  `module_id` int(11) UNSIGNED NOT NULL,
  `youtube` varchar(11) NOT NULL,
  `topic` text NOT NULL,
  `description` text NOT NULL,
  `views` int(11) UNSIGNED NOT NULL,
  `last_update` int(11) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_widget`
--

CREATE TABLE `{prefix}_widget` (
  `id` int(10) UNSIGNED NOT NULL,
  `owner` varchar(20) NOT NULL,
  `module` varchar(20) NOT NULL,
  `topic` varchar(69) NOT NULL,
  `detail` text NOT NULL,
  `position` enum('xsidebar','content','sidebar','custom','disabled') NOT NULL,
  `config` text NOT NULL,
  `order` tinyint(2) UNSIGNED NOT NULL,
  `default` enum('1','0') NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `{prefix}_activities`
--
ALTER TABLE `{prefix}_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `src_id` (`src_id`),
  ADD KEY `module` (`module`),
  ADD KEY `action` (`action`);

--
-- Indexes for table `{prefix}_board_q`
--
ALTER TABLE `{prefix}_board_q`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `{prefix}_board_q` ADD FULLTEXT KEY `topic` (`topic`);
ALTER TABLE `{prefix}_board_q` ADD FULLTEXT KEY `detail` (`detail`);
ALTER TABLE `{prefix}_board_q` ADD FULLTEXT KEY `topic_2` (`topic`);
ALTER TABLE `{prefix}_board_q` ADD FULLTEXT KEY `detail_2` (`detail`);
ALTER TABLE `{prefix}_board_q` ADD FULLTEXT KEY `topic_3` (`topic`);
ALTER TABLE `{prefix}_board_q` ADD FULLTEXT KEY `detail_3` (`detail`);

--
-- Indexes for table `{prefix}_board_r`
--
ALTER TABLE `{prefix}_board_r`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `{prefix}_board_r` ADD FULLTEXT KEY `detail` (`detail`);
ALTER TABLE `{prefix}_board_r` ADD FULLTEXT KEY `detail_2` (`detail`);
ALTER TABLE `{prefix}_board_r` ADD FULLTEXT KEY `detail_3` (`detail`);

--
-- Indexes for table `{prefix}_category`
--
ALTER TABLE `{prefix}_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_comment`
--
ALTER TABLE `{prefix}_comment`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `{prefix}_comment` ADD FULLTEXT KEY `detail` (`detail`);
ALTER TABLE `{prefix}_comment` ADD FULLTEXT KEY `detail_2` (`detail`);
ALTER TABLE `{prefix}_comment` ADD FULLTEXT KEY `detail_3` (`detail`);

--
-- Indexes for table `{prefix}_counter`
--
ALTER TABLE `{prefix}_counter`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_download`
--
ALTER TABLE `{prefix}_download`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_edocument`
--
ALTER TABLE `{prefix}_edocument`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_edocument_download`
--
ALTER TABLE `{prefix}_edocument_download`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_emailtemplate`
--
ALTER TABLE `{prefix}_emailtemplate`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_eventcalendar`
--
ALTER TABLE `{prefix}_eventcalendar`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_friends`
--
ALTER TABLE `{prefix}_friends`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_gallery`
--
ALTER TABLE `{prefix}_gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_gallery_album`
--
ALTER TABLE `{prefix}_gallery_album`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_index`
--
ALTER TABLE `{prefix}_index`
  ADD PRIMARY KEY (`id`,`module_id`);

--
-- Indexes for table `{prefix}_index_detail`
--
ALTER TABLE `{prefix}_index_detail`
  ADD PRIMARY KEY (`id`,`module_id`,`language`);
ALTER TABLE `{prefix}_index_detail` ADD FULLTEXT KEY `topic` (`topic`);
ALTER TABLE `{prefix}_index_detail` ADD FULLTEXT KEY `detail` (`detail`);

--
-- Indexes for table `{prefix}_language`
--
ALTER TABLE `{prefix}_language`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_menus`
--
ALTER TABLE `{prefix}_menus`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_modules`
--
ALTER TABLE `{prefix}_modules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_personnel`
--
ALTER TABLE `{prefix}_personnel`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_portfolio`
--
ALTER TABLE `{prefix}_portfolio`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_product`
--
ALTER TABLE `{prefix}_product`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_product_detail`
--
ALTER TABLE `{prefix}_product_detail`
  ADD PRIMARY KEY (`id`,`language`);

--
-- Indexes for table `{prefix}_product_price`
--
ALTER TABLE `{prefix}_product_price`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_tags`
--
ALTER TABLE `{prefix}_tags`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_textlink`
--
ALTER TABLE `{prefix}_textlink`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_user`
--
ALTER TABLE `{prefix}_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `line_uid` (`line_uid`);

--
-- Indexes for table `{prefix}_useronline`
--
ALTER TABLE `{prefix}_useronline`
  ADD PRIMARY KEY (`session`);

--
-- Indexes for table `{prefix}_video`
--
ALTER TABLE `{prefix}_video`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_widget`
--
ALTER TABLE `{prefix}_widget`
  ADD PRIMARY KEY (`id`,`module`),
  ADD KEY `position` (`position`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `{prefix}_activities`
--
ALTER TABLE `{prefix}_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_board_q`
--
ALTER TABLE `{prefix}_board_q`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_board_r`
--
ALTER TABLE `{prefix}_board_r`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_category`
--
ALTER TABLE `{prefix}_category`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_comment`
--
ALTER TABLE `{prefix}_comment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_counter`
--
ALTER TABLE `{prefix}_counter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_download`
--
ALTER TABLE `{prefix}_download`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_edocument`
--
ALTER TABLE `{prefix}_edocument`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_edocument_download`
--
ALTER TABLE `{prefix}_edocument_download`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_emailtemplate`
--
ALTER TABLE `{prefix}_emailtemplate`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_eventcalendar`
--
ALTER TABLE `{prefix}_eventcalendar`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_friends`
--
ALTER TABLE `{prefix}_friends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_gallery`
--
ALTER TABLE `{prefix}_gallery`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_gallery_album`
--
ALTER TABLE `{prefix}_gallery_album`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_index`
--
ALTER TABLE `{prefix}_index`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_language`
--
ALTER TABLE `{prefix}_language`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_menus`
--
ALTER TABLE `{prefix}_menus`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_modules`
--
ALTER TABLE `{prefix}_modules`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_personnel`
--
ALTER TABLE `{prefix}_personnel`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_portfolio`
--
ALTER TABLE `{prefix}_portfolio`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_product`
--
ALTER TABLE `{prefix}_product`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_product_price`
--
ALTER TABLE `{prefix}_product_price`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_tags`
--
ALTER TABLE `{prefix}_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_textlink`
--
ALTER TABLE `{prefix}_textlink`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_user`
--
ALTER TABLE `{prefix}_user`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_video`
--
ALTER TABLE `{prefix}_video`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_widget`
--
ALTER TABLE `{prefix}_widget`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
