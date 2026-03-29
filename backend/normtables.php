<?php
	$show_only_user = 'min_manager';
	$title = 'Normtabellen';
	$description = 'Verwalte Normtabellen im Backend';
	$keywords = 'normtabellen, backend, verwaltung';
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');
?>
<article>
	<section>
		<?php questionnaire_show_backend_overview('Normtabellen', 'Übersicht über verfügbare Normtabellen und deren Pflege.'); ?>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); ?>
