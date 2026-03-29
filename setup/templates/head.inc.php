<?php
    @session_start();
    include('../include/database/database.php');
    include('../include/functions.inc.php');
?>
<html lang="de">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
	<style>
		:root {
			--maincolor: #009688;
			--mainfontcolor: #0f0f0f;
			--mainbackgroundcolor: #ffffff;
			--mainhovercolor: #00ad9d;
		}
		html,
		body {
			font-family: sans-serif;
		}
	</style>
	<title>LupusGUI Installation</title>
	<meta name="description" content="Der Installationsmanager leitet dich durch den Vorgang.">
	<link rel="apple-touch-icon" sizes="57x57" href="../include/images/apple-icon-57x57.png">
	<link rel="apple-touch-icon" sizes="60x60" href="../include/images/apple-icon-60x60.png">
	<link rel="apple-touch-icon" sizes="72x72" href="../include/images/apple-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="76x76" href="../include/images/apple-icon-76x76.png">
	<link rel="apple-touch-icon" sizes="114x114" href="../include/images/apple-icon-114x114.png">
	<link rel="apple-touch-icon" sizes="120x120" href="../include/images/apple-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="144x144" href="../include/images/apple-icon-144x144.png">
	<link rel="apple-touch-icon" sizes="152x152" href="../include/images/apple-icon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="../include/images/apple-icon-180x180.png">
	<link rel="icon" type="image/png" sizes="192x192"  href="../include/images/android-icon-192x192.png">
	<link rel="icon" type="image/png" sizes="32x32" href="../include/images/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="96x96" href="../include/images/favicon-96x96.png">
	<link rel="icon" type="image/png" sizes="16x16" href="../include/images/favicon-16x16.png">
	<link rel="manifest" href="../include/images/manifest.json">
	<meta name="msapplication-TileImage" content="../include/images/ms-icon-144x144.png">
	<link rel="stylesheet" href="../backend/design/cleanlupus/style.css">
</head>
