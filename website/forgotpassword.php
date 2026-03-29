<?php
	$show_only_user = false;
	$showForm = true;
	$title = 'Passwort vergessen';
	$description = 'Wenn du dein Passwort vergessen hast, kannst du es hier zurücksetzen';
	$keywords = 'passwort vergessen';
    include($_SERVER['DOCUMENT_ROOT'].'/include/website/head.inc.php');
?>
<article>
    <section>
		<h1>Passwort vergessen</h1>
		<?php 			 
			require($options['pluginpath'].'forgotpassword.inc.php');
			get('error');
			include($options['pluginhtmlpath'].'forgotpassword.html.inc.php');
		?>
	</section>
</article>
<?php 
	include($_SERVER['DOCUMENT_ROOT']."/include/website/footer.inc.php")
?>
