	<ul>
		<li><a href="<?php echo $options['siteurl']; ?>">Startseite</a></li>
		<?php if (is_checked_in()) { ?>
		<li><a href="<?php echo $options['siteurl']; ?>/backend">Administrationsbereich</a></li>
		<li><a href="<?php echo $options['siteurl']; ?>/profile">Profil</a></li>
		<li><a href="<?php echo $options['siteurl']; ?>/logout">Logout</a></li>
		<?php } else { ?>
		<li><a href="<?php echo $options['siteurl']; ?>/login">Einloggen</a></li>
		<li><a href="<?php echo $options['siteurl']; ?>/register">Registrieren</a></li>
		<?php } ?>
	</ul>
