<?php
	$row = null;
	$show_profile = false;
	$avatar_test = false;

	if (isset($_GET['user'])) {
		$username = $_GET['user'];
		$sql = "SELECT * FROM users WHERE username='$username'";
		$result = $db->query($sql);
		if ($result->num_rows > 0) {
			if ($row = $result->fetch_assoc()) {
				$avatar = $options['siteurl'].'/include/database/profilpictures/'.$row['id'].'.jpg';
				$avatar_test = @getimagesize($avatar);
				$show_profile = true;
			}
		} else {
			$msg = "Es ist kein Benutzer mit diesem Benutzernamen registriert.";
		}
	} else {
		$avatar = $options['siteurl'].'/include/database/profilpictures/'.$user['id'].'.jpg';
		$avatar_test = @getimagesize($avatar);
		$row = $user;
	}

	$profileData = is_array($row) ? $row : $user;
	$profileGender = $profileData['gender'] ?? 'noinformation';
	$profileId = $profileData['id'] ?? ($user['id'] ?? null);

	//wandelt die Geschlechtsdaten um
	if (!$avatar_test) {
		if ($profileGender === 'female') {
			$avatar_type_all = 'default_female.png';
			$gender_all = 'Weiblich';
		} elseif ($profileGender === 'male') {
			$avatar_type_all = 'default_male.png';
			$gender_all = 'Männlich';
		} elseif ($profileGender === 'other') {
			$avatar_type_all = 'default_other.png';
			$gender_all = 'Anderes Geschlecht';
		} else {
			$avatar_type_all = 'default_other.png';
			$gender_all = 'Keine Angabe';
		}
		$avatar_all = $options['siteurl'].'/include/database/profilpictures/'.$avatar_type_all;

		$userGender = $user['gender'] ?? 'noinformation';
		if ($userGender === 'female') {
			$avatar_type = 'default_female.png';
			$gender = 'Weiblich';
		} elseif ($userGender === 'male') {
			$avatar_type = 'default_male.png';
			$gender = 'Männlich';
		} elseif ($userGender === 'other') {
			$avatar_type = 'default_other.png';
			$gender = 'Anderes Geschlecht';
		} else {
			$avatar_type = 'default_other.png';
			$gender = 'Keine Angabe';
		}
		$avatar = $options['siteurl'].'/include/database/profilpictures/'.$avatar_type;
	} else {
		if ($profileGender === 'female') {
			$gender_all = 'Weiblich';
		}
		if ($profileGender === 'male') {
			$gender_all = 'Männlich';
		}
		if ($profileGender === 'other') {
			$gender_all = 'Anderes Geschlecht';
		}
		if ($profileGender === 'noinformation') {
			$gender_all = 'Keine Angabe';
		}
		if ($profileId !== null) {
			$avatar_all = $options['siteurl'].'/include/database/profilpictures/'.$profileId.'.jpg';
		}
	}
?>
