<?php
	$show_only_user = false;
	$title = 'Login';
	$description = 'Hier kannst du dich einloggen um auf mehr Seiten zu kommen.';
	$keywords = 'login, anmelden';
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');
?>
<article>
    <section>
		<h1>Login</h1>
		<?php
			require($options['pluginpath'].'login.inc.php');
			get('error');
			include($options['pluginhtmlpath'].'login.html.inc.php');
		?>
	</section>
</article>
<?php 
	include($_SERVER['DOCUMENT_ROOT']."/include/backend/footer.inc.php")
?>
