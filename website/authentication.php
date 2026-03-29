<?php
	$show_only_user = false;
	$title = 'Account bestätigen';
	$description = 'Bestätige deine E-Mail um den Account nutzen zu können.';
	$keywords = 'account,aktivieren';
    include($_SERVER['DOCUMENT_ROOT'].'/include/website/head.inc.php');
?>
<article>
    <section>
		<h1>Account bestätigen</h1>
		<?php 
			require($options['pluginpath'].'authentication.inc.php');
			get('error');
			include($options['pluginhtmlpath'].'authentication.html.inc.php'); 
		?>
	</section>
</article>
<?php
	include($_SERVER['DOCUMENT_ROOT']."/include/website/footer.inc.php");
?>
