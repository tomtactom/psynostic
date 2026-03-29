<?php
	$requestPath = parse_url(isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '', PHP_URL_PATH);
	if ($requestPath !== null && substr((string)$requestPath, -17) === '/questionnaires.php') {
		header('Location: /questionnaires', true, 301);
		exit;
	}

	$show_only_user = false;
	$title = 'Fragebögen – Übersicht';
	$description = 'Übersicht der verfügbaren Fragebögen';
	$keywords = 'fragebogen, umfrage, formular';
	include($_SERVER['DOCUMENT_ROOT'].'/include/website/head.inc.php');
?>
<article>
	<section>
		<?php questionnaire_show_frontend('overview'); ?>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/website/footer.inc.php'); ?>
