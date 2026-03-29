<?php
	@session_start();
	require("config/config.php");
	if("/setup/index.php" == $_SERVER['REQUEST_URI']) {
		header("Location: ../setup");
	}

	if(!file_exists("../include/database/database.php")) {
		file_put_contents('../include/database/database.php', '');
	}

	//Zeige nächte schritte
	$nextStep = "introduction";
	if (isset($_POST['nextStep']))
		$nextStep = $_POST['nextStep'];

	// Definiere Variablen
	$step = $nextStep;
	$title = $step;
	$header = $config['header'];
	$product = $introduction["product"];
	$setup = true;

	include("templates/head.inc.php");
	include("templates/header.inc.php");
	include($nextStep.".inc.php");
	include("templates/footer.inc.php");
