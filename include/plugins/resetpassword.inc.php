<?php
	if(!isset($_GET['userid']) || !isset($_GET['code'])) {
		$error_msg = 'Leider wurde beim Aufruf dieser Website kein Code zum Zurücksetzen deines Passworts übermittelt';
	}
	$showForm = true; 
	$userid = $_GET['userid'];
	$code = $_GET['code'];

	//Abfrage des Nutzers
	$statement = $pdo->prepare("SELECT * FROM users WHERE id = :userid");
	$result = $statement->execute(array('userid' => $userid));
	$user = $statement->fetch();

	//Überprüfe dass ein Nutzer gefunden wurde und dieser auch ein Passwortcode hat
	if($user === null || $user['passwortcode'] === null) {
		$error_msg = "Der Benutzer wurde nicht gefunden oder hat kein neues Passwort angefordert.";
	}

	if($user['passwortcode_time'] === null || strtotime($user['passwortcode_time']) < (time()-24*3600) ) {
		error("Dein Code ist leider abgelaufen. Bitte benutze die Passwort vergessen Funktion erneut.");
	}

	//Überprüfe den Passwortcode
	if(sha1($code) != $user['passwortcode']) {
		$error_msg = "Der übergebene Code war ungültig. Stell sicher, dass du den genauen Link in der URL aufgerufen hast. Solltest du mehrmals die Passwort-vergessen Funktion genutzt haben, so ruf den Link in der neuesten E-Mail auf.";
	}

	//Der Code war korrekt, der Nutzer darf ein neues Passwort eingeben
	if(isset($_GET['send'])) {
		$passwort = $_POST['passwort'];
		$passwort2 = $_POST['passwort2'];

		if($passwort != $passwort2) {
			$msg =  "Bitte identische Passwörter eingeben";
		} else if (strlen($passwort) < 8 || strlen($passwort) > 255) {
			$msg = "Dein neues Passwort muss mindestens 8 Zeichen und maximal 255 Zeichen lang sein";
		} else { //Speichere neues Passwort und lösche den Code
			$passworthash = password_hash($passwort, PASSWORD_DEFAULT);
			$statement = $pdo->prepare("UPDATE users SET passwort = :passworthash, passwortcode = NULL, passwortcode_time = NULL WHERE id = :userid");
			$result = $statement->execute(array('passworthash' => $passworthash, 'userid'=> $userid ));

			if($result) {
				$success_msg = "Dein Passwort wurde erfolgreich geändert";
				$showForm = false;
			}
		}
	}
?>
