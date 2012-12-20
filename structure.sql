CREATE TABLE IF NOT EXISTS `links_to_filter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` text NOT NULL,
  `nesting_level` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `is_good` enum('Y','N') NOT NULL DEFAULT 'N',
  `is_filtered` enum('Y','N') NOT NULL DEFAULT 'N', -- может не пригодиться
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;
