<?php
	$show_only_user = false;
	$title = 'Fragebogen';
	$description = 'Fragebogen ausfüllen';
	$keywords = 'fragebogen, umfrage, formular';
	include($_SERVER['DOCUMENT_ROOT'].'/include/website/head.inc.php');
?>
<article>
	<section>
		<?php questionnaire_show_frontend(); ?>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/website/footer.inc.php'); ?>
