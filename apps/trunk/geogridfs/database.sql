-- MySQL dump 10.13  Distrib 5.6.23-72.1, for debian-linux-gnu (x86_64)
-- Server version	5.6.23-72.1-log

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
  `mode` varchar(10) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `identity` varchar(32) NOT NULL,
  `clause` varchar(255) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`task_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `drain_task`
--

CREATE TABLE `drain_task` (
  `task_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shard` int(10) unsigned NOT NULL DEFAULT '0',
  `files` bigint(21) NOT NULL DEFAULT '0',
  `bytes` bigint(21) NOT NULL DEFAULT '0',
  `clause` varchar(255) NOT NULL,
  `target` varchar(10) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `executed` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`task_id`),
  UNIQUE KEY `target` (`target`,`shard`,`clause`,`files`)
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
  `replicas` set('cream','jam','milk','scone','toast','teas1','teas2','teah1','teah2','cakes1','cakes2','cakeh1','cakeh2','amz') NOT NULL,
  `replica_count` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `replica_target` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `backups` set('amz','dwo','rhw','and','ovh','dsp','adc','mac','sph','uka') NOT NULL DEFAULT '',
  `backup_count` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `backup_target` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `meta_created` datetime NOT NULL,
  `meta_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `class` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`file_id`),
  UNIQUE KEY `filename` (`filename`),
  KEY `folder_id` (`folder_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `file_check`
--

CREATE TABLE `file_check` (
  `host` varchar(10) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `example` varchar(255) NOT NULL,
  `errors` text CHARACTER SET utf8 NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fixed` datetime NOT NULL,
  UNIQUE KEY `example` (`example`,`created`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `file_stat`
--

CREATE TABLE `file_stat` (
  `shard` int(10) unsigned NOT NULL DEFAULT '0',
  `class` varchar(32) NOT NULL DEFAULT '',
  `replicas` set('cream','jam','milk','scone','toast','teas1','teas2','teah1','teah2','cakes1','cakes2','cakeh1','cakeh2','amz') NOT NULL,
  `replica_count` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `replica_target` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `backups` set('amz','dwo','rhw','and','ovh','dsp','adc','mac','sph','uka') NOT NULL DEFAULT '',
  `backup_count` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `backup_target` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `example` varchar(255) NOT NULL,
  `count` bigint(21) NOT NULL DEFAULT '0',
  `bytes` bigint(21) unsigned NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`shard`,`class`,`replicas`,`replica_count`,`replica_target`,`backups`,`backup_count`,`backup_target`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `file_top_stat`
--

CREATE TABLE `file_top_stat` (
  `files` bigint(21) NOT NULL DEFAULT '0',
  `class` varchar(32) NOT NULL DEFAULT '',
  `replicas` set('cream','jam','milk','scone','toast','teas1','teas2','teah1','teah2','cakes1','cakes2','cakeh1','cakeh2') NOT NULL,
  `replica_count` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `replica_target` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `backups` set('amz','dwo','rhw','and','ovh','dsp','adc','mac','sph','uka') NOT NULL DEFAULT '',
  `backup_count` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `backup_target` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `total` decimal(30,0) DEFAULT NULL,
  `oldest` datetime DEFAULT NULL,
  `newest` datetime DEFAULT NULL,
  `example` varchar(255) NOT NULL
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
-- Table structure for table `folder_stat`
--

CREATE TABLE `folder_stat` (
  `folder_id` int(10) unsigned NOT NULL,
  `replicas` set('cream','jam','milk','scone','toast','teas1','teas2','teah1','teah2','cakes1','cakes2','cakeh1','cakeh2') NOT NULL,
  `files` bigint(21) NOT NULL DEFAULT '0',
  `bytes` decimal(30,0) DEFAULT NULL,
  `min(replica_count)` tinyint(3) unsigned DEFAULT NULL,
  `max(replica_count)` tinyint(3) unsigned DEFAULT NULL,
  `min(replica_target)` tinyint(3) unsigned DEFAULT NULL,
  `max(replica_target)` tinyint(3) unsigned DEFAULT NULL,
  KEY `folder_id` (`folder_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `mounts`
--

CREATE TABLE `mounts` (
  `mount` varchar(10) NOT NULL,
  `total` int(10) unsigned NOT NULL,
  `used` int(10) unsigned NOT NULL,
  `available` int(10) unsigned NOT NULL,
  `capacity` tinyint(3) unsigned NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mount`)
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
-- Table structure for table `repair_task`
--

CREATE TABLE `repair_task` (
  `task_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shard` int(10) unsigned NOT NULL DEFAULT '0',
  `files` bigint(21) NOT NULL DEFAULT '0',
  `bytes` bigint(21) NOT NULL DEFAULT '0',
  `clause` varchar(255) NOT NULL,
  `target` varchar(10) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `executed` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`task_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `replica_log`
--

CREATE TABLE `replica_log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `file_id` int(10) unsigned NOT NULL,
  `identity` varchar(10) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `replica_task`
--

CREATE TABLE `replica_task` (
  `task_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shard` int(10) unsigned NOT NULL DEFAULT '0',
  `files` bigint(21) NOT NULL DEFAULT '0',
  `bytes` bigint(21) NOT NULL DEFAULT '0',
  `clause` varchar(255) NOT NULL,
  `target` varchar(10) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `executed` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`task_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `speed_test`
--

CREATE TABLE `speed_test` (
  `host` varchar(10) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `replica` varchar(10) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `files` int(10) unsigned NOT NULL DEFAULT '0',
  `speed` float NOT NULL,
  `errors` int(10) unsigned NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `class` varchar(32) NOT NULL DEFAULT 'thumb.jpg'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `stat`
--

CREATE TABLE `stat` (
  `id` varchar(64) NOT NULL,
  `value` varchar(64) NOT NULL,
  `files` int(10) unsigned NOT NULL,
  `bytes` bigint(20) unsigned NOT NULL,
  `example` varchar(255) NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `stat_by_day`
--

CREATE TABLE `stat_by_day` (
  `day` varchar(10) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `files` bigint(21) NOT NULL DEFAULT '0',
  `total` decimal(30,0) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `test_task`
--

CREATE TABLE `test_task` (
  `task_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shard` int(10) unsigned NOT NULL DEFAULT '0',
  `files` bigint(21) NOT NULL DEFAULT '0',
  `bytes` bigint(21) NOT NULL DEFAULT '0',
  `clause` varchar(255) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `executed` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`task_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `thumb_dup`
--

CREATE TABLE `thumb_dup` (
  `md5sum` varchar(32) NOT NULL,
  `cnt` bigint(21) NOT NULL DEFAULT '0',
  `status` enum('new','pending','delt','invalid','deleted','unknown') NOT NULL DEFAULT 'new',
  `user_id` int(10) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `md5sum` (`md5sum`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `thumb_md5`
--

CREATE TABLE `thumb_md5` (
  `file_id` int(10) unsigned NOT NULL DEFAULT '0',
  `size` mediumint(8) unsigned NOT NULL,
  `md5sum` varchar(32) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_modified` datetime NOT NULL,
  KEY `md5sum` (`md5sum`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `usage_log`
--

CREATE TABLE `usage_log` (
  `filename` varchar(255) NOT NULL,
  `action` varchar(32) NOT NULL,
  `value` varchar(32) NOT NULL,
  `mount` varchar(32) NOT NULL,
  `server` varchar(32) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


-- Dump completed on 2016-06-02 18:35:32
