-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb2+deb7u8
-- http://www.phpmyadmin.net
--
-- Host: vwp1693.webpack.hosteurope.de
-- Erstellungszeit: 08. Nov 2017 um 00:18
-- Server Version: 5.6.36
-- PHP-Version: 5.4.45-0+deb7u11

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `db1036181-geo`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `apikeys`
--

CREATE TABLE IF NOT EXISTS `apikeys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `apikey` varchar(32) NOT NULL DEFAULT '',
  `ip` int(11) unsigned NOT NULL DEFAULT '0',
  `enabled` enum('Y','N') NOT NULL DEFAULT 'Y',
  `homepage_url` varchar(128) NOT NULL DEFAULT '',
  `comments` text NOT NULL,
  `accesses` int(11) unsigned NOT NULL DEFAULT '0',
  `records` int(11) unsigned NOT NULL DEFAULT '0',
  `last_use` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `added_by` int(11) NOT NULL DEFAULT '0',
  `upd_timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `crt_timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `article`
--

CREATE TABLE IF NOT EXISTS `article` (
  `article_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `article_cat_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `url` varchar(64) NOT NULL DEFAULT '',
  `title` varchar(64) NOT NULL DEFAULT '',
  `content` longtext NOT NULL,
  `gridsquare_id` int(10) unsigned NOT NULL DEFAULT '0',
  `extract` varchar(255) NOT NULL DEFAULT '',
  `licence` enum('none','pd','cc-by-sa/2.0','copyright') NOT NULL DEFAULT 'none',
  `publish_date` date NOT NULL DEFAULT '0000-00-00',
  `approved` tinyint(4) NOT NULL DEFAULT '0',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `article_sort_order` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`article_id`),
  UNIQUE KEY `title` (`title`),
  UNIQUE KEY `url` (`url`),
  KEY `article_cat_id` (`article_cat_id`,`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `article_cat`
--

