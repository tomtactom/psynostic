<?php
	if(isset($_POST['q']) || isset($_POST['email'])) {
		if (isset($_POST['q']) && !empty($_POST['q'])) {
			if(strlen($_POST['q']) > 64 && strlen($_POST['q']) < 3) {
				$error_msg = 'Deine Frage muss zwischen 3 und 64 Zeichen lang sein';
				$error = true;
			}
			if(!preg_match('/^[a-zA-ZÄäÖöÜüẞß?,\- ]+$/', $_POST['q'])) {
				$error_msg = 'Bitte verwende nur Buchstaben für deine Frage';
				$error = true;
			}
			
			if(isset($_POST['email']) && !empty($_POST['email'])) {
				if(strlen($_POST['q']) > 64 && strlen($_POST['q']) < 8) {
					$error_msg = 'Deine E-Mail muss zwischen 8 und 64 Zeichen lang sein';
					$error = true;
				}
				if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
					$error_msg = 'Bitte eine gültige E-Mail-Adresse eingeben';
					$error = true;
				}
			}

			if($_POST['accept'] != true) {
				$error_msg = 'Bitte akzeptiere unsere Datenschutzerklärung und unsere AGBs';
				$error = true;
			}

			if(!isset($error)) {
				$question = htmlspecialchars($_POST['q']);
				$email = htmlspecialchars($_POST['email']);

				$statement = $db->prepare("INSERT INTO `faq` (question, email, questiondate) VALUES ('".$question."', '".$email."', NOW())");
				if ($statement->execute()) {
					$success_msg = "Deine Frage wurde an uns geschickt";
				} else {
					$error = "Es ist ein Fehler aufgetreten! Bitte versuche es erneut oder kontaktiere uns";
				}
			}
		} else {
			$error_msg = "Bitte fülle das \"Frage Stellen\" Feld aus";
		}
	}
?>
