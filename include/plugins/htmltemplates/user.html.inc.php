<?php
	if ($modus_aendern) {
?>
<form action="user" method="post" enctype="multipart/form-data">
	<label for="inputVorname">Vorname: 
		<input type="text" id="inputVorname" name="vorname" value="<?php echo $vorname; ?>" maxlength="255">
	</label>
	<label for="inputNachname">Nachname: 
		<input type="text" id="inputNachname" name="nachname" value="<?php echo $nachname; ?>" maxlength="255">
	</label>
	<label for="inputEmail">E-Mail: 
		<input type="email" id="inputEmail" name="email" value="<?php echo $email; ?>" maxlength="255">
	</label>			
	<label for="inputBiography">Biografie: 
		<textarea id="inputBiography" cols="30" rows="15" name="biography" maxlength="255"><?php echo $biography; ?></textarea>
	</label>

	<label for="inputUsername">Benutzername: 
		<input type="text" id="inputUsername" name="username" value="<?php echo $username; ?>" maxlength="255">
	</label>

	<label for="inputPasswort">Passwort: 
		<input type="text" id="inputPasswort" name="passwort" value="<?php echo $passwort; ?>" maxlength="255">
	</label>

	<label for="inputRole">Rolle: 
		<select name="role" id="inputRole">
			<option value="adminstrator" <?php echo $role_adminstrator_select; ?>>Administrator</option>
			<option value="manager" <?php echo $role_manager_select; ?>>Manager</option>
			<option value="supporter" <?php echo $role_supporter_select; ?>>Supporter</option>
			<option value="member" <?php echo $role_member_select; ?>>Mitglied</option>
			<option value="user" <?php echo $role_user_select; ?>>Benutzer</option>
		</select>
	</label>

	<label for="inputGender">Geschlecht: 
		<select name="gender" id="inputGender">
			<option value="female" <?php echo $gender_femal_select; ?>>Weiblich</option>
			<option value="male" <?php echo $gender_male_select; ?>>Männlich</option>
			<option value="other" <?php echo $gender_other_select; ?>>Anders</option>
			<option value="noinformation" <?php echo $gender_noinformation_select; ?>>Keine Angabe</option>
		</select>
	</label>

	<label for="inputBirthday">Geburtsdatum: 
		<input type="date" id="inputBirthday" name="birthday" value="<?php echo $birthday; ?>">
	</label>
	
	<label for="inputProfilpicture">Profilbild: 
		Bitte lade ein Quadratisches Bild mit einer Auflösung von mindestens 600x600 Pixeln hoch. 
		<input type="file" id="inputProfilpicture" name="profilpicture" accept=".jpg">
	</label>
	<?php
		if (@$profilpicturecheck == 'none') {
	?>
	<label for="inputProfilpictureCheck">
		<input type="checkbox" id="inputProfilpictureCheck" name="profilpicturecheck" value="same" checked>
		Profilbild nicht löschen
	</label>
	<?php } else { ?>
		<input type="hidden" id="inputProfilpictureCheck" name="profilpicturecheck" value="same">
	<?php } ?>
	
	<input type="hidden" id="inputAktion" name="aktion" value="speichern">
	<?php
if ($modus_aendern != true) {
	?>
	<input type="hidden" id="inputAktion" name="aktion" value="speichern">
	<button type="submit">speichern</button>
</form>
	<?php		
	} else {
	?>
	<input type="hidden" id="inputId" name="id" value="<?php echo $id; ?>">
	<input type="hidden" id="inputAktion" name="aktion" value="korrigieren">
	<button type="submit">ändern</button>
</form>
	<?php } ?>
</form>
<?php } ?>
