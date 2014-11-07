DROP TABLE IF EXISTS `gui_acl_privileges`;
CREATE TABLE `gui_acl_privileges` (
  `role_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `allow` varchar(25) NOT NULL,
  PRIMARY KEY (`role_id`,`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `gui_acl_privileges` values(1,1,'post,get');


DROP TABLE IF EXISTS `gui_acl_resources`;
CREATE TABLE `gui_acl_resources` (
  `resource_id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_name` varchar(50) NOT NULL,
  PRIMARY KEY (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
INSERT INTO `gui_acl_resources` VALUES (1, 'route:Account\\Handler\\LoginHandler');


DROP TABLE IF EXISTS `gui_acl_roles`;
CREATE TABLE `gui_acl_roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(25) NOT NULL,
  `role_parent` int(11) DEFAULT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

INSERT INTO `gui_acl_roles` VALUES (1, 'guest',NULL),(2, 'developer',1),(3, 'administrator',2),(4, 'root',3);


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
  `creation_time` timestamp NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `gui_metadata`;
CREATE TABLE `gui_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL,
  `version` varchar(10) NOT NULL,
  `data` text,
  `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `gui_snapshots`;
CREATE TABLE `gui_snapshots` (
  `id` int(11) NOT NULL,
  `name` varchar(25) NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  `creation_time` timestamp NOT NULL,
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

INSERT INTO `gui_users` VALUES (1, 'u_9d9dxf9d9sf', 'admin', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'admin@local.dev', 'root');


DROP TABLE IF EXISTS `gui_webapi_keys`;
CREATE TABLE `gui_webapi_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(25) NOT NULL,
  `username` varchar(25) NOT NULL,
  `name` varchar(25) NOT NULL,
  `hash` varchar(255) NOT NULL,
  `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
INSERT INTO `gui_webapi_keys` (`key`, `username`, `name`, `hash`) VALUES ('wk_x8838f8x8x8', 'admin', 'apiuser', '42ba756218bc239402d402deef12d6aca30efb9708a125f245c1f752a7f2c473');


#  Server Stuff 

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
  `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_time` timestamp DEFAULT 0,
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
  `creation_time` timestamp NOT NULL,
  `repeats` tinyint(1) NOT NULL DEFAULT '0',
  `show_at` timestamp DEFAULT 0,
  `notified` tinyint(1) DEFAULT '0',
  `extra_data` text,
  `server_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;