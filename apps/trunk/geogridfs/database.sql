-- MySQL dump 10.13  Distrib 5.1.70, for debian-linux-gnu (x86_64)

--
-- Table structure for table `file`
--

CREATE TABLE `file` (
  `file_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `folder_id` int(10) unsigned NOT NULL,
  `size` mediumint(8) unsigned DEFAULT NULL,
  `file_created` datetime NOT NULL,
  `file_modified` datetime NOT NULL,
  `file_accessed` datetime NOT NULL,
  `md5sum` varchar(32) NOT NULL,
  `replicas` set('cream','jam','milk','scone','toast') NOT NULL,
  `replica_count` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `replica_target` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `backups` set('amz','dwo') NOT NULL,
  `backup_count` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `backup_target` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `meta_created` datetime NOT NULL,
  `meta_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `class` varchar(32) NOT NULL, --todo: change this into a enum!
  PRIMARY KEY (`file_id`),
  UNIQUE KEY `filename` (`filename`),
  KEY `folder_id` (`folder_id`),
  KEY `class` (`class`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `folder`
--

CREATE TABLE `folder` (
  `folder_id` int(11) NOT NULL AUTO_INCREMENT,
  `folder` varchar(255) NOT NULL,
  `folder_created` datetime NOT NULL,
  `folder_modified` datetime NOT NULL,
  `folder_accessed` datetime NOT NULL,
  `meta_created` datetime NOT NULL,
  `meta_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`folder_id`),
  UNIQUE KEY `folder` (`folder`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
