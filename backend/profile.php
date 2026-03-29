<?php
	$show_only_user = 'min_member';
	$title = 'Profil';
	$description = 'In deinem Profil stehen Daten über dich.';
	$keywords = 'profil';
    include('../include/backend/head.inc.php');
    include('../include/backend/header.inc.php');
?>
<article>
    <section>
		<?php
			require($options['pluginpath'].'profile.inc.php');
			require($options['pluginpath'].'settings.inc.php');
			get('error');
		?>
		<?php if (isset($show_profile)) { ?>
		<?php
			include($options['pluginhtmlpath'].'profile.html.inc.php');
		?>
		<?php } else { ?>
			<h1>Dein Account</h1>
			<?php
				include($options['pluginhtmlpath'].'myprofile.html.inc.php');
			?>
			<?php include($options['pluginhtmlpath'].'settings.html.inc.php'); ?>
			<h2>Alle Benutzer</h2>
			<?php
				require($options['pluginpath'].'members.inc.php');
			}
			?>
	</section>
</article>
<?php
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php');
?>
