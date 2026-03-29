<?php

// Functions for Plugin Questionnaire

function questionnaire_get_urls() {
	global $options;
	return [
		'questionnaires' => $options['siteurl'].'/backend/questionnaires.php',
		'normtables' => $options['siteurl'].'/backend/normtables.php',
		'results' => $options['siteurl'].'/backend/results.php',
		'frontend' => $options['siteurl'].'/website/questionnaire.php'
	];
}

function questionnaire_show_backend_overview($title, $description) {
	$urls = questionnaire_get_urls();
	echo '<h1>'.$title.'</h1>';
	echo '<p>'.$description.'</p>';
	echo '<ul>';
	echo '<li><a href="'.$urls['questionnaires'].'">Fragebögen</a></li>';
	echo '<li><a href="'.$urls['normtables'].'">Normtabellen</a></li>';
	echo '<li><a href="'.$urls['results'].'">Ergebnisse</a></li>';
	echo '<li><a href="'.$urls['frontend'].'">Frontend-Ansicht</a></li>';
	echo '</ul>';
}

function questionnaire_show_frontend() {
	echo '<h1>Fragebogen</h1>';
	echo '<p>Hier können Fragebögen bereitgestellt und ausgefüllt werden.</p>';
}
?>
