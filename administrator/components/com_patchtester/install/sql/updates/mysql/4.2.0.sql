ALTER TABLE #__patchtester_pulls ADD is_draft TINYINT(1) UNSIGNED DEFAULT 0 NULL COMMENT 'If the pull request is a draft PR';
