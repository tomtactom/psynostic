<?php

	include("helper.inc.php");

	//Hier muss der ganze PHP code hin
	if(isset($_POST['websitesettings'])) {
			$siteurl = trim((string)($_POST['siteurl'] ?? ''));
			$sitename = trim((string)($_POST['sitename'] ?? ''));
			$sitedescription = trim((string)($_POST['sitedescription'] ?? ''));
			$keywordsmain = trim((string)($_POST['keywordsmain'] ?? ''));
			$adminemail = trim((string)($_POST['adminemail'] ?? ''));
			$country = (string)($_POST['country'] ?? '');
			$robots = (string)($_POST['robots'] ?? '');
			$allowregister = (string)($_POST['allowregister'] ?? '');
			$author = trim((string)($_POST['author'] ?? ''));
			$mainrole = (string)($_POST['mainrole'] ?? '');
			$backenddesign = (string)($_POST['backenddesign'] ?? '');
			$frontenddesign = (string)($_POST['frontenddesign'] ?? '');
			$maincolor = trim((string)($_POST['maincolor'] ?? ''));
			$mainfontcolor = trim((string)($_POST['mainfontcolor'] ?? ''));
			$mainbackgroundcolor = trim((string)($_POST['mainbackgroundcolor'] ?? ''));
			$mainhovercolor = trim((string)($_POST['mainhovercolor'] ?? ''));
			$font = (string)($_POST['font'] ?? '');
			$mindestalter = trim((string)($_POST['mindestalter'] ?? ''));
			$language = (string)($_POST['language'] ?? '');
			$fontname = trim((string)($_POST['fontname'] ?? ''));
			$recaptcha_secretkey = trim((string)($_POST['recaptcha_secretkey'] ?? ''));
			$recaptcha_sitekey = trim((string)($_POST['recaptcha_sitekey'] ?? ''));
			$created = date('Y-m-d');
		//Überprüfe ob alle Felder ausgefüllt worden sind
		if(empty($siteurl) || empty($sitename) || empty($sitedescription) || empty($keywordsmain) || empty($adminemail) || empty($country) || empty($author) || empty($mainrole) || empty($backenddesign) || empty($maincolor) || empty($frontenddesign) || empty($mainfontcolor) || empty($mainbackgroundcolor) || empty($mainhovercolor) || empty($font) || empty($mindestalter) || empty($language) || empty($recaptcha_secretkey) || empty($recaptcha_sitekey)) {
			$error_msg = 'Bitte alle Felder ausfüllen';
			$error = true;
		}
		//überprüfe die Minimale Stringlänge
		if(strlen($siteurl) > 255 || strlen($sitename) > 255 || strlen($sitedescription) > 255 || strlen($keywordsmain) > 255 || strlen($adminemail) > 255 || strlen($country) > 255 || strlen($author) > 255 || strlen($mainrole) > 10 || strlen($backenddesign) > 255 || strlen($maincolor) != 7 || strlen($frontenddesign) > 255 || strlen($mainfontcolor) != 7 || strlen($mainbackgroundcolor) != 7 || strlen($mainhovercolor) != 7 || strlen($font) > 255 || strlen($mindestalter) > 3 || strlen($language) > 10 || strlen($fontname) > 255 || strlen($recaptcha_secretkey) > 255 || strlen($recaptcha_sitekey) > 255) {
			$error_msg = 'Mindestens eine Eingabe war ungültig! Bitte maximal 255 Zeichen pro Feld eingeben.';
			$error = true;
		}
		//überprüfe die Minimale Stringlänge
		if(strlen($siteurl) < 6 || strlen($sitedescription) < 12 || strlen($keywordsmain) < 12 || strlen($adminemail) < 6 || strlen($author) < 3) {
			$error_msg = 'Mindestens eine Eingabe war ungültig! Bitte halte dich an die Mindesteingabe';
			$error = true;
		}
		//URL muss noch getrimmt werden
		if(!filter_var($siteurl, FILTER_VALIDATE_URL)) {
			$error_msg = 'Bitte gebe einen gültigen URL an';
			$error = true;
		}
		$siteurl = trim($siteurl, '/');

		if(!filter_var($adminemail, FILTER_VALIDATE_EMAIL)) {
			$error_msg = 'Bitte eine gültige Administrator E-Mail-Adresse eingeben';
			$error = true;
		}
		//Überprüfe ob selection Felder richtig gesetzt worden sind
		if ($country != 'germany' && $country != 'austria' && $country != 'switzerland' && $country != 'luxembourg' && $country != 'lichtenstein' && $country != 'unitedstates' && $country != 'unitedkingdom' && $country != 'canada' && $country != 'australia' && $country != 'newzealand' && $country != 'ireland') {
			$error_msg = 'Bitte gebe ein gültiges Land an';
			$error= true;
		}
		if ($language != 'german' && $language != 'english') {
			$error_msg = 'Bitte gebe eine gültige Sprache an';
			$error= true;
		}
		if ($robots != '0' && $robots != '1' && $robots !== 1 && $robots !== 0) {
			$error_msg = 'Bitte wähle aus ob die Seite indexiert werden soll oder nicht';
			$error= true;
		}
		if ($allowregister !== '0' && $allowregister !== '1') {
			$error_msg = 'Bitte wähle aus ob Registrierungen erlaubt sein sollen';
			$error= true;
		}
		if ($mainrole != 'user' && $mainrole != 'member' && $mainrole != 'supporter') {
			$error_msg = 'Bitte wähle eine gültige Standartrolle aus';
			$error= true;
		}
		//Speichert alles in der config Datei
		if (!isset($error)) {
			$stmt = $db->prepare("UPDATE `option` SET `option_value` = ?, `updated_at` = CURRENT_TIMESTAMP() WHERE `option_name` = ?");
			$stmt->bind_param("ss", $value, $name);

			$name = 'siteurl';
			$value = $siteurl;
			$stmt->execute();

			$name = 'sitename';
			$value = $sitename;
			$stmt->execute();

			$name = 'sitedescription';
			$value = $sitedescription;
			$stmt->execute();

			$name = 'keywordsmain';
			$value = $keywordsmain;
			$stmt->execute();

			$name = 'adminemail';
			$value = $adminemail;
			$stmt->execute();

			$name = 'country';
			$value = $country;
			$stmt->execute();

			$name = 'robots';
			$value = $robots;
			$stmt->execute();

			$name = 'allowregister';
			$value = $allowregister;
			$stmt->execute();

			$name = 'author';
			$value = $author;
			$stmt->execute();

			$name = 'mainrole';
			$value = $mainrole;
			$stmt->execute();

			$name = 'backenddesign';
			$value = $backenddesign;
			$stmt->execute();

			$name = 'frontenddesign';
			$value = $frontenddesign;
			$stmt->execute();

			$name = 'maincolor';
			$value = $maincolor;
			$stmt->execute();

			$name = 'mainfontcolor';
			$value = $mainfontcolor;
			$stmt->execute();

			$name = 'mainbackgroundcolor';
			$value = $mainbackgroundcolor;
			$stmt->execute();

			$name = 'mainhovercolor';
			$value = $mainhovercolor;
			$stmt->execute();

			$name = 'font';
			$value = $font;
			$stmt->execute();

			$name = 'fontname';
			$value = $fontname;
			$stmt->execute();

			$name = 'mindestalter';
			$value = $mindestalter;
			$stmt->execute();

			$name = 'language';
			$value = $language;
			$stmt->execute();

			$name = 'recaptcha_sitekey';
			$value = $recaptcha_sitekey;
			$stmt->execute();

			$name = 'recaptcha_secretkey';
			$value = $recaptcha_secretkey;
			$stmt->execute();

			$name = 'created';
			$value = $created;
			$stmt->execute();

			$stmt->close();

			$success_msg = "Alle Felder wurden erfolgreich gespeichert.";
			$goToNextStep = true;
		}
	}

	// show error
	include("templates/websitesettings.inc.php");
?>
