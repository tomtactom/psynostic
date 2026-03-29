<?php
if (isset($_GET['aktion']) and $_GET['aktion'] == 'loeschen') {
    if (isset($_GET['id'])) {
        $id = (INT)$_GET['id'];
        if ($id > 0) {
            $loeschen = $db->prepare("DELETE FROM users WHERE id=(?) LIMIT 1");
            $loeschen->bind_param('i', $id);
            if ($loeschen->execute()) {
                //Profilbild löschen
                @unlink('../include/database/profilpictures/' . "$id" . '.jpg');
                @unlink('../include/database/profilpictures/thumbnail/' . "$id" . '.jpg');

                $success_msg = 'Benutzer wurde gelöscht';
            }
        }
    }
}

if (isset($_POST['aktion']) and $_POST['aktion'] == 'korrigieren') {
    $msg = 'Es gab einen Fehler';
    $error = false;
    $upd_id = "";
    $gender_filter = ['male', 'female', 'other', 'noinformation'];
    $role_filter = ['user', 'member', 'supporter', 'manager', 'adminstrator'];

    if (isset($_POST['id'])) {
        $upd_id = (INT)trim($_POST['id']);
    }
    $upd_vorname = "";

    if (isset($_POST['vorname']) && strlen($_POST['vorname']) >= 3 && strlen($_POST["vorname"]) <= 32) {
        if (!preg_match("/[a-zA-Z]$/", $_POST['vorname'])) {
            $msg = 'Der Vorname ist ungültig';
            $error = true;
        } else {
            $upd_vorname = trim($_POST['vorname']);
        }
    } else {
        $error = true;
        $msg = 'Bitte gib einen richtigen Vornamen ein';
    }

    $upd_nachname = "";
    if (isset($_POST['nachname']) && strlen($_POST['nachname']) >= 3 && strlen($_POST["nachname"]) <= 32) {
        if (!preg_match("/[a-zA-Z]$/", $_POST['nachname'])) {
            $msg = 'Der Nachname ist ungültig';
            $error = true;
        } else {
            $upd_nachname = trim($_POST['nachname']);
        }
    } else {
        $error = true;
        $msg = "Bitte gib einen richtigen Nachnamen ein";
    }

    $upd_email = "";
    if (!empty($_POST["email"]) && filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
        $upd_email = trim($_POST['email']);
    } else {
        $error = true;
        $msg = "Diese Email ist ungültig";
    }

    $upd_role = "";
    if (isset($_POST['role']) && in_array(trim($_POST['role']), $role_filter) == true) {
        $upd_role = trim($_POST['role']);
    } else {
        $error = true;
        $msg = "Bitte teile dem User eine verfügbare Rolle zu";
    }

    $upd_biography = "";
    if (isset($_POST['biography'])) {
        $upd_biography = trim($_POST['biography']);
    }

    $upd_gender = "";
    if (isset($_POST['gender']) && in_array(trim($_POST['gender']), $gender_filter) == true) {
        $upd_gender = trim($_POST['gender']);
    } else {
        $error = true;
        $msg = "Bitte teile dem User ein verfügbares Geschlecht zu";
    }

    $upd_birthday = "";
    if (isset($_POST['birthday']) && strlen($_POST['birthday']) == 10) {
        $upd_birthday = trim($_POST['birthday']);
    } else {
        $error = true;
        $msg = "Bitte gib ein richtiges Datum ein!";
    }

    $upd_username = "";
    if (isset($_POST['username']) && strlen($_POST['username']) >= 3 && strlen($_POST['username']) <= 255) {
        if (!preg_match('/[A-Za-z0-9(_)\?!]$/', $_POST['username'])) {
            $error_msg = "Dein Benutzername enthällt ungültige Zeichen";
            $error = true;
        } else {
            $upd_username = trim($_POST['username']);
        }
    } else {
        $error = true;
        $msg = "Bitte gib ein Username ein";
    }

    if (isset($_FILES['profilpicture']['name'])) {
        if (strlen($_FILES['profilpicture']['name']) > 0) {
            $upd_profilpicture = trim($_FILES['profilpicture']['name']);
        }
    }

    if ($_POST['profilpicturecheck'] === 'same') {
        $upd_profilpicturecheck = $_POST['profilpicturecheck'];
    } else {
        $upd_profilpicturecheck = false;
    }

    $upd_passwort = "";
    if (isset($_POST['passwort'])) {
        if (strpos($_POST['passwort'], '$2y$10') === 0) {
            $upd_passwort = $_POST['passwort'];
        } else {
            $upd_passwort = password_hash(trim($_POST['passwort']), PASSWORD_DEFAULT);
        }
    }

    //Überprüfe, dass die E-Mail-Adresse und der Benutzername noch nicht registriert wurden
    $statement = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $result = $statement->execute(array('email' => $email));
    $user = $statement->fetch();

    $username_statement = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $username_result = $username_statement->execute(array('username' => $username));
    $username_user = $username_statement->fetch();

    if ($user !== false) {
        $error_msg = 'Diese E-Mail-Adresse ist bereits vergeben';
        $error = true;
    }
    if ($username_user !== false) {
        $error_msg = 'Dieser Benutzername ist bereits vergeben';
        $error = true;
    }
    if ($error == false) {
        if ($upd_id != '' AND ($upd_vorname != '' or $upd_nachname != '' or $upd_email != '' or $upd_role != '' or $upd_biography != '' or $upd_gender != '' or $upd_birthday != '' or $upd_username != '' or $upd_passwort != '' or $upd_profilpicture != '' or !isset($error))) {

            // speichern
            $update = $db->prepare("UPDATE users SET vorname =?, nachname=?, email=?, role=?, biography=?, gender=?, birthday=?, username=?, passwort=? WHERE id=? LIMIT 1");
            $update->bind_param('sssssssssi', $upd_vorname, $upd_nachname, $upd_email, $upd_role, $upd_biography, $upd_gender, $upd_birthday, $upd_username, $upd_passwort, $upd_id);

            if ($upd_profilpicture) {
                $upload_folder = '../include/database/profilpictures/'; //Das Upload-Verzeichnis
                $filename = "$upd_id";
                $extension = strtolower(pathinfo($_FILES['profilpicture']['name'], PATHINFO_EXTENSION));
                //Überprüfung der Dateiendung
                $allowed_extensions = array('jpg');
                if (!in_array($extension, $allowed_extensions)) {
                    $error_msg = "Nur .jpg Dateien sind erlaubt";
                }
                //Überprüfung der Dateigröße
                $max_size = 10000 * 1024; //500 KB
                if ($_FILES['profilpicture']['size'] > $max_size) {
                    $error_msg = "Bitte keine Dateien größer 10MB hochladen";
                }
                //Überprüfung dass das Bild keine Fehler enthält
                if (function_exists('exif_imagetype')) {
                    $allowed_types = array(IMAGETYPE_JPEG);
                    $detected_type = exif_imagetype($_FILES['profilpicture']['tmp_name']);
                    if (!in_array($detected_type, $allowed_types)) {
                        $error_msg = "Das ist kein gültiges Bild";
                    }
                }
                //Pfad zum Upload
                $new_path = $upload_folder . $filename . '.' . $extension;
                //Alles okay, verschiebe Datei an neuen Pfad
                move_uploaded_file($_FILES['profilpicture']['tmp_name'], $new_path);
                //Bild wird skaliert
                bild_skalieren($new_path, $new_path, 600, 600);
                //Thumbnail erstellen
                bild_skalieren($new_path, '../include/database/profilpictures/thumbnail/' . "$upd_id" . '.jpg', 150, 150);
                $success_msg = 'Bild erfolgreich hochgeladen: <a href="' . $new_path . '">' . $new_path . '</a>';
            }

            //Wenn #inputProfilpictureCheck nicht ausgewählt wurde, wird zum Benutzer zugehöriges Profilbild gelöscht.
            if ($upd_profilpicturecheck != 'same') {
                //Profilbild löschen
                @unlink('../include/database/profilpictures/' . "$upd_id" . '.jpg');
                @unlink('../include/database/profilpictures/thumbnail/' . "$upd_id" . '.jpg');

                $success_msg = 'Profilbild wurde gelöscht.';
            }

            if ($update->execute()) {
                $msg = 'Benutzerdaten wurden erfolgreich geändert';
                $modus_aendern = false;
            }
        }
    }

    if (isset($_POST['aktion']) and $_POST['aktion'] == 'speichern') {
        $vorname = "";
        if (isset($_POST['vorname'])) {
            $vorname = trim($_POST['vorname']);
        }
        $nachname = "";
        if (isset($_POST['nachname'])) {
            $nachname = trim($_POST['nachname']);
        }
        $email = "";
        if (isset($_POST['email'])) {
            $email = trim($_POST['email']);
        }
        $role = "";
        if (isset($_POST['role'])) {
            $role = trim($_POST['role']);
        }
        $biography = "";
        if (isset($_POST['biography'])) {
            $biography = trim($_POST['biography']);
        }
        $gender = "";
        if (isset($_POST['gender'])) {
            $gender = trim($_POST['gender']);
        }
        $birthday = "";
        if (isset($_POST['birthday'])) {
            $gender = trim($_POST['birthday']);
        }
        $username = "";
        if (isset($_POST['username'])) {
            $username = trim($_POST['username']);
        }
        if (!empty($_POST['passwort'])) {
            $passwort = trim($_POST['passwort']);
        }

        if ($vorname != '' or $nachname != '' or $email != '' or $role != '' or $biography != '' or $gender != '' or $birthday != '' or $username != '' or $passwort != '') {


            // speichern
            $einfuegen = $db->prepare("INSERT INTO users (vorname, nachname, email, role, biography, gender, birthday, username, passwort, id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $einfuegen->bind_param('sssssssssi', $vorname, $nachname, $email, $role, $biography, $gender, $birthday, $username, $passwort, $id);

            if ($einfuegen->execute()) {
                header('Location: user?aktion=feedbackgespeichert');
                die();
                $msg = 'gespeichert';
            }
        }
    }
}
if (isset($_GET['aktion']) and $_GET['aktion'] == 'feedbackgespeichert') {
    $msg = 'Benutzer wurde aktualisiert';
}
$modus_aendern = false;
if (isset($_GET['aktion']) and $_GET['aktion'] == 'bearbeiten') {
    $modus_aendern = true;
}
if ($modus_aendern != true) {
    $daten = array();
    if ($erg = $db->query("SELECT * FROM users")) {
        if ($erg->num_rows) {
            while ($datensatz = $erg->fetch_object()) {
                $daten[] = $datensatz;
            }
            $erg->free();
        }
    }
    if (!count($daten)) {
        $msg = 'Es sind keine Benutzer registriert';
    } else {
        echo '<table id="sort">
				<thead>
					<tr>
						<th>Nutzeraktion</th>
						<th>ID</th>
						<th>Vorname</th>
						<th>Nachname</th>
						<th>E-Mail</th>
						<th>Rolle</th>
						<th>Biografie</th>
						<th>Geschlecht</th>
						<th>Geburtsdatum</th>
						<th>Benutzername</th>
						<th>Profilbild</th>
				</thead>
				<tbody>';
        foreach ($daten as $inhalt) {

            // Überprüft ob Benutzer ein Probilbild hat. Wenn nicht wird geguckt ob er z.B. Männlich/Weblich ist und dementsprechend wird das neue Profilbild gesetzt.
            $avatar = $options['siteurl'] . '/include/database/profilpictures/thumbnail/' . $inhalt->id . '.jpg';
            $avatar_test = @getimagesize($avatar);
            //Der Nutzer hat kein Profilbild
            if (!$avatar_test) {
                if ($inhalt->gender === 'female') {
                    $avatar_type = 'default_female.png';
                } else if ($inhalt->gender === 'male') {
                    $avatar_type = 'default_male.png';
                } else {
                    $avatar_type = 'default_other.png';
                }
                $avatar = $options['siteurl'] . '/include/database/profilpictures/thumbnail/' . $avatar_type;
            }

            echo '
						<tr>
							<td>
								<a href="?aktion=bearbeiten&id=' . $inhalt->id . '"><img src="../include/images/user_edit.svg" height="25px" style="float: left;" alt="bearbeiten"></a> 
								<a href="?aktion=loeschen&id=' . $inhalt->id . '"><img src="../include/images/user_delete.svg" height="25px" style="float: right;" alt="löschen"></a>
							</td>
							<td>' . $inhalt->id . '</td>
							<td>' . bereinigen($inhalt->vorname) . '</td>
							<td>' . bereinigen($inhalt->nachname) . '</td>
							<td>' . bereinigen($inhalt->email) . '</td>
							<td>' . bereinigen($inhalt->role) . '</td>
							<td>' . bereinigen($inhalt->biography) . '</td>
							<td>' . bereinigen($inhalt->gender) . '</td>
							<td>' . bereinigen($inhalt->birthday) . '</td>
							<td>' . bereinigen($inhalt->username) . '</td>
							<td style="padding: 0; margin: 0;"><img src="' . $avatar . '" alt="Profilbild ' . bereinigen($inhalt->username) . '" height="55" width="55"></td>
						</tr>
						';
        }
        echo '</tbody></table>';
    }
}

if ($modus_aendern == true and isset($_GET['id'])) {
    $id_einlesen = (INT)$_GET['id'];
    if ($id_einlesen > 0) {
        $dseinlesen = $db->prepare("SELECT id, vorname, nachname, email, role, biography, gender, birthday, username, passwort FROM users WHERE id=? ");
        $dseinlesen->bind_param('i', $id_einlesen);
        $dseinlesen->bind_result($id, $vorname, $nachname, $email, $role, $biography, $gender, $birthday, $username, $passwort);
        $dseinlesen->execute();
        while ($dseinlesen->fetch()) {
            // echo "<li>";
            // echo $id . ' / '. $vorname .' '. $nachname.' '. $email.' '.$role.' '.$biography;
        }
    }
}
function bereinigen($inhalt = '')
{
    $inhalt = trim($inhalt);
    $inhalt = htmlentities($inhalt, ENT_QUOTES, "UTF-8");
    return ($inhalt);
}

// Wenn der Benutzer kein Profilbild hat, wird der Punkt: "Profilbild nicht löschen" ausgeblendet.
if ($modus_aendern == true) {
    if (@getimagesize('../include/database/profilpictures/' . $id_einlesen . '.jpg')) {
        $upd_profilpicturecheck = 'same';
        $profilpicturecheck = 'none';
    }
}

//setzt angegebennen wert der $role Variable auf selected, dass im <select name="role"> die richtige Rolle anvisiert wird.
if (@$role === 'adminstrator') {
    $role_adminstrator_select = 'selected';
}
if (@$role === 'manager') {
    $role_manager_select = 'selected';
}
if (@$role === 'supporter') {
    $role_supporter_select = 'selected';
}
if (@$role === 'member') {
    $role_member_select = 'selected';
}
if (@$role === 'user') {
    $role_user_select = 'selected';
}

//setzt angegebenen Wert der $gender Variable auf selected
if (@$gender === 'female') {
    $gender_female_select = 'selected';
}
if (@$gender === 'male') {
    $gender_male_select = 'selected';
}
if (@$gender === 'other') {
    $gender_other_select = 'selected';
}
if (@$gender === 'noinformation') {
    $gender_noinformation_select = 'selected';
}

?>
