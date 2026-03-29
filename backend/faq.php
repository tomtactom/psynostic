<?php
	$show_only_user = 'min_supporter';
	$title = 'Häufig gestellte Fragen beantworten';
	$description = 'Bearbeite, verwalte und lösche gestellte Fragen';
	$keywords = 'Fragen bearbeiten, Fragen löschen';
    require_once($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');

?>
<article>
    <section>
		<h1>Fragen verwalten</h1>
		<?php
			require($options['pluginpath'].'faq/faqcontrol.inc.php');
			get('error');
			require($options['pluginpath'].'faq/faqcontrol.html.inc.php');
		?>
	</section>
</article>
<?php 
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); 
?>
