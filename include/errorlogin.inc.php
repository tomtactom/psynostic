<?php
	$title = 'Logge dich ein um die Seite zu besuchen';
	$description = 'Diese Seite können nur angemeldete Nutzer besuchen.';
	$keywords = 'login, errorlogin';
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');
?>
<article>
    <section>
		<h1>Bitte logge dich ein, um die Seite zu besuchen</h1>
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