CREATE TABLE IF NOT EXISTS `article_cat` (
  `article_cat_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(64) NOT NULL DEFAULT '',
  `sort_order` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`article_cat_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `article_lock`
--

CREATE TABLE IF NOT EXISTS `article_lock` (
  `article_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lock_obtained` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`article_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `article_log`
--

CREATE TABLE IF NOT EXISTS `article_log` (
  `month` varchar(7) NOT NULL,
  `article_url` varchar(255) NOT NULL,
  `views` mediumint(8) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `article_revisions`
--

CREATE TABLE IF NOT EXISTS `article_revisions` (
  `article_id` int(10) unsigned NOT NULL DEFAULT '0',
  `article_cat_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `url` varchar(64) NOT NULL DEFAULT '',
  `title` varchar(64) NOT NULL DEFAULT '',
  `content` longtext NOT NULL,
  `gridsquare_id` int(10) unsigned NOT NULL DEFAULT '0',
  `extract` varchar(255) NOT NULL DEFAULT '',
  `licence` enum('none','pd','cc-by-sa/2.0','copyright') NOT NULL DEFAULT 'none',
  `publish_date` date NOT NULL DEFAULT '0000-00-00',
  `approved` tinyint(4) NOT NULL DEFAULT '1',
  `update_time` datetime DEFAULT '0000-00-00 00:00:00',
  `create_time` datetime DEFAULT '0000-00-00 00:00:00',
  `article_sort_order` tinyint(4) NOT NULL DEFAULT '0',
  `article_revision_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `modifier` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`article_revision_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `article_stat`
--

CREATE TABLE IF NOT EXISTS `article_stat` (
  `article_id` int(10) unsigned NOT NULL,
  `views` mediumint(8) unsigned NOT NULL,
  `images` mediumint(8) unsigned NOT NULL,
  `words` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`article_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `autologin`
--

CREATE TABLE IF NOT EXISTS `autologin` (
  `user_id` int(11) NOT NULL DEFAULT '0',
  `token` char(32) NOT NULL DEFAULT '',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`token`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `category_stat`
--

CREATE TABLE IF NOT EXISTS `category_stat` (
  `imageclass` varchar(32) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `c` bigint(21) NOT NULL DEFAULT '0',
  `gridimage_id` int(11) NOT NULL DEFAULT '0',
  KEY `c` (`c`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `compare_done`
--

CREATE TABLE IF NOT EXISTS `compare_done` (
  `compare_pair_id` int(10) unsigned NOT NULL,
  `ipaddr` int(10) unsigned NOT NULL,
  `ua` varchar(128) NOT NULL,
  `when` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`compare_pair_id`,`ipaddr`,`ua`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `compare_pair`
--

CREATE TABLE IF NOT EXISTS `compare_pair` (
  `compare_pair_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gridimage_id1` int(10) unsigned NOT NULL,
  `gridimage_id2` int(10) unsigned NOT NULL,
  `topic_id` int(10) unsigned NOT NULL,
  `post_id` int(10) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('new','confirmed','rejected') NOT NULL,
  `crccol` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`compare_pair_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `competition_code`
--

CREATE TABLE IF NOT EXISTS `competition_code` (
  `competition_code_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `gridimage_id` int(10) unsigned NOT NULL,
  `code` varchar(6) NOT NULL,
  `submitted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`competition_code_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `content`
--

CREATE TABLE IF NOT EXISTS `content` (
  `content_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `foreign_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `gridimage_id` int(10) unsigned NOT NULL,
  `gridsquare_id` int(10) unsigned NOT NULL,
  `extract` varchar(255) NOT NULL,
  `titles` text NOT NULL,
  `tags` text NOT NULL,
  `words` text NOT NULL,
  `source` enum('article','gallery','gsd','themed','help','other','trip') NOT NULL,
  `type` enum('info','document') NOT NULL DEFAULT 'info',
  `updated` datetime NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`content_id`),
  UNIQUE KEY `foreign_id` (`foreign_id`,`source`),
  KEY `title` (`title`),
  KEY `type` (`source`),
  KEY `updated` (`updated`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='consolidated index of geograph content';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `content_group`
--

CREATE TABLE IF NOT EXISTS `content_group` (
  `content_id` int(10) unsigned NOT NULL,
  `label` varchar(128) NOT NULL,
  `score` float NOT NULL,
  `sort_order` tinyint(3) unsigned NOT NULL,
  `source` varchar(10) NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `event`
--

CREATE TABLE IF NOT EXISTS `event` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_name` varchar(64) DEFAULT NULL,
  `event_param` text,
  `posted` datetime DEFAULT NULL,
  `processed` datetime DEFAULT NULL,
  `instances` int(11) DEFAULT '1',
  `priority` int(11) DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  PRIMARY KEY (`event_id`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `event_handled_by`
--

CREATE TABLE IF NOT EXISTS `event_handled_by` (
  `event_id` int(11) NOT NULL DEFAULT '0',
  `class_name` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`event_id`,`class_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `event_log`
--

CREATE TABLE IF NOT EXISTS `event_log` (
  `event_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL DEFAULT '0',
  `logtime` datetime DEFAULT NULL,
  `verbosity` enum('error','warning','trace','verbose') DEFAULT NULL,
  `log` text,
  PRIMARY KEY (`event_log_id`),
  KEY `event_id` (`event_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `explorermaps`
--

CREATE TABLE IF NOT EXISTS `explorermaps` (
  `number` varchar(4) NOT NULL DEFAULT '',
  `name` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`number`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `exploreroutlines1`
--

CREATE TABLE IF NOT EXISTS `exploreroutlines1` (
  `Map` varchar(7) NOT NULL DEFAULT '',
  `addon` enum('','a','b','c') NOT NULL DEFAULT '',
  `left` smallint(6) NOT NULL DEFAULT '0',
  `bottom` smallint(6) NOT NULL DEFAULT '0',
  `right` smallint(6) NOT NULL DEFAULT '0',
  `top` smallint(6) NOT NULL DEFAULT '0',
  `numberic` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`Map`,`addon`),
  KEY `bottom` (`left`,`bottom`,`right`,`top`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT=' v0.5 - Ian Richardson (ian@elsworth.demon.co.uk) 2004-09-28';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `feedback`
--

CREATE TABLE IF NOT EXISTS `feedback` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(32) COLLATE latin1_german2_ci NOT NULL,
  `question` varchar(128) COLLATE latin1_german2_ci NOT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `flickr_photos`
--

CREATE TABLE IF NOT EXISTS `flickr_photos` (
  `id` int(11) NOT NULL DEFAULT '0',
  `upd_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `owner` varchar(15) NOT NULL DEFAULT '',
  `secret` varchar(12) NOT NULL DEFAULT '',
  `server` char(3) NOT NULL DEFAULT '',
  `title` varchar(64) NOT NULL DEFAULT '',
  `ispublic` enum('0','1') NOT NULL DEFAULT '0',
  `isfriend` enum('0','1') NOT NULL DEFAULT '0',
  `isfamily` enum('0','1') NOT NULL DEFAULT '0',
  `dateadded` int(11) NOT NULL DEFAULT '0',
  `isgeograph` enum('0','1') NOT NULL DEFAULT '0',
  `gridsquare_id` int(11) NOT NULL DEFAULT '0',
  `nateastings` mediumint(9) NOT NULL DEFAULT '0',
  `natnorthings` mediumint(9) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `flickr_tags`
--

CREATE TABLE IF NOT EXISTS `flickr_tags` (
  `id` int(11) NOT NULL DEFAULT '0',
  `photo_id` int(11) NOT NULL DEFAULT '0',
  `author` varchar(15) NOT NULL DEFAULT '',
  `raw` varchar(64) NOT NULL DEFAULT '',
  `tag_tidy` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `photo_id` (`photo_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `flickr_users`
--

CREATE TABLE IF NOT EXISTS `flickr_users` (
  `owner` varchar(15) NOT NULL DEFAULT '',
  `ownername` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`owner`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `game_image_rate`
--

CREATE TABLE IF NOT EXISTS `game_image_rate` (
  `gridimage_id` int(10) unsigned NOT NULL DEFAULT '0',
  `game_id` tinyint(4) NOT NULL DEFAULT '0',
  `rating` decimal(4,0) DEFAULT NULL,
  `ratings` bigint(21) NOT NULL DEFAULT '0',
  PRIMARY KEY (`gridimage_id`,`game_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `game_image_score`
--

CREATE TABLE IF NOT EXISTS `game_image_score` (
  `game_image_score_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` tinyint(4) NOT NULL DEFAULT '0',
  `level` tinyint(4) NOT NULL,
  `gridimage_id` int(10) unsigned NOT NULL DEFAULT '0',
  `score` tinyint(4) NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`game_image_score_id`),
  KEY `game_id` (`game_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `game_rate`
--

CREATE TABLE IF NOT EXISTS `game_rate` (
  `game_rate_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` tinyint(4) NOT NULL DEFAULT '0',
  `gridimage_id` int(10) unsigned NOT NULL DEFAULT '0',
  `queries_id` int(10) unsigned NOT NULL DEFAULT '0',
  `rating` tinyint(4) NOT NULL DEFAULT '-2',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`game_rate_id`),
  KEY `game_id` (`game_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `game_score`
--

CREATE TABLE IF NOT EXISTS `game_score` (
  `game_score_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` tinyint(4) NOT NULL DEFAULT '0',
  `level` tinyint(4) NOT NULL,
  `score` mediumint(9) NOT NULL DEFAULT '0',
  `games` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `username` varchar(64) NOT NULL DEFAULT '',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approved` tinyint(4) NOT NULL DEFAULT '1',
  `ipaddr` int(10) unsigned NOT NULL,
  `ua` varchar(128) NOT NULL,
  `session` varchar(32) NOT NULL,
  PRIMARY KEY (`game_score_id`),
  KEY `game_id` (`game_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `geobb_banned`
--

CREATE TABLE IF NOT EXISTS `geobb_banned` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `banip` varchar(15) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `geobb_forums`
--

CREATE TABLE IF NOT EXISTS `geobb_forums` (
  `forum_id` int(10) NOT NULL AUTO_INCREMENT,
  `forum_name` varchar(150) NOT NULL DEFAULT '',
  `forum_desc` text NOT NULL,
  `forum_order` int(10) NOT NULL DEFAULT '0',
  `forum_icon` varchar(255) NOT NULL DEFAULT 'default.gif',
  `topics_count` int(10) NOT NULL DEFAULT '0',
  `posts_count` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`forum_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `geobb_lastviewed`
--

CREATE TABLE IF NOT EXISTS `geobb_lastviewed` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `topic_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_post_id` int(10) unsigned NOT NULL DEFAULT '0',
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`topic_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `geobb_posts`
--

CREATE TABLE IF NOT EXISTS `geobb_posts` (
  `post_id` int(10) NOT NULL AUTO_INCREMENT,
  `forum_id` int(10) NOT NULL DEFAULT '1',
  `topic_id` int(10) NOT NULL DEFAULT '1',
  `poster_id` int(10) NOT NULL DEFAULT '0',
  `poster_name` varchar(40) NOT NULL DEFAULT 'Anonymous',
  `post_text` text NOT NULL,
  `post_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `poster_ip` varchar(45) NOT NULL DEFAULT '',
  `post_status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`post_id`),
  KEY `post_id` (`post_id`),
  KEY `forum_id` (`forum_id`),
  KEY `topic_id` (`topic_id`),
  KEY `poster_id` (`poster_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `geobb_send_mails`
--

CREATE TABLE IF NOT EXISTS `geobb_send_mails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '1',
  `topic_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `geobb_topics`
--

CREATE TABLE IF NOT EXISTS `geobb_topics` (
  `topic_id` int(10) NOT NULL AUTO_INCREMENT,
  `topic_title` varchar(100) NOT NULL DEFAULT '',
  `topic_poster` int(10) NOT NULL DEFAULT '0',
  `topic_poster_name` varchar(40) NOT NULL DEFAULT 'Anonymous',
  `topic_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `topic_views` int(10) NOT NULL DEFAULT '0',
  `forum_id` int(10) NOT NULL DEFAULT '1',
  `topic_status` tinyint(1) NOT NULL DEFAULT '0',
  `topic_last_post_id` int(10) NOT NULL DEFAULT '1',
  `posts_count` int(10) NOT NULL DEFAULT '0',
  `sticky` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`topic_id`),
  KEY `topic_id` (`topic_id`),
  KEY `forum_id` (`forum_id`),
  KEY `topic_last_post_id` (`topic_last_post_id`),
  KEY `sticky` (`sticky`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `geobb_users`
--

CREATE TABLE IF NOT EXISTS `geobb_users` (
  `user_id` int(10) NOT NULL AUTO_INCREMENT,
  `username` varchar(40) NOT NULL DEFAULT '',
  `user_regdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_password` varchar(60) NOT NULL DEFAULT '',
  `user_email` varchar(50) NOT NULL DEFAULT '',
  `user_icq` varchar(50) NOT NULL DEFAULT '',
  `user_website` varchar(100) NOT NULL DEFAULT '',
  `user_occ` varchar(100) NOT NULL DEFAULT '',
  `user_from` varchar(100) NOT NULL DEFAULT '',
  `user_interest` varchar(150) NOT NULL DEFAULT '',
  `user_viewemail` tinyint(1) NOT NULL DEFAULT '0',
  `user_sorttopics` tinyint(1) NOT NULL DEFAULT '1',
  `user_forums` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `user_show` varchar(64) NOT NULL DEFAULT '',
  `user_latest` enum('5','10','20','30','40') NOT NULL DEFAULT '30',
  `user_newpwdkey` varchar(32) NOT NULL DEFAULT '',
  `user_newpasswd` varchar(32) NOT NULL DEFAULT '',
  `language` char(3) NOT NULL DEFAULT '',
  `activity` int(1) NOT NULL DEFAULT '1',
  `user_custom1` varchar(255) NOT NULL DEFAULT '',
  `user_custom2` varchar(255) NOT NULL DEFAULT '',
  `user_custom3` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`),
  KEY `username` (`username`,`user_password`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `geoevent`
--

CREATE TABLE IF NOT EXISTS `geoevent` (
  `geoevent_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(128) CHARACTER SET utf8 NOT NULL,
  `description` varchar(255) CHARACTER SET utf8 NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `gridsquare_id` int(10) unsigned NOT NULL,
  `gridimage_id` int(10) unsigned NOT NULL,
  `event_time` datetime NOT NULL,
  `url` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approved` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`geoevent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `geoevent_attendee`
--

CREATE TABLE IF NOT EXISTS `geoevent_attendee` (
  `geoevent_attendee_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `geoevent_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `message` varchar(160) CHARACTER SET utf8 NOT NULL,
  `type` enum('attend','maybe','not') NOT NULL DEFAULT 'attend',
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`geoevent_attendee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `geotrips`
--

CREATE TABLE IF NOT EXISTS `geotrips` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `user` varchar(128) NOT NULL DEFAULT '',
  `type` enum('trip','walk','bike','road','rail','boat','bus') NOT NULL DEFAULT 'trip',
  `location` varchar(128) NOT NULL DEFAULT '',
  `start` varchar(128) NOT NULL DEFAULT '',
  `title` varchar(128) NOT NULL DEFAULT '',
  `date` date NOT NULL DEFAULT '0000-00-00',
  `bbox` varchar(128) NOT NULL DEFAULT '',
  `track` text NOT NULL,
  `search` int(10) unsigned DEFAULT NULL,
  `img` int(11) NOT NULL DEFAULT '0',
  `descr` text NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL,
  `contfrom` int(10) unsigned NOT NULL DEFAULT '0',
  `userlist` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `contfrom` (`contfrom`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `geotrips_bak`
--

CREATE TABLE IF NOT EXISTS `geotrips_bak` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `user` varchar(128) NOT NULL DEFAULT '',
  `type` enum('trip','walk','bike','road','rail','boat','bus') NOT NULL DEFAULT 'trip',
  `location` varchar(128) NOT NULL DEFAULT '',
  `start` varchar(128) NOT NULL DEFAULT '',
  `title` varchar(128) NOT NULL DEFAULT '',
  `date` date NOT NULL DEFAULT '0000-00-00',
  `bbox` varchar(128) NOT NULL DEFAULT '',
  `track` text NOT NULL,
  `search` int(10) unsigned DEFAULT NULL,
  `img` int(11) NOT NULL DEFAULT '0',
  `descr` text NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL,
  `contfrom` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `contfrom` (`contfrom`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `glossary`
--

CREATE TABLE IF NOT EXISTS `glossary` (
  `glossery_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `source_lang` char(2) NOT NULL DEFAULT '',
  `source_dialect` varchar(30) NOT NULL DEFAULT '',
  `source_word` varchar(64) NOT NULL DEFAULT '',
  `tran_lang` char(2) NOT NULL DEFAULT '',
  `tran_dialect` varchar(30) NOT NULL DEFAULT '',
  `tran_word` varchar(64) NOT NULL DEFAULT '',
  `tran_definition` varchar(255) NOT NULL DEFAULT '',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` mediumint(8) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`glossery_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage`
--

CREATE TABLE IF NOT EXISTS `gridimage` (
  `gridimage_id` int(11) NOT NULL AUTO_INCREMENT,
  `gridsquare_id` int(11) NOT NULL DEFAULT '0',
  `seq_no` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `realname` varchar(128) NOT NULL,
  `ftf` int(11) NOT NULL DEFAULT '0',
  `moderation_status` enum('rejected','pending','accepted','geograph') DEFAULT NULL,
  `title` varchar(128) DEFAULT NULL,
  `title2` varchar(128) DEFAULT NULL,
  `comment` text,
  `comment2` text,
  `submitted` datetime DEFAULT NULL,
  `nateastings` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `natnorthings` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `natgrlen` enum('10','8','6','4','2') NOT NULL DEFAULT '4',
  `reference_index` tinyint(1) NOT NULL DEFAULT '0',
  `imageclass` varchar(32) NOT NULL DEFAULT '',
  `imagetaken` date NOT NULL DEFAULT '0000-00-00',
  `moderator_id` int(11) NOT NULL DEFAULT '0',
  `moderated` datetime DEFAULT NULL,
  `upd_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `placename_id` int(10) unsigned NOT NULL DEFAULT '0',
  `viewpoint_eastings` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `viewpoint_northings` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `viewpoint_grlen` enum('10','8','6','4','2','0') NOT NULL DEFAULT '0',
  `viewpoint_refindex` tinyint(1) NOT NULL DEFAULT '0',
  `view_direction` smallint(6) NOT NULL DEFAULT '-1',
  `use6fig` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `user_status` enum('','accepted','rejected') NOT NULL DEFAULT '',
  `distance` mediumint(8) unsigned DEFAULT NULL,
  PRIMARY KEY (`gridimage_id`),
  UNIQUE KEY `gridsquare_id` (`gridsquare_id`,`seq_no`),
  KEY `user_id` (`user_id`),
  KEY `imageclass` (`imageclass`),
  KEY `placename_id` (`placename_id`),
  KEY `moderation_status` (`moderation_status`),
  KEY `submitted` (`submitted`),
  KEY `gridsquare_id_2` (`gridsquare_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_daily`
--

CREATE TABLE IF NOT EXISTS `gridimage_daily` (
  `gridimage_id` int(11) NOT NULL DEFAULT '0',
  `showday` date DEFAULT NULL,
  `vote_baysian` float NOT NULL,
  PRIMARY KEY (`gridimage_id`),
  KEY `showday` (`showday`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_diversity`
--

CREATE TABLE IF NOT EXISTS `gridimage_diversity` (
  `gridimage_id` int(10) unsigned NOT NULL,
  `type` varchar(10) COLLATE latin1_german2_ci NOT NULL,
  `ratio` float NOT NULL,
  PRIMARY KEY (`gridimage_id`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_exif`
--

CREATE TABLE IF NOT EXISTS `gridimage_exif` (
  `gridimage_id` int(10) unsigned NOT NULL DEFAULT '0',
  `exif` text NOT NULL,
  PRIMARY KEY (`gridimage_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_group`
--

CREATE TABLE IF NOT EXISTS `gridimage_group` (
  `gridimage_id` int(10) unsigned NOT NULL,
  `label` varchar(128) NOT NULL,
  `score` float NOT NULL,
  `sort_order` tinyint(3) unsigned NOT NULL,
  `source` varchar(10) NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `label` (`label`),
  KEY `gridimage_id` (`gridimage_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_group_stat`
--

CREATE TABLE IF NOT EXISTS `gridimage_group_stat` (
  `gridimage_group_stat_id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(128) NOT NULL,
  `images` mediumint(8) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`gridimage_group_stat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_kml`
--

CREATE TABLE IF NOT EXISTS `gridimage_kml` (
  `gridimage_id` int(11) NOT NULL DEFAULT '0',
  `x` smallint(3) NOT NULL DEFAULT '0',
  `y` smallint(4) NOT NULL DEFAULT '0',
  `grid_reference` varchar(7) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `title` varchar(128) CHARACTER SET latin1 DEFAULT NULL,
  `title2` varchar(128) CHARACTER SET latin1 DEFAULT NULL,
  `credit_realname` tinyint(4) NOT NULL DEFAULT '0',
  `realname` varchar(128) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `wgs84_lat` decimal(10,6) NOT NULL DEFAULT '0.000000',
  `wgs84_long` decimal(10,6) NOT NULL DEFAULT '0.000000',
  `natgrlen` enum('10','8','6','4','2') CHARACTER SET latin1 NOT NULL DEFAULT '4',
  `view_direction` smallint(6) NOT NULL DEFAULT '-1',
  `point_xy` point NOT NULL,
  `tile` int(1) NOT NULL DEFAULT '0',
  `imagecount` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `x` (`x`,`y`),
  SPATIAL KEY `point_xy` (`point_xy`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_link`
--

CREATE TABLE IF NOT EXISTS `gridimage_link` (
  `gridimage_link_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gridimage_id` int(10) unsigned NOT NULL,
  `url` varchar(255) NOT NULL,
  `HTTP_Status` smallint(5) unsigned NOT NULL,
  `HTTP_Last_Modified` varchar(64) NOT NULL,
  `HTTP_Location` varchar(255) NOT NULL,
  `match_score` smallint(6) NOT NULL,
  `page_title` varchar(255) NOT NULL,
  `last_checked` datetime NOT NULL,
  `next_check` datetime NOT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `parent_link_id` int(10) unsigned NOT NULL,
  `failure_count` smallint(6) NOT NULL,
  PRIMARY KEY (`gridimage_link_id`),
  UNIQUE KEY `gridimage_id_2` (`gridimage_id`,`url`),
  KEY `gridimage_id` (`gridimage_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_loc_placenames`
--

CREATE TABLE IF NOT EXISTS `gridimage_loc_placenames` (
  `placename_id` int(10) unsigned NOT NULL DEFAULT '0',
  `country` enum('uk','ie') CHARACTER SET latin1 NOT NULL,
  `adm1` char(2) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `reference_index` tinyint(4) NOT NULL DEFAULT '1',
  `full_name` varchar(64) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `c` bigint(21) NOT NULL DEFAULT '0',
  `gridimage_id` int(11) NOT NULL DEFAULT '0',
  KEY `reference_index` (`reference_index`,`country`,`adm1`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_moderation_lock`
--

CREATE TABLE IF NOT EXISTS `gridimage_moderation_lock` (
  `gridimage_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lock_obtained` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lock_type` enum('modding','cantmod') NOT NULL DEFAULT 'modding',
  PRIMARY KEY (`gridimage_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_notes`
--

CREATE TABLE IF NOT EXISTS `gridimage_notes` (
  `note_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gridimage_id` int(10) unsigned NOT NULL,
  `x1` int(10) unsigned NOT NULL,
  `y1` int(10) unsigned NOT NULL,
  `x2` int(10) unsigned NOT NULL,
  `y2` int(10) unsigned NOT NULL,
  `z` tinyint(4) NOT NULL DEFAULT '0',
  `imgwidth` int(10) unsigned NOT NULL,
  `imgheight` int(10) unsigned NOT NULL,
  `status` enum('visible','pending','deleted') NOT NULL DEFAULT 'pending',
  `comment` text NOT NULL,
  PRIMARY KEY (`note_id`),
  KEY `gridimage_id` (`gridimage_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_os_gaz`
--

CREATE TABLE IF NOT EXISTS `gridimage_os_gaz` (
  `placename_id` int(10) unsigned NOT NULL DEFAULT '0',
  `co_code` enum('AB','AG','AN','AR','BA','BB','BC','BD','BE','BF','BG','BH','BI','BL','BM','BN','BO','BP','BR','BS','BT','BU','BX','BY','BZ','CA','CB','CD','CE','CF','CH','CL','CM','CN','CT','CU','CV','CW','CY','DB','DD','DE','DG','DL','DN','DR','DT','DU','DY','DZ','EA','EB','ED','EG','EL','EN','ER','ES','EX','EY','FA','FF','FL','GH','GL','GR','GW','GY','HA','HD','HE','HF','HG','HI','HL','HN','HP','HR','HS','HT','HV','IA','IL','IM','IN','IS','IW','KC','KG','KH','KL','KN','KT','LA','LB','LC','LD','LL','LN','LO','LP','LS','LT','MA','MB','ME','MI','MK','MM','MO','MR','MT','NA','NC','ND','NE','NG','NH','NI','NK','NL','NN','NP','NR','NS','NT','NW','NY','OH','OK','ON','PB','PE','PK','PL','PO','PW','PY','RB','RC','RD','RE','RG','RH','RL','RO','RT','SA','SB','SC','SD','SE','SF','SG','SH','SI','SJ','SK','SL','SM','SN','SO','SP','SQ','SR','SS','ST','SU','SV','SW','SX','SY','SZ','TB','TF','TH','TR','TS','TU','VG','WA','WB','WC','WD','WE','WF','WG','WH','WI','WJ','WK','WL','WM','WN','WO','WP','WR','WS','WT','WW','WX','XX','YK','YS','YT','YY') CHARACTER SET latin1 NOT NULL,
  `full_name` varchar(60) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `c` bigint(21) NOT NULL DEFAULT '0',
  `gridimage_id` int(11) NOT NULL DEFAULT '0',
  KEY `co_code` (`co_code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_pending`
--

CREATE TABLE IF NOT EXISTS `gridimage_pending` (
  `gridimage_id` int(10) unsigned NOT NULL DEFAULT '0',
  `upload_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `gridimage_ticket_id` int(10) unsigned NOT NULL DEFAULT '0',
  `suggested` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `type` enum('picnik','original','altimg') DEFAULT 'picnik',
  `status` enum('new','open','accepted','confirmed','rejected') NOT NULL DEFAULT 'new',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_post`
--

CREATE TABLE IF NOT EXISTS `gridimage_post` (
  `seq_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL DEFAULT '0',
  `gridimage_id` int(10) unsigned NOT NULL DEFAULT '0',
  `topic_id` int(10) unsigned NOT NULL DEFAULT '0',
  `type` enum('T','I') NOT NULL DEFAULT 'T',
  PRIMARY KEY (`seq_id`),
  KEY `post_id` (`post_id`,`gridimage_id`),
  KEY `topic_id` (`topic_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_post_stat`
--

CREATE TABLE IF NOT EXISTS `gridimage_post_stat` (
  `gridimage_id` int(10) unsigned NOT NULL DEFAULT '0',
  `images` bigint(21) NOT NULL DEFAULT '0',
  KEY `gridimage_id` (`gridimage_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_query`
--

CREATE TABLE IF NOT EXISTS `gridimage_query` (
  `seq_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `query_id` int(10) unsigned NOT NULL DEFAULT '0',
  `gridimage_id` int(10) unsigned NOT NULL DEFAULT '0',
  `crt_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`seq_id`),
  KEY `query_id` (`query_id`,`gridimage_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_rating`
--

CREATE TABLE IF NOT EXISTS `gridimage_rating` (
  `gridimage_id` int(10) unsigned NOT NULL,
  `type` enum('info','like','site','qual') NOT NULL,
  `rating` float(7,6) NOT NULL,
  `votes` int(11) unsigned NOT NULL,
  `weighted_votes` float(11,6) NOT NULL,
  PRIMARY KEY (`gridimage_id`,`type`),
  KEY `rating` (`rating`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_recent`
--

CREATE TABLE IF NOT EXISTS `gridimage_recent` (
  `gridimage_id` int(11) NOT NULL DEFAULT '0',
  `gridsquare_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `moderation_status` enum('rejected','pending','accepted','geograph') DEFAULT NULL,
  `title` varchar(128) DEFAULT NULL,
  `title2` varchar(128) DEFAULT NULL,
  `submitted` datetime DEFAULT NULL,
  `imageclass` varchar(32) NOT NULL DEFAULT '',
  `imagetaken` date NOT NULL DEFAULT '0000-00-00',
  `upd_timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `x` smallint(3) NOT NULL DEFAULT '0',
  `y` smallint(4) NOT NULL DEFAULT '0',
  `grid_reference` varchar(7) NOT NULL DEFAULT '',
  `credit_realname` tinyint(4) NOT NULL DEFAULT '0',
  `realname` varchar(128) NOT NULL DEFAULT '',
  `reference_index` tinyint(1) NOT NULL DEFAULT '0',
  `comment` text,
  `comment2` text,
  `wgs84_lat` decimal(10,6) NOT NULL DEFAULT '0.000000',
  `wgs84_long` decimal(10,6) NOT NULL DEFAULT '0.000000',
  `ftf` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `seq_no` smallint(5) unsigned NOT NULL DEFAULT '0',
  `notes` text,
  `point_xy` point NOT NULL DEFAULT '',
  `point_ll` point NOT NULL DEFAULT '',
  `recent_id` int(3) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`recent_id`),
  UNIQUE KEY `gridimage_id` (`gridimage_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_search`
--

CREATE TABLE IF NOT EXISTS `gridimage_search` (
  `gridimage_id` int(11) NOT NULL DEFAULT '0',
  `gridsquare_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `moderation_status` enum('rejected','pending','accepted','geograph') DEFAULT NULL,
  `title` varchar(128) DEFAULT NULL,
  `title2` varchar(128) DEFAULT NULL,
  `submitted` datetime DEFAULT NULL,
  `imageclass` varchar(32) NOT NULL DEFAULT '',
  `imagetaken` date NOT NULL DEFAULT '0000-00-00',
  `upd_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `x` smallint(3) NOT NULL DEFAULT '0',
  `y` smallint(4) NOT NULL DEFAULT '0',
  `grid_reference` varchar(7) NOT NULL DEFAULT '',
  `credit_realname` tinyint(4) NOT NULL DEFAULT '0',
  `realname` varchar(128) NOT NULL DEFAULT '',
  `reference_index` tinyint(1) NOT NULL DEFAULT '0',
  `comment` text,
  `comment2` text,
  `wgs84_lat` decimal(10,6) NOT NULL DEFAULT '0.000000',
  `wgs84_long` decimal(10,6) NOT NULL DEFAULT '0.000000',
  `ftf` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `seq_no` smallint(5) unsigned NOT NULL DEFAULT '0',
  `notes` text,
  `point_xy` point NOT NULL,
  `point_ll` point NOT NULL,
  PRIMARY KEY (`gridimage_id`),
  KEY `user_id` (`user_id`),
  KEY `imageclass` (`imageclass`),
  KEY `imagetaken` (`imagetaken`),
  KEY `submitted` (`submitted`),
  KEY `x` (`x`,`y`),
  KEY `moderation_status` (`moderation_status`),
  KEY `wgs84_lat` (`wgs84_lat`,`wgs84_long`),
  KEY `grid_reference` (`grid_reference`),
  SPATIAL KEY `point_xy` (`point_xy`),
  SPATIAL KEY `point_ll` (`point_ll`),
  KEY `title` (`title`,`title2`),
  KEY `gridsquare_id` (`gridsquare_id`),
  KEY `title_2` (`title`,`title2`,`comment`(250),`comment2`(250),`imageclass`,`notes`(150))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_size`
--

CREATE TABLE IF NOT EXISTS `gridimage_size` (
  `gridimage_id` int(10) unsigned NOT NULL,
  `width` smallint(5) unsigned NOT NULL,
  `height` smallint(5) unsigned NOT NULL,
  `original_width` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `original_height` mediumint(8) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`gridimage_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_ticket`
--

CREATE TABLE IF NOT EXISTS `gridimage_ticket` (
  `gridimage_ticket_id` int(11) NOT NULL AUTO_INCREMENT,
  `gridimage_id` int(11) DEFAULT NULL,
  `suggested` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `moderator_id` int(11) DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `status` enum('pending','open','closed') DEFAULT NULL,
  `notes` text,
  `type` enum('normal','minor') NOT NULL DEFAULT 'normal',
  `notify` enum('','suggestor') NOT NULL DEFAULT 'suggestor',
  `deferred` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `public` enum('no','owner','everyone') NOT NULL DEFAULT 'everyone',
  PRIMARY KEY (`gridimage_ticket_id`),
  KEY `gridimage_id` (`gridimage_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_ticket_comment`
--

CREATE TABLE IF NOT EXISTS `gridimage_ticket_comment` (
  `gridimage_ticket_comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `gridimage_ticket_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) DEFAULT NULL,
  `comment` text,
  `added` datetime DEFAULT NULL,
  PRIMARY KEY (`gridimage_ticket_comment_id`),
  KEY `gridimage_ticket_id` (`gridimage_ticket_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_ticket_item`
--

CREATE TABLE IF NOT EXISTS `gridimage_ticket_item` (
  `gridimage_ticket_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `gridimage_ticket_id` int(11) NOT NULL DEFAULT '0',
  `approver_id` int(11) DEFAULT NULL,
  `field` varchar(64) DEFAULT NULL,
  `note_id` int(10) unsigned DEFAULT NULL,
  `oldvalue` text,
  `newvalue` text,
  `status` enum('pending','immediate','approved','rejected') DEFAULT NULL,
  PRIMARY KEY (`gridimage_ticket_item_id`),
  KEY `gridimage_ticket_id` (`gridimage_ticket_id`),
  KEY `note_id` (`note_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridimage_vote`
--

CREATE TABLE IF NOT EXISTS `gridimage_vote` (
  `gridimage_id` int(10) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `type` enum('info','like','site','qual') NOT NULL,
  `vote` tinyint(4) NOT NULL,
  `weight` float(7,6) NOT NULL DEFAULT '0.000000',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gridimage_id`,`user_id`,`type`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridprefix`
--

CREATE TABLE IF NOT EXISTS `gridprefix` (
  `reference_index` int(11) NOT NULL DEFAULT '0',
  `prefix` varchar(4) NOT NULL DEFAULT '',
  `origin_x` int(11) NOT NULL DEFAULT '0',
  `origin_y` int(11) NOT NULL DEFAULT '0',
  `width` int(11) NOT NULL DEFAULT '100',
  `height` int(11) NOT NULL DEFAULT '100',
  `landcount` int(11) DEFAULT '0',
  `title` varchar(128) DEFAULT NULL,
  `boundary` varchar(128) DEFAULT NULL,
  `labelcentre` varchar(16) DEFAULT NULL,
  `labelminwidth` mediumint(4) unsigned NOT NULL DEFAULT '0',
  `geometry_boundary` geometry NOT NULL DEFAULT '',
  `point_origin_xy` point NOT NULL DEFAULT '',
  PRIMARY KEY (`reference_index`,`prefix`),
  SPATIAL KEY `geometry_boundary` (`geometry_boundary`),
  SPATIAL KEY `point_origin_xy` (`point_origin_xy`),
  KEY `origin_x` (`origin_x`,`origin_y`),
  KEY `prefix` (`prefix`),
  KEY `origin_x_2` (`origin_x`,`origin_y`,`landcount`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridsquare`
--

CREATE TABLE IF NOT EXISTS `gridsquare` (
  `gridsquare_id` int(11) NOT NULL AUTO_INCREMENT,
  `x` int(11) NOT NULL DEFAULT '0',
  `y` int(11) NOT NULL DEFAULT '0',
  `percent_land` tinyint(4) NOT NULL DEFAULT '100',
  `imagecount` int(11) NOT NULL DEFAULT '0',
  `grid_reference` varchar(7) NOT NULL DEFAULT '',
  `reference_index` tinyint(4) DEFAULT '1',
  `has_geographs` tinyint(4) NOT NULL DEFAULT '0',
  `permit_photographs` tinyint(1) NOT NULL DEFAULT '1',
  `permit_geographs` tinyint(1) NOT NULL DEFAULT '1',
  `point_xy` point NOT NULL DEFAULT '',
  `placename_id` int(10) unsigned NOT NULL DEFAULT '0',
  `mapservices` set('1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','32') NOT NULL DEFAULT '',
  PRIMARY KEY (`gridsquare_id`),
  UNIQUE KEY `grid_reference` (`grid_reference`),
  KEY `x` (`x`),
  KEY `y` (`y`),
  SPATIAL KEY `point_xy` (`point_xy`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridsquare_gmcache`
--

CREATE TABLE IF NOT EXISTS `gridsquare_gmcache` (
  `gridsquare_id` int(11) NOT NULL,
  `gxlow` int(11) NOT NULL,
  `gylow` int(11) NOT NULL,
  `gxhigh` int(11) NOT NULL,
  `gyhigh` int(11) NOT NULL,
  `area` double(9,6) NOT NULL,
  `cliparea` double(9,6) NOT NULL,
  `rotangle` double(8,6) NOT NULL,
  `scale` double(8,6) NOT NULL,
  `cgx` double(10,2) NOT NULL,
  `cgy` double(10,2) NOT NULL,
  `polycount` tinyint(1) NOT NULL,
  `poly1gx` double(10,2) DEFAULT NULL,
  `poly1gy` double(10,2) DEFAULT NULL,
  `poly2gx` double(10,2) DEFAULT NULL,
  `poly2gy` double(10,2) DEFAULT NULL,
  `poly3gx` double(10,2) DEFAULT NULL,
  `poly3gy` double(10,2) DEFAULT NULL,
  `poly4gx` double(10,2) DEFAULT NULL,
  `poly4gy` double(10,2) DEFAULT NULL,
  `poly5gx` double(10,2) DEFAULT NULL,
  `poly5gy` double(10,2) DEFAULT NULL,
  `poly6gx` double(10,2) DEFAULT NULL,
  `poly6gy` double(10,2) DEFAULT NULL,
  `poly7gx` double(10,2) DEFAULT NULL,
  `poly7gy` double(10,2) DEFAULT NULL,
  `poly8gx` double(10,2) DEFAULT NULL,
  `poly8gy` double(10,2) DEFAULT NULL,
  PRIMARY KEY (`gridsquare_id`),
  KEY `gxlow` (`gxlow`),
  KEY `gxhigh` (`gxhigh`),
  KEY `gylow` (`gylow`),
  KEY `gyhigh` (`gyhigh`),
  KEY `xy` (`gxlow`,`gxhigh`,`gylow`,`gyhigh`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridsquare_mappal`
--

CREATE TABLE IF NOT EXISTS `gridsquare_mappal` (
  `gridsquare_id` int(11) NOT NULL,
  `level` tinyint(2) NOT NULL,
  `mappal` float(10,4) NOT NULL DEFAULT '0.0000',
  `norm` float(10,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`gridsquare_id`,`level`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridsquare_moderation_lock`
--

CREATE TABLE IF NOT EXISTS `gridsquare_moderation_lock` (
  `gridsquare_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lock_obtained` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lock_type` enum('modding','cantmod') NOT NULL DEFAULT 'modding',
  PRIMARY KEY (`gridsquare_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridsquare_percentage`
--

CREATE TABLE IF NOT EXISTS `gridsquare_percentage` (
  `gridsquare_id` int(11) NOT NULL,
  `level` tinyint(2) NOT NULL,
  `community_id` int(9) unsigned NOT NULL,
  `percent` tinyint(4) NOT NULL,
  PRIMARY KEY (`gridsquare_id`,`level`,`community_id`),
  KEY `gridsquare_id` (`gridsquare_id`),
  KEY `subject` (`level`,`community_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridsquare_percentage_old`
--

CREATE TABLE IF NOT EXISTS `gridsquare_percentage_old` (
  `gridsquare_id` int(11) NOT NULL,
  `level` tinyint(2) NOT NULL,
  `community_id` int(9) unsigned NOT NULL,
  `percent` tinyint(4) NOT NULL,
  PRIMARY KEY (`gridsquare_id`,`level`,`community_id`),
  KEY `gridsquare_id` (`gridsquare_id`),
  KEY `subject` (`level`,`community_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `gridsquare_topic`
--

CREATE TABLE IF NOT EXISTS `gridsquare_topic` (
  `gridsquare_id` int(11) NOT NULL DEFAULT '0',
  `topic_id` int(11) NOT NULL DEFAULT '0',
  `forum_id` int(11) NOT NULL DEFAULT '0',
  `last_post` datetime DEFAULT NULL,
  PRIMARY KEY (`gridsquare_id`,`topic_id`),
  KEY `topic_id` (`topic_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `hectad_assignment`
--

CREATE TABLE IF NOT EXISTS `hectad_assignment` (
  `hectad_assignment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `hectad` varchar(4) NOT NULL,
  `status` enum('new','deleted') NOT NULL DEFAULT 'new',
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sort_order` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`hectad_assignment_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `hectad_complete`
--

CREATE TABLE IF NOT EXISTS `hectad_complete` (
  `hectad_ref` varchar(7) NOT NULL DEFAULT '',
  `completed` date NOT NULL DEFAULT '0000-00-00',
  `landcount` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `reference_index` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `largemap_token` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`hectad_ref`),
  KEY `completed` (`completed`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `hectad_stat`
--

CREATE TABLE IF NOT EXISTS `hectad_stat` (
  `reference_index` tinyint(4) DEFAULT '1',
  `x` int(11) NOT NULL DEFAULT '0',
  `y` int(11) NOT NULL DEFAULT '0',
  `hectad` varchar(7) NOT NULL DEFAULT '',
  `landsquares` smallint(5) unsigned DEFAULT '0',
  `images` int(11) unsigned NOT NULL DEFAULT '0',
  `geographs` mediumint(8) unsigned DEFAULT '0',
  `squares` smallint(5) unsigned NOT NULL DEFAULT '0',
  `geosquares` smallint(5) unsigned NOT NULL DEFAULT '0',
  `users` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `first_submitted` datetime DEFAULT NULL,
  `last_submitted` datetime DEFAULT NULL,
  `map_token` varchar(128) DEFAULT NULL,
  `largemap_token` varchar(128) DEFAULT NULL,
  `ftfusers` mediumint(8) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`hectad`),
  KEY `reference_index` (`reference_index`),
  KEY `geosquares` (`geosquares`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `kmlcache`
--

CREATE TABLE IF NOT EXISTS `kmlcache` (
  `url` varchar(64) NOT NULL DEFAULT '',
  `filename` varchar(64) NOT NULL DEFAULT '',
  `filesize` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `level` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rendered` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`url`),
  KEY `rendered` (`rendered`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `landrangermaps`
--

CREATE TABLE IF NOT EXISTS `landrangermaps` (
  `number` varchar(4) NOT NULL DEFAULT '',
  `name` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`number`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `landrangeroutlines1`
--

CREATE TABLE IF NOT EXISTS `landrangeroutlines1` (
  `map` char(3) NOT NULL DEFAULT '',
  `left` smallint(6) NOT NULL DEFAULT '0',
  `bottom` smallint(6) NOT NULL DEFAULT '0',
  `right` smallint(6) NOT NULL DEFAULT '0',
  `top` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`map`),
  KEY `bottom` (`left`,`bottom`,`right`,`top`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `loc_abgaz`
--

CREATE TABLE IF NOT EXISTS `loc_abgaz` (
  `gaz_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(128) NOT NULL DEFAULT '',
  `gridref` varchar(7) NOT NULL DEFAULT '',
  `hcounty` varchar(64) NOT NULL DEFAULT '',
  `acounty` varchar(64) NOT NULL DEFAULT '',
  `district` varchar(64) NOT NULL DEFAULT '',
  `ua` varchar(64) NOT NULL DEFAULT '',
  `police` varchar(64) NOT NULL DEFAULT '',
  `region` varchar(64) NOT NULL DEFAULT '',
  `e` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `n` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `point_en` point NOT NULL DEFAULT '',
  PRIMARY KEY (`gaz_id`),
  KEY `gridref` (`gridref`),
  SPATIAL KEY `point_en` (`point_en`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `loc_adm1`
--

CREATE TABLE IF NOT EXISTS `loc_adm1` (
  `country` char(2) NOT NULL DEFAULT '',
  `adm1` char(2) NOT NULL DEFAULT '0',
  `name` varchar(32) NOT NULL DEFAULT '',
  `reference_index` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`adm1`,`country`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `loc_counties`
--

CREATE TABLE IF NOT EXISTS `loc_counties` (
  `county_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `e` int(11) NOT NULL DEFAULT '0',
  `n` int(11) NOT NULL DEFAULT '0',
  `name` varchar(64) NOT NULL DEFAULT '',
  `reference_index` tinyint(4) NOT NULL DEFAULT '0',
  `point_en` point NOT NULL DEFAULT '',
  PRIMARY KEY (`county_id`),
  SPATIAL KEY `point_en` (`point_en`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `loc_counties_pre74`
--

CREATE TABLE IF NOT EXISTS `loc_counties_pre74` (
  `county_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `e` int(11) NOT NULL DEFAULT '0',
  `n` int(11) NOT NULL DEFAULT '0',
  `name` varchar(64) NOT NULL DEFAULT '',
  `reference_index` tinyint(4) NOT NULL DEFAULT '0',
  `wgs84_lat` decimal(10,6) NOT NULL DEFAULT '0.000000',
  `wgs84_long` decimal(10,6) NOT NULL DEFAULT '0.000000',
  `point_en` point NOT NULL DEFAULT '',
  PRIMARY KEY (`county_id`),
  SPATIAL KEY `point_en` (`point_en`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `loc_country`
--

CREATE TABLE IF NOT EXISTS `loc_country` (
  `code` char(2) NOT NULL DEFAULT '',
  `name` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `loc_dsg`
--

CREATE TABLE IF NOT EXISTS `loc_dsg` (
  `code` enum('ADM1','ADM2','ADM3','ADMD','AGRF','AIRF','AIRQ','ANCH','ANS','ARCH','AREA','BAR','BAY','BCH','BDG','BLDG','BNK','BOG','BTL','CAPE','CARN','CAVE','CHN','CHNM','CHNN','CLF','CMP','CNL','COVE','CRNT','CSTL','CSWY','DCK','DCKB','DEPU','DPR','EST','ESTY','FLLS','FLTT','FRM','FRST','FT','GAP','GDN','GRGE','HBR','HDLD','HLL','HLLS','HSE','HSEC','HSTS','HTH','HTL','INLT','ISL','ISLS','ISLT','LCTY','LDGU','LGN','LK','LKS','LOCK','LTHSE','MDW','MNMT','MOOR','MRSH','MT','MTS','NRWS','NVB','OVF','PAL','PASS','PEN','PIER','PK','PKS','PLN','PND','POOL','PPL','PPLC','PPLL','PPLX','PRK','PROM','PRSH','PRT','PT','RCH','RD','RDA','RDGE','RDST','RECR','RESN','RF','RGN','RK','RKS','RR','RSTN','RSV','RUIN','SAND','SD','SHOL','SLCE','SLP','SPIT','ST','STM','STMC','STMM','STMX','STRT','TNL','TOWR','UPLD','VAL','WHRL') NOT NULL DEFAULT 'ADM1',
  `name` varchar(64) NOT NULL DEFAULT '',
  `text` varchar(64) NOT NULL DEFAULT '',
  `class` varchar(64) NOT NULL DEFAULT '',
  KEY `code` (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `loc_hier`
--

CREATE TABLE IF NOT EXISTS `loc_hier` (
  `level` tinyint(2) NOT NULL,
  `community_id` int(9) unsigned NOT NULL,
  `contains_cid_min` int(9) unsigned NOT NULL,
  `contains_cid_max` int(9) unsigned NOT NULL,
  `capital_cid` int(9) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `mappal` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`level`,`community_id`),
  KEY `contains` (`contains_cid_min`,`contains_cid_max`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `loc_hier_old`
--

CREATE TABLE IF NOT EXISTS `loc_hier_old` (
  `level` tinyint(2) NOT NULL,
  `community_id` int(9) unsigned NOT NULL,
  `contains_cid_min` int(9) unsigned NOT NULL,
  `contains_cid_max` int(9) unsigned NOT NULL,
  `capital_cid` int(9) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `mappal` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`level`,`community_id`),
  KEY `contains` (`contains_cid_min`,`contains_cid_max`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `loc_hier_stat`
--

CREATE TABLE IF NOT EXISTS `loc_hier_stat` (
  `level` tinyint(2) NOT NULL,
  `community_id` int(9) unsigned NOT NULL,
  `squares_total` int(8) unsigned NOT NULL DEFAULT '0',
  `squares_geo` int(8) unsigned NOT NULL DEFAULT '0',
  `images_total` int(8) unsigned NOT NULL DEFAULT '0',
  `squares_submitted` int(8) unsigned NOT NULL DEFAULT '0',
  `tenk_total` int(8) unsigned NOT NULL DEFAULT '0',
  `geographs_submitted` int(8) unsigned NOT NULL DEFAULT '0',
  `tenk_submitted` int(8) unsigned NOT NULL DEFAULT '0',
  `images_thisweek` int(8) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`level`,`community_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `loc_placenames`
--

CREATE TABLE IF NOT EXISTS `loc_placenames` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dsg` enum('ADM1','ADMD','ANCH','ANS','AREA','BAR','BAY','BCH','BDG','BLDG','BNK','BOG','BRKS','BRKW','CAPE','CARN','CAVE','CH','CHN','CHNM','CLF','CMP','CNFL','CNL','COVE','CRKT','CSTL','DCK','DCKB','DCKY','DPR','EST','ESTY','FLD','FLLS','FLTT','FORD','FRM','FRST','FSR','FT','GAP','GASF','GRGE','GRVE','HBR','HDLD','HLL','HLLS','HMCK','HSE','HSEC','HSP','HTL','INDS','INLT','ISL','ISLS','ISLT','LCTY','LGN','LK','LKS','MDW','MN','MNMT','MOOR','MRSH','MSTY','MT','MTS','MUS','NRWS','OBPT','OILR','PASS','PCLI','PEN','PIER','PK','PLDR','PLN','PND','POOL','PPL','PPLA','PPLC','PPLL','PPLX','PRK','PRN','PRSH','PRT','PS','PT','QUAY','RD','RDGE','RDST','RF','RGN','RJCT','RK','RKS','RNGA','RPDS','RSTN','RSTP','RSV','RUIN','SAND','SCH','SCHC','SD','SEA','SHOL','SLP','SPIT','SPNG','SPUR','STM','STMA','STMM','STNE','STRT','TMB','TOWR','TRL','TRMO','VAL','WEIR','WLL','WTRW','ZZZZZ') NOT NULL DEFAULT 'ADM1',
  `adm1` char(2) NOT NULL DEFAULT '',
  `full_name` varchar(64) NOT NULL DEFAULT '',
  `full_name_soundex` varchar(32) NOT NULL DEFAULT '',
  `gns_ufi` int(11) NOT NULL,
  `gns_uni` int(11) NOT NULL,
  `nametype` char(1) NOT NULL,
  `e` int(11) NOT NULL DEFAULT '0',
  `n` int(11) NOT NULL DEFAULT '0',
  `reference_index` tinyint(4) NOT NULL DEFAULT '1',
  `country` enum('uk','ie') NOT NULL,
  `point_en` point NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `full_name` (`full_name`),
  SPATIAL KEY `point_en` (`point_en`),
  KEY `gns_ufi` (`gns_ufi`),
  KEY `dsg` (`dsg`,`reference_index`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `loc_postcodes`
--

CREATE TABLE IF NOT EXISTS `loc_postcodes` (
  `e` int(11) NOT NULL DEFAULT '0',
  `n` int(11) NOT NULL DEFAULT '0',
  `code` varchar(7) NOT NULL DEFAULT '',
  `reference_index` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `loc_ppl`
--

CREATE TABLE IF NOT EXISTS `loc_ppl` (
  `UFI` int(11) NOT NULL DEFAULT '0',
  `UNI` int(11) NOT NULL DEFAULT '0',
  `LAT` decimal(8,7) NOT NULL DEFAULT '0.0000000',
  `LONG` decimal(8,7) NOT NULL DEFAULT '0.0000000',
  `DSG` varchar(5) NOT NULL DEFAULT '',
  `NT` char(1) NOT NULL DEFAULT '',
  `FULL_NAME_ND` varchar(200) NOT NULL DEFAULT '',
  `full_name_soundex` varchar(32) NOT NULL DEFAULT '',
  `e` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `n` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `county` enum('','Avon','Bedfordshire','Berkshire','Buckinghamshire','Cambridgeshire','Central','Cheshire','Cleveland','Clwyd','Cornwall','Cumbria','Derbyshire','Devon','Dorset','Dumfries and Galloway','Durham','Dyfed','East Sussex','Essex','Fife','Gloucestershire','Grampian','Greater London','Greater Manchester','Gwent','Gwynedd','Hampshire','Hereford and Worcester','Hertfordshire','Highland','Humberside','Isle of Wight','Kent','Lancashire','Leicestershire','Lincolnshire','Lothian','Louth','Merseyside','Mid Glamorgan','Monaghan','Norfolk','North Yorkshire','Northamptonshire','Northern Ireland','Northumberland','Nottinghamshire','Orkney Islands','Oxfordshire','Powys','Scottish Borders','Shetland','Shropshire','Somerset','South Glamorgan','South Yorkshire','Staffordshire','Strathclyde','Suffolk','Surrey','Tayside','Tyne and Wear','Warwickshire','West Glamorgan','West Midlands','West Sussex','West Yorkshire','Western Isles','Wiltshire') NOT NULL DEFAULT '',
  PRIMARY KEY (`UNI`),
  KEY `e` (`e`,`n`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Cut-down database from 30-03-2006';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `loc_towns`
--

CREATE TABLE IF NOT EXISTS `loc_towns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `short_name` varchar(50) NOT NULL DEFAULT '',
  `e` mediumint(6) unsigned NOT NULL DEFAULT '0',
  `n` mediumint(7) unsigned NOT NULL DEFAULT '0',
  `x` int(11) NOT NULL DEFAULT '0',
  `y` int(11) NOT NULL DEFAULT '0',
  `cx` int(11) NOT NULL DEFAULT '0',
  `cy` int(11) NOT NULL DEFAULT '0',
  `s` enum('1','2','3','4','5','') NOT NULL DEFAULT '1',
  `reference_index` tinyint(4) NOT NULL DEFAULT '0',
  `quad` tinyint(4) NOT NULL DEFAULT '0',
  `point_en` point NOT NULL DEFAULT '',
  `point_xy` point NOT NULL DEFAULT '',
  `point_cxy` point NOT NULL DEFAULT '',
  `community_id` int(9) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `gis` (`e`,`n`,`reference_index`),
  SPATIAL KEY `point_en` (`point_en`),
  SPATIAL KEY `point_xy` (`point_xy`),
  KEY `cxy` (`cx`,`cy`),
  SPATIAL KEY `point_cxy` (`point_cxy`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `loc_wikipedia`
--

CREATE TABLE IF NOT EXISTS `loc_wikipedia` (
  `url` varchar(255) NOT NULL DEFAULT '',
  `exists` char(1) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `text` varchar(255) NOT NULL DEFAULT '',
  `iscity` varchar(255) NOT NULL DEFAULT '',
  `country` char(2) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mapcache`
--

CREATE TABLE IF NOT EXISTS `mapcache` (
  `map_x` int(8) NOT NULL DEFAULT '0',
  `map_y` int(8) NOT NULL DEFAULT '0',
  `max_x` int(8) NOT NULL DEFAULT '0',
  `max_y` int(8) NOT NULL DEFAULT '0',
  `image_w` smallint(6) unsigned NOT NULL DEFAULT '0',
  `image_h` smallint(6) unsigned NOT NULL DEFAULT '0',
  `pixels_per_km` float NOT NULL DEFAULT '0',
  `type_or_user` smallint(6) NOT NULL DEFAULT '0',
  `force_ri` smallint(6) NOT NULL DEFAULT '0',
  `mercator` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `overlay` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `layers` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `level` smallint(3) NOT NULL DEFAULT '0',
  `tile_x` mediumint(8) NOT NULL DEFAULT '0',
  `tile_y` mediumint(8) NOT NULL DEFAULT '0',
  `age` smallint(5) unsigned NOT NULL DEFAULT '0',
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tile_x`,`tile_y`,`image_w`,`image_h`,`level`,`type_or_user`,`force_ri`,`mercator`,`overlay`,`layers`),
  KEY `xy` (`mercator`,`map_x`,`map_y`,`max_x`,`max_y`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mapfix_log`
--

CREATE TABLE IF NOT EXISTS `mapfix_log` (
  `mapfix_log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `gridsquare_id` int(10) unsigned NOT NULL DEFAULT '0',
  `level` tinyint(2) NOT NULL DEFAULT '-2',
  `community_id` int(9) unsigned NOT NULL DEFAULT '0',
  `comment` varchar(128) NOT NULL,
  `new_percent_land` tinyint(4) DEFAULT NULL,
  `old_percent_land` tinyint(4) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`mapfix_log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `milestone`
--

CREATE TABLE IF NOT EXISTS `milestone` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `gridimage_id` int(10) unsigned NOT NULL DEFAULT '0',
  `milestone` mediumint(6) unsigned NOT NULL DEFAULT '0',
  KEY `user_id` (`user_id`),
  KEY `gridimage_id` (`gridimage_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `moderation_log`
--

CREATE TABLE IF NOT EXISTS `moderation_log` (
  `moderation_log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `gridimage_id` int(10) unsigned NOT NULL DEFAULT '0',
  `new_status` enum('rejected','pending','accepted','geograph') NOT NULL DEFAULT 'rejected',
  `old_status` enum('rejected','pending','accepted','geograph','') NOT NULL DEFAULT 'rejected',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `type` enum('dummy','real') NOT NULL DEFAULT 'dummy',
  PRIMARY KEY (`moderation_log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `os_gaz`
--

CREATE TABLE IF NOT EXISTS `os_gaz` (
  `seq` mediumint(6) unsigned NOT NULL,
  `km_ref` varchar(7) NOT NULL DEFAULT '',
  `def_nam` varchar(60) NOT NULL DEFAULT '',
  `tile_ref` varchar(4) NOT NULL DEFAULT '',
  `lat_deg` tinyint(4) NOT NULL DEFAULT '0',
  `lat_min` double(3,1) NOT NULL DEFAULT '0.0',
  `long_deg` tinyint(4) NOT NULL DEFAULT '0',
  `long_min` double(3,1) NOT NULL DEFAULT '0.0',
  `north` mediumint(7) unsigned NOT NULL,
  `east` mediumint(6) unsigned NOT NULL,
  `gmt` enum('E','W') NOT NULL,
  `co_code` enum('AB','AG','AN','AR','BA','BB','BC','BD','BE','BF','BG','BH','BI','BL','BM','BN','BO','BP','BR','BS','BT','BU','BX','BY','BZ','CA','CB','CD','CE','CF','CH','CL','CM','CN','CT','CU','CV','CW','CY','DB','DD','DE','DG','DL','DN','DR','DT','DU','DY','DZ','EA','EB','ED','EG','EL','EN','ER','ES','EX','EY','FA','FF','FL','GH','GL','GR','GW','GY','HA','HD','HE','HF','HG','HI','HL','HN','HP','HR','HS','HT','HV','IA','IL','IM','IN','IS','IW','KC','KG','KH','KL','KN','KT','LA','LB','LC','LD','LL','LN','LO','LP','LS','LT','MA','MB','ME','MI','MK','MM','MO','MR','MT','NA','NC','ND','NE','NG','NH','NI','NK','NL','NN','NP','NR','NS','NT','NW','NY','OH','OK','ON','PB','PE','PK','PL','PO','PW','PY','RB','RC','RD','RE','RG','RH','RL','RO','RT','SA','SB','SC','SD','SE','SF','SG','SH','SI','SJ','SK','SL','SM','SN','SO','SP','SQ','SR','SS','ST','SU','SV','SW','SX','SY','SZ','TB','TF','TH','TR','TS','TU','VG','WA','WB','WC','WD','WE','WF','WG','WH','WI','WJ','WK','WL','WM','WN','WO','WP','WR','WS','WT','WW','WX','XX','YK','YS','YT','YY') NOT NULL,
  `county` enum('0','Aberd','Angus','Arg & Bt','Bark & Dag','Barnet','Barns','Bath & NE Somer','Beds','Bexley','Birm','Black w Dar','Blackp','Blae Gw','Bolton','Bourne','Brackn','Brad','Brent','Brig','Brom','Bucks','Bury','C of Aber','C of Bri & Hov','C of Bris','C of Derb','C of Dun','C of Edin','C of Glas','C of K upon H','C of Leic','C of Lon','C of Nott','C of Peterb','C of Plym','C of Port','C of Soton','C of Stoke','C of West','C of Wolv','Caer','Cald','Cambs','Camden','Card','Carm','Cered','Ches','Clackm','Conwy','Corn','Cov','Croy','Cumbr','D & G','Darl','Denb','Derby','Devon','Donc','Dorset','Dudley','Durham','E Ayr','E Dunb','E Loth','E Renf','E Susx','E Yorks','Ealing','Enf','Essex','Falk','Fife','Flint','Ghead','Glos','Gren','Gwyn','Hack','Halton','Ham & Ful','Hants','Hargy','Harrow','Hartpl','Hav','Heref','Herts','Highld','Hill','Houns','I of Angl','I of M','I of W','I Scilly','Inverc','Isling','Ken & Ch','Kent','King','Kirk','Know','Lam','Lancs','Leeds','Leic','Lew','Lincs','Liv','Luton','Man','Medway','Merth Tyd','Merton','Midd','Midlo','Mil Key','Monm','Moray','N Ayr','N Eil','N Lanak','N Lincs','N Som','N Tyne','N upon Ty','N Yks','NE Lincs','Newham','Newp','Norf','Northnts','Northum','Notts','Nth Pt Talb','Oldham','Orkney','Oxon','Pemb','Poole','Powys','Pth & Kin','Read','Red & Cl','Redbr','Renf','Rho Cyn Taf','Rich','Roch','Roth','Rut','S Ayr','S Glos','S Lanak','S Tyne','Salf','Sand','Scot Bord','Sefton','Sheff','Shetld','Shrops','Slough','Sol','Somer','Sou-on-Sea','St Hel','Staffs','Sthwk','Stir','Stock','Stock on T','Suff','Sund','Surrey','Sutton','Swan','Swin','T Ham','Tames','Thurr','Torbay','Torf','Traf','V of Glam','W Berks','W Dunb','W Loth','W Susx','Wakf','Wal F','Wals','Wan','Warr','Warw','Wigan','Wilts','Win & Maid','Wirral','Wok','Worcs','Wrekin','Wrex','York') NOT NULL,
  `full_county` enum('Aberdeen City','Aberdeenshire','Angus','Argyll and Bute','Barking & Dagenham','Barnet','Barnsley','Bath and North East Somerset','Bedfordshire','Bexley','Birmingham','Blackburn with Darwen','Blackpool','Blaenau Gwent','Bolton','Bournemouth','Bracknell Forest','Bradford','Brent','Bridgend','Bromley','Buckinghamshire','Bury','Caerphilly','Calderdale','Cambridgeshire','Camden','Cardiff','Carmarthenshire','Ceredigion','Cheshire','City of Brighton and Hove','City of Bristol','City of Derby','City of Edinburgh','City of Kingston upon Hull','City of Leicester','City of London','City of Nottingham','City of Peterborough','City of Plymouth','City of Portsmouth','City of Southampton','City of Stoke-on-Trent','City of Westminster','City of Wolverhampton','Clackmannanshire','Conwy','Cornwall','Coventry','Croydon','Cumbria','Darlington','Denbighshire','Derbyshire','Devon','Doncaster','Dorset','Dudley','Dumfries and Galloway','Dundee City','Durham','Ealing','East Ayrshire','East Dunbartonshire','East Lothian','East Renfrewshire','East Riding of Yorkshire','East Sussex','Enfield','Essex','Falkirk','Fife','Flintshire','Gateshead','Glasgow City','Gloucestershire','Greenwich','Gwynedd','Hackney','Halton','Hammersmith &Fulham','Hampshire','Haringey','Harrow','Hartlepool','Havering','Herefordshire','Hertfordshire','Highland','Hillingdon','Hounslow','Inverclyde','Isle of Anglesey','Isle of Man','Isle of Wight','Isles of Scilly','Islington','Kent','Kingston upon Thames','Kirklees','Knowsley','Lambeth','Lancashire','Leeds','Leicestershire','Lewisham','Lincolnshire','Liverpool','Luton','Manchester','Medway','Merthyr Tydfil','Merton','Middlesbrough','Midlothian','Milton Keynes','Monmouthshire','Moray','Na h-Eileanan an Iar','Neath Port Talbot','Newcastle upon Tyne','Newham','Newport','Norfolk','North Ayrshire','North East Lincolnshire','North Lanarkshire','North Lincolnshire','North Somerset','North Tyneside','North Yorkshire','Northamptonshire','Northumberland','Nottinghamshire','Oldham','Orkney Islands','Oxfordshire','Pembrokeshire','Perth and Kinross','Poole','Powys','Reading','Redbridge','Redcar & Cleveland','Renfrewshire','Rhondda,Cynon,Taff','Richmond upon Thames','Rochdale','Rotherham','Royal Borough of Kensington & Chelsea','Rutland','Salford','Sandwell','Scottish Borders','Sefton','Sheffield','Shetland Islands','Shropshire','Slough','Solihull','Somerset','South Ayrshire','South Gloucestershire','South Lanarkshire','South Tyneside','Southend-on-Sea','Southwark','St Helens','Staffordshire','Stirling','Stockport','Stockton on Tees','Suffolk','Sunderland','Surrey','Sutton','Swansea','Swindon','Tameside','Telford and Wrekin','The Vale of Glamorgan','Thurrock','Torbay','Torfaen','Tower Hamlets','Trafford','Wakefield','Walsall','Waltham Forest','Wandsworth','Warrington','Warwickshire','West Berkshire','West Dunbartonshire','West Lothian','West Sussex','Wigan','Wiltshire','Windsor and Maidenhead','Wirral','Wokingham','Worcestershire','Wrexham','XXXXXXXX','York') NOT NULL,
  `f_code` enum('C','T','O','A','F','FM','H','R','W','X') NOT NULL DEFAULT 'C',
  `e_date` varchar(11) NOT NULL DEFAULT '',
  `update_co` enum('I','U') NOT NULL,
  `sheet_1` smallint(6) NOT NULL DEFAULT '0',
  `sheet_2` smallint(6) NOT NULL DEFAULT '0',
  `sheet_3` smallint(6) NOT NULL DEFAULT '0',
  `def_nam_soundex` varchar(32) NOT NULL DEFAULT '',
  `point_en` point NOT NULL DEFAULT '',
  `hcounty` enum('','Aberdeenshire','Anglesey','Angus','Argyllshire','Ayrshire','Banffshire','Bedfordshire','Bedfordshire / Hertfordshire','Beds, pre-1844 part in det pt of Hunts','Berks, pre-1844 in det pt of Wilts','Berks, pre-1844 part in det pt of Wilts','Berkshire','Berwickshire','Brecknockshire','Brecknockshire / Monmouthshire','Brecknockshire / Radnorshire','Buckinghamshire','Bucks, pre-1844 in det pt of Herts','Bucks, pre-1844 in det pt of Oxon','Buteshire','Caernarfon (det), locally in Denbighs','Caernarfonshire','Caernarfonshire / Merioneth','Caithness','Cambridgeshire','Cambridgeshire / Hertfordshire','Cambridgeshire / Hunts','Cambridgeshire / Norfolk','Cambridgeshire / Suffolk','Cardiganshire','Carmarthenshire','Carmarthenshire / Brecknock','Cheshire','Cheshire / Flintshire','Clackmannanshire','Cornwall','Cornwall, pre-1884 in det pt of Devon','Cromartyshire','Cromartyshire / Ross-shire','Cumberland','Denbighshire','Denbighshire / Caernarfons','Denbighshire / Flintshire','Denbighshire / Montgomeryshire','Derbys (det), locally in Leics','Derbyshire','Derbyshire / Leicestershire','Derbyshire / Nottinghamshire','Devon','Devon / Dorset','Devon / Somerset','Devon, pre-1844 in det pt of Dorset','Dorset','Dorset / Hampshire','Dorset, pre-1844 in det pt of Devon','Dorset, pre-1844 in det pt of Som''set','Dumfriesshire','Dunbartonshire','Durham','East Lothian','Essex','Fife','Flints (det), locally in Denbighs','Flintshire','Flintshire / Denbighshire','Glamorgan','Glamorgan / Brecknockshire','Gloucestershire','Gloucestershire / Herefords','Gloucs, pre-1844 in det pt of Wilts','Gloucs, pre-1844 in det pt of Worcs','Hampshire','Hampshire / Berkshire','Hampshire / Sussex','Hampshire / Wiltshire','Herefds, pre-1844 in det pt of Gloucs','Herefordshire','Herefordshire / Gloucestershire','Hertfordshire','Hertfordshire / Bedfordshire','Hertfordshire / Buckinghams','Hertfordshire / Middlesex','Huntingdonshire','Hunts (det), locally in Beds','Inverness (det), locally in Moray','Inverness-shire','Isles of Scilly','Kent','Kent / Surrey','Kent / Sussex','Kincardineshire','Kinross-shire','Kirkcudbrightshire','Lanarkshire','Lancashire','Lancs / Ches / Yorks, W.R.','Leicestershire','Leicestershire / Derbyshire','Leicestershire / Lincolnshire','Leicestershire / Northants','Lincolnshire','Lincolnshire / Cambridgeshire','Lincolnshire / Yorks, West Riding','Merioneth','Merioneth / Montgomeryshire','Middlesex','Middlesex / Hertfordshire','Midlothian','Monmouthshire','Montgomeryshire','Moray (det), locally in Inverness','Morayshire','Morayshire / Inverness-shir','N''thumb, pre-1844 in det pt of Durham','Nairnshire','Norfolk','Norfolk / Cambridgeshire','Norfolk / Suffolk','Northamptonshire','Northumberland','Nottinghamshire','Orkney','Oxfordshire','Oxon, pre-1844 in det pt of Bucks','Peeblesshire','Pembrokeshire','Perthshire','Perthshire (det), locally in Fife','Radnorshire','Renfrewshire','Ross-shire','Roxburghshire','Rutland','Selkirk (det), locally in Roxburgh','Selkirkshire','Selkirkshire / Roxburghshire','Shetland','Shropshire','Shropshire / Montgomeryshire','Shropshire / Staffordshire','Somerset','Somerset / Devon','Somerset / Dorset','Staffordshire','Staffordshire / Cheshire','Staffordshire / Warwickshire','Staffordshire / Worcestershire','Stirling (det), locally in Clackman','Stirlingshire','Suffolk','Suffolk / Cambridgeshire','Suffolk / Essex','Suffolk / Norfolk','Surrey','Surrey / Kent','Surrey / Sussex','Sussex','Sussex / Hampshire','Sussex / Surrey','Sussex, pre-1844 in det pt of Hants','Sutherland','Warks, pre-1844 in det pt of Gloucs','Warwickshire','Warwickshire / Leicestershire','Warwickshire / Staffordshire','Warwickshire / Worcestershire','West Lothian','Westmorland','Wigtownshire','Wilts, pre-1844 in det pt of Gloucs','Wiltshire','Wiltshire / Berkshire','Wiltshire / Hampshire','Worcestershire','Worcestershire / Shropshire','Worcestershire / Warwickshire','Worcs (det), locally in Gloucs','Worcs (det), locally in Herefds','Worcs (det), locally in Staffs','Worcs (det), locally in Warks','Worcs, pre-1844 in det pt of Herefds','Worcs, pre-1844 in det pt of Shrops','Worcs, pre-1844 in det pt of Staffs','Worcs, pre-1844 in det pt of Warks','Yorkshire, East Riding','Yorkshire, North Riding','Yorkshire, W.R.','Yorkshire, West Riding','Yorkshire, West Riding / Li','Yorkshire, West Riding / Notts') NOT NULL,
  PRIMARY KEY (`seq`),
  KEY `east` (`east`,`north`,`f_code`),
  KEY `def_nam_soundex` (`def_nam_soundex`,`f_code`),
  SPATIAL KEY `point_en` (`point_en`),
  KEY `co_code` (`co_code`),
  KEY `tile_ref` (`tile_ref`),
  KEY `def_nam` (`def_nam`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `os_gaz_250`
--

CREATE TABLE IF NOT EXISTS `os_gaz_250` (
  `seq` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `def_name` varchar(250) COLLATE latin1_german2_ci NOT NULL,
  `full_county` varchar(250) COLLATE latin1_german2_ci NOT NULL,
  `east` mediumint(8) unsigned NOT NULL,
  `north` mediumint(8) unsigned NOT NULL,
  `def_nam_soundex` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `point_en` point NOT NULL DEFAULT '',
  PRIMARY KEY (`seq`),
  SPATIAL KEY `point_en` (`point_en`),
  KEY `def_nam_soundex` (`def_nam_soundex`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `os_gaz_code`
--

CREATE TABLE IF NOT EXISTS `os_gaz_code` (
  `f_code` char(2) NOT NULL DEFAULT '',
  `code_name` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`f_code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `os_gaz_county`
--

CREATE TABLE IF NOT EXISTS `os_gaz_county` (
  `name` varchar(60) NOT NULL DEFAULT '',
  `co_code` enum('AB','AG','AN','AR','BA','BB','BC','BD','BE','BF','BG','BH','BI','BL','BM','BN','BO','BP','BR','BS','BT','BU','BX','BY','BZ','CA','CB','CD','CE','CF','CH','CL','CM','CN','CT','CU','CV','CW','CY','DB','DD','DE','DG','DL','DN','DR','DT','DU','DY','DZ','EA','EB','ED','EG','EL','EN','ER','ES','EX','EY','FA','FF','FL','GH','GL','GR','GW','GY','HA','HD','HE','HF','HG','HI','HL','HN','HP','HR','HS','HT','HV','IA','IL','IM','IN','IS','IW','KC','KG','KH','KL','KN','KT','LA','LB','LC','LD','LL','LN','LO','LP','LS','LT','MA','MB','ME','MI','MK','MM','MO','MR','MT','NA','NC','ND','NE','NG','NH','NI','NK','NL','NN','NP','NR','NS','NT','NW','NY','OH','OK','ON','PB','PE','PK','PL','PO','PW','PY','RB','RC','RD','RE','RG','RH','RL','RO','RT','SA','SB','SC','SD','SE','SF','SG','SH','SI','SJ','SK','SL','SM','SN','SO','SP','SQ','SR','SS','ST','SU','SV','SW','SX','SY','SZ','TB','TF','TH','TR','TS','TU','VG','WA','WB','WC','WD','WE','WF','WG','WH','WI','WJ','WK','WL','WM','WN','WO','WP','WR','WS','WT','WW','WX','XX','YK','YS','YT','YY') NOT NULL,
  `c` bigint(21) NOT NULL DEFAULT '0',
  `e` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `n` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `reference_index` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `country` char(1) NOT NULL DEFAULT '',
  PRIMARY KEY (`name`),
  KEY `co_code` (`co_code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `queries`
--

CREATE TABLE IF NOT EXISTS `queries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `searchdesc` varchar(255) NOT NULL DEFAULT '',
  `searchclass` enum('County','GridRef','Placename','Postcode','Random','Text','All','Special') NOT NULL DEFAULT 'All',
  `searchq` varchar(255) NOT NULL DEFAULT '',
  `searchtext` varchar(255) NOT NULL DEFAULT '',
  `limit1` varchar(7) NOT NULL DEFAULT '',
  `limit2` enum('','geograph','geograph,accepted','geograph,accepted,pending','pending','accepted','rejected') NOT NULL DEFAULT '',
  `limit3` varchar(32) NOT NULL DEFAULT '',
  `limit4` char(1) NOT NULL DEFAULT '',
  `limit5` char(3) NOT NULL DEFAULT '',
  `limit6` varchar(32) NOT NULL DEFAULT '',
  `limit7` varchar(32) NOT NULL DEFAULT '',
  `limit8` smallint(5) NOT NULL DEFAULT '0',
  `limit9` int(10) unsigned NOT NULL DEFAULT '0',
  `limit10` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `limit11` varchar(32) NOT NULL DEFAULT '',
  `x` smallint(5) NOT NULL DEFAULT '0',
  `y` smallint(5) NOT NULL DEFAULT '0',
  `displayclass` enum('full','text','thumbs','slide','more','spelling','thumbsmore','search','searchtext','reveal','cluster','moremod','piclens','gmap','cluster2','vote') NOT NULL DEFAULT 'full',
  `resultsperpage` smallint(5) NOT NULL DEFAULT '15',
  `orderby` varchar(32) NOT NULL DEFAULT '',
  `breakby` varchar(32) NOT NULL DEFAULT '',
  `crt_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `searchuse` enum('search','flickr','gazetteer','discuss','syndicator') NOT NULL DEFAULT 'search',
  `use_timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `favorite` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `queries_archive`
--

CREATE TABLE IF NOT EXISTS `queries_archive` (
  `id` int(10) unsigned NOT NULL,
  `searchdesc` varchar(255) NOT NULL DEFAULT '',
  `searchclass` enum('County','GridRef','Placename','Postcode','Random','Text','All','Special') NOT NULL DEFAULT 'All',
  `searchq` varchar(255) NOT NULL DEFAULT '',
  `searchtext` varchar(255) NOT NULL DEFAULT '',
  `limit1` varchar(7) NOT NULL DEFAULT '',
  `limit2` enum('','geograph','geograph,accepted','geograph,accepted,pending','pending','accepted','rejected') NOT NULL DEFAULT '',
  `limit3` varchar(32) NOT NULL DEFAULT '',
  `limit4` char(1) NOT NULL DEFAULT '',
  `limit5` char(3) NOT NULL DEFAULT '',
  `limit6` varchar(32) NOT NULL DEFAULT '',
  `limit7` varchar(32) NOT NULL DEFAULT '',
  `limit8` smallint(5) NOT NULL DEFAULT '0',
  `limit9` int(10) unsigned NOT NULL DEFAULT '0',
  `limit10` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `limit11` varchar(32) NOT NULL DEFAULT '',
  `x` smallint(5) NOT NULL DEFAULT '0',
  `y` smallint(5) NOT NULL DEFAULT '0',
  `displayclass` enum('full','text','thumbs','slide','more','spelling','thumbsmore','search','searchtext','reveal','cluster','moremod','piclens','gmap','cluster2','vote') NOT NULL DEFAULT 'full',
  `resultsperpage` smallint(5) NOT NULL DEFAULT '15',
  `orderby` varchar(32) NOT NULL DEFAULT '',
  `breakby` varchar(32) NOT NULL DEFAULT '',
  `crt_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `searchuse` enum('search','flickr','gazetteer','discuss','syndicator') NOT NULL DEFAULT 'search',
  `use_timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `favorite` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `queries_count`
--

CREATE TABLE IF NOT EXISTS `queries_count` (
  `id` int(10) unsigned NOT NULL DEFAULT '0',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `queries_featured`
--

CREATE TABLE IF NOT EXISTS `queries_featured` (
  `id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `comment` varchar(100) COLLATE latin1_german2_ci NOT NULL,
  `approved` tinyint(4) NOT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `stickied` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `recent_gridimage`
--

CREATE TABLE IF NOT EXISTS `recent_gridimage` (
  `gridimage_id` int(11) NOT NULL DEFAULT '0',
  `added` datetime DEFAULT NULL,
  PRIMARY KEY (`gridimage_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `route`
--

CREATE TABLE IF NOT EXISTS `route` (
  `route_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL DEFAULT '',
  `reference_index` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `orderby` varchar(32) NOT NULL DEFAULT '',
  `enabled` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `crt_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `route_group` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`route_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `route_item`
--

CREATE TABLE IF NOT EXISTS `route_item` (
  `gridref` varchar(7) NOT NULL DEFAULT '',
  `route_id` mediumint(8) unsigned NOT NULL DEFAULT '1',
  `routeitem_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`routeitem_id`),
  KEY `route_id` (`route_id`),
  KEY `gridref` (`gridref`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `SESSKEY` varchar(64) NOT NULL DEFAULT '',
  `EXPIRY` int(11) unsigned NOT NULL DEFAULT '0',
  `EXPIREREF` varchar(64) DEFAULT NULL,
  `DATA` text NOT NULL,
  `ipaddr` varchar(45) NOT NULL DEFAULT '',
  PRIMARY KEY (`SESSKEY`),
  KEY `SESSKEY` (`SESSKEY`,`EXPIRY`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `smarty_cache_page`
--

CREATE TABLE IF NOT EXISTS `smarty_cache_page` (
  `CacheID` varchar(255) COLLATE latin1_german2_ci NOT NULL,
  `TemplateFile` varchar(255) COLLATE latin1_german2_ci NOT NULL,
  `GroupCache` varchar(255) COLLATE latin1_german2_ci NOT NULL,
  PRIMARY KEY (`CacheID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sph_counter`
--

CREATE TABLE IF NOT EXISTS `sph_counter` (
  `counter_id` varchar(64) NOT NULL,
  `max_doc_id` int(11) NOT NULL,
  PRIMARY KEY (`counter_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `throttle`
--

CREATE TABLE IF NOT EXISTS `throttle` (
  `used` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `feature` enum('ecard','usermsg') NOT NULL DEFAULT 'ecard',
  KEY `user_id` (`user_id`,`feature`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `password` varchar(64) DEFAULT NULL,
  `salt` varchar(8) NOT NULL DEFAULT '',
  `realname` varchar(128) DEFAULT NULL,
  `credit_realname` varchar(128) NOT NULL,
  `email` varchar(128) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `rights` set('basic','moderator','admin','ticketmod','traineemod','suspicious','dormant','mapmod','forum') DEFAULT NULL,
  `signup_date` datetime DEFAULT NULL,
  `nickname` varchar(128) DEFAULT NULL,
  `public_email` int(11) DEFAULT '0',
  `rank` int(10) unsigned NOT NULL DEFAULT '0',
  `to_rise_rank` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `slideshow_delay` tinyint(3) unsigned NOT NULL DEFAULT '5',
  `search_results` tinyint(3) unsigned NOT NULL DEFAULT '15',
  `about_yourself` text NOT NULL,
  `public_about` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `age_group` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `use_age_group` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `home_gridsquare` mediumint(8) unsigned DEFAULT NULL,
  `ticket_option` enum('all','major','digest','none') NOT NULL DEFAULT 'all',
  `default_style` enum('white','black','gray') NOT NULL DEFAULT 'white',
  `role` varchar(32) NOT NULL DEFAULT '',
  `message_sig` varchar(250) NOT NULL DEFAULT '',
  `ticket_public` enum('no','owner','everyone') NOT NULL DEFAULT 'everyone',
  `calendar_public` enum('no','registered','everyone') NOT NULL DEFAULT 'no',
  `http_host` varchar(64) NOT NULL DEFAULT '',
  `upload_size` smallint(5) unsigned NOT NULL DEFAULT '0',
  `clear_exif` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`user_id`),
  KEY `rights` (`rights`,`role`),
  KEY `realname` (`realname`),
  KEY `nickname` (`nickname`),
  KEY `email` (`email`),
  FULLTEXT KEY `realname_2` (`realname`,`nickname`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_change`
--

CREATE TABLE IF NOT EXISTS `user_change` (
  `user_id` int(10) unsigned NOT NULL,
  `field` enum('realname','nickname') COLLATE latin1_german2_ci NOT NULL,
  `value` varchar(128) COLLATE latin1_german2_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_delete`
--

CREATE TABLE IF NOT EXISTS `user_delete` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `password` varchar(64) DEFAULT NULL,
  `salt` varchar(8) NOT NULL DEFAULT '',
  `realname` varchar(128) DEFAULT NULL,
  `credit_realname` varchar(128) NOT NULL,
  `email` varchar(128) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `rights` set('basic','moderator','admin','ticketmod','traineemod','suspicious','dormant','mapmod','forum') DEFAULT NULL,
  `signup_date` datetime DEFAULT NULL,
  `nickname` varchar(128) DEFAULT NULL,
  `public_email` int(11) DEFAULT '0',
  `rank` int(10) unsigned NOT NULL DEFAULT '0',
  `to_rise_rank` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `slideshow_delay` tinyint(3) unsigned NOT NULL DEFAULT '5',
  `search_results` tinyint(3) unsigned NOT NULL DEFAULT '15',
  `about_yourself` text NOT NULL,
  `public_about` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `age_group` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `use_age_group` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `home_gridsquare` mediumint(8) unsigned DEFAULT NULL,
  `ticket_option` enum('all','major','digest','none') NOT NULL DEFAULT 'all',
  `default_style` enum('white','black','gray') NOT NULL DEFAULT 'white',
  `role` varchar(32) NOT NULL DEFAULT '',
  `message_sig` varchar(250) NOT NULL DEFAULT '',
  `ticket_public` enum('no','owner','everyone') NOT NULL DEFAULT 'everyone',
  `calendar_public` enum('no','registered','everyone') NOT NULL DEFAULT 'no',
  `http_host` varchar(64) NOT NULL DEFAULT '',
  `upload_size` smallint(5) unsigned NOT NULL DEFAULT '0',
  `clear_exif` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`user_id`),
  KEY `rights` (`rights`,`role`),
  KEY `realname` (`realname`),
  KEY `nickname` (`nickname`),
  KEY `email` (`email`),
  FULLTEXT KEY `realname_2` (`realname`,`nickname`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_emailchange`
--

CREATE TABLE IF NOT EXISTS `user_emailchange` (
  `user_emailchange_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `oldemail` varchar(128) DEFAULT NULL,
  `newemail` varchar(128) DEFAULT NULL,
  `requested` datetime DEFAULT NULL,
  `completed` datetime DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT NULL,
  PRIMARY KEY (`user_emailchange_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_gridsquare`
--

CREATE TABLE IF NOT EXISTS `user_gridsquare` (
  `user_id` int(11) NOT NULL DEFAULT '0',
  `grid_reference` varchar(7) CHARACTER SET latin1 NOT NULL DEFAULT '',
  KEY `user_id` (`user_id`,`grid_reference`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_log`
--

CREATE TABLE IF NOT EXISTS `user_log` (
  `userlog_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `event` enum('login','pwdchange','mailchange','register') NOT NULL DEFAULT 'login',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mailsent` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`userlog_id`),
  KEY `user_id` (`user_id`),
  KEY `created` (`created`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_quadsquare`
--

CREATE TABLE IF NOT EXISTS `user_quadsquare` (
  `gridsquare_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `quadsquares` bigint(21) NOT NULL DEFAULT '0',
  KEY `user_id` (`user_id`,`gridsquare_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_stat`
--

CREATE TABLE IF NOT EXISTS `user_stat` (
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `images` mediumint(5) unsigned NOT NULL DEFAULT '0',
  `squares` mediumint(5) unsigned NOT NULL DEFAULT '0',
  `geosquares` smallint(5) unsigned NOT NULL DEFAULT '0',
  `geo_rank` smallint(5) unsigned NOT NULL DEFAULT '0',
  `geo_rise` smallint(5) unsigned NOT NULL DEFAULT '0',
  `points` mediumint(5) unsigned NOT NULL DEFAULT '0',
  `points_rank` smallint(5) unsigned NOT NULL DEFAULT '0',
  `points_rise` smallint(5) unsigned NOT NULL DEFAULT '0',
  `geographs` mediumint(5) unsigned NOT NULL DEFAULT '0',
  `days` smallint(5) unsigned NOT NULL DEFAULT '0',
  `depth` decimal(6,2) NOT NULL DEFAULT '0.00',
  `myriads` tinyint(5) unsigned NOT NULL DEFAULT '0',
  `hectads` smallint(3) unsigned NOT NULL DEFAULT '0',
  `last` int(11) unsigned NOT NULL DEFAULT '0',
  `content` mediumint(5) unsigned NOT NULL DEFAULT '0',
  `selfrate_like` mediumint(5) unsigned NOT NULL DEFAULT '0',
  `selfrate_site` mediumint(5) unsigned NOT NULL DEFAULT '0',
  `selfrate_qual` mediumint(5) unsigned NOT NULL DEFAULT '0',
  `selfrate_info` mediumint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  KEY `points` (`points`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Stellvertreter-Struktur des Views `user_stat_view`
--
CREATE TABLE IF NOT EXISTS `user_stat_view` (
`user_id` int(11) unsigned
,`images` mediumint(5) unsigned
,`images_d` double
);
-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_vote_stat`
--

CREATE TABLE IF NOT EXISTS `user_vote_stat` (
  `user_id` int(11) unsigned NOT NULL,
  `type` enum('info','like','site','qual') NOT NULL,
  `votes` int(11) unsigned NOT NULL DEFAULT '0',
  `votes1` int(11) unsigned NOT NULL DEFAULT '0',
  `votes2` int(11) unsigned NOT NULL DEFAULT '0',
  `votes3` int(11) unsigned NOT NULL DEFAULT '0',
  `votes4` int(11) unsigned NOT NULL DEFAULT '0',
  `votes5` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `vision_queue`
--

CREATE TABLE IF NOT EXISTS `vision_queue` (
  `gridimage_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`gridimage_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `vision_results`
--

CREATE TABLE IF NOT EXISTS `vision_results` (
  `id` bigint(20) unsigned NOT NULL,
  `file` varchar(128) NOT NULL,
  `type` varchar(32) NOT NULL,
  `mid` varchar(32) NOT NULL,
  `description` varchar(128) NOT NULL,
  `score` float NOT NULL,
  `boundingPoly` varchar(32) DEFAULT NULL,
  `locations` varchar(32) DEFAULT NULL,
  `locale` varchar(32) DEFAULT NULL,
  `created` datetime NOT NULL,
  `validated` tinyint(3) unsigned DEFAULT NULL,
  `auto_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`auto_id`),
  KEY `id` (`id`),
  KEY `mid` (`mid`),
  KEY `description` (`description`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `vision_results_del`
--

CREATE TABLE IF NOT EXISTS `vision_results_del` (
  `id` bigint(20) unsigned NOT NULL,
  `file` varchar(128) NOT NULL,
  `type` varchar(32) NOT NULL,
  `mid` varchar(32) NOT NULL,
  `description` varchar(128) NOT NULL,
  `score` float NOT NULL,
  `boundingPoly` varchar(32) DEFAULT NULL,
  `locations` varchar(32) DEFAULT NULL,
  `locale` varchar(32) DEFAULT NULL,
  `created` datetime NOT NULL,
  `validated` tinyint(3) unsigned DEFAULT NULL,
  `auto_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`auto_id`),
  KEY `id` (`id`),
  KEY `mid` (`mid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `vote_log`
--

CREATE TABLE IF NOT EXISTS `vote_log` (
  `vote_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) COLLATE latin1_german2_ci NOT NULL,
  `id` int(10) unsigned NOT NULL,
  `vote` tinyint(4) NOT NULL,
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(10) unsigned NOT NULL,
  `ipaddr` int(10) unsigned NOT NULL,
  `final` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`vote_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `vote_stat`
--

CREATE TABLE IF NOT EXISTS `vote_stat` (
  `type` varchar(20) COLLATE latin1_german2_ci NOT NULL,
  `id` int(10) unsigned NOT NULL,
  `num` mediumint(8) unsigned NOT NULL,
  `users` mediumint(8) unsigned NOT NULL,
  `avg` float NOT NULL,
  `std` float NOT NULL,
  `baysian` float NOT NULL,
  `v1` mediumint(8) unsigned NOT NULL,
  `v2` mediumint(8) unsigned NOT NULL,
  `v3` mediumint(8) unsigned NOT NULL,
  `v4` mediumint(8) unsigned NOT NULL,
  `v5` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`type`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `wordnet`
--

CREATE TABLE IF NOT EXISTS `wordnet` (
  `gid` int(11) NOT NULL DEFAULT '0',
  `len` char(1) NOT NULL DEFAULT '',
  `words` varchar(64) NOT NULL DEFAULT '',
  `title` smallint(6) NOT NULL DEFAULT '0',
  `comment` smallint(6) NOT NULL DEFAULT '0',
  `total` smallint(6) NOT NULL DEFAULT '0',
  KEY `len` (`len`,`words`),
  KEY `gid` (`gid`),
  KEY `gid_2` (`gid`,`words`),
  KEY `gid_3` (`gid`,`title`,`words`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `wordnet1`
--

CREATE TABLE IF NOT EXISTS `wordnet1` (
  `gid` int(11) NOT NULL DEFAULT '0',
  `words` varchar(64) NOT NULL DEFAULT '',
  `title` smallint(6) NOT NULL DEFAULT '0',
  KEY `gid_2` (`gid`,`words`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `wordnet2`
--

CREATE TABLE IF NOT EXISTS `wordnet2` (
  `gid` int(11) NOT NULL DEFAULT '0',
  `words` varchar(64) NOT NULL DEFAULT '',
  `title` smallint(6) NOT NULL DEFAULT '0',
  KEY `gid_2` (`gid`,`words`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `wordnet3`
--

CREATE TABLE IF NOT EXISTS `wordnet3` (
  `gid` int(11) NOT NULL DEFAULT '0',
  `words` varchar(64) NOT NULL DEFAULT '',
  `title` smallint(6) NOT NULL DEFAULT '0',
  KEY `gid_2` (`gid`,`words`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_tables`
--

CREATE TABLE IF NOT EXISTS `_tables` (
  `table_name` varchar(64) COLLATE latin1_german2_ci NOT NULL,
  `description` text COLLATE latin1_german2_ci NOT NULL,
  `type` enum('primary','secondary','derivied','temp','static','primary_archive','old') COLLATE latin1_german2_ci NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`table_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

-- --------------------------------------------------------

--
-- Struktur des Views `user_stat_view`
--
DROP TABLE IF EXISTS `user_stat_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`dbu1036181`@`server%.dbadmin.hosteurope.de` SQL SECURITY DEFINER VIEW `user_stat_view` AS select `user_stat`.`user_id` AS `user_id`,`user_stat`.`images` AS `images`,pow(10,(floor(log10(`user_stat`.`images`)) + 1)) AS `images_d` from `user_stat`;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
