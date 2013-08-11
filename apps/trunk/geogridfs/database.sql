-- MySQL dump 10.13  Distrib 5.5.24, for Linux (x86_64)
-- Server version	5.5.24-55

--
-- Table structure for table `api_log`
--

CREATE TABLE `api_log` (
  `log_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `remote_id` tinyint(4) NOT NULL,
  `command` varchar(30) NOT NULL,
  `folder` varchar(255) NOT NULL,
  `mode` varchar(20) NOT NULL,
  `ids` mediumint(8) unsigned NOT NULL,
  `ipaddr` int(10) unsigned NOT NULL,
  `useragent` varchar(128) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `backup_task`
--

CREATE TABLE `backup_task` (
  `task_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `start` int(10) unsigned DEFAULT NULL,
  `end` int(10) unsigned DEFAULT NULL,
  `files` bigint(21) NOT NULL DEFAULT '0',
  `mode` varchar(4) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `identity` varchar(32) NOT NULL,
  PRIMARY KEY (`task_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `file`
--

CREATE TABLE `file` (
  `file_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `folder_id` int(10) unsigned NOT NULL,
  `size` mediumint(8) unsigned NOT NULL,
  `file_created` datetime NOT NULL,
  `file_modified` datetime NOT NULL,
  `file_accessed` datetime NOT NULL,
  `md5sum` varchar(32) NOT NULL,
  `replicas` set('cream','jam','milk','scone','toast') NOT NULL,
  `replica_count` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `replica_target` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `backups` set('amz','dwo') NOT NULL DEFAULT '',
  `backup_count` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `backup_target` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `meta_created` datetime NOT NULL,
  `meta_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `class` enum('','full.jpg','original.jpg','thumb.jpg','detail.png','detail.jpg','base.png','thumb.gd','preview.jpg','pending.jpg','tile.png','tile.tif','kml','sitemap.gz','sitemap.html','torrents','templates','rss','.others') NOT NULL DEFAULT '',
  `replica_active` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`file_id`),
  UNIQUE KEY `filename` (`filename`),
  KEY `folder_id` (`folder_id`),
  KEY `replica_active` (`replica_active`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `folder`
--

CREATE TABLE `folder` (
  `folder_id` int(11) NOT NULL AUTO_INCREMENT,
  `folder` varchar(255) NOT NULL,
  `meta_created` datetime NOT NULL,
  `meta_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`folder_id`),
  UNIQUE KEY `folder` (`folder`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `remote`
--

CREATE TABLE `remote` (
  `remote_id` int(11) NOT NULL AUTO_INCREMENT,
  `identity` varchar(10) NOT NULL,
  `secret` varchar(32) NOT NULL,
  `active` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`remote_id`),
  UNIQUE KEY `identity` (`identity`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `stat`
--

CREATE TABLE `stat` (
  `id` varchar(64) NOT NULL,
  `value` varchar(64) NOT NULL,
  `files` int(10) unsigned NOT NULL,
  `total_bytes` bigint(20) unsigned NOT NULL,
  `folders` mediumint(8) unsigned NOT NULL,
  `oldest` datetime NOT NULL,
  `newest` datetime NOT NULL,
  `example` varchar(255) NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Dump completed on 2013-08-11  2:33:40
