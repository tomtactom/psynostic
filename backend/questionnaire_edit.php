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
			$rulesDecoded = json_decode($rulesInput, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$errors[] = 'Standardregeln müssen valides JSON sein.';
			} elseif (isset($rulesDecoded['quality_parameters']) && !is_array($rulesDecoded['quality_parameters'])) {
				$errors[] = 'quality_parameters muss ein JSON-Objekt sein.';
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
<article class="qnr-layout qnr-layout--backend">
	<section class="qnr-card">
		<h1>Fragebogen bearbeiten</h1>
		<p><a href="questionnaires.php">&laquo; Zurück zur Fragebogenliste</a></p>
		<p>
			<strong>ID:</strong> <?php echo (int)$questionnaire['id']; ?> |
			<strong>Slug:</strong> <?php echo htmlentities($questionnaire['slug']); ?> |
			<strong>Status:</strong> <?php echo htmlentities($questionnaire['status']); ?>
		</p>
	</section>

	<section class="qnr-card">
		<h2>Stammdaten</h2>
		<form action="" method="post">
			<div class="qnr-form-row">
				<label for="title">Titel</label>
				<input class="qnr-input" type="text" name="title" id="title" maxlength="255" value="<?php echo htmlentities((string)$questionnaire['title']); ?>" required>
			</div>

			<div class="qnr-form-row">
				<label for="intro_text">Intro</label>
				<textarea class="qnr-textarea" name="intro_text" id="intro_text" rows="6" maxlength="20000"><?php echo htmlentities((string)$questionnaire['intro_text']); ?></textarea>
			</div>

			<div class="qnr-form-row">
				<label for="standard_rules_json">Standardregeln (JSON)</label>
				<textarea class="qnr-textarea" name="standard_rules_json" id="standard_rules_json" rows="10"><?php echo htmlentities((string)$questionnaire['standard_rules_json']); ?></textarea>
			</div>
			<p><strong>Beispiel für Qualitätsparameter:</strong></p>
			<pre class="qnr-pre-wrap">{
  "quality_parameters": {
    "total_minimum_answered_ratio": 0.8,
    "subscale_minimum_answered_ratio": { "depression": 0.75 },
    "speeding": { "min_seconds": 120, "max_seconds": 2400 },
    "inconsistency_pairs": [
      { "item_no_left": 3, "item_no_right": 9, "max_abs_diff": 2 }
    ],
    "reliability": { "enabled": true }
  }
}</pre>

			<button class="qnr-btn qnr-focusable" type="submit" name="save_questionnaire" value="1">Speichern</button>
		</form>
	</section>

	<section class="qnr-card">
		<h2>Rückmeldungen</h2>
		<?php if (empty($errors) && empty($messages)) { ?>
			<p>Keine Rückmeldungen.</p>
		<?php } ?>
		<?php if (!empty($errors)) { ?>
			<ul class="qnr-alert qnr-alert--error">
				<?php foreach ($errors as $errorMessage) { ?>
					<li><?php echo htmlentities($errorMessage); ?></li>
				<?php } ?>
			</ul>
		<?php } ?>
		<?php if (!empty($messages)) { ?>
			<ul class="qnr-alert qnr-alert--success">
				<?php foreach ($messages as $message) { ?>
					<li><?php echo htmlentities($message); ?></li>
				<?php } ?>
			</ul>
		<?php } ?>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); ?>
