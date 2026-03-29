<form action="" method="post">
		<label for="inputSiteurl">Website URL: 
			<input type="url" id="inputSiteurl" name="siteurl" value="<?php echo $options['siteurl']; ?>" minlength="6" maxlength="255" required>
		</label>
		<label for="inputSitename">Website Name: 
			<input type="text" id="inputSitename" name="sitename" value="<?php echo $options['sitename']; ?>" maxlength="255" pattern="[a-zA-Z0-9 ]+" required>
		</label>
		<label for="inputSitedescription">Website Beschreibung: 
			<input type="text" id="inputSitedescription" name="sitedescription" value="<?php echo $options['sitedescription']; ?>" minlength="12" maxlength="255" required>
		</label>
		<label for="inputKeywordsmain">Hauptschlagwörter: 
			<input type="text" id="inputKeywordsmain" name="keywordsmain" value="<?php echo $options['keywordsmain']; ?>" minlength="3" maxlength="255" required>
		</label>
		<label for="inputAdminemail">Adminstrator E-Mail: 
			<input type="email" id="inputAdminemail" name="adminemail" value="<?php echo $options['adminemail']; ?>" minlength="6" maxlength="255" required>
		</label>
		<label for="inputCountry">Land: 
			<select id="inputCountry" name="country" required>
				<option <?php echo $country_germany_select ?> value="germany">Deutschland</option>
				<option <?php echo $country_austria_select ?> value="austria">Österreich</option>
				<option <?php echo $country_switzerland_select ?> value="switzerland">Schweiz</option>
				<option <?php echo $country_luxembourg_select ?> value="luxembourg">Luxemburg</option>
				<option <?php echo $country_lichtenstein_select ?> value="lichtenstein">Lichtenstein</option>
				<option <?php echo $country_unitedstates_select ?> value="unitedstates">Vereinigte Staaten</option>
				<option <?php echo $country_unitedkingdom_select ?> value="unitedkingdom">Vereinigtes Königreich</option>
				<option <?php echo $country_canada_select ?> value="canada">Kanada</option>
				<option <?php echo $country_australia_select ?> value="australia">Australien</option>
				<option <?php echo $country_newzealand_select ?> value="newzealand">Neuseeland</option>
				<option <?php echo $country_ireland_select ?> value="ireland">Irland</option>
			</select>
		</label>
		<label for="inputLanguage">Sprache: 
			<select id="inputLanguage" name="language" required>
				<option <?php echo $language_german_select ?> value="german">Deutsch</option>
				<option <?php echo $language_english_select ?> value="english">Englisch</option>
			</select>
		</label>
		<label for="inputRobots">Suchmaschienen Indexierung: 
			<select id="inputRobots" name="robots" required>
				<option <?php echo $robots_0_select; ?> value="0">In Suchmaschienen nicht Indexieren</option>
				<option <?php echo $robots_1_select; ?> value="1">In Suchmaschienen Indexieren</option>
			</select>
		</label>
		<label for="inputAllowregister">Registrierung im Frontend
			<select for="inputAllowregister" name="allowregister" required>
				<option <?php echo $allowregister_0_select; ?> value="0">Registrieren nicht erlauben</option>
				<option <?php echo $allowregister_1_select; ?> value="1">Registrieren erlauben</option>
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
									<option <?php if($options['backenddesign'] == $file_design){ echo 'selected'; } ?> value="<?php echo $file_design; ?>"><?php echo $file_design; ?></option>
				<?php
								}
							}
							closedir($handle_design);
						}
					}
				?>
				<option <?php if($options['backenddesign'] == 'none'){ echo 'selected'; } ?> value="none">Kein Design</option>
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
									<option <?php if($options['frontenddesign'] == $file_frontenddesign){ echo 'selected'; } ?> value="<?php echo $file_frontenddesign; ?>"><?php echo $file_frontenddesign; ?></option>
				<?php
								}
							}
							closedir($handle_frontenddesign);
						}
					}
				?>
				<option <?php if($options['frontenddesign'] == 'none'){ echo 'selected'; } ?> value="none">Kein Design</option>
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
									<option <?php if($options['font'] == $file_font){ echo 'selected'; } ?> value="<?php echo $file_font; ?>"><?php echo $file_font; ?></option>
				<?php
								}
							}
							closedir($handle_font);
						}
					}
				?>
				<option <?php if($options['font'] == 'none'){ echo 'selected'; } ?> value="none">Keine Schriftart</option>
			</select>
		</label>
	
		<label for="inputFontname">Schriftname: 
			<input type="text" id="inputFontname" name="fontname" value="<?php echo $options['fontname']; ?>" pattern="[a-zA-Z ]+" maxlength="32">
		</label>
	
		<label for="inputMaincolor">Hauptfarbe: 
			<input type="color" id="inputMaincolor" name="maincolor" value="<?php echo $options['maincolor']; ?>" maxlength="7" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" required>
		</label>
		
		<label for="inputMainfontcolor">Haupt Schriftfarbe: 
			<input type="color" id="inputMainfontcolor" name="mainfontcolor" value="<?php echo $options['mainfontcolor']; ?>" maxlength="7" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" required>
		</label>
		
		<label for="inputMainbackgroundcolor">Haupt Hintergrundfarbe: 
			<input type="color" id="inputMainbackgroundcolor" name="mainbackgroundcolor" value="<?php echo $options['mainbackgroundcolor']; ?>" maxlength="7" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" required>
		</label>
		
		<label for="inputMainhovercolor">Haupt hover Farbe: 
			<input type="color" id="inputMainhovercolor" name="mainhovercolor" value="<?php echo $options['mainhovercolor']; ?>" maxlength="7" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" required>
		</label>

		<label for="inputAuthor">Author Name/Copyright: 
			<input type="text" id="inputAuthor" name="author" value="<?php echo $options['author']; ?>" maxlength="255" pattern="[a-zA-Z0-9 ]+" required>
		</label>
		
		<label for="inputMindestalter">Mindestalter: 
			<input type="number" id="inputMindestalter" name="mindestalter" value="<?php echo $options['mindestalter']; ?>" pattern="/[0-9]$/" required>
		</label>
		
		<label for="inputMainrole">Standartrolle: 
			<select id="inputMainrole" name="mainrole" required>
				<option <?php echo $mainrole_user_select; ?> value="user">Benutzer</option>
				<option <?php echo $mainrole_member_select; ?> value="member">Mitglied</option>
				<?php if($user['role'] === 'adminstrator' || $setup == true) { ?>
					<option <?php echo $mainrole_supporter_select; ?> value="supporter">Supporter</option>
				<?php } ?>				
			</select>
		</label>
		
		<button type="submit" name="save_settings">Speichern</button>
	</form>
	Hochladen: <details>
	<form action="" method="post" enctype="multipart/form-data">
		<label for="inputZipbackend">Backenddesign hochladen: 
			<input type="file" id="inputZipbackend" name="zip" accept=".zip" required>
		</label>
		<button type="submit" name="backenddesignupload">Hochladen</button>
	</form>

	<form action="" method="post" enctype="multipart/form-data">
		<label for="inputZipfrontend">Frontenddesign hochladen: 
			<input type="file" id="inputZipfrontend" name="zip" accept=".zip" required>
		</label>
		<button type="submit" name="frontenddesignupload">Hochladen</button>
	</form>

	<form action="" method="post" enctype="multipart/form-data">
		<label for="inputFont">Schriftart hochladen: 
			<input type="file" id="inputFont" name="font" accept=".woff, .woff2, .otp, .ttf, .ps" required>
		</label>
		<button type="submit" name="uploadfont">Hochladen</button>
	</form>
	</details>
	
	Löschen: <details>
	<form action="" method="post">
		<label for="inputDeletefrontend">
			<select id="inputDeletefrontend" name="frontenddesign">
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
			</select>
		</label>
		<button type="submit" name="deletefrontend">Frontenddesign löschen</button>
	</form>

	<form action="" method="post">
		<label for="inputDeletebackend">
			<select id="inputDeletebackend" name="backenddesign">
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
			</select>
		</label>
		<button type="submit" name="deletebackend">Backenddesign löschen</button>
	</form>

	<form action="" method="post">
		<label for="inputDeletefont">
			<select id="inputDeletefont" name="font">
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
			</select>
		</label>
		<button type="submit" name="deletefont">Schriftart löschen</button>
	</form>
	</details>
