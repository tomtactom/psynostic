<?php
	if($options['allowregister']) {
?>
	<form action="?register=1" method="post">
		<label for="inputUsername">Benutzername:* 
			<input type="text" id="inputUsername" size="40" minlength="3" maxlength="32" name="username" placeholder="Benutzername" pattern="[A-Za-z0-9/_-]+" required>
		</label>
		<label for="inputFirstname">Vorname:* 
			<input type="text" id="inputFirstname" size="40" minlength="3" maxlength="255" name="vorname" placeholder="Vorname" pattern="[a-zA-Z ]+" required>
		</label>
		<label for="inputLastname">Nachname:* 
			<input type="text" id="inputLastname" size="40" minlength="3" maxlength="255" name="nachname" placeholder="Nachname" pattern="[a-zA-Z]+" required>
		</label>
		<label for="inputEmail">E-Mail:* 
			<input type="email" id="inputEmail" size="40" minlength="6" maxlength="255" name="email" placeholder="E-Mail" required>
		</label>
		<label for="inputPassword">Passwort:* 
			<input type="password" id="inputPassword" size="40" minlength="8" maxlength="255" name="passwort" placeholder="Passwort" required>
		</label>
		<label for="inputPassword2">Passwort wiederholen:* 
			<input type="password" id="inputPassword2" size="40" minlenght="8" maxlength="255" name="passwort2" placeholder="Passwort wiederholen" required>
		</label>
		<label for="inputBiography">Biografie: 
			<textarea id="inputBiography" cols="30" rows="15" maxlength="255" name="biography" placeholder="Biografie"></textarea>
		</label>
		<label for="inputBirthday">Geburtsdatum:* 
			<input type="date" id="inputBirthday" maxlength="10" name="birthday" placeholder="Geburtsdatum" required>
		</label>
		<label for="gender">Geschlecht:* 
			<select name="gender" id="gender">
				<option value="female">Weiblich</option>
				<option value="male">Männlich</option>
				<option value="other">Anderes Geschlecht</option>
				<option value="noinformation" selected>Keine Angabe</option>
			</select>
		</label>
		<?php
		if($user['role'] === 'adminstrator') {
		?>
		<label for="role">Rolle:* 
			<select name="role" id="role">
				<option value="adminstrator">Adminstrator</option>
				<option value="manager">Manager</option>
				<option value="supporter">Supporter</option>
				<option value="member">Mitglied</option>
				<option value="user" selected>Benutzer</option>
			</select>
		</label>
		<?php }
		if($user['role'] === 'manager') {
		?>
		<label for="role">Rolle:* 
			<select name="role" id="role">
				<option value="member">Mitglied</option>
				<option value="user" selected>Benutzer</option>
			</select>
		</label>
		<?php } ?>
		<button type="submit">Registrieren</button>
	</form>
	Alle Felder mit <font style="color: red;">*</font> müssen ausgefüllt werden.
<?php } ?>
