<?php
	if(!isset($_GET['userid']) || !isset($_GET['code'])) {
		$error_msg = 'Leider wurde beim Aufruf dieser Website kein Code zum Aktivieren deines Kontos übermittelt.';
		$showForm = false;
	} else {
		$showForm = true;
	}
	$userid = $_GET['userid'];
	$code = $_GET['code'];
	
	//Abfrage des Nutzers
	$statement = $pdo->prepare("SELECT * FROM users WHERE id = :userid");
	$result = $statement->execute(array('userid' => $userid));
	$user = $statement->fetch();
	//Überprüfe dass ein Nutzer gefunden wurde und dieser auch ein Passwortcode hat
	if($user === null || $user['authenticationcode'] === null) {
		$error_msg = "Der Benutzer wurde nicht gefunden.";
		$showForm = false;
	}
	//Überprüfe den Passwortcode
	if($code != $user['authenticationcode']) {
		$error_msg = "Der übergebene Code war ungültig. Stell sicher, dass du den genauen Link in der URL aufgerufen hast.";
		$showForm = false;
	}

	//Der Code war korrekt, der Nutzer darf ein neues Passwort eingeben
	if(isset($_POST['send'])) {

			$statement = $pdo->prepare("UPDATE users SET authenticationcode = NULL, authenticationcode_time = NULL WHERE id = :userid");
			$result = $statement->execute(array( 'userid'=> $userid ));

			if($result) {
				$success_msg = "Dein Account wurde erfolgreich aktiviert";
				$showForm = false;
			}
		}
?>
