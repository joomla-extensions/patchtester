ALTER TABLE `#__patchtester_pulls` ADD COLUMN `branch` varchar(255) NOT NULL DEFAULT '' AFTER `is_rtc`;
