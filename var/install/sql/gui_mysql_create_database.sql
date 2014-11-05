DROP TABLE IF EXISTS `gui_acl_privileges`;
CREATE TABLE `gui_acl_privileges` (
  `role_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `allow` varchar(25) NOT NULL,
  PRIMARY KEY (`role_id`,`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `gui_acl_resources`;
CREATE TABLE `gui_acl_resources` (
  `resource_id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_name` varchar(50) NOT NULL,
  PRIMARY KEY (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `gui_acl_roles`;
CREATE TABLE `gui_acl_roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(25) NOT NULL,
  `role_parent` int(11) DEFAULT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

INSERT INTO `gui_acl_roles` VALUES (1,'guest',NULL),(2,'developer',1),(3,'administrator',2),(4,'root',3);


DROP TABLE IF EXISTS `gui_available_logs`;
CREATE TABLE `gui_available_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `filepath` varchar(255) NOT NULL,
  `directive` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `gui_hooks`;
CREATE TABLE `gui_hooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(25) NOT NULL,
  `username` varchar(50) NOT NULL,
  `end_point` varchar(255) NOT NULL,
  `mode` varchar(25) NOT NULL,
  `creation_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `gui_metadata`;
CREATE TABLE `gui_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL,
  `data` text NOT NULL,
  `creation_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

INSERT INTO `gui_metadata` VALUES (1,'gui_schema_version','1.0','2014-02-11 11:29:32');


DROP TABLE IF EXISTS `gui_snapshots`;
CREATE TABLE `gui_snapshots` (
  `id` int(11) NOT NULL,
  `name` varchar(25) NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  `creation_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `gui_users`;
CREATE TABLE `gui_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(25) NOT NULL,
  `name` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(50) NOT NULL,
  `role` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

INSERT INTO `gui_users` VALUES (1,'u_9d9dxf9d9sf','admin','2ddb205a2ac140044cfeab4e6d9f6adca05c415cd9272b5d04171207c7f520b0','admin@localhost','root');


DROP TABLE IF EXISTS `gui_webapi_keys`;
CREATE TABLE `gui_webapi_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(25) NOT NULL,
  `username` varchar(25) NOT NULL,
  `name` varchar(25) NOT NULL,
  `hash` varchar(255) NOT NULL,
  `creation_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;


INSERT INTO `gui_webapi_keys` VALUES (1,'wk_x8838f8x8x8','admin','apiuser','42ba756218bc239402d402deef12d6aca30efb9708a125f245c1f752a7f2c473','2014-02-11 11:35:16');


DROP TABLE IF EXISTS `server_event_actions`;
CREATE TABLE `server_event_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(25) NOT NULL,
  `event` varchar(25) NOT NULL,
  `email` varchar(50) NOT NULL,
  `custom_action` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `server_events`;
CREATE TABLE `server_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(25) NOT NULL,
  `gui_user_id` varchar(25) NOT NULL,
  `type` varchar(25) NOT NULL,
  `name` varchar(25) NOT NULL,
  `data` text NOT NULL,
  `creation_time` datetime NOT NULL,
  `sent_time` datetime NOT NULL,
  `response` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `server_event_actions`;
CREATE TABLE `server_event_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(25) NOT NULL,
  `name` varchar(25) NOT NULL,
  `email` varchar(50) NOT NULL,
  `custom_action` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `server_notifications_actions`;
CREATE TABLE `server_notifications_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(25) NOT NULL,
  `name` varchar(25) NOT NULL,
  `custom_action` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `server_notifications`;
CREATE TABLE `server_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(25) NOT NULL,
  `severity` int(11) NOT NULL,
  `creation_time` datetime NOT NULL,
  `repeats` tinyint(1) NOT NULL DEFAULT '0',
  `show_at` datetime DEFAULT NULL,
  `notified` tinyint(1) DEFAULT '0',
  `extra_data` text,
  `server_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;