<?php
	//Gibt verschiedene Error/Fehler/Fertig meldungen aus (mit Funktion get('error') Inkludieren)
	global $error, $error_msg, $msg, $success_msg;
	if(isset($error) && !empty($error) && $error !== true) {
		echo '<div class="message">
						<span onclick="this.parentElement.style.display=\'none\';" style="float: right; cursor: pointer;">&times;</span>
						<strong>Error!</strong> '.$error.'
					  </div>';
	}
	if(isset($error_msg) && !empty($error_msg)) {
		echo '<div class="message">
						<span onclick="this.parentElement.style.display=\'none\';" style="float: right; cursor: pointer;">&times;</span>
						<strong>Fehler!</strong> '.$error_msg.'
					  </div>';
	}
	if(isset($msg) && !empty($msg)) {
		echo '<div class="message">
						<span onclick="this.parentElement.style.display=\'none\';" style="float: right; cursor: pointer;">&times;</span>
						<strong>Info!</strong> '.$msg.'
					  </div>';
	}
	if(isset($success_msg) && !empty($success_msg)) {
		echo '<div class="message">
						<span onclick="this.parentElement.style.display=\'none\';" style="float: right; cursor: pointer;">&times;</span>
						<strong>Fertig!</strong> '.$success_msg.'
					  </div>';
	}
?>
