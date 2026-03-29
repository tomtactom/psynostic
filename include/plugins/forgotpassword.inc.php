<?php 			 
	if(isset($_GET['send']) ) {
		if(!isset($_POST['email']) || empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) || strlen($_POST['email']) > 255 || strlen($_POST['email']) < 7) {
			$error_msg = "Bitte eine gültige E-Mail-Adresse eintragen";
		} else {
			$statement = $pdo->prepare("SELECT * FROM users WHERE email = :email");
			$result = $statement->execute(array('email' => $_POST['email']));
			$user = $statement->fetch();		

			if($user === false) {
				$error = "Kein Benutzer gefunden.";
			} else {

				$passwortcode = random_string();
				$statement = $pdo->prepare("UPDATE users SET passwortcode = :passwortcode, passwortcode_time = NOW() WHERE id = :userid");
				$result = $statement->execute(array('passwortcode' => sha1($passwortcode), 'userid' => $user['id']));
				
				$empfaenger = $user['email'];
				$betreff = "Neues Passwort von deinen Account auf ".$options['siteurl_trim'];
				$from = "From: ".$sitename." <noreply@".$options['siteurl_trim'].">";
				$url_passwortcode = $options['siteurl'].'/backend/resetpassword?userid='.$user['id'].'&code='.$passwortcode; //Setzt hier eure richtige Domain ein
$text = 'Hallo '.$user['vorname'].',
bei deinen Account auf '.$options['siteurl_trim'].' wurde nach einem neuen Passwort gefragt. Um ein neues Passwort zu vergeben, rufe innerhalb 24 Stunden die folgende Website auf:

	'.$url_passwortcode.'
	
Sollte dir dein Passwort wieder eingefallen sein oder hast du dies nicht angefordert, so bitte ignoriere diese E-Mail.
						
						
Dein
'.$options['sitename'].'-Team';
				
				mail($empfaenger, $betreff, $text, $from);
				
				$msg = "Ein Link um dein Passwort zurückzusetzen wurde an deine E-Mail-Adresse gesendet.";	
				$showForm = false;
			}
		}
	}
?>
