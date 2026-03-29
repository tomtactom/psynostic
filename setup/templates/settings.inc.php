	<article>
		<section>
			<h3>Administrator Account erstellen</h3>
			<form method="post">
			<?php
				if (!isset($goToNextStep)) {
			?>
			<style>
				label {
					display: block;
				}
			</style>
				<label for="inputUsername">Benutzername:*
					<input type="text" id="inputUsername" size="40" minlength="3" maxlength="32" name="username" placeholder="Benutzername" value="<?php echo $_POST['username']; ?>" pattern="[A-Za-z0-9/_-]+" required>
				</label>
				<label for="inputFirstname">Vorname:*
					<input type="text" id="inputFirstname" size="40" minlength="3" maxlength="255" name="vorname" placeholder="Vorname" value="<?php echo $_POST['vorname']; ?>" pattern="[a-zA-Z ]+" required>
				</label>
				<label for="inputLastname">Nachname:*
					<input type="text" id="inputLastname" size="40" minlength="3" maxlength="255" name="nachname" placeholder="Nachname" value="<?php echo $_POST['nachname']; ?>" pattern="[a-zA-Z]+" required>
				</label>
				<label for="inputEmail">E-Mail:*
					<input type="email" id="inputEmail" size="40" maxlength="250" name="email" placeholder="E-Mail" value="<?php echo $_POST['email']; ?>" required>
				</label>
				<label for="inputPassword">Passwort:*
					<input type="password" id="inputPassword" size="40" minlength="8" maxlength="250" name="passwort" placeholder="Passwort" required>
				</label>
				<label for="inputPassword2">Passwort wiederholen:*
					<input type="password" id="inputPassword2" size="40" minlenght="8" maxlength="250" name="passwort2" placeholder="Passwort wiederholen" required>
				</label>
				<label for="inputBiography">Biografie:
					<textarea id="inputBiography" maxlength="250" name="biography" placeholder="Biografie"><?php echo $_POST['biography']; ?></textarea>
				</label>
				<label for="inputBirthday">Geburtsdatum:*
					<input type="date" id="inputBirthday" maxlength="10" name="birthday" placeholder="Geburtsdatum" value="<?php echo $_POST['birthday']; ?>" required>
				</label>
				<label for="gender">Geschlecht:*
					<select name="gender" id="gender">
						<option value="female"<?php if($_POST['gender'] == 'female') { echo 'selected'; } ?>>Weiblich</option>
						<option value="male"<?php if($_POST['gender'] == 'male') { echo 'selected'; } ?>>Männlich</option>
						<option value="other"<?php if($_POST['gender'] == 'other') { echo 'selected'; } ?>>Anderes Geschlecht</option>
						<option value="noinformation" <?php if($_POST['gender'] != 'female' && $_POST['gender'] != 'male' && $_POST['gender'] != 'other') { echo 'selected'; } ?>>Keine Angabe</option>
					</select>
				</label>
			Alle Felder mit <font style="color: red;">*</font> müssen ausgefüllt werden.
			<hr>
		<?php } if (isset($goToNextStep)) { ?>
					<div class="success">Dein Administrator Account wurde erfolgreich installiert</div>
					<a href="index.php">Abbrechen</a>
					<input type="hidden" name="nextStep" value="websitesettings">
					<button type="submit">Weiter</button>
				<?php } else { ?>
					<a href="index.php">Abbrechen</a>
					<input type="hidden" name="nextStep" value="settings">
					<button type="submit" name="register" value="1">Account erstellen</button>
				<?php } ?>
			</form>
		</section>
	</article>
