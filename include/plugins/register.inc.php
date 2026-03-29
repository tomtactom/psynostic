<?php
	if (!$options['allowregister'] || $options['allowregister'] === '0'){
		$msg = 'Die Registrierfunktion ist für\'s Frontend momentan leider deaktiviert';
	}
	if (!$options['mainrole']) {
		$options['mainrole'] = 'user';
	}
	if(isset($_GET['register'])) {
		$error = false;
		$vorname = ucfirst(strtolower(trim($_POST['vorname'])));
		$nachname = ucfirst(strtolower(trim($_POST['nachname'])));
		$email = trim($_POST['email']);
		$passwort = $_POST['passwort'];
		$passwort2 = $_POST['passwort2'];
		$biography = $_POST['biography'];
		$birthday = trim($_POST['birthday']);
		$gender = trim($_POST['gender']);
		$username = trim($_POST['username']);
		
		if($user['role'] === 'adminstrator' || $user['role'] === 'manager') {
				$role = trim($_POST['role']);
			} else {
			$role = $options['mainrole'];
		}
		
		if(empty($vorname) || empty($nachname) || empty($email) || empty($birthday) || empty($gender) || empty($role) || empty($username)) {
			$error_msg = 'Bitte alle Felder mit * ausfüllen';
			$error = true;
		}
		if(strlen($vorname) > 32 || strlen($nachname) > 32 || strlen($email) > 255 || strlen($username) > 32 || (!empty($biography) && strlen($biography) > 255)) {
			$error_msg = 'Bitte an die maximale Zeichenbegrenzung halten';
			$error = true;
		}
		if(strlen($vorname) < 3 || strlen($nachname) < 3 || strlen($email) < 3 || strlen($username) < 3 || strlen($email) < 7) {
			$error_msg = 'Bitte an die minimale Zeichenbegrenzung halten';
			$error = true;
		}
		if (!preg_match("/[a-zA-Z]$/", $vorname) || !preg_match("/[a-zA-Z]$/", $nachname)) {
			$error_msg = 'Der Name ist ungültig';
			$error = true;
		}
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$error_msg = 'Bitte eine gültige E-Mail-Adresse eingeben';
			$error = true;
		} 	
		if(strlen($passwort) == 0) {
			$error_msg = 'Bitte ein Passwort angeben';
			$error = true;
		}
		if(strlen($passwort) < 8 || strlen($passwort) > 255) {
			$error_msg = 'Das Passwort muss mindestens 8 Zeichen und maximal 255 lang sein';
			$error = true;
		}
		if($passwort != $passwort2) {
			$error_msg = 'Die Passwörter müssen übereinstimmen';
			$error = true;
		}
		if (!preg_match('/[A-Za-z0-9(_)\?!]$/', $username)) {
			$error_msg = "Dein Benutzername enthält ungültige Zeichen";
			$error = true;
		}
		if(strlen($birthday) != 10) {
			$error_msg = 'Das Geburtsdatum ist ungültig (<i>YYYY-MM-DD</i>)';
			$error = true;
		}
		if ($role != 'user' && $role != 'member' && $role != 'supporter' && $role != 'manager' && $role != 'adminstrator') {
			$error_msg = 'Bitte wähle alle Rollen Auswahlfelder aus';
			$error= true;	
		}
		if ($gender != 'male' && $gender != 'female' && $gender != 'other' && $gender != 'noinformation') {
			$error_msg = 'Bitte wähle alle Geschlechts Auswahlfelder aus';
			$error= true;	
		}
		
		//Überprüfe, dass die E-Mail-Adresse und der Benutzername noch nicht registriert wurden
		if(!$error) { 
			$statement = $pdo->prepare("SELECT * FROM users WHERE email = :email");
			$result = $statement->execute(array('email' => $email));
			$user = $statement->fetch();
			
			$username_statement = $pdo->prepare("SELECT * FROM users WHERE username = :username");
			$username_result = $username_statement->execute(array('username' => $username));
			$username_user = $username_statement->fetch();
			
			if($user !== false) {
				$error_msg = 'Diese E-Mail-Adresse ist bereits vergeben';
				$error = true;
			}
			if($username_user !== false) {
				$error_msg = 'Dieser Benutzername ist bereits vergeben';
				$error = true;
			}	
		}
		
		//Überprüfe ob das Geburtsdatum gültig ist
		if ($birthday > date("Y-m-d")) {
			$error_msg = 'Das Geburtsdatum ist ungültig (<i>YYYY-MM-DD</i>)';
			$error = true;
		} else {
			$var = explode("-", $birthday); // Geburtsdatum zerlegen
			$check = @checkdate($var[1], $var[2], $var[0]); // angegebenes Geburtsdatum auf Gültigkeit prüfen
			if($check == true) { // wenn Geburtsdatum gültig ist
				$min = strtotime("-".$options['mindestalter']." years"); // aktueller Timestamp abzüglich $mindestalter Jahre
				$geb = mktime(0, 0, 0, $var[1], $var[2], $var[0]); // Timestamp des angegebenen Geburtsdatums
				if($min >= $geb) { // ist der Timestamp abzüglich $mindestalter Jahre grösser oder gleichgross als der Timestamp des Geburtsdatums
				
				} else {
					$date = false;
					$error_msg = 'Du musst mindestens '.$options['mindestalter'].' Jahre alt sein.';
					$error = true;
				}
			}
		}
		
		//Keine Fehler, wir können den Nutzer registrieren
		if(!$error) {	
			$passwort_hash = password_hash($passwort, PASSWORD_DEFAULT);
			
			$statement = $pdo->prepare("INSERT INTO users (email, passwort, vorname, nachname, biography, birthday, gender, username, role) VALUES (:email, :passwort, :vorname, :nachname, :biography, :birthday, :gender, :username, :role)");
			$result = $statement->execute(array('email' => $email, 'passwort' => $passwort_hash, 'vorname' => $vorname, 'nachname' => $nachname, 'biography' => $biography, 'birthday' => $birthday, 'gender' => $gender, 'username' => $username, 'role' => $role));
			
			//User muss E-Mail noch Aktivieren
			$statement = $pdo->prepare("SELECT * FROM users WHERE email = :email");
			$result = $statement->execute(array('email' => $email));
			$user = $statement->fetch();		

			if($user === false) {
				$error = "Du konntest nicht registriert werden.";
			} else {

				$authenticationcode = random_string();
				$statement = $pdo->prepare("UPDATE users SET authenticationcode = :authenticationcode, authenticationcode_time = NOW() WHERE id = :userid");
				$result = $statement->execute(array('authenticationcode' => $authenticationcode, 'userid' => $user['id']));

				$empfaenger = $user['email'];
				$betreff = "Dein Account auf ".$options['siteurl_trim']." aktivieren";
				$from = "From: ".$options['sitename']." <noreply@".$options['siteurl_trim'].">";
				if (str_replace('/register', '', $_SERVER['PHP_SELF']) == '/website') {
					$url_authenticationcode = $options['siteurl'].'/authentication?userid='.$user['id'].'&code='.$authenticationcode;
				} else {
					$url_authenticationcode = $options['siteurl'].'/backend/authentication?userid='.$user['id'].'&code='.$authenticationcode;
				}
$text = 'Hallo '.$user['vorname'].',
um deinen Account auf '.$options['sitename'].' zu aktivieren, rufe innerhalb 24 Stunden die folgende Website auf:

	'.$url_authenticationcode.'
	
Sollte dein Account schon aktiviert sein oder hast dich nicht bei uns registriert, so bitte ignoriere diese E-Mail.
						
						
Dein
'.$options['sitename'].'-Team';	
				mail($empfaenger, $betreff, $text, $from);

				$msg = "Ein Link um deinen Account zu aktivieren wurde an deine E-Mail-Adresse gesendet.";	
				$showForm = false;
			}
			if($result == true) {
				$success_msg = 'Du wurdest erfolgreich registriert';
				$options['allowregister'] = false;
			} else {
				$error = 'Beim Abspeichern ist leider ein Fehler aufgetreten<br>';
			}
		}
	}
?>
