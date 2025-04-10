CREATE TABLE IF NOT EXISTS `#__patchtester_pulls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pull_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` varchar(150) NOT NULL DEFAULT '',
  `pull_url` varchar(255) NOT NULL,
  `sha` varchar(40) NOT NULL DEFAULT '',
  `is_rtc` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__patchtester_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pull_id` int(11) NOT NULL,
  `data` longtext NOT NULL,
  `patched_by` int(11) NOT NULL,
  `applied` int(11) NOT NULL,
  `applied_version` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
