<?php
//Verwaltet das Loginsystem
function check_user() {
	global $pdo;
	if(!isset($_SESSION['userid']) && isset($_COOKIE['identifier']) && isset($_COOKIE['securitytoken'])) {
		$identifier = $_COOKIE['identifier'];
		$securitytoken = $_COOKIE['securitytoken'];
		
		$statement = $pdo->prepare("SELECT * FROM securitytokens WHERE identifier = ?");
		$result = $statement->execute(array($identifier));
		$securitytoken_row = $statement->fetch();
	
		if(sha1($securitytoken) !== $securitytoken_row['securitytoken']) {
			//Vermutlich wurde der Security Token gestohlen
			//Hier ggf. eine Warnung o.ä. anzeigen
			echo "Dein Passwort bzw. securetytoken wurde möglicherweise gehackt. <br> Bitte wende dich an den Support!";
			
			
		} else { //Token war korrekt
			//Setze neuen Token
			$neuer_securitytoken = random_string();
			$insert = $pdo->prepare("UPDATE securitytokens SET securitytoken = :securitytoken WHERE identifier = :identifier");
			$insert->execute(array('securitytoken' => sha1($neuer_securitytoken), 'identifier' => $identifier));
			setcookie("identifier",$identifier,time()+(3600*24*365)); //1 Jahr Gültigkeit
			setcookie("securitytoken",$neuer_securitytoken,time()+(3600*24*365)); //1 Jahr Gültigkeit
	
			//Logge den Benutzer ein
			$_SESSION['userid'] = $securitytoken_row['user_id'];
		}
	}
	if(!isset($_SESSION['userid'])) {
		die( include('errorlogin.inc.php'));
	}
	$statement = $pdo->prepare("SELECT * FROM users WHERE id = :id");
	$result = $statement->execute(array('id' => $_SESSION['userid']));
	$user = $statement->fetch();
	return $user;
}

//überprüft ob Nutzer eingeloggt ist
function is_checked_in() {
	return isset($_SESSION['userid']);
}

//gibt einen zufällig erstellten String aus
function random_string() {
	if(function_exists('openssl_random_pseudo_bytes')) {
		$bytes = openssl_random_pseudo_bytes(16);
		$str = bin2hex($bytes); 
	} else if(function_exists('mcrypt_create_iv')) {
		$bytes = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
		$str = bin2hex($bytes); 
	} else {
		$str = md5(uniqid('your_secret_string', true));
	}	
	return $str;
}

//gibt aktuellen URL aus
function getSiteURL() {
	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
	return $protocol.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/';
}

//Über Funktion error() eine Errormeldung als String einspeichern
function error($error_msg) {
	die( include($_SERVER['DOCUMENT_ROOT'].'/include/error.inc.php'));
	exit();
}

//Skaliert Bild
function bild_skalieren($bild_tmp_name,$bild_neuer_name,$neue_breite,$neue_hoehe) {
	$size=getimagesize($bild_tmp_name); 
	$breite=$size[0]; 
	$hoehe=$size[1];
	if($breite>$hoehe) {
		$hoehe_skaliert=intval($hoehe*$neue_breite/$breite);
		$breite_skaliert=$neue_breite;
		$src_x=intval(($breite_skaliert-$neue_breite)/2);
		$src_y=0;
	} else {
		$hoehe_skaliert=$neue_hoehe;			
		$breite_skaliert=intval($breite*$neue_hoehe/$hoehe);
		$src_x=0;
		$src_y=intval(($hoehe_skaliert-$neue_hoehe)/2);
	} 
	$altesBild=imagecreatefromjpeg($bild_tmp_name); 
	$neuesBild_skaliert=imagecreatetruecolor($breite_skaliert,$hoehe_skaliert);
	$neuesBild_zugeschnitten=imagecreatetruecolor($neue_breite,$neue_hoehe);
	ImageCopyResized($neuesBild_skaliert,$altesBild,0,0,0,0,$breite_skaliert,$hoehe_skaliert,$breite,$hoehe);
	imagecopyresampled($neuesBild_zugeschnitten, $neuesBild_skaliert,0,0,$src_x,$src_y,$neue_breite,$neue_hoehe,$breite_skaliert,$hoehe_skaliert);
	ImageJPEG($neuesBild_zugeschnitten, $bild_neuer_name, 100); 
}

/** 
 * Löschen eines kompletten Directorys inklusive 
 * vorhandenen Files. 
 * 
 * @param string $path  
 * @return boolean 
 */ 
function loeschen($path) { 
    if (is_dir($path) === true) { 
        $files = array_diff(scandir($path), array('.', '..')); 
        // Durch die vorhandenen Dateien laufen 
        foreach ($files as $file) { 
          loeschen(realpath($path) . '/' . $file); 
        } 
        return rmdir($path); 
    } 
    // Datei entfernen 
    else if (is_file($path) === true) { 
        return unlink($path); 
    } 
    return false; 
}  

/** 
 * Gibt den kompletten URL aus 
 * 
 * @param none  
 * @return string 
 */ 
function getPageLink() {
	if(isset($_SERVER['HTTPS'])) {
		return "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	} else {
		return "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	}
}

//Zeigt Error Meldungen als Popup an
function get($value) {
	if ($value === 'error') {
		include($_SERVER['DOCUMENT_ROOT'].'/include/error.inc.php');
	}
}
