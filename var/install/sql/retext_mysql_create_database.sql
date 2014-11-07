DROP TABLE IF EXISTS `retext_codes`;
CREATE TABLE `retext_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(25) NOT NULL,
  `code` varchar(25) NOT NULL,
  `message` text NOT NULL,
  `mode` varchar(25) NOT NULL,
  `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `retext_messages`;
CREATE TABLE `retext_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(25) NOT NULL,
  `code` varchar(25) NOT NULL,
  `message_received` text NOT NULL,
  `message_sent` text NOT NULL,
  `status` varchar(25) NOT NULL DEFAULT 'pending',
  `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;