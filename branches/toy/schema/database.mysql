CREATE TABLE counter (
	`count` int unsigned not null default 0,
	`updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP
);
INSERT `counter` SET `count` = 0;


CREATE TABLE `event` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_name` varchar(64) DEFAULT NULL,
  `event_param` text,
  `posted` datetime DEFAULT NULL,
  `processed` datetime DEFAULT NULL,
  `instances` int(11) DEFAULT '1',
  `priority` int(11) DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


