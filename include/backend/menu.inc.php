	<ul>
		<li><a href="<?php echo $options['siteurl']; ?>">Startseite</a></li>
		<li><a href="<?php echo $options['siteurl']; ?>/backend">Adminstrationsbereich</a></li>
		<?php if (is_checked_in()) { ?>
		<?php $user = check_user(); ?>
		<li><a href="<?php echo $options['siteurl']; ?>/backend/settings">Einstellungen</a></li>
		<li><a href="<?php echo $options['siteurl']; ?>/backend/register">Registrieren</a></li>
		<li><a href="<?php echo $options['siteurl']; ?>/backend/logout">Logout</a></li>
		<li><a href="<?php echo $options['siteurl']; ?>/backend/user">Benutzer</a></li>
		<li><a href="<?php echo $options['siteurl']; ?>/backend/profile">Profile</a></li>
		<li><a href="<?php echo $options['siteurl']; ?>/backend/faq">Fragen beantworten</a></li>
		<?php if ($user['role'] === 'manager' || $user['role'] === 'adminstrator') { ?>
		<li><a href="<?php echo $options['siteurl']; ?>/backend/questionnaires.php">Fragebögen</a></li>
		<li><a href="<?php echo $options['siteurl']; ?>/backend/normtables.php">Normtabellen</a></li>
		<li><a href="<?php echo $options['siteurl']; ?>/backend/results.php">Ergebnisse</a></li>
		<?php } ?>
		<li><a href="<?php echo $options['siteurl']; ?>/backend/update">Update</a></li>
		<?php } else { ?>
		<li><a href="<?php echo $options['siteurl']; ?>/backend/forgotpassword">Passwort vergessen</a></li>
		<li><a href="<?php echo $options['siteurl']; ?>/backend/login">Login</a></li>
		<li><a href="<?php echo $options['siteurl']; ?>/backend/register">Registrieren</a></li>
		<?php } ?>
	</ul>
