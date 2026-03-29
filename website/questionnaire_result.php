<?php
	$show_only_user = false;
	$title = 'Fragebogen-Auswertung';
	$description = 'Auswertung abgeschlossener Fragebogen-Sessions';
	$keywords = 'fragebogen, auswertung, ergebnis';
	include($_SERVER['DOCUMENT_ROOT'].'/include/website/head.inc.php');
?>
<article>
	<section>
		<?php questionnaire_show_frontend('result'); ?>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/website/footer.inc.php'); ?>
