<?php
	$show_only_user = 'min_manager';
	$title = 'Fragebögen';
	$description = 'Verwalte Fragebögen im Backend';
	$keywords = 'fragebogen, backend, verwaltung';
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');
?>
<article>
	<section>
		<?php questionnaire_show_backend_overview('Fragebögen', 'Übersicht über Fragebögen, Normtabellen und Ergebnisse.'); ?>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); ?>
