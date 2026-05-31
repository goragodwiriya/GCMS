-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 14, 2026 at 08:50 PM
-- Server version: 10.4.34-MariaDB
-- PHP Version: 7.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `wk_gcms`
--

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_board_q`
--

CREATE TABLE `{prefix}_board_q` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `ip` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `visited` smallint(6) DEFAULT NULL,
  `comments` smallint(3) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `commentator_id` int(11) DEFAULT NULL,
  `comment_date` datetime DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `pin` tinyint(1) NOT NULL DEFAULT 0,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `topic` varchar(64) NOT NULL,
  `detail` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_board_r`
--

CREATE TABLE `{prefix}_board_r` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `index_id` int(11) NOT NULL,
  `detail` mediumtext NOT NULL,
  `member_id` int(11) NOT NULL,
  `ip` varchar(50) NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_category`
--

CREATE TABLE `{prefix}_category` (
  `id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `module_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `language` varchar(2) NOT NULL DEFAULT '',
  `config` mediumtext DEFAULT NULL,
  `topic` mediumtext NOT NULL,
  `detail` mediumtext DEFAULT NULL,
  `icon` mediumtext DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_comment`
--

CREATE TABLE `{prefix}_comment` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `index_id` int(11) NOT NULL,
  `detail` mediumtext NOT NULL,
  `sender` varchar(50) NOT NULL,
  `member_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip` varchar(50) NOT NULL,
  `last_update` int(11) NOT NULL,
  `picture` mediumtext DEFAULT NULL,
  `pictureW` int(11) DEFAULT NULL,
  `pictureH` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_download`
--

CREATE TABLE `{prefix}_download` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `category_id` smallint(5) DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `detail` varchar(200) NOT NULL,
  `last_update` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `ext` varchar(5) NOT NULL,
  `size` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `downloads` int(11) NOT NULL,
  `reciever` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_edocument`
--

CREATE TABLE `{prefix}_edocument` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `reciever` mediumtext NOT NULL,
  `last_update` int(11) NOT NULL,
  `downloads` int(11) NOT NULL,
  `document_no` varchar(20) NOT NULL,
  `detail` mediumtext NOT NULL,
  `topic` varchar(50) NOT NULL,
  `ext` varchar(4) NOT NULL,
  `size` double NOT NULL,
  `file` varchar(15) NOT NULL,
  `ip` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_edocument_download`
--

CREATE TABLE `{prefix}_edocument_download` (
  `id` int(10) NOT NULL,
  `module_id` int(10) NOT NULL,
  `document_id` int(10) NOT NULL,
  `member_id` int(10) NOT NULL,
  `downloads` int(10) NOT NULL,
  `last_update` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_emailtemplate`
--

