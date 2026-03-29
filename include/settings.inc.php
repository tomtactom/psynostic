<?php
	//Überprüfe ob das Setup schon gemacht wurde und leitet ggf. weiter
	if (!isset($db)) {
		if (file_exists($_SERVER['DOCUMENT_ROOT'].'/setup')) {
			header('Location: /setup');
		} else {
			echo 'Error! Deine Datenbankzugangsdaten sind kaputt';
		}
	}

	// Liest `option` Daten aus der Datenbank aus
	$result = $db->query("SELECT option_name, option_value FROM option");
	$options = [];
	$options_time = [];
	while ($row = $result->fetch_assoc()) {
		@$options[$row['option_name']] = $row['option_value'];
		@$options_time[$row['option_name']] = $row['updated_at'];
	}

	//stellt lehre Felder auf 0
	if ($options['robots'] === '1') {
		$options['robots'] = true;
	} else {
		$options['robots'] = false;
	}
	if ($showFormular === "") {
		$showFormular = "0";
	} else {
		$showFormular = "1";
	}

    //Stellt ein ob die Seite von Suchmaschienen erfasst werden soll oder nicht
    if ($options['robots'] == false) {
		$options['robots'] = 'noindex,nofollow';
    } else {
        $options['robots'] = 'index,follow';
    }
	//richtige URLs
	if (isset($options['siteurl'])) {
		$options['backenddesignurl'] = $options['siteurl'].'/backend/design/'.$options['backenddesign'];
		$options['frontenddesignurl'] = $options['siteurl'].'/website/design/'.$options['frontenddesign'];
		$options['backendstylesheeturl'] = $options['backenddesignurl'].'/style.css';
		$options['frontendstylesheeturl'] = $options['frontenddesignurl'].'/style.css';
	} else {
		$options['siteurl'] = getSiteURL();
	}

	//Kürzt die Domain so, dass vorne das "http://" oder das "https://" weggenommen wird
	if (!empty($options['siteurl'])) {
		$options['siteurl_trim'] = str_replace('http://', "", $options['siteurl']);
		$options['siteurl_trim'] = str_replace('https://', "", $options['siteurl']);
	}

	//Erlaubt nur Supportern oder höheren Rangstufen das betreten der Seite
	if ($show_only_user == true) {
		$user = check_user();
		if ($show_only_user === 'min_member' || $show_only_user === 'min_4' || $show_only_user === '1234') {
			if ($user['role'] !== 'member' && $user['role'] !== 'supporter' && $user['role'] !== 'manager' && $user['role'] !== 'adminstrator') {
				die('<script>alert("Du hast keine Berechtigung diese Seite zu besuchen."); location.replace("'.$options['siteurl'].'");</script>');
			}
		}
		if ($show_only_user === 'min_supporter' || $show_only_user === 'min_3' || $show_only_user === '123') {
			if ($user['role'] !== 'supporter' && $user['role'] !== 'manager' && $user['role'] !== 'adminstrator') {
				die('<script>alert("Du hast keine Berechtigung diese Seite zu besuchen."); location.replace("'.$options['siteurl'].'");</script>');
			}
		}
		if ($show_only_user === 'min_manager' || $show_only_user === 'min_2' || $show_only_user === '12') {
			if ($user['role'] !== 'manager' && $user['role'] !== 'adminstrator') {
				die('<script>alert("Du hast keine Berechtigung diese Seite zu besuchen."); location.replace("'.$options['siteurl'].'");</script>');
			}
		}
		if ($show_only_user === 'adminstrator' || $show_only_user === 'admin' || $show_only_user === 'min_adminstrator' || $show_only_user === '1') {
			if ($user['role'] !== 'adminstrator') {
				die('<script>alert("Du hast keine Berechtigung diese Seite zu besuchen."); location.replace("'.$options['siteurl'].'");</script>');
			}
		}
	}

	//Plugin Pfad
	if (!isset($options['pluginpath'])) {
		$options['pluginpath'] = $_SERVER['DOCUMENT_ROOT'].'/include/plugins/';
	}

	//HTML-Plugin Pfad
	if (!isset($options['pluginhtmlpath'])) {
		$options['pluginhtmlpath'] = $_SERVER['DOCUMENT_ROOT'].'/include/plugins/htmltemplates/';
	}

	//CMS-Plugin Pfad
	if (!isset($options['plugincmspath'])) {
		$options['plugincmspath'] = $_SERVER['DOCUMENT_ROOT'].'/include/plugins/cms/';
	}

	//Statistik-Plugin Pfad
	if (!isset($options['pluginstatisticpath'])) {
		$options['pluginstatisticpath'] = $_SERVER['DOCUMENT_ROOT'].'/include/plugins/statistic/';
	}

	//Sprache Kurz
	if (isset($options['language'])) {
		if ($options['language'] === 'german') {
			$options['short_language'] = 'de';
		} elseif ($options['language'] === 'english') {
			$options['short_language'] = 'en';
		} else {
			if (!$setup) {
				$error = 'Es ist noch keine Sprache angegeben. Bitte gebe eine in den Websiteeinstellungen an.';
			}
			$options['short_language'] = 'en';
		}
	}

	//Keywords richtig anzeigen lassen
	if (isset($keywords)) {
		$options['keywordsmain'] = $options['keywordsmain'].', '.$keywords;
	}

	//Beschreibung richtig anzeigen lassen
	if (isset($description)) {
		$options['sitedescription'] = $description;
	}
?>
