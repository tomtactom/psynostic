<?php
	$show_only_user = 'min_manager';
	$title = 'Update';
	$description = 'Damit das System immer auf dem neusten Stand bleibt, kannst du es hier updaten.';
	$keywords = 'update';
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');
?>
<article>
    <section>
		<h1>Update</h1>
		<?php
			require($options['pluginpath'].'update.inc.php');
			get('error');
			include($options['pluginhtmlpath'].'update.html.inc.php');
		?>
	</section>
</article>
<?php 
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php');
?>
