<?php
	$show_only_user = 'min_manager';
	$title = 'Ergebnisse';
	$description = 'Sieh Ergebnisse von Fragebögen ein';
	$keywords = 'ergebnisse, fragebogen, backend';
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');
?>
<article>
	<section>
		<?php questionnaire_show_backend_overview('Ergebnisse', 'Auswertung und Einsicht von Fragebogen-Ergebnissen.'); ?>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); ?>
