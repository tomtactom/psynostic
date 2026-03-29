	<article>
		<section>
			<h3>Datenbankverbindung</h3>
			<?php if ($error) { ?>
				<p>
					<strong>Datenbankverbindung fehlgeschlagen: <?php echo $error; ?></strong><br>
					<br>
					Entweder ist der Benutzername oder das Passwort falsch oder der Datenbankserver konnte nicht kontaktiert werden: <i><?php echo $host; ?></i>. <br> 
				</p>
			<?php } ?>
			<form method="post">
				<label for="inputDatabase">Datenbankname: 
					<input type="text" id="inputDatabase" name="database" value="<?php echo $database; ?>">
				</label><br>
				<label for="inputUsername">MySQL Benutzername: 
					<input type="text" id="inputUsername" name="username" value="<?php echo $username; ?>">
				</label><br>
				<label for="inputPassword">MySQL Passwort: 
					<input type="password" id="inputPassword" name="password" value="<?php echo $password; ?>">
				</label><br>
				<label for="inputHost">MySQL <abbr title='z.B. "http://example.com"'>Host</abbr>: 
					<input type="text" id="inputHost" name="host" value="<?php echo $host; ?>">
				</label><br>
				<hr>
				<?php if ($goToNextStep) { ?>
					<div class="success">Es wurde sich erfolgreich mit der Datenbank verbunden</div>
					<a href="index.php">Abbrechen</a>
					<input type="hidden" name="nextStep" value="importSQL">
					<button type="submit">Weiter</button>
				<?php } else { ?>
					<a href="index.php">Abbrechen</a>
					<input type="hidden" name="nextStep" value="database">
					<button type="submit">Verbindung testen</button>
				<?php } ?>
			</form>
		</section>
	</article>
