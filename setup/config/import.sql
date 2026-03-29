CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `passwort` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `vorname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nachname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `passwortcode` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `passwortcode_time` timestamp NULL DEFAULT NULL,
  `role` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `biography` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `birthday` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `gender` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `authenticationcode` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `authenticationcode_time` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE (`email`), UNIQUE (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `securitytokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(10) NOT NULL,
  `identifier` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `securitytoken` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `Statistiken` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `timeofview` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `link` text COLLATE utf8_unicode_ci NOT NULL,
  `wasonsite` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ip` text COLLATE utf8_unicode_ci NOT NULL,
  `country` text COLLATE utf8_unicode_ci NOT NULL,
  `browsername` text COLLATE utf8_unicode_ci NOT NULL,
  `browserversion` text COLLATE utf8_unicode_ci NOT NULL,
  `platform` text COLLATE utf8_unicode_ci NOT NULL,
  `useragent` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `faq` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `question` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `questiondate` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `answer` text COLLATE utf8_unicode_ci,
  `answerdate` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `option` (
  `option_id` int(10) UNSIGNED NOT NULL,
  `option_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `option_value` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  UNIQUE (`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT IGNORE INTO `option` (`option_id`, `option_name`, `option_value`) VALUES
	(1, 'siteurl', ''),
	(2, 'sitename', ''),
	(3, 'sitedescription', ''),
	(4, 'adminemail', ''),
	(5, 'mindestalter', ''),
	(6, 'country', ''),
	(7, 'robots', ''),
	(8, 'allowregister', ''),
	(9, 'keywordsmain', ''),
	(10, 'mainrole', ''),
	(11, 'backenddesign', ''),
	(12, 'frontenddesign', ''),
	(13, 'maincolor', ''),
	(14, 'mainfontcolor', ''),
	(15, 'mainbackgroundcolor', ''),
	(16, 'mainhovercolor', ''),
	(17, 'font', ''),
	(18, 'created', ''),
	(19, 'author', ''),
	(20, 'language', ''),
	(21, 'version', '1.0'),
	(22, 'fontname', ''),
  (23, 'recaptcha_secretkey', ''),
  (24, 'recaptcha_sitekey', '');

CREATE TABLE IF NOT EXISTS `questionnaires` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `intro_text` text COLLATE utf8mb4_unicode_ci,
  `standard_rules_json` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_questionnaires_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `questionnaire_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `questionnaire_id` int(10) unsigned NOT NULL,
  `item_no` int(10) unsigned NOT NULL,
  `item_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `scale_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'likert',
  `likert_min` int(10) NOT NULL,
  `likert_max` int(10) NOT NULL,
  `is_reversed` tinyint(1) NOT NULL DEFAULT 0,
  `subscale_key` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_questionnaire_items_questionnaire_id` (`questionnaire_id`),
  CONSTRAINT `fk_questionnaire_items_questionnaire_id`
    FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `questionnaire_demographic_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `questionnaire_id` int(10) unsigned NOT NULL,
  `field_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `allowed_values_json` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_questionnaire_demographic_fields_questionnaire_id` (`questionnaire_id`),
  CONSTRAINT `fk_questionnaire_demographic_fields_questionnaire_id`
    FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `questionnaire_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `questionnaire_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at` timestamp NULL DEFAULT NULL,
  `completion_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_questionnaire_sessions_questionnaire_id` (`questionnaire_id`),
  KEY `idx_questionnaire_sessions_user_id` (`user_id`),
  CONSTRAINT `fk_questionnaire_sessions_questionnaire_id`
    FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_questionnaire_sessions_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `questionnaire_answers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int(10) unsigned NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  `raw_value` decimal(10,4) DEFAULT NULL,
  `scored_value` decimal(10,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_questionnaire_answers_session_id` (`session_id`),
  CONSTRAINT `fk_questionnaire_answers_session_id`
    FOREIGN KEY (`session_id`) REFERENCES `questionnaire_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_questionnaire_answers_item_id`
    FOREIGN KEY (`item_id`) REFERENCES `questionnaire_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `questionnaire_scores` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int(10) unsigned NOT NULL,
  `score_type` enum('total','subscale') COLLATE utf8mb4_unicode_ci NOT NULL,
  `score_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `raw_mean` decimal(10,4) DEFAULT NULL,
  `raw_sum` decimal(10,4) DEFAULT NULL,
  `n_answered` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_questionnaire_scores_session_id` (`session_id`),
  KEY `idx_questionnaire_scores_score_key` (`score_key`),
  CONSTRAINT `fk_questionnaire_scores_session_id`
    FOREIGN KEY (`session_id`) REFERENCES `questionnaire_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `norm_tables` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `questionnaire_id` int(10) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `csv_schema_version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_norm_tables_questionnaire_id` (`questionnaire_id`),
  CONSTRAINT `fk_norm_tables_questionnaire_id`
    FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `norm_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `norm_table_id` int(10) unsigned NOT NULL,
  `group_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `demographic_rule_json` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_norm_groups_norm_table_id` (`norm_table_id`),
  CONSTRAINT `fk_norm_groups_norm_table_id`
    FOREIGN KEY (`norm_table_id`) REFERENCES `norm_tables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `norm_rows` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `norm_group_id` int(10) unsigned NOT NULL,
  `score_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `raw_min` decimal(10,4) DEFAULT NULL,
  `raw_max` decimal(10,4) DEFAULT NULL,
  `norm_label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `norm_value` decimal(10,4) DEFAULT NULL,
  `percentile` decimal(6,2) DEFAULT NULL,
  `t_score` decimal(6,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_norm_rows_norm_group_id` (`norm_group_id`),
  KEY `idx_norm_rows_score_key` (`score_key`),
  CONSTRAINT `fk_norm_rows_norm_group_id`
    FOREIGN KEY (`norm_group_id`) REFERENCES `norm_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `questionnaire_reports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int(10) unsigned NOT NULL,
  `report_text_apa7` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_questionnaire_reports_session_id` (`session_id`),
  CONSTRAINT `fk_questionnaire_reports_session_id`
    FOREIGN KEY (`session_id`) REFERENCES `questionnaire_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
