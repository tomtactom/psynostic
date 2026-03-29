	<details>
		<style>
			details {
				cursor: pointer;
				margin-bottom: 15px;
			}
			summary.question::-webkit-details-marker {
				display: none;
			}
			summary.question:before {
				content: "❔";
			}
			summary.question {
				-ms-user-select: none; 
				-moz-user-select: none; 
				-webkit-user-select: none;
			}
		</style>
		<summary class="question">Stelle eine Frage...</summary>
		<h3>Stelle eine Frage...</h3>
		<form method="post" id="newquestion" style="height:22em;">
				<label for="inputQ">
					<input type="text" name="q" id="inputQ" placeholder="Stelle eine Frage" minlength="3" maxlength="64" pattern="[a-zA-ZÄäÖöÜüẞß?,.\- ]+" title="Bitte nur Buchstaben und Satzzeichen verwenden" required>
				</label>
				<label for="inputEmail">
					<input type="email" name="email" id="inputEmail" placeholder="E-Mail (optional)" minlength="8" maxlength="64">
				</label>
				<input type="checkbox" id="inputAccept" name="accept" required>
				<label for="inputAccept">Ich habe die <a href="./privacy-policy" hreflang="<?php echo $options['short_language']; ?>" title="lese die Datenschutzerklärung" rel="nofollow" target="_blank">Datenschutzerklärung</a> und die <a href="./agbs" hreflang="<?php echo $options['short_language']; ?>" title="lese die Allgemeinen Geschäftsbedingungen" rel="nofollow" target="_blank">AGBs</a> gelesen</label><br><br>
				<button type="submit">Frage stellen</button>
		</form>
		<script>
			document.getElementById("newquestion").addEventListener("submit", function(e) {
				if (!allowSubmit) {
					e.preventDefault();
				}
			});
		</script>
		<br>
		<hr>
		<br>
	</details>
	<form method="get" class="single" id="search">
		<input type="search" name="s" placeholder="Suche nach häufig gestellten Fragen" minlength="3" maxlength="64" required>
		<button type="submit"></button>
	</form>
	<main>
	<?php
		if(isset($_GET['s']) && !empty($_GET['s'])) {
			$statement = $pdo->prepare("SELECT DISTINCT * FROM `faq` WHERE LOWER(`question`) LIKE ? OR LOWER(`answer`) LIKE ? ORDER BY `id` DESC");
			$result = $statement->execute(array("%".strtolower($_GET['s'])."%","%".strtolower($_GET['s'])."%"));
		} else {
			$statement = $pdo->prepare("SELECT * FROM `faq` ORDER BY `id` DESC");
			$result = $statement->execute();
		}
		while($row = $statement->fetch()) {
			if($row['answer'] == true) {
			?>
				<div id="groupfaq<?php echo $row['id']; ?>" style="width: 100%; height: unset;">
					<h2><?php echo $row['question']; ?></h2>
					<hr>
					<p><?php echo $row['answer']; ?></p>
				</div>
			<?php
			}
		}
	?>
	</main>
	