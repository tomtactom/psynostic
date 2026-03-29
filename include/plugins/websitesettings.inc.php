<?php
	$success_msg = false;
		if(isset($_POST['save_settings'])) {
			$siteurl = $_POST['siteurl'];
			$sitename = $_POST['sitename'];
			$sitedescription = $_POST['sitedescription'];
			$keywordsmain = $_POST['keywordsmain'];
			$adminemail = $_POST['adminemail'];
			$country = $_POST['country'];
			$robots = $_POST['robots'];
			$allowregister = $_POST['allowregister'];
			$author = $_POST['author'];
			$mainrole = $_POST['mainrole'];
			$backenddesign = $_POST['backenddesign'];
			$frontenddesign = $_POST['frontenddesign'];
			$maincolor = $_POST['maincolor'];
			$mainfontcolor = $_POST['mainfontcolor'];
			$mainbackgroundcolor = $_POST['mainbackgroundcolor'];
			$mainhovercolor = $_POST['mainhovercolor'];
			$font = $_POST['font'];
			$mindestalter = $_POST['mindestalter'];
			$language = $_POST['language'];
			$recaptcha_secretkey = $_POST['recaptcha_secretkey'];
			$recaptcha_sitekey = $_POST['recaptcha_sitekey'];
			$fontname = $_POST['fontname'];
		//Überprüfe ob alle Felder ausgefüllt worden sind
		if(empty($siteurl) || empty($sitename) || empty($sitedescription) || empty($keywordsmain) || empty($adminemail) || empty($country) || empty($author) || empty($mainrole) || empty($backenddesign) || empty($maincolor) || empty($frontenddesign) || empty($mainfontcolor) || empty($mainbackgroundcolor) || empty($mainhovercolor) || empty($font) || empty($mindestalter) || empty($language) || empty($recaptcha_secretkey) || empty($recaptcha_sitekey) || strlen($recaptcha_secretkey) > 255 || strlen($recaptcha_sitekey) > 255) {
			$error_msg = 'Bitte alle Felder ausfüllen';
			$error = true;
		}
		//überprüfe die Minimale Stringlänge
		if(strlen($siteurl) > 255 || strlen($sitename) > 255 || strlen($sitedescription) > 255 || strlen($keywordsmain) > 255 || strlen($adminemail) > 255 || strlen($country) > 255 || strlen($author) > 255 || strlen($mainrole) > 10 || strlen($backenddesign) > 255 || strlen($maincolor) != 7 || strlen($frontenddesign) > 255 || strlen($mainfontcolor) != 7 || strlen($mainbackgroundcolor) != 7 || strlen($mainhovercolor) != 7 || strlen($font) > 255 || strlen($mindestalter) > 3 || strlen($language) > 10 || strlen($fontname) > 255) {
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
		if ($country != 'germany' && $country != 'austria' && $country != 'swizerland' && $country != 'luxembourg' && $country != 'lichtenstein' && $country != 'unitedstates' && $country != 'unitedkindom' && $country != 'canada' && $country != 'australia' && $country != 'newzealand' && $country != 'ireland') {
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

			$stmt->close();

			$success_msg = "Alle Felder wurden erfolgreich gespeichert.<meta http-equiv='refresh' content='1; URL=settings'>";
		}
	}

	// Zip Datei fürs Backenddesign - Manager
	if(isset($_POST['backenddesignupload'])) {
		$filename = str_replace('.zip', "", $_FILES['zip']['name']);
		$upload_folder = "../backend/design/";
		$extension = strtolower(pathinfo($_FILES['zip']['name'], PATHINFO_EXTENSION));
		$allowed_extensions = array('zip');
		if(!in_array($extension, $allowed_extensions)) {
			$error_msg = "Nur .zip Dateien sind erlaubt";
		} else {
			$max_size = 50 * 1024 * 1024;
			if($_FILES['zip']['size'] > $max_size) {
				$error_msg = "Bitte keine Dateien größer 50MB hochladen";
			} else {
				$new_path = $upload_folder.$filename.'.'.$extension;
				if (!file_exists($upload_folder)) {
					mkdir($upload_folder, 0777, true);
				}
				move_uploaded_file($_FILES['zip']['tmp_name'], $new_path);
				$zip = new ZipArchive;
				$res = $zip->open($new_path);
				if ($res === true) {
					if (!file_exists($upload_folder.$filename.'/')) {
						mkdir($upload_folder.$filename.'/', 0777, true);
					}
					$zip->extractTo($upload_folder.$filename.'/');
					$zip->close();
				}
				unlink($new_path);
				$success_msg = 'ZIP Datei erfolgreich hochgeladen und verarbeitet';
			}
		}
	}

	// Zip Datei fürs Frontenddesign - Manager
	if(isset($_POST['frontenddesignupload'])) {
		$filename = str_replace('.zip', '', $_FILES['zip']['name']);
		$upload_folder = "../website/design/";
		$extension = strtolower(pathinfo($_FILES['zip']['name'], PATHINFO_EXTENSION));
		$allowed_extensions = array('zip');
		if(!in_array($extension, $allowed_extensions)) {
			$error_msg = "Nur .zip Dateien sind erlaubt";
		} else {
			$max_size = 50 * 1024 * 1024;
			if($_FILES['zip']['size'] > $max_size) {
				$error_msg = "Bitte keine Dateien größer 50MB hochladen";
			} else {
				$new_path = $upload_folder.$filename.'.'.$extension;
				if (!file_exists($upload_folder)) {
					mkdir($upload_folder, 0777, true);
				}
				move_uploaded_file($_FILES['zip']['tmp_name'], $new_path);
				$zip = new ZipArchive;
				$res = $zip->open($new_path);
				if ($res === true) {
					if (!file_exists($upload_folder.$filename.'/')) {
						mkdir($upload_folder.$filename.'/', 0777, true);
					}
					$zip->extractTo($upload_folder.$filename.'/');
					$zip->close();
				}
				unlink($new_path);
				$success_msg = 'ZIP Datei erfolgreich hochgeladen und verarbeitet';
			}
		}
	}

	// Upload Schriftart
	if(isset($_POST['uploadfont'])) {
		$filename = str_replace('.woff', '', $_FILES['font']['name']);
		$filename = str_replace('.woff2', '', $filename);
		$filename = str_replace('.otp', '', $filename);
		$filename = str_replace('.ttf', '', $filename);
		$filename = str_replace('.ps', '', $filename);
		$upload_folder = "../include/database/fonts/";
		$extension = strtolower(pathinfo($_FILES['font']['name'], PATHINFO_EXTENSION));
		//Überprüfung der Dateiendung
		$allowed_extensions = array('woff', 'woff2', 'otp', 'ttf', 'ps');
		if(!in_array($extension, $allowed_extensions)) {
			$error_msg = "Nur .woff, .woff2, .otp, .ttf, .ps Dateien sind erlaubt";
		}
		//Überprüfung der Dateigröße
		$max_size = 20*1024*1024; //500 KB
		if($_FILES['font']['size'] > $max_size) {
			$error_msg = "Bitte keine Dateien größer 20MB hochladen";
		}
		//Pfad zum Upload
		$new_path = $upload_folder.$filename.'.'.$extension;
		//Alles okay, verschiebe Datei an neuen Pfad
		move_uploaded_file($_FILES['font']['tmp_name'], $new_path);
		$success_msg = 'Schriftart wurde erfolgreich hochgeladen';
	}

	//löscht Frontenddesign
	if(isset($_POST['deletefrontend'])) {
		$filename = $_POST['frontenddesign'];
		$pfad = "../website/design/".$filename;
		function rrmdir($dir) {
			if (is_dir($dir)) {
				$objects = scandir($dir);
				foreach ($objects as $object) {
					if ($object != "." && $object != "..") {
						if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
					}
				}
				reset($objects);
				rmdir($dir);
			}
		}
		rrmdir($pfad);
		$success_msg = "Frontenddesign: \"".$filename."\" wurde erfolgreich gelöscht.";
	}

	//löscht Backenddesign
	if(isset($_POST['deletebackend'])) {
		$filename = $_POST['backenddesign'];
		$pfad = "../backend/design/".$filename;
		function rrmdir($dir) {
			if (is_dir($dir)) {
				$objects = scandir($dir);
				foreach ($objects as $object) {
					if ($object != "." && $object != "..") {
						if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
					}
				}
				reset($objects);
				rmdir($dir);
			}
		}
		rrmdir($pfad);
		$success_msg = "Backenddesign: \"".$filename."\" wurde erfolgreich gelöscht.";
	}

	//löscht Schriftart
	if(isset($_POST['deletefont'])) {
			unlink('../include/database/fonts/'.$_POST['font']);
			$success_msg = 'Schriftart wurde erfolgreich gelöscht.';
	}

	//setzt angegebenen Wert der $country Variable auf selected
	if ($options['country'] === 'germany') {
		$country_germany_select = 'selected';
	}
	if ($options['country'] === 'austria') {
		$country_austria_select = 'selected';
	}
	if ($options['country'] === 'switzerland') {
		$country_switzerland_select = 'selected';
	}
	if ($options['country'] === 'luxembourg') {
		$country_luxembourg_select = 'selected';
	}
	if ($options['country'] === 'lichtenstein') {
		$country_lichtenstein_select = 'selected';
	}
	if ($options['country'] === 'unitedstates') {
		$country_unitedstates_select = 'selected';
	}
	if ($options['country'] === 'unitedkingdom') {
		$country_unitedkingdom_select = 'selected';
	}
	if ($options['country'] === 'canada') {
		$country_canada_select = 'selected';
	}
	if ($options['country'] === 'australia') {
		$country_australia_select = 'selected';
	}
	if ($options['country'] === 'newzealand') {
		$country_newzealand_select = 'selected';
	}
	if ($options['country'] === 'ireland') {
		$country_ireland_select = 'selected';
	}

	//setzt angegebenen Wert der $robots Variable auf selected
	if ($options['robots'] === '1') {
		$robots_1_select = 'selected';
	}
	if ($options['robots'] === '0') {
		$robots_0_select = 'selected';
	}

	//setzt angegebenen Wert der $allowregister Variable auf selected
	if ($options['allowregister'] === '1') {
		$allowregister_1_select = 'selected';
	}
	if ($options['allowregister'] === '0') {
		$allowregister_0_select = 'selected';
	}

	//setzt angegebenen Wert der $mainrole Variable auf selected
	if ($options['mainrole'] === 'user') {
		$mainrole_user_select = 'selected';
	}
	if ($options['mainrole'] === 'member') {
		$mainrole_member_select = 'selected';
	}
	if ($options['mainrole'] === 'supporter') {
		$mainrole_supporter_select = 'selected';
	}

	//setzt angegebenen Wert der $language Variable auf selected
	if ($options['language'] === 'german') {
		$language_german_select = 'selected';
	}
	if ($options['language'] === 'english') {
		$language_english_select = 'selected';
	}
?>
