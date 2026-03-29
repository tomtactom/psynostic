<?php
	$show_only_user = false;
	$title = 'Passwort zurücksetzen';
	$description = 'Gebe dein neues Passwort ein um es zurückzusetzen.';
	$keywords = 'passwort zurücksetzen';
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');
?>
<article>
    <section>
		<h1>Passwort zurücksetzen</h1>
		<?php 			 
			require($options['pluginpath'].'resetpassword.inc.php');
			get('error');
			include($options['pluginhtmlpath'].'resetpassword.html.inc.php');
		?>
	</section>
</article>
<?php 
	include($_SERVER['DOCUMENT_ROOT']."/include/backend/footer.inc.php")
?>
