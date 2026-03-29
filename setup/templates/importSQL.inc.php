	<article>
		<section>
			<h3>SQL Importieren</h3>

			<p>Es werden Daten zur Datenbank hinzugefügt</p>
			<hr>

			<?php 
				if (isset($errors)) { 
			?>
				<p>
					Beim Import der SQL-Daten sind einige Fehler aufgetreten!
				</p>
				<ul>
					<?php foreach ($errors as $error): ?>
						<li><?php echo $error; ?></li>
					<?php endforeach; ?>
				</ul>
			<?php 
				} else { 
					$goToNextStep = true;
			?>
				<div class="success">Daten erfolgreich importiert!</div>
				
			<?php } ?>
			<hr>
			<a href="index.php">Abbrechen</a>
			<?php if ($goToNextStep) { ?>
				<form method="post">
					<input type="hidden" name="nextStep" value="settings">
					<button type="submit">Weiter</button>
				</form>
			<?php } else { ?>
				<form method="post">
					<input type="hidden" name="nextStep" value="importSQL">
					<button type="submit">Nochmal versuchen</button>
				</form>
			<?php } ?>
		</section>
	</article>
