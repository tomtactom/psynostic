<?php
	session_destroy();
	unset($_SESSION['userid']);
	//Remove Cookies
	setcookie("identifier","",time()-(3600*24*365));
	setcookie("securitytoken","",time()-(3600*24*365));
?>
