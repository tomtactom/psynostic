<?php
	$show_only_user = 'min_manager';
	$title = 'Einstellungen';
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
    include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');
?>
<article>
    <section>
		<h1>Einstellungen</h1>
		<?php 
			require($options['pluginpath'].'websitesettings.inc.php');
			get('error');
		?>
		<blockquote>
			<?php include($options['pluginhtmlpath'].'websitesettings.html.inc.php'); ?>
		</blockquote>
	</section>
</article>
<?php
	include($_SERVER['DOCUMENT_ROOT']."/include/backend/footer.inc.php");
?>
