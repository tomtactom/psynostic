<form method="post">
		<label for="inputEmail">E-Mail oder Benutzername: <br>
			<input type="text" name="emailorusername" placeholder="E-Mail oder Benutzername" value="<?php echo $email_value; ?>" minlength="3" required>
		</label>
		<label for="inputPassword">Passwort: <br>
			<input type="password" id="inputPassword" name="passwort" placeholder="Passwort" required>
		</label>
		<label for="inputAngemeldet_bleiben">
			<input type="checkbox" id="inputAngemeldet_bleiben" name="angemeldet_bleiben" value="1"> Angemeldet bleiben
		</label>
		<button type="submit">Login</button>
		<a href="forgotpassword">Passwort vergessen</a><br>
		<big><strong><a href="register" title="Jetzt Registrieren">Registrieren</a></strong></big>
	</form>
