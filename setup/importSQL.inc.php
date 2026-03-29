<?php

	include("helper.inc.php");
	$conn =new mysqli($_SESSION['db_host'], $_SESSION['db_user'], $_SESSION['db_pass'] , $_SESSION['db_name']);
	$query = '';
	$sqlScript = file('config/import.sql');
	foreach ($sqlScript as $line)	{

		$startWith = substr(trim($line), 0 ,2);
		$endWith = substr(trim($line), -1 ,1);

		if (empty($line) || $startWith == '--' || $startWith == '/*' || $startWith == '//') {
			continue;
		}

		$query = $query . $line;
		if ($endWith == ';') {
			mysqli_query($conn,$query) or die('Problem beim Ausführen der SQL-Abfrage <b>' . $query. '</b>');
			$query= '';		
		}
	}
	$msg = "Tabelle erfolgreich importiert";

	
	// show error
	include("templates/importSQL.inc.php");