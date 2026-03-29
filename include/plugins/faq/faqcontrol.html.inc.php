<?php
	if ($modus_aendern) {
?>
<form method="post" enctype="multipart/form-data">
	<label for="inputQuestion">
		<input type="text" name="question" id="inputQuestion" placeholder="Frage..." autocomplete="off" maxlength="64" title="Bitte verwende möglichst nur Buchstaben, Zahlen und Satzzeichen für die Frage" required value="<?php echo $question; ?>">
	</label>
	<label for="inputEmail">
		<input type="email" name="email" id="inputEmail" placeholder="E-Mail (optional)" maxlength="64" value="<?php echo $email; ?>">
	</label>
	<label for="inputAnswer">
		<textarea type="text" name="answer" id="inputAnswer" placeholder="Ausfürliche Antwort auf die gestellte Frage" colls="5" autocomplete="off" title="Bitte verwende möglichst nur Buchstaben, Zahlen und Satzzeichen für die Antwort" maxlength="10000"><?php echo $answer; ?></textarea>
	</label>
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
