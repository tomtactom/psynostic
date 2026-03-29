<?php
	$show_only_user = false;
	$title = 'Kontaktformular & Support';
	$keywords = 'support, kontakt';
	include($_SERVER['DOCUMENT_ROOT'].'/include/website/head.inc.php'); 
  ?><article>
		<section>
<?php require($options['pluginpath'].'contact.inc.php'); ?>
			<?php get('error'); ?>
			<section>
				<h1>Kontaktiere uns, wenn du uns Feedback geben möchtest oder dich etwas stört🙂</h1>
			</section>
			<section>
<?php require($options['pluginhtmlpath'].'contact.html.inc.php'); ?>
			</section>
		</section>
    </article>
<?php include($_SERVER['DOCUMENT_ROOT']."/include/website/footer.inc.php"); ?>
