<?php
	if(isset($_POST['emailorusername']) && isset($_POST['passwort'])) {
		$emailorusername = $_POST['emailorusername'];
		$passwort = $_POST['passwort'];

		if(filter_var($emailorusername, FILTER_VALIDATE_EMAIL)) {
			$email = $_POST['emailorusername'];
		} else {
			$username = $_POST['emailorusername'];
		}

		if(isset($email)) {
			$statement = $pdo->prepare("SELECT * FROM users WHERE email = :email");
			$result = $statement->execute(array('email' => $email));
			$user = $statement->fetch();
		}

		if(isset($username)) {
			$statement = $pdo->prepare("SELECT * FROM users WHERE username = :username");
			$result = $statement->execute(array('username' => $username));
			$user = $statement->fetch();	
		}
				

		//Überprüfung des Passworts
		if ($user !== false && password_verify($passwort, $user['passwort']) && @$user['authenticationcode'] === null) {
			$_SESSION['userid'] = $user['id'];

			//Soll der Benutzer angemeldet beleiben?
			if(isset($_POST['angemeldet_bleiben'])) {
				$identifier = random_string();
				$securitytoken = random_string();

				$insert = $pdo->prepare("INSERT INTO securitytokens (user_id, identifier, securitytoken) VALUES (:user_id, :identifier, :securitytoken)");
				$insert->execute(array('user_id' => $user['id'], 'identifier' => $identifier, 'securitytoken' => sha1($securitytoken)));
				setcookie("identifier",$identifier,time()+(3600*24*365)); //Valid for 1 year
				setcookie("securitytoken",$securitytoken,time()+(3600*24*365)); //Valid for 1 year
			}
			$redirectTarget = (getPageLink() == $options['siteurl'].'/login' || getPageLink() == $options['siteurl'].'/backend/login')
				? 'profile'
				: getPageLink();

			if (!headers_sent()) {
				header("Location: " . $redirectTarget);
				exit;
			}

			$escapedRedirectTarget = htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8');
			echo '<script>window.location.href=' . json_encode($redirectTarget) . ';</script>';
			echo '<noscript><meta http-equiv="refresh" content="0;url=' . $escapedRedirectTarget . '"></noscript>';
			exit;
		} else {
			$error_msg =  "Die E-Mail, der Benutzername oder das Passwort war ungültig oder du hast deine E-Mail noch nicht bestätigt.";
		}
	}
	$email_value = "";
	if(isset($_POST['email']))
		$email_value = htmlentities($_POST['email']); 
?>
