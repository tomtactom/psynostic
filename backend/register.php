<?php
	$show_only_user = 'min_member';
	$title = 'Registrieren';
	$description = 'Wenn du dich Registrierst, hast du viel mehr Möglichkeiten die Webseite zu nutzen.';
	$keywords = 'registrieren';
	$showFormularAfterRegister = true;
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');
?>
<article>
    <section>
		<h1>Registrieren</h1>
		<?php
			require($options['pluginpath'].'register.inc.php');
			get('error');
			include($options['pluginhtmlpath'].'register.html.inc.php');
		?>
	</section>
</article>
<?php 
	include($_SERVER['DOCUMENT_ROOT']."/include/backend/footer.inc.php");
?>
