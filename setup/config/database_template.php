<?php
	$db_host = '%%host%%';
	$db_name = '%%database%%';
	$db_user = '%%username%%';
	$db_password = '%%password%%';

	//Datenbank einfügen
	$db = mysqli_connect ($db_host, $db_user, $db_password, $db_name);
	$pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_password);
?>