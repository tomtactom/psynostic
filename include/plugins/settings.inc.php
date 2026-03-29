<?php
    if (isset($_POST['save_biography'])) {
        $biography = $_POST['biography'];
        if (!empty(strlen($biography) > 255)) {
            $error_msg = "Deine Biografie darf nicht länger als 255 Zeichen sein.";
        } else {
            $statement = $pdo->prepare("UPDATE users SET biography = :biography, updated_at=NOW() WHERE id = :userid");
            $result = $statement->execute(array('biography' => $biography, 'userid' => $user['id']));
            $success_msg = "Daten erfolgreich gespeichert.";
        }
    }
    if (isset($_POST['save_username'])) {
        $username = $_POST['username'];
        $username_statement = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $username_user = $username_statement->fetch();
        if ($username == "") {
            $error_msg = "Bitte trage einen Benutzernamen ein";
		} elseif (strlen($username) > 32) {
            $error_msg = "Deine Benutzername darf nicht länger als 32 Zeichen sein.";
        } elseif (strlen($username) < 3) {
            $error_msg = "Dein Benutzername muss mindestens 3 Zeichen lang sein.";
        } elseif ($username_user !== false) {
            $error_msg = 'Dieser Benutzername ist bereits vergeben.';
        } else {
			if (!preg_match('/[A-Za-z0-9(_)\?!]$/', $username)) {
				$error_msg = "Dein Benutzername enthält ungültige Zeichen";
			} else {
				$statement = $pdo->prepare("UPDATE users SET username = :username, updated_at=NOW() WHERE id = :userid");
				$result = $statement->execute(array('username' => $username, 'userid' => $user['id']));
				$success_msg = "Benutzername erfolgreich gespeichert.";
			}
        }
    }
    if (isset($_POST['save_email'])) {
        $passwort = $_POST['passwort'];
        $email = $_POST['email'];
        $email2 = $_POST['email2'];
        if (!empty($email && $email2) && $email != $email2) {
            $error_msg = "Die eingegebenen E-Mail-Adressen stimmten nicht überein.";
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = "Bitte eine gültige E-Mail-Adresse eingeben.";
        } else if (!password_verify($passwort, $user['passwort'])) {
            $error_msg = "Bitte korrektes Passwort eingeben.";
        } elseif (strlen($passwort) >= 255 || strlen($email) >= 255 || strlen($email2) >= 255) {
            $error_msg = "Bitte nicht mehr als 255 Zeichen eingeben.";
        } elseif (strlen($passwort) <= 8 || strlen($email) <= 7 || strlen($email2) <= 7) {
            $error_msg = "Bitte nicht weniger als 7 Zeichen.";
        } elseif (strlen($passwort) <= 8) {
            $error_msg = "Bitte nicht weniger als 8 Zeichen.";
        } else {
            $statement = $pdo->prepare("UPDATE users SET email = :email WHERE id = :userid");
            $result = $statement->execute(array('email' => $email, 'userid' => $user['id']));
            $success_msg = "E-Mail-Adresse erfolgreich gespeichert.";
        }
    }
    if (isset($_POST['save_passwort'])) {
        $passwortAlt = $_POST['passwortAlt'];
        $passwortNeu = $_POST['passwortNeu'];
        $passwortNeu2 = $_POST['passwortNeu2'];
        if (!empty($passwortNeu && $passwortNeu2) && $passwortNeu != $passwortNeu2) {
            $error_msg = "Die eingegebenen Passwörter stimmten nicht überein.";
        } else if ($passwortNeu == "") {
            $error_msg = "Das Passwort darf nicht leer sein.";
        } else if (!password_verify($passwortAlt, $user['passwort'])) {
            $error_msg = "Bitte korrektes Passwort eingeben.";
        } elseif (strlen($passwortNeu) >= 255 || strlen($passwortNeu) <= 8) {
            $error_msg = "Dein Passwort muss zwischen 8 und 255 Zeichen sein.";
        } else {
            $passwort_hash = password_hash($passwortNeu, PASSWORD_DEFAULT);
            $statement = $pdo->prepare("UPDATE users SET passwort = :passwort WHERE id = :userid");
            $result = $statement->execute(array('passwort' => $passwort_hash, 'userid' => $user['id']));
            $success_msg = "Passwort erfolgreich gespeichert.";
        }
    }
    if (isset($_POST['save_profilpicture'])) {
        $upload_folder = '../include/database/profilpictures/'; //Das Upload-Verzeichnis
        $filename = $user['id'];
        $extension = strtolower(pathinfo($_FILES['profilpicture']['name'], PATHINFO_EXTENSION));
        //Überprüfung der Dateigröße
        $max_size = 10000 * 1024; //500 KB
        if ($_FILES['profilpicture']['size'] > $max_size) {
            $error_msg = "Bitte keine Dateien größer 10MB hochladen";
        }
        //Überprüfung dass das Bild keine Fehler enthält
        if (function_exists('exif_imagetype')) {
            $allowed_types = array(IMAGETYPE_JPEG);
            $detected_type = @exif_imagetype($_FILES['profilpicture']['tmp_name']);
            if (!in_array($detected_type, $allowed_types)) {
                $error = true;
                $error_msg = "Das ist kein gültiges Bild";
            }
        }
        if (!$error) {
            //Pfad zum Upload
            $new_path = $upload_folder . $filename . '.' . $extension;
            //Alles okay, verschiebe Datei an neuen Pfad
            move_uploaded_file($_FILES['profilpicture']['tmp_name'], $new_path);
            //Bild wird skaliert
            bild_skalieren($new_path, $new_path, 600, 600);
            //Thumbnail erstellen
            bild_skalieren($new_path, '../include/database/profilpictures/thumbnail/' . $filename . '.jpg', 150, 150);
            $success_msg = 'Profilbild erfolgreich hochgeladen';
        }
    }
    if (isset($_POST['save_profilpicturedelete'])) {
        unlink('../include/database/profilpictures/' . $user['id'] . '.jpg');
        unlink('../include/database/profilpictures/thumbnail/' . $user['id'] . '.jpg');
        $success_msg = 'Profilbild wurde erfolgreich gelöscht.';
    }

    if (isset($_POST['save_accountdelete'])) {
        if (isset($_POST['deleteok']) && !empty(password_verify($_POST['password'], $user['passwort']))) {
            $id = (INT)$user['id'];
            if ($id > 0) {
                $loeschen = $db->prepare("DELETE FROM users WHERE id=(?) LIMIT 1");
                $loeschen->bind_param('i', $id);
                if ($loeschen->execute()) {
                    //Profilbild löschen
                    unlink('../include/database/profilpictures/' . "$id" . '.jpg');
                    unlink('../include/database/profilpictures/thumbnail/' . "$id" . '.jpg');

                    $success_msg = 'Dein Account wurde gelöscht!';
                    //hier php weiterleitung zu Startseite einfügen
                }
            }
        } else {
            $error_msg = "Das Passwort war falsch oder du hast den Haken nicht aktiviert";
        }
    }
	if (isset($success_msg)) {
		echo "<meta http-equiv='refresh' content='1; URL=profile'>";
	}
?>
