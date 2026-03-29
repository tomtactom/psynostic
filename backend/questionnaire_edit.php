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
		$scaleTypeInput = isset($_POST['scale_type']) ? trim((string)$_POST['scale_type']) : 'likert';
		$likertMinInput = isset($_POST['likert_min']) ? (int)$_POST['likert_min'] : 1;
		$likertMaxInput = isset($_POST['likert_max']) ? (int)$_POST['likert_max'] : 5;
		$scaleLabelsInput = isset($_POST['scale_labels_json']) ? trim((string)$_POST['scale_labels_json']) : '';
		$rawMappingInput = isset($_POST['raw_mapping_json']) ? trim((string)$_POST['raw_mapping_json']) : '';
		$allowedScaleTypes = array('likert', 'binary', 'custom');

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
		if (!in_array($scaleTypeInput, $allowedScaleTypes, true)) {
			$errors[] = 'Ungültiger Skalentyp für den Fragebogen.';
		}
		if ($likertMinInput < -100 || $likertMaxInput > 100 || $likertMinInput >= $likertMaxInput) {
			$errors[] = 'Likert-Min/Max sind ungültig (erlaubt -100 bis 100, Min muss kleiner als Max sein).';
		}
		if ($scaleTypeInput === 'binary' && !($likertMinInput === 0 && $likertMaxInput === 1)) {
			$errors[] = 'Beim Skalentyp "binary" müssen Likert-Min/Max exakt 0 und 1 sein.';
		}
		if ($scaleLabelsInput !== '' && json_decode($scaleLabelsInput, true) === null && json_last_error() !== JSON_ERROR_NONE) {
			$errors[] = 'Skalen-Labels müssen valides JSON sein.';
		}
		if ($rawMappingInput !== '' && json_decode($rawMappingInput, true) === null && json_last_error() !== JSON_ERROR_NONE) {
			$errors[] = 'Rohwert-Mapping muss valides JSON sein.';
		}

		if (empty($errors)) {
			$updateStmt = $pdo->prepare('UPDATE questionnaires SET title = :title, intro_text = :intro_text, standard_rules_json = :standard_rules_json, scale_type = :scale_type, likert_min = :likert_min, likert_max = :likert_max, scale_labels_json = :scale_labels_json, raw_mapping_json = :raw_mapping_json, updated_at = NOW() WHERE id = :id LIMIT 1');
			$updateStmt->execute(array(
				':title' => $titleInput,
				':intro_text' => $introInput,
				':standard_rules_json' => $rulesInput === '' ? null : $rulesInput,
				':scale_type' => $scaleTypeInput,
				':likert_min' => $likertMinInput,
				':likert_max' => $likertMaxInput,
				':scale_labels_json' => $scaleLabelsInput === '' ? null : $scaleLabelsInput,
				':raw_mapping_json' => $rawMappingInput === '' ? null : $rawMappingInput,
				':id' => $questionnaireId
			));
			$messages[] = 'Stammdaten wurden gespeichert.';
		}
	}

	$loadStmt = $pdo->prepare('SELECT id, slug, title, intro_text, standard_rules_json, scale_type, likert_min, likert_max, scale_labels_json, raw_mapping_json, needs_manual_scale_cleanup, status, created_at, updated_at FROM questionnaires WHERE id = :id LIMIT 1');
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

			<h3>Skala für gesamten Fragebogen</h3>
			<div class="qnr-form-row">
				<label for="scale_type">Skalentyp</label>
				<select class="qnr-select" name="scale_type" id="scale_type" required>
					<?php foreach (array('likert', 'binary', 'custom') as $scaleTypeOption) { ?>
						<option value="<?php echo htmlentities($scaleTypeOption); ?>" <?php echo (string)$questionnaire['scale_type'] === $scaleTypeOption ? 'selected' : ''; ?>><?php echo htmlentities($scaleTypeOption); ?></option>
					<?php } ?>
				</select>
			</div>
			<div class="qnr-grid qnr-grid--2">
				<div class="qnr-form-row">
					<label for="likert_min">Likert-Min</label>
					<input class="qnr-input" type="number" name="likert_min" id="likert_min" value="<?php echo (int)$questionnaire['likert_min']; ?>" required>
				</div>
				<div class="qnr-form-row">
					<label for="likert_max">Likert-Max</label>
					<input class="qnr-input" type="number" name="likert_max" id="likert_max" value="<?php echo (int)$questionnaire['likert_max']; ?>" required>
				</div>
			</div>
			<div class="qnr-form-row">
				<label for="scale_labels_json">Skalen-Labels (JSON, optional)</label>
				<textarea class="qnr-textarea" name="scale_labels_json" id="scale_labels_json" rows="4"><?php echo htmlentities((string)$questionnaire['scale_labels_json']); ?></textarea>
			</div>
			<div class="qnr-form-row">
				<label for="raw_mapping_json">Rohwert-Mapping (JSON, optional)</label>
				<textarea class="qnr-textarea" name="raw_mapping_json" id="raw_mapping_json" rows="4"><?php echo htmlentities((string)$questionnaire['raw_mapping_json']); ?></textarea>
			</div>
			<?php if ((int)$questionnaire['needs_manual_scale_cleanup'] === 1) { ?>
				<p class="qnr-alert qnr-alert--error">Hinweis: Dieser Fragebogen hat inkonsistente Alt-Item-Skalen und ist für manuelle Bereinigung markiert.</p>
			<?php } ?>
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
