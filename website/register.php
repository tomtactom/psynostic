<?php
	$show_only_user = false;
	$title = 'Registrieren';
	$description = 'Wenn du dich Registrierst, hast du viel mehr Möglichkeiten die Webseite zu nutzen.';
	$keywords = 'registrieren';
    include($_SERVER['DOCUMENT_ROOT'].'/include/website/head.inc.php');
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
	include($_SERVER['DOCUMENT_ROOT']."/include/website/footer.inc.php");
?>
