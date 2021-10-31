CREATE TABLE IF NOT EXISTS `#__patchtester_pulls`
(
    `id`          int(11)                                 NOT NULL AUTO_INCREMENT,
    `pull_id`     int(11)                                 NOT NULL,
    `title`       varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
    `description` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    `pull_url`    varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `sha`         varchar(40) COLLATE utf8mb4_unicode_ci  NOT NULL DEFAULT '',
    `is_rtc`      tinyint(1)                              NOT NULL DEFAULT 0,
    `is_npm`      tinyint(1)                                       DEFAULT 0,
    `branch`      varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    `is_draft`    tinyint(1) unsigned                              DEFAULT 0 COMMENT 'If the pull request is a draft PR',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__patchtester_pulls_labels`
(
    `id`      int(11)                                 NOT NULL AUTO_INCREMENT,
    `pull_id` int(11)                                 NOT NULL,
    `name`    varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
    `color`   varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__patchtester_tests`
(
    `id`              int(11)     NOT NULL AUTO_INCREMENT,
    `pull_id`         int(11)     NOT NULL,
    `data`            longtext    NOT NULL,
    `patched_by`      int(11)     NOT NULL,
    `applied`         int(11)     NOT NULL,
    `applied_version` varchar(25) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE = utf8mb4_unicode_ci;
