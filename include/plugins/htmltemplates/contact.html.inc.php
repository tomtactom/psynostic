<form method="post" id="contact">
	<label for="inputGender">Anrede 
		<select name="gender" id="inputGender">
			<option value="noinformation">Keine Angabe</option>
			<option value="male">Herr</option>
			<option value="female">Frau</option>
		</select>
	</label>
	<label for="inputFirstname">
		<input type="text" name="firstname" id="inputFirstname" placeholder="Vorname" minlenth="2" maxlength="64" pattern="[a-zA-ZÄäËëÏïÖöÜüŸÿẞßÀÁÂÃÅÆÇÈÉÊÌÍÎÐÑÒÓÔÕØŒÙÚÛÝÞàáâãåæçèéêìíîðñòóôõøœùúûýþŠšČč ]+" title="Bitte nur Buchstaben verwenden" required>
	</label>
	<label for="inputLastname">
		<input type="text" name="lastname" id="inputLastname" placeholder="Nachname" minlenth="2" maxlength="64" pattern="[a-zA-ZÄäËëÏïÖöÜüŸÿẞßÀÁÂÃÅÆÇÈÉÊÌÍÎÐÑÒÓÔÕØŒÙÚÛÝÞàáâãåæçèéêìíîðñòóôõøœùúûýþŠšČč ]+" title="Bitte nur Buchstaben verwenden" required>
	</label>
	<label for="inputEmail">
		<input type="email" name="email" id="inputEmail" placeholder="E-Mail-Adresse" maxlength="64" required>
	</label>
	<label for="inputMessage">
		<textarea type="text" name="message" id="inputMessage" placeholder="Nachricht" rows="5" autocomplete="off" maxlength="10000" required></textarea>
	</label>
	<label for="inputCategory">Kategorie 
		<select name="category" id="inputCategory" required>
			<option value="1">Allgemein</option>
			<option value="2">Werbung</option>
			<option value="3">Funktionen</option>
			<option value="4">Beschwerden/Rechtliches</option>
			<option value="5">Gruppe melden</option>
		</select>
	</label>
	<input type="checkbox" id="inputAccept" name="accept">
	<label for="inputAccept">Ich habe die <a href="<?php echo $options['siteurl']; ?>/privacy-policy" hreflang="de" title="lese die Datenschutzerklärung" rel="nofollow" target="_blank">Datenschutzerklärung</a> und die <a href="<?php echo $options['siteurl']; ?>/agbs" hreflang="de" title="lese die Allgemeinen Geschäftsbedingungen" rel="nofollow" target="_blank">AGBs</a> gelesen</label><br><br>
	<div class="g-recaptcha" data-callback="capcha_filled" data-expired-callback="capcha_expired" data-sitekey="6LfWXm0UAAAAAKbRLqMVasA8lPm40aS6RCL-qTLi"></div>
	<button type="submit">Nachricht senden</button>
</form>
<script>
	document.getElementById("contact").addEventListener("submit", function(e) {
		if (!allowSubmit) {
			e.preventDefault();
		}
	});
</script>
