<?php

	$error = false;
	$goToNextStep = false;
	
	if (isset($_POST['database'])) {
		$database = $_POST['database'];
		$username = $_POST['username'];
		$password = $_POST['password'];
		$host = $_POST['host'];
		// check connection
		$connection = @new mysqli($host, $username, $password, $database);
		if ($connection->connect_errno) {
			$connection = false;
		} else {
			$connection = true;
		}
		
		if ($connection) {			
			if (!$error) {
				// save settings in database config file
				// load template
				$template = file_get_contents("config/database_template.php");
				$template = str_replace("%%host%%", $host, $template);
				$template = str_replace("%%username%%", $username, $template);
				$template = str_replace("%%password%%", $password, $template);
				$template = str_replace("%%database%%", $database, $template);
				
				// write config file
				$dbFile = dirname(getenv('SCRIPT_FILENAME'))."/".$config['applicationPath'].$config['database_file'];
				
				file_put_contents($dbFile, $template);
				
				// save login in session for further use
				$_SESSION['db_host'] = $host;
				$_SESSION['db_user'] = $username;
				$_SESSION['db_pass'] = $password;
				$_SESSION['db_name'] = $database;
				
				// allow user to proceed
				$goToNextStep = true;
			}
			else $error = 'Es konnte sich leider nicht verbunden werden';
		}
		else
			$error = 'Es konnte sich leider nicht verbunden werden';
	} else {
		if (isset($_SESSION['db_host'])) {
			$host = $_SESSION['db_host'];
			$username = $_SESSION['db_user'];
			$password = $_SESSION['db_pass'];
			$database = $_SESSION['db_name'];
		} else {
			$database = "";
			$username = "";
			$password = "";
			$host = "localhost";
		}
	}
	include("templates/database.inc.php");
