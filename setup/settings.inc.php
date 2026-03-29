<br><br>&nspb;<br><br>
<?php

	include("helper.inc.php");

	//Hier muss der ganze PHP code hin
	if(isset($_POST['register'])) {
		$error = false;
		$vorname = trim($_POST['vorname']);
		$nachname = trim($_POST['nachname']);
		$email = trim($_POST['email']);
		$passwort = $_POST['passwort'];
		$passwort2 = $_POST['passwort2'];
		$biography = $_POST['biography'];
		$birthday = $_POST['birthday'];
		$gender = $_POST['gender'];
		$username = $_POST['username'];
		if(isset($setup)) {
			$role = 'adminstrator';
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
				$error_msg = 'Diese E-Mail-Adresse ist bereits vergeben<br>';
				$error = true;
			}
			if($username_user !== false) {
				$error_msg = 'Dieser Benutzername ist bereits vergeben<br>';
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
				$min = strtotime("-".'16'." years"); // aktueller Timestamp abzüglich $mindestalter Jahre
				$geb = mktime(0, 0, 0, $var[1], $var[2], $var[0]); // Timestamp des angegebenen Geburtsdatums
				if($min >= $geb) { // ist der Timestamp abzüglich $mindestalter Jahre grösser oder gleichgross als der Timestamp des Geburtsdatums

				} else {
					$date = false;
					$error_msg = 'Du musst mindestens 16 Jahre alt sein.';
					$error = true;
				}
			}
		}

		//Keine Fehler, wir können den Nutzer registrieren
		if(!$error) {
			$passwort_hash = password_hash($passwort, PASSWORD_DEFAULT);
			$statement = $pdo->prepare("INSERT INTO users (email, passwort, vorname, nachname, biography, birthday, gender, username, role) VALUES (:email, :passwort, :vorname, :nachname, :biography, :birthday, :gender, :username, :role)");
			$result = $statement->execute(array('email' => $email, 'passwort' => $passwort_hash, 'vorname' => $vorname, 'nachname' => $nachname, 'biography' => $biography, 'birthday' => $birthday, 'gender' => $gender, 'username' => $username, 'role' => $role));
		$success_msg = 'Du wurdest erfolgreich als Administrator angelegt';
		$goToNextStep = true;
		}
	}
	get('error');
	// show error
	include("templates/settings.inc.php");
?>
