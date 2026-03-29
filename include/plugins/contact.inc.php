<?php
	if(isset($_POST['gender']) || isset($_POST['firstname']) || isset($_POST['lastname']) || isset($_POST['email']) || isset($_POST['message']) || isset($_POST['category'])) {
		//Anforderungen testen und überprüfen
		if(empty($_POST['gender']) || empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['email']) || empty($_POST['message']) || empty($_POST['category'])) {
			$error_msg = "Bitte fülle alle Felder aus";
			$error = true;
		}
		if($_POST['gender'] != "male" && $_POST['gender'] != "female" && $_POST['gender'] != "noinformation") {
			$error_msg = 'Bitte wähle eine Anrede oder "Keine Angabe" aus';
			$error = true;
		}
		if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			$error_msg = 'Bitte eine gültige E-Mail-Adresse eingeben';
			$error = true;
		}
		if(strlen($_POST['firstname']) > 64 || strlen($_POST['firstname']) < 2 || strlen($_POST['lastname']) > 64 || strlen($_POST['lastname']) < 2) {
			$error_msg = 'Deine Name muss zwischen zwei und 64 Zeichen lang sein';
			$error = true;
		}
		if(strlen($_POST['message']) > 10000) {
			$error_msg = 'Deine Nachricht darf maximal 10.000 Zeichen lang sein';
			$error = true;
		}
		if($_POST['category'] != "1" && $_POST['category'] != "2" && $_POST['category'] != "3" && $_POST['category'] != "4" && $_POST['category'] != "5") {
			$error_msg = 'Bitte wähle ein Thema worum es geht aus';
			$error = true;
		}
		if($_POST['accept'] != true) {
			$error_msg = 'Bitte akzeptiere unsere Datenschutzerklärung und unsere AGBs';
			$error = true;
		}
		$json = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=6LfWXm0UAAAAAK1XQRM5IlnnhNQdbX3z7wc-zj7l&response='.$_POST['g-recaptcha-response']), true);
		if(intval($json['success']) !== 1) {
			$error_msg = 'Bitte bestätige dass du kein Roboter bist';	
			$error = true;
		}
		if(!preg_match('/^[a-zA-ZÄäËëÏïÖöÜüŸÿẞßÀÁÂÃÅÆÇÈÉÊÌÍÎÐÑÒÓÔÕØŒÙÚÛÝÞàáâãåæçèéêìíîðñòóôõøœùúûýþŠšČč ]+$/', $_POST['firstname']) || !preg_match('/^[a-zA-ZÄäËëÏïÖöÜüŸÿẞßÀÁÂÃÅÆÇÈÉÊÌÍÎÐÑÒÓÔÕØŒÙÚÛÝÞàáâãåæçèéêìíîðñòóôõøœùúûýþŠšČč ]+$/', $_POST['lastname'])) {
			$error_msg = 'Bitte verwende nur Buchstaben in deinem Namen';
			$error = true;
		}
		// Schipfwortfilter überprüfung
		$_POST['firstname'] = preg_replace('/\s+/', ' ', $_POST['firstname']);
		$arr = explode(" ", $_POST['firstname']);
		$datei = fopen("../include/plugins/groupstar/blacklist.txt","r+");
		while (!feof($datei)) {
			$zeile = fgets($datei,1000);
			foreach ($arr as $word) {
				if (preg_match('/\b' . $word . '\b/i', $zeile)) {
					$firs_error = true;
				}
			}
		}
		fclose($datei);
		$_POST['lastname'] = preg_replace('/\s+/', ' ', $_POST['lastname']);
		$arr = explode(" ", $_POST['lastname']);
		$datei = fopen("../include/plugins/groupstar/blacklist.txt","r+");
		while (!feof($datei)) {
			$zeile = fgets($datei,1000);
			foreach ($arr as $word) {
				if (preg_match('/\b' . $word . '\b/i', $zeile)) {
					$last_error = true;
				}
			}
		}
		fclose($datei);
		if($firs_error == true || $last_error == true) {
			$error_msg = "Bitte wähle einen Vor und Nachnamen, der unseren Richtlinien entspricht";
			$error = true;
		}

		// Geschlecht und Anrede
		if($_POST['gender'] == 'male') {
			$salutation = 'Herr ';
			$_POST['gender'] = 'männlich';
		} elseif($_POST['gender'] == 'female') {
			$salutation = 'Frau ';
			$_POST['gender'] = 'weiblich';
		} else {
			$salutation = '';
			$_POST['gender'] = 'Keine Angabe';
		}

		// Kategorie zuordnen
		if($_POST['category'] == '1') {
			$_POST['category'] = 'Allgemein';
		} elseif($_POST['category'] == '2') {
			$_POST['category'] = 'Werbung';
		} elseif($_POST['category'] == '3') {
			$_POST['category'] = 'Funktionen';
		} elseif($_POST['category'] == '4') {
			$_POST['category'] = 'Beschwerden/Rechtliches';
		} elseif($_POST['category'] == '5') {
			$_POST['category'] = 'Gruppe melden';
		} else {
			$_POST['category'] = 'Sonstiges';
		}

		// Namen Ordnen
		$_POST['firstname'] = ucfirst(strtolower($_POST['firstname']));
		$_POST['lastname'] = ucfirst(strtolower($_POST['lastname']));

		// E-Mail versenden
		if(!$error) {
			// E-Mail an uns
			$mailtext = '
	<html lang="de">
	<head>
		<meta charset="utf-8">
		<title>Nachricht von '.$_POST['firstname'].' '.$_POST['lastname'].' aus dem Kontaktformular auf '.$options['sitename'].' </title>
	</head>
	<body>
	<h3>('.$_POST['category'].')Nachricht aus dem Kontaktformular auf '.$options['siteurl'].'</h3>
	<big>Datum/Uhrzeit: '.date('Y/m/d/H/i/s').'</big>
	<table border="0">
	  <tr>
		<td>Anrede</td>
		<td>'.$salutation.'</td>
	  </tr>
	  <tr>
		<td>Vorname</td>
		<td>'.$_POST['firstname'].'</td>
	  </tr>
	  <tr>
		<td>Nachname</td>
		<td>'.$_POST['lastname'].'</td>
	  </tr>
	  <tr>
		<td>E-Mail-Adresse</td>
		<td>'.$_POST['email'].'</td>
	  </tr>
	  <tr>
		<td>Kategorie</td>
		<td>'.$_POST['category'].'</td>
	  </tr>
	</table>
	<hr>
	<p>'.$_POST['message'].'</p>
	</body>
	</html>
	';

			$empfaenger = $options['adminemail'];
			$absender   = "noreply@".$options['siteurl_trim'];
			$betreff    = "Kontaktformular Nachricht von: ".$salutation.$_POST['lastname'];
			$antwortan  = $_POST['email'];

			$header  = "MIME-Version: 1.0\r\n";
			$header .= "Content-type: text/html; charset=utf-8\r\n";

			$header .= "From: ".$options['sitename']."<".$absender.">\r\n";
			$header .= "Reply-To: $antwortan\r\n";
			// $header .= "Cc: $cc\r\n";  // falls an CC gesendet werden soll
			$header .= "X-Mailer: PHP ". phpversion();
			$result1 = mail($empfaenger,$betreff,$mailtext,$header);


			//E-Mail an Kunden
			$mailtext = '
	<html lang="de">
	<head>
		<meta charset="utf-8">
		<title>Deine Nachricht aus dem Kontaktformular der Seite: '.$options['sitename'].'</title>
	</head>
	<body>
	<h3>Hallo '.$salutation.$_POST['lastname'].', wir haben diese E-Mail, übers Kontaktformular der Seite: '.$options['siteurl'].' erhalten</h3>
	<big>Datum/Uhrzeit: '.date('Y/m/d/H/i/s').'</big>
	<table border="0">
	  <tr>
		<td>Anrede</td>
		<td>'.$salutation.'</td>
	  </tr>
	  <tr>
		<td>Vorname</td>
		<td>'.$_POST['firstname'].'</td>
	  </tr>
	  <tr>
		<td>Nachname</td>
		<td>'.$_POST['lastname'].'</td>
	  </tr>
	  <tr>
		<td>E-Mail-Adresse</td>
		<td>'.$_POST['email'].'</td>
	  </tr>
	  <tr>
		<td>Kategorie</td>
		<td>'.$_POST['category'].'</td>
	  </tr>
	  <tr>
		<td>IP-Adresse</td>
		<td>'.$_SERVER['REMOTE_ADDR'].'</td>
	  </tr>
	</table>
	<hr>
	<p>'.$_POST['message'].'</p>
	<hr>
	Wenn du dies nicht warst, dann ignoriere diese E-Mail oder teile uns dies mit, indem du auf diese E-Mail antwortest.
	</body>
	</html>
	';

			$empfaenger = $_POST['email'];
			$absender   = $options['adminemail'];
			$betreff    = "Deine Nachricht aus dem Kontaktformular der Seite: ".$options['sitename'];
			$antwortan  = $options['adminemail'];

			$header  = "MIME-Version: 1.0\r\n";
			$header .= "Content-type: text/html; charset=utf-8\r\n";

			$header .= "From: ".$options['sitename']."<".$absender.">\r\n";
			$header .= "Reply-To: $antwortan\r\n";
			// $header .= "Cc: $cc\r\n";  // falls an CC gesendet werden soll
			$header .= "X-Mailer: PHP ". phpversion();
			$result2 = mail($empfaenger,$betreff,$mailtext,$header);

			if($result1 == true && $result2 == true) {
				$success_msg = "Deine Nachricht wurde an uns gesendet, wir werden uns so schnell wie möglich um dein Anliegen kümmern";
			} else {
				$error = "Es ist ein Fehler aufgetreten! Es kann sein, dass wir deine E-Mail nicht erhalten haben. Bitte schicke uns, über dein E-Mail Programm eine Nachricht";
			}
		}
	}
?>
