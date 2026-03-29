	<article>
		<section>
			<h3>Die ersten Einstellungen</h3>
			<?php get('error'); ?>
			<form method="post">
			<?php
				if (!isset($goToNextStep)) {
			?>
			<style>
				label {
					display: block;
				}
			</style>
				<label for="inputSiteurl">Website URL:
					<input type="url" id="inputSiteurl" name="siteurl" minlength="12" maxlength="255" value="<?php echo str_replace("/setup/", "", $_SERVER['HTTP_REFERER']); ?>" required>
				</label>
				<label for="inputSitename">Website Name:
					<input type="text" id="inputSitename" name="sitename" maxlength="255" required>
				</label>
				<label for="inputSitedescription">Website Beschreibung:
					<input type="text" id="inputSitedescription" name="sitedescription" minlength="12" maxlength="255" required>
				</label>
				<label for="inputKeywordsmain">Hauptschlagwörter:
					<input type="text" id="inputKeywordsmain" name="keywordsmain" minlength="3" maxlength="255" required>
				</label>
				<a href="https://www.google.com/recaptcha" target="_blank" rel="external">google.com/recaptcha</a>
				<label for="inputRecaptcha_sitekey">Websiteschlüssel Google reCAPTCHA:
					<input type="text" id="inputRecaptcha_sitekey" name="recaptcha_sitekey" minlength="3" maxlength="255" required>
				</label>
				<label for="inputRecaptcha_secretkey">Geheimer Schlüssel Google reCAPTCHA:
					<input type="text" id="inputRecaptcha_secretkey" name="recaptcha_secretkey" minlength="3" maxlength="255" required>
				</label>
				<label for="inputAdminemail">Adminstrator E-Mail:
					<input type="email" id="inputAdminemail" name="adminemail" minlength="6" maxlength="255" required>
				</label>
				<label for="inputCountry">Land:
					<select id="inputCountry" name="country" required>
						<option value="germany">Deutschland</option>
						<option value="austria">Österreich</option>
						<option value="switzerland">Schweiz</option>
						<option value="luxembourg">Luxemburg</option>
						<option value="lichtenstein">Lichtenstein</option>
						<option value="unitedstates">Vereinigte Staaten</option>
						<option value="unitedkingdom">Vereinigtes Königreich</option>
						<option value="canada">Kanada</option>
						<option value="australia">Australien</option>
						<option value="newzealand">Neuseeland</option>
						<option value="ireland">Irland</option>
					</select>
				</label>
				<label for="inputLanguage">Sprache:
					<select id="inputLanguage" name="language" required>
						<option value="german">Deutsch</option>
						<option value="english">Englisch</option>
					</select>
				</label>
				<label for="inputRobots">Suchmaschienen Indexierung:
					<select id="inputRobots" name="robots" required>
						<option value="0">In Suchmaschienen nicht Indexieren</option>
						<option value="1" selected>In Suchmaschienen Indexieren</option>
					</select>
				</label>
				<label for="inputAllowregister">Registrierung im Frontend
					<select for="inputAllowregister" name="allowregister" required>
						<option value="0">Registrieren nicht erlauben</option>
						<option value="1">Registrieren erlauben</option>
					</select>
				</label>

				<label for="inputBackenddesign">Backend Design:
					<select id="inputBackenddesign" name="backenddesign" required>
						<?php
							$verzeichnis_design = "../backend/design/";
							if ( is_dir ( $verzeichnis_design )) {
								if ( $handle_design = opendir($verzeichnis_design) ) {
									while (($file_design = readdir($handle_design)) !== false) {
										if ($file_design !== '.' and $file_design !== '..' and $file_design !== 'none') {
						?>
											<option value="<?php echo $file_design; ?>"><?php echo $file_design; ?></option>
						<?php
										}
									}
									closedir($handle_design);
								}
							}
						?>
						<option value="none">Kein Design</option>
					</select>
				</label>
				<label for="inputFrontenddesign">Frontend Design:
					<select id="inputFrontenddesign" name="frontenddesign" required>
						<?php
							$verzeichnis_frontenddesign = "../website/design/";
							if ( is_dir ( $verzeichnis_frontenddesign )) {
								if ( $handle_frontenddesign = opendir($verzeichnis_frontenddesign) ) {
									while (($file_frontenddesign = readdir($handle_frontenddesign)) !== false) {
										if ($file_frontenddesign !== '.' and $file_frontenddesign !== '..' and $file_frontenddesign !== 'none') {
						?>
											<option value="<?php echo $file_frontenddesign; ?>"><?php echo $file_frontenddesign; ?></option>
						<?php
										}
									}
									closedir($handle_frontenddesign);
								}
							}
						?>
						<option value="none">Kein Design</option>
					</select>
				</label>

				<label for="inputFont">Schriftart:
					<select id="inputFont" name="font" required>
						<?php
							$verzeichnis_font = "../include/database/fonts/";
							if ( is_dir ( $verzeichnis_font )) {
								if ( $handle_font = opendir($verzeichnis_font) ) {
									while (($file_font = readdir($handle_font)) !== false) {
										if ($file_font !== '.' and $file_font !== '..') {
						?>
											<option value="<?php echo $file_font; ?>"><?php echo $file_font; ?></option>
						<?php
										}
									}
									closedir($handle_font);
								}
							}
						?>
						<option value="none">Keine Schriftart</option>
					</select>
				</label>

				<label for="inputFontname">Schriftname:
					<input type="text" id="inputFontname" name="fontname" maxlength="32">
				</label>

				<label for="inputMaincolor">Hauptfarbe:
					<input type="color" id="inputMaincolor" name="maincolor" maxlength="7" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" value="#009688" required>
				</label>

				<label for="inputMainfontcolor">Haupt Schriftfarbe:
					<input type="color" id="inputMainfontcolor" name="mainfontcolor" maxlength="7" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" value="#0f0f0f" required>
				</label>

				<label for="inputMainbackgroundcolor">Haupt Hintergrundfarbe:
					<input type="color" id="inputMainbackgroundcolor" name="mainbackgroundcolor" maxlength="7" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" value="#ffffff" required>
				</label>

				<label for="inputMainhovercolor">Haupt hover Farbe:
					<input type="color" id="inputMainhovercolor" name="mainhovercolor" maxlength="7" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" value="#00ad9d" required>
				</label>

				<label for="inputAuthor">Author Name/Copyright:
					<input type="text" id="inputAuthor" name="author" maxlength="255"  required>
				</label>

				<label for="inputMindestalter">Mindestalter:
					<input type="number" id="inputMindestalter" name="mindestalter" pattern="/[0-9]$/" value="16" required>
				</label>

				<label for="inputMainrole">Standartrolle:
					<select id="inputMainrole" name="mainrole" required>
						<option value="user">Benutzer</option>
						<option value="member">Mitglied</option>
						<option value="supporter">Supporter</option>
					</select>
				</label>
			<hr>
		<?php } if (isset($goToNextStep)) { ?>
					<div class="success">Die Einstellungen wurden erfolgreich gespeichert</div>
					<a href="index.php">Abbrechen</a>
					<input type="hidden" name="nextStep" value="done">
					<button type="submit">Weiter</button>
				<?php } else { ?>
					<a href="index.php">Abbrechen</a>
					<input type="hidden" name="nextStep" value="websitesettings">
					<button type="submit" name="websitesettings">Einstellungen Speichern</button>
				<?php } ?>
			</form>
		</section>
	</article>
