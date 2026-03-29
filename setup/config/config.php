<?php
	$config['header'] = "LupusGUI";
	$config['applicationPath'] = "../";
	$config['database_file'] = "include/database/database.php";
	
	// INTRODUCTION
	$introduction = array();
	$introduction["product"] = "LupusGUI";
	$introduction["productVersion"] = "1.0";
	$introduction["company"] = "Tom";

	// SERVER REQUIREMENTS
	$requirements = array();
	$requirements["phpVersion"] = "5.6";
	$requirements["extensions"] = array("mysqli", "pdo", "pcre");

	// FILE PERMISSIONS
	// r = readable, w = writable, x = executable
	$filePermissions = array();
	$filePermissions["include/database/database.php"] = "rw";
	$filePermissions["include/tmp"] = "rw";