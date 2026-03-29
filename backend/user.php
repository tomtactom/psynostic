<?php
	$show_only_user = 'min_manager';
	$title = 'Benutzerdaten ändern';
	$description = 'Als Manager kannst du hier alle Benutzer verwalten.';
	$keywords = 'Benutzer bearbeiten, user';
    require_once($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');

?>
<article>
    <section>
		<h1>Benutzer</h1>
		<?php
			require($options['pluginpath'].'user.inc.php');
			get('error');
			include($options['pluginhtmlpath'].'user.html.inc.php');
		?>
	</section>
</article>
<?php 
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); 
?>
