<?php
	$show_only_user = false;
	$showForm = true;
	$title = 'Passwort vergessen';
	$description = 'Wenn du dein Passwort vergessen hast, kannst du es hier zurücksetzen';
	$keywords = 'passwort vergessen';
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');
?>
<article>
    <section>
		<h1>Passwort vergessen</h1>
		<?php 			 
			require($options['pluginpath'].'forgotpassword.inc.php');
			get('error');
			require($options['pluginhtmlpath'].'forgotpassword.html.inc.php');
		?>
	</section>
</article>
<?php 
	include($_SERVER['DOCUMENT_ROOT']."/include/backend/footer.inc.php")
?>
