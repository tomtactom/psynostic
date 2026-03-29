<?php
	$show_only_user = 'min_manager';
	$title = 'Fragebogen bearbeiten';
	$description = 'Stammdaten eines Fragebogens bearbeiten';
	$keywords = 'fragebogen, bearbeiten, backend';
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');

	$questionnaireId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$errors = array();
	$messages = array();

	if ($questionnaireId <= 0) {
		die('<p>Ungültige Fragebogen-ID.</p>');
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_questionnaire'])) {
		$titleInput = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
		$introInput = isset($_POST['intro_text']) ? trim((string)$_POST['intro_text']) : '';
		$rulesInput = isset($_POST['standard_rules_json']) ? trim((string)$_POST['standard_rules_json']) : '';

		if ($titleInput === '' || mb_strlen($titleInput) > 255) {
			$errors[] = 'Titel ist erforderlich und darf maximal 255 Zeichen lang sein.';
		}

		if (mb_strlen($introInput) > 20000) {
			$errors[] = 'Intro darf maximal 20.000 Zeichen enthalten.';
		}

		if ($rulesInput !== '') {
			json_decode($rulesInput, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$errors[] = 'Standardregeln müssen valides JSON sein.';
			}
		}

		if (empty($errors)) {
			$updateStmt = $pdo->prepare('UPDATE questionnaires SET title = :title, intro_text = :intro_text, standard_rules_json = :standard_rules_json, updated_at = NOW() WHERE id = :id LIMIT 1');
			$updateStmt->execute(array(
				':title' => $titleInput,
				':intro_text' => $introInput,
				':standard_rules_json' => $rulesInput === '' ? null : $rulesInput,
				':id' => $questionnaireId
			));
			$messages[] = 'Stammdaten wurden gespeichert.';
		}
	}

	$loadStmt = $pdo->prepare('SELECT id, slug, title, intro_text, standard_rules_json, status, created_at, updated_at FROM questionnaires WHERE id = :id LIMIT 1');
	$loadStmt->execute(array(':id' => $questionnaireId));
	$questionnaire = $loadStmt->fetch(PDO::FETCH_ASSOC);

	if (!$questionnaire) {
		die('<p>Fragebogen nicht gefunden.</p>');
	}
?>
<article>
	<section>
		<h1>Fragebogen bearbeiten</h1>
		<p><a href="questionnaires.php">&laquo; Zurück zur Fragebogenliste</a></p>
		<p>
			<strong>ID:</strong> <?php echo (int)$questionnaire['id']; ?> |
			<strong>Slug:</strong> <?php echo htmlentities($questionnaire['slug']); ?> |
			<strong>Status:</strong> <?php echo htmlentities($questionnaire['status']); ?>
		</p>
	</section>

	<section>
		<h2>Stammdaten</h2>
		<form action="" method="post">
			<label for="title">Titel</label><br>
			<input type="text" name="title" id="title" maxlength="255" value="<?php echo htmlentities((string)$questionnaire['title']); ?>" required><br><br>

			<label for="intro_text">Intro</label><br>
			<textarea name="intro_text" id="intro_text" rows="6" cols="80" maxlength="20000"><?php echo htmlentities((string)$questionnaire['intro_text']); ?></textarea><br><br>

			<label for="standard_rules_json">Standardregeln (JSON)</label><br>
			<textarea name="standard_rules_json" id="standard_rules_json" rows="10" cols="80"><?php echo htmlentities((string)$questionnaire['standard_rules_json']); ?></textarea><br><br>

			<button type="submit" name="save_questionnaire" value="1">Speichern</button>
		</form>
	</section>

	<section>
		<h2>Rückmeldungen</h2>
		<?php if (empty($errors) && empty($messages)) { ?>
			<p>Keine Rückmeldungen.</p>
		<?php } ?>
		<?php if (!empty($errors)) { ?>
			<ul>
				<?php foreach ($errors as $errorMessage) { ?>
					<li><?php echo htmlentities($errorMessage); ?></li>
				<?php } ?>
			</ul>
		<?php } ?>
		<?php if (!empty($messages)) { ?>
			<ul>
				<?php foreach ($messages as $message) { ?>
					<li><?php echo htmlentities($message); ?></li>
				<?php } ?>
			</ul>
		<?php } ?>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); ?>
