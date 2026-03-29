<?php if ($showForm) { ?>
	<p>Gib hier deine E-Mail-Adresse ein, um ein neues Passwort anzufordern.</p>
	<form action="?send=1" method="post">
		<label for="inputEmail">E-Mail: 
			<input type="email" id="inputEmail" placeholder="E-Mail" name="email" value="<?php echo isset($_POST['email']) ? htmlentities($_POST['email']) : ''; ?>" minlength="6" maxlength="255" required>
		</label>
		<button type="submit">Neues Passwort</button>
	</form>
<?php } ?>
