<?php
	if (isset($_GET['user'])) {
		$username = $_GET['user'];
		$sql = "SELECT * FROM users WHERE username='$username'";
		$result = $db->query($sql);
		if ($result->num_rows > 0) {
			if($row = $result->fetch_assoc()) {
				$avatar = $options['siteurl'].'/include/database/profilpictures/'.$row["id"].'.jpg';
				$avatar_test = @getimagesize($avatar);
				$show_profile = true;
			}
		}
		else {
			$msg = "Es ist kein Benutzer mit diesem Benutzernamen registriert.";
		}
	} else {
		$avatar = $options['siteurl'].'/include/database/profilpictures/'.$user["id"].'.jpg';
		$avatar_test = @getimagesize($avatar);
	}

	//wandelt die Geschlechtsdaten um
	if (!$avatar_test) {
		if ($row['gender'] === 'female') {
			$avatar_type_all = 'default_female.png';
			$gender_all = 'Weiblich';
		} elseif ($row['gender'] === 'male') {
			$avatar_type_all = 'default_male.png';
			$gender_all = 'Männlich';
		} elseif($row['gender'] === 'other') {
			$avatar_type_all = 'default_other.png';
			$gender_all = 'Anderes Geschlecht';
		} elseif ($row['gender'] === 'noinformation') {
			$avatar_type_all = 'default_other.png';
			$gender_all = 'Keine Angabe';
		}
		@$avatar_all = $options['siteurl'].'/include/database/profilpictures/'.$avatar_type_all;
		if ($user['gender'] === 'female') {
			$avatar_type = 'default_female.png';
			$gender = 'Weiblich';
		} elseif ($user['gender'] === 'male') {
			$avatar_type = 'default_male.png';
			$gender = 'Männlich';
		} elseif($user['gender'] == 'other') {
			$avatar_type = 'default_other.png';
			$gender = 'Anderes Geschlecht';
		} elseif ($user['gender'] = 'noinformation') {
			$avatar_type = 'default_other.png';
			$gender = 'Keine Angabe';
		}
		@$avatar = $options['siteurl'].'/include/database/profilpictures/'.$avatar_type;
	} else {
		if ($row['gender'] === 'female' || $user['gender'] === 'female') {
			$gender_all = 'Weiblich';
		}
		if ($row['gender'] === 'male' || $user['gender'] === 'male') {
			$gender_all = 'Männlich';
		}
		if ($row['gender'] === 'other' || $user['gender'] === 'other') {
			$gender_all = 'Anderes Geschlecht';
		}
		if ($row['gender'] === 'noinformation' || $user['gender'] === 'noinformation') {
			$gender_all = 'Keine Angabe';
		}
		@$avatar_all = $options['siteurl'].'/include/database/profilpictures/'.$row['id'].'.jpg';
	}
?>