CREATE TABLE `{prefix}_emailtemplate` (
  `id` int(10) NOT NULL,
  `module` varchar(20) NOT NULL,
  `email_id` int(10) NOT NULL,
  `code` varchar(20) NOT NULL,
  `language` varchar(2) NOT NULL,
  `from_email` mediumtext NOT NULL,
  `copy_to` mediumtext NOT NULL,
  `name` mediumtext NOT NULL,
  `subject` mediumtext NOT NULL,
  `detail` mediumtext NOT NULL,
  `last_update` int(11) NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_eventcalendar`
--

CREATE TABLE `{prefix}_eventcalendar` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `topic` varchar(64) NOT NULL,
  `detail` mediumtext NOT NULL,
  `description` varchar(149) NOT NULL,
  `keywords` varchar(149) NOT NULL,
  `member_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `visited`int(11) NOT NULL,
  `begin_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `color` varchar(11) NOT NULL,
  `published` tinyint(1) NOT NULL,
  `published_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_gallery_album`
--

CREATE TABLE `{prefix}_gallery_album` (
  `id` int(11) UNSIGNED NOT NULL,
  `module_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `topic` varchar(255) NOT NULL,
  `detail` text DEFAULT NULL,
  `published_date` date DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `visited` int(11) NOT NULL,
  `count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_gallery_image`
--

CREATE TABLE `{prefix}_gallery_image` (
  `id` int(10) UNSIGNED NOT NULL,
  `module_id` int(11) DEFAULT NULL,
  `album_id` int(10) UNSIGNED NOT NULL,
  `image` varchar(255) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `count` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_index`
--

CREATE TABLE `{prefix}_index` (
  `id` int(11) NOT NULL,
  `index` tinyint(1) NOT NULL DEFAULT 0,
  `module_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `visited` int(11) DEFAULT 0,
  `visited_today` int(11) DEFAULT 0,
  `picture` mediumtext DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `published_date` date DEFAULT NULL,
  `alias` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_index_detail`
--

CREATE TABLE `{prefix}_index_detail` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `language` varchar(2) NOT NULL,
  `topic` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `detail` mediumtext NOT NULL,
  `keywords` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_index_tag`
--

CREATE TABLE `{prefix}_index_tag` (
  `index_id` int(11) NOT NULL,
  `tag` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_language`
--

CREATE TABLE `{prefix}_language` (
  `id` int(11) NOT NULL,
  `key` mediumtext NOT NULL,
  `type` varchar(5) NOT NULL,
  `th` mediumtext DEFAULT NULL,
  `en` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_logs`
--

CREATE TABLE `{prefix}_logs` (
  `id` int(11) NOT NULL,
  `src_id` int(11) NOT NULL,
  `module` varchar(20) NOT NULL,
  `action` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `reason` mediumtext DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `topic` mediumtext NOT NULL,
  `datas` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_menus`
--

CREATE TABLE `{prefix}_menus` (
  `id` int(11) NOT NULL,
  `index_id` int(11) NOT NULL,
  `parent` enum('0_MAINMENU','1_SIDEMENU','2_BOTTOMMENU','') NOT NULL,
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
  `icon` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_modules`
--

CREATE TABLE `{prefix}_modules` (
  `id` int(11) NOT NULL,
  `owner` varchar(20) NOT NULL,
  `module` varchar(64) NOT NULL,
  `config` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_personnel`
--

CREATE TABLE `{prefix}_personnel` (
  `id` int(10) UNSIGNED NOT NULL,
  `module_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `department` int(11) NOT NULL,
  `position` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `level` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `published` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `detail` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Personnel Module';

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_portfolio`
--

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
  `visited` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_tags`
--

CREATE TABLE `{prefix}_tags` (
  `id` int(11) NOT NULL,
  `tag` varchar(64) NOT NULL,
  `count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_textlink`
--

CREATE TABLE `{prefix}_textlink` (
  `id` int(11) NOT NULL,
  `text` mediumtext NOT NULL,
  `url` mediumtext NOT NULL,
  `publish_start` date DEFAULT NULL,
  `publish_end` date DEFAULT NULL,
  `logo` varchar(20) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `type` varchar(11) NOT NULL,
  `name` varchar(11) NOT NULL,
  `published` smallint(1) NOT NULL DEFAULT 1,
  `link_order` smallint(2) NOT NULL,
  `last_preview` int(11) DEFAULT NULL,
  `description` varchar(49) NOT NULL,
  `template` mediumtext DEFAULT NULL,
  `target` varchar(6) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_user`
--

CREATE TABLE `{prefix}_user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `salt` varchar(32) NOT NULL,
  `password` varchar(64) NOT NULL,
  `token` varchar(512) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL,
  `token_version` int(11) NOT NULL,
  `status` tinyint(1) DEFAULT 0,
  `permission` mediumtext DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `displayname` varchar(50) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `sex` varchar(1) DEFAULT NULL,
  `id_card` varchar(13) DEFAULT NULL,
  `address` varchar(64) DEFAULT NULL,
  `address2` varchar(64) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `phone1` varchar(20) DEFAULT NULL,
  `provinceID` smallint(3) DEFAULT NULL,
  `province` varchar(64) DEFAULT NULL,
  `zipcode` varchar(5) DEFAULT NULL,
  `country` varchar(2) DEFAULT 'TH',
  `created_at` datetime NOT NULL,
  `active` tinyint(1) DEFAULT 0,
  `social` enum('user','facebook','google','line','telegram') DEFAULT 'user',
  `email` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `tax_id` varchar(13) DEFAULT NULL,
  `line_uid` varchar(33) DEFAULT NULL,
  `telegram_id` varchar(20) DEFAULT NULL,
  `activatecode` varchar(64) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `company` varchar(64) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `lastvisited` int(11) NOT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `visited` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_user_meta`
--

CREATE TABLE `{prefix}_user_meta` (
  `value` varchar(10) NOT NULL,
  `name` varchar(20) NOT NULL,
  `member_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_ai_chat_settings`
--

CREATE TABLE `{prefix}_ai_chat_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` mediumtext DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_ai_chat_quick_answers`
--

CREATE TABLE `{prefix}_ai_chat_quick_answers` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `keywords` mediumtext NOT NULL,
  `match_mode` varchar(20) NOT NULL DEFAULT 'contains',
  `answer_text` mediumtext NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_ai_chat_handoffs`
--

CREATE TABLE `{prefix}_ai_chat_handoffs` (
  `id` int(11) NOT NULL,
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
  `closed_by_name` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_video`
--

CREATE TABLE `{prefix}_video` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `youtube` varchar(11) NOT NULL,
  `topic` mediumtext NOT NULL,
  `description` mediumtext NOT NULL,
  `views` int(11) NOT NULL,
  `last_update` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

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
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type` (`module_id`,`type`,`category_id`) USING BTREE;

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
-- Indexes for table `{prefix}_gallery_album`
--
ALTER TABLE `{prefix}_gallery_album`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`,`published_date`,`id`);

--
-- Indexes for table `{prefix}_gallery_image`
--
ALTER TABLE `{prefix}_gallery_image`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cover` (`album_id`,`count`);

--
-- Indexes for table `{prefix}_index`
--
ALTER TABLE `{prefix}_index`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `alias` (`alias`,`module_id`),
  ADD KEY `idx_article_list` (`module_id`,`index`,`published`,`published_date`,`id`),
  ADD KEY `idx_category` (`category_id`,`module_id`,`published`,`published_date`,`id`);

--
-- Indexes for table `{prefix}_index_detail`
--
ALTER TABLE `{prefix}_index_detail`
  ADD PRIMARY KEY (`id`,`module_id`,`language`);
ALTER TABLE `{prefix}_index_detail` ADD FULLTEXT KEY `ft_search` (`topic`,`description`,`detail`,`keywords`);

--
-- Indexes for table `{prefix}_index_tag`
--
ALTER TABLE `{prefix}_index_tag`
  ADD PRIMARY KEY (`index_id`,`tag`) USING BTREE,
  ADD KEY `idx_tag` (`tag`,`index_id`) USING BTREE;

--
-- Indexes for table `{prefix}_language`
--
ALTER TABLE `{prefix}_language`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_logs`
--
ALTER TABLE `{prefix}_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `src_id` (`src_id`),
  ADD KEY `source` (`source`);

--
-- Indexes for table `{prefix}_menus`
--
ALTER TABLE `{prefix}_menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lang_parent_order` (`language`,`parent`,`menu_order`),
  ADD KEY `parent` (`parent`,`menu_order`);

--
-- Indexes for table `{prefix}_modules`
--
ALTER TABLE `{prefix}_modules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_personnel`
--
ALTER TABLE `{prefix}_personnel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `sort` (`level`),
  ADD KEY `published` (`published`),
  ADD KEY `department` (`department`);

--
-- Indexes for table `{prefix}_portfolio`
--
ALTER TABLE `{prefix}_portfolio`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_tags`
--
ALTER TABLE `{prefix}_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tag` (`tag`);

--
-- Indexes for table `{prefix}_textlink`
--
ALTER TABLE `{prefix}_textlink`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_user`
--
ALTER TABLE `{prefix}_user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_useronline`
--
ALTER TABLE `{prefix}_useronline`
  ADD PRIMARY KEY (`session`);

--
-- Indexes for table `{prefix}_user_meta`
--
ALTER TABLE `{prefix}_user_meta`
  ADD KEY `member_id` (`member_id`,`name`);

--
-- Indexes for table `{prefix}_ai_chat_settings`
--
ALTER TABLE `{prefix}_ai_chat_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `{prefix}_ai_chat_quick_answers`
--
ALTER TABLE `{prefix}_ai_chat_quick_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `published_sort` (`published`,`sort_order`);

--
-- Indexes for table `{prefix}_ai_chat_handoffs`
--
ALTER TABLE `{prefix}_ai_chat_handoffs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `channel` (`channel`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `{prefix}_video`
--
ALTER TABLE `{prefix}_video`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_edocument`
--
ALTER TABLE `{prefix}_edocument`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_edocument_download`
--
ALTER TABLE `{prefix}_edocument_download`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_emailtemplate`
--
ALTER TABLE `{prefix}_emailtemplate`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_eventcalendar`
--
ALTER TABLE `{prefix}_eventcalendar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_gallery_album`
--
ALTER TABLE `{prefix}_gallery_album`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_gallery_image`
--
ALTER TABLE `{prefix}_gallery_image`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_index`
--
ALTER TABLE `{prefix}_index`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_language`
--
ALTER TABLE `{prefix}_language`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_logs`
--
ALTER TABLE `{prefix}_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_menus`
--
ALTER TABLE `{prefix}_menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_modules`
--
ALTER TABLE `{prefix}_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_personnel`
--
ALTER TABLE `{prefix}_personnel`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_portfolio`
--
ALTER TABLE `{prefix}_portfolio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_ai_chat_settings`
--
ALTER TABLE `{prefix}_ai_chat_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_ai_chat_quick_answers`
--
ALTER TABLE `{prefix}_ai_chat_quick_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_ai_chat_handoffs`
--
ALTER TABLE `{prefix}_ai_chat_handoffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_video`
--
ALTER TABLE `{prefix}_video`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
