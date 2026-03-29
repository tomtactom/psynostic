<?php
	$show_only_user = false;
	$title = 'Häufig gestellte Fragen - FAQ';
	$keywords = 'faq, frage, hilfe';
	include($_SERVER['DOCUMENT_ROOT'].'/include/website/head.inc.php'); 
  ?><article>
		<section>
<?php require($options['pluginpath'].'faq/faq.inc.php'); ?>
			<?php get('error'); ?>
			<section>
				<h1>Die häufig gestelltesten Fragen!</h1>
			</section>
<?php require($options['pluginpath'].'faq/faq.html.inc.php'); ?>
        </section>
    </article>
<?php include($_SERVER['DOCUMENT_ROOT']."/include/website/footer.inc.php"); ?>
