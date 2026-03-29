<?php if ($showForm) { ?>
	<form action="?send=1&amp;userid=<?php echo htmlentities($userid); ?>&amp;code=<?php echo htmlentities($code); ?>" method="post">
		<label for="inputPasswort">Bitte gib ein neues Passwort ein: 
			<input type="password" id="inputPasswort" name="passwort" placeholder="Passwort" minlength="8" maxlength="255" required>
		</label>
		<label for="inputPasswort2">Passwort erneut eingeben: 
			<input type="password" id="inputPasswort2" name="passwort2" placeholder="Passwort (wiederholen)" minlength="8" maxlength="255" required>
		</label>
		<button type="submit">Passwort speichern</button>
	</form>
<?php } ?>
