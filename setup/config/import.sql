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
