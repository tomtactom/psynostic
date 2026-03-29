<?php
//Frage löschen
if (isset($_GET['aktion']) and $_GET['aktion'] == 'loeschen') {
    if (isset($_GET['id'])) {
        $id = (INT)$_GET['id'];
        if ($id > 0) {
            $loeschen = $db->prepare("DELETE FROM `faq` WHERE id=(?) LIMIT 1");
            $loeschen->bind_param('i', $id);
            if ($loeschen->execute()) {
                $success_msg = 'Frage wurde gelöscht';
            }
        }
    }
}

if (isset($_POST['aktion']) and $_POST['aktion'] == 'korrigieren') {
    $msg = 'Es gab einen Fehler';
    $error = false;
	
    $upd_id = "";
    if (isset($_POST['id'])) {
        $upd_id = (INT)trim($_POST['id']);
    }
	
    $upd_question = "";
    if (isset($_POST['question']) && strlen($_POST['question']) >= 3 && strlen($_POST["question"]) <= 72) {
            $upd_question = htmlspecialchars($_POST['question']);
    } else {
        $error = true;
        $error_msg = 'Bitte gib einen gültige Frage ein';
    }
	
	$upd_answer = "";
	if(isset($_POST['answer']) && !empty($_POST['answer'])) {
		if (strlen($_POST['answer']) >= 3) {
				$upd_answer = htmlspecialchars($_POST['answer']);
		} else {
			$error = true;
			$error_msg = 'Bitte gib einen gültige Antwort ein';
		}
	}
	
	$upd_email = "";
	if(isset($_POST['email']) && !empty($_POST["email"])) {
		if (filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
			$upd_email = htmlspecialchars(trim($_POST['email']));
		} else {
			$error = true;
			$msg = "Diese Email ist ungültig";
		}
	}

    if ($error != true) {
        if ($upd_id != '' AND ($upd_question != '' or !isset($error))) {
			if($upd_answer != '') {
				// speichern
				$update = $db->prepare("UPDATE `faq` SET `question`=?, `answer`=?, `answerdate`= NOW(), `email`=? WHERE id=? LIMIT 1");	
				$update->bind_param('sssi', strval($upd_question), strval($upd_answer), strval($upd_email), intval($upd_id));
				if(!empty($upd_email)) {
			$mailtext = '
	<html lang="de">
	<head>
		<meta charset="utf-8">
		<title>Antwort auf deine Frage auf: '.$options['sitename'].'</title>
	</head>
	<body>
	<h3>Hallo, du hast auf '.$options['siteurl'].' eine Frage gestellt die wir beantworten konnten bzw. die wir geändert haben</h3>
	<p>
		Es kann sein, dass wir deine Frage etwas umformuliert haben, um sie allgemein zu halten.
	</p>
	<table border="1">
	  <tr>
		<td>Frage</td>
		<td><b>'.$upd_question.'</b></td>
	  </tr>
	  <tr>
		<td>Unsere Antwort:</td>
		<td>'.$upd_answer.'</td>
	  </tr>
	  <tr>
		<td>Link zur Frage</td>
		<td><a href="'.$options['siteurl'].'/faq#groupfaq'.$upd_id.'">'.$options['siteurl'].'/faq#groupfaq'.$upd_id.'</a></td>
	  </tr>
	</table>
	<hr>
	<p>Vielen Dank für deine Hilfe<br>
	Die Frage wird auf unserer Webseite ohne Personenbezogenen Informationen (mit Ausnahme der E-Mail) gespeichert.</p>
	<hr>
	Wenn du dies nicht warst, dann ignoriere diese E-Mail oder teile uns dies mit, indem du auf diese E-Mail antwortest.
	</body>
	</html>
	';

			$empfaenger = $upd_email;
			$absender   = $options['adminemail'];
			$betreff    = "Antwort auf deine Frage auf: ".$options['sitename'];
			$antwortan  = $options['adminemail'];

			$header  = "MIME-Version: 1.0\r\n";
			$header .= "Content-type: text/html; charset=utf-8\r\n";

			$header .= "From: ".$options['sitename']." <".$absender.">\r\n";
			$header .= "Reply-To: $antwortan\r\n";
			// $header .= "Cc: $cc\r\n";  // falls an CC gesendet werden soll
			$header .= "X-Mailer: PHP ". phpversion();
			mail($empfaenger,$betreff,$mailtext,$header);
				}
			} else {
				//Keine Antwort gesetzt
				$update = $db->prepare("UPDATE `faq` SET `question`=?, `email`=? WHERE id=? LIMIT 1");	
				$update->bind_param('ssi', strval($upd_question), strval($upd_email), intval($upd_id));
			}

            if ($update->execute()) {
                $msg = 'Fragendaten wurden erfolgreich geändert';
				echo '<meta http-equiv="refresh" content="1; URL='.$options['siteurl'].'/backend/faq">';
                $modus_aendern = false;
            }
        }
    }
	//####################### HIER WEITERMACHEN ################################
    if (isset($_POST['aktion']) and $_POST['aktion'] == 'speichern') {
        $question = "";
        if (isset($_POST['question'])) {
            $question = $_POST['question'];
        }
        $answer = "";
        if (isset($_POST['answer'])) {
            $invite = $_POST['answer'];
        }
        $email = "";
        if (isset($_POST['email'])) {
            $email = $_POST['email'];
        }
		
        if ($question != '') {
			if($answer != '') {
				// speichern
				$einfuegen = $db->prepare("INSERT INTO `faq` (`question`, `answer`, `answerdate`, `email`, `id`) VALUES (?, ?, NOW(), ?, ?)");
				$einfuegen->bind_param('sssi', $question, $answer, $email, $id);
			} else {
				// speichern
				$einfuegen = $db->prepare("INSERT INTO `faq` (`question`, `email`, `id`) VALUES (?, ?, ?)");
				$einfuegen->bind_param('ssi', $question, $email, $id);
			}
	
            if ($einfuegen->execute()) {
                header('Location: faq?aktion=feedbackgespeichert');
                die();
                $msg = 'gespeichert';
            }
        }
    }
}
if (isset($_GET['aktion']) and $_GET['aktion'] == 'feedbackgespeichert') {
    $msg = 'Frage wurde aktualisiert';
}
$modus_aendern = false;
if (isset($_GET['aktion']) and $_GET['aktion'] == 'bearbeiten') {
    $modus_aendern = true;
}
if ($modus_aendern != true) {
    $daten = array();
    if ($erg = $db->query("SELECT * FROM `faq`")) {
        if ($erg->num_rows) {
            while ($datensatz = $erg->fetch_object()) {
                $daten[] = $datensatz;
            }
            $erg->free();
        }
    }
    if (!count($daten)) {
        $msg = 'Es sind keine Fragen gestellt worden';
    } else {
        echo '<table id="sort">
				<thead>
					<tr>
						<th>Aktion</th>
						<th>ID</th>
						<th>Frage</th>
						<th>Antwort</th>
						<th>E-Mail</th>
				</thead>
				<tbody>';
        foreach ($daten as $inhalt) {
            echo '
						<tr>
							<td>
								<a href="?aktion=bearbeiten&id=' . $inhalt->id . '"><img src="../include/plugins/faq/question_edit.svg" height="25px" style="float: left;" alt="bearbeiten"></a> 
								<a href="?aktion=loeschen&id=' . $inhalt->id . '"><img src="../include/plugins/faq/question_delete.svg" height="25px" style="float: right;" alt="löschen"></a>
							</td>
							<td>' . $inhalt->id . '</td>
							<td>' . bereinigen($inhalt->question) . '</td>
							<td>' . bereinigen($inhalt->answer) . '</td>
							<td>' . bereinigen($inhalt->email) . '</td>
						</tr>
						';
        }
        echo '</tbody></table>';
    }
}

if ($modus_aendern == true and isset($_GET['id'])) {
    $id_einlesen = (INT)$_GET['id'];
    if ($id_einlesen > 0) {
        $dseinlesen = $db->prepare("SELECT `id`, `question`, `answer`, `answerdate`, `email` FROM `faq` WHERE `id`=? ");
        $dseinlesen->bind_param('i', $id_einlesen);
        $dseinlesen->bind_result($id, $question, $answer, $answerdate, $email);
        $dseinlesen->execute();
        while ($dseinlesen->fetch()) {
            // echo "<li>";
            // echo $id . ' / '. $question .' '. $answer.' '. $answerdate.' '.$email;
        }
    }
}
function bereinigen($inhalt = '') {
    $inhalt = trim($inhalt);
    $inhalt = htmlentities($inhalt, ENT_QUOTES, "UTF-8");
    return ($inhalt);
}


?>