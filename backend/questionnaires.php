<?php
	$show_only_user = 'min_manager';
	$title = 'Fragebögen';
	$description = 'Verwalte Fragebögen im Backend';
	$keywords = 'fragebogen, backend, verwaltung';

	$errors = array();
	$messages = array();
	$fieldErrors = array();
	$formData = array(
		'title' => '',
		'slug' => '',
		'status' => 'inactive',
		'intro_text' => ''
	);

	$wizardSteps = array(
		'base' => 'Basisdaten',
		'scale' => 'Skalenkonfiguration (global)',
		'items' => 'Item-Builder',
		'preview' => 'Vorschau (Frontend-Simulation)',
		'activation' => 'Aktivierung'
	);
	$wizardMinimumItems = 5;
	$allowedScaleTypes = array('likert', 'binary', 'custom');
	$wizardStep = isset($_GET['step']) ? trim((string)$_GET['step']) : 'base';
	if (!isset($wizardSteps[$wizardStep])) {
		$wizardStep = 'base';
	}
	$wizardQuestionnaireId = isset($_GET['wizard_id']) ? (int)$_GET['wizard_id'] : 0;
	$wizardQuestionnaire = null;
	$wizardProgress = array('current_step' => 'base', 'completed_steps' => array(), 'updated_at' => null);
	$wizardChecklist = array();
	$wizardGlobalScale = array('type' => 'likert', 'min' => 1, 'max' => 5);
	$wizardItems = array();

	function questionnaireDecodeRules($rulesJson)
	{
		$decoded = array();
		if ($rulesJson !== null && trim((string)$rulesJson) !== '') {
			$parsed = json_decode((string)$rulesJson, true);
			if (is_array($parsed)) {
				$decoded = $parsed;
			}
		}
		return $decoded;
	}

	function questionnaireEncodeRules(array $rules)
	{
		if (empty($rules)) {
			return null;
		}
		return json_encode($rules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	}

	function questionnaireMergeWizardProgress(array $existing, $currentStep, array $completedSteps)
	{
		$existingCompleted = array();
		if (isset($existing['completed_steps']) && is_array($existing['completed_steps'])) {
			$existingCompleted = $existing['completed_steps'];
		}
		$mergedCompleted = array_values(array_unique(array_merge($existingCompleted, $completedSteps)));
		return array(
			'current_step' => $currentStep,
			'completed_steps' => $mergedCompleted,
			'updated_at' => date('c')
		);
	}

	function questionnairePersistWizardProgress(PDO $pdo, $questionnaireId, $currentStep, array $completedSteps, array $wizardSteps, &$wizardProgress)
	{
		$loadStmt = $pdo->prepare('SELECT standard_rules_json FROM questionnaires WHERE id = :id LIMIT 1');
		$loadStmt->execute(array(':id' => $questionnaireId));
		$currentRulesJson = $loadStmt->fetchColumn();
		$currentRules = questionnaireDecodeRules($currentRulesJson);
		$filteredCompleted = array();
		foreach ($completedSteps as $stepKey) {
			if (isset($wizardSteps[$stepKey])) {
				$filteredCompleted[] = $stepKey;
			}
		}
		$currentRules['wizard_progress'] = questionnaireMergeWizardProgress(
			isset($currentRules['wizard_progress']) && is_array($currentRules['wizard_progress']) ? $currentRules['wizard_progress'] : array(),
			$currentStep,
			$filteredCompleted
		);

		$wizardProgress = $currentRules['wizard_progress'];
		$updateStmt = $pdo->prepare('UPDATE questionnaires SET standard_rules_json = :rules, updated_at = NOW() WHERE id = :id LIMIT 1');
		$updateStmt->execute(array(
			':rules' => questionnaireEncodeRules($currentRules),
			':id' => $questionnaireId
		));
	}

	function questionnaireRedirectToWizard($questionnaireId, $step)
	{
		$target = 'questionnaires.php?wizard_id='.(int)$questionnaireId.'&step='.rawurlencode((string)$step);
		if (!headers_sent()) {
			header('Location: '.$target);
			exit;
		}
		echo '<script>window.location.href='.json_encode($target).';</script>';
		echo '<noscript><meta http-equiv="refresh" content="0;url='.htmlentities($target).'" /></noscript>';
		exit;
	}

	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');

	$autoInstallNotice = '';
	if (isset($_SESSION['questionnaire_schema_autoinstall_notice'])) {
		$autoInstallNotice = (string)$_SESSION['questionnaire_schema_autoinstall_notice'];
		unset($_SESSION['questionnaire_schema_autoinstall_notice']);
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_questionnaire'])) {
		$formData['title'] = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
		$formData['slug'] = isset($_POST['slug']) ? trim((string)$_POST['slug']) : '';
		$formData['status'] = isset($_POST['status']) ? trim((string)$_POST['status']) : 'inactive';
		$formData['intro_text'] = isset($_POST['intro_text']) ? trim((string)$_POST['intro_text']) : '';

		$titleInput = $formData['title'];
		$slugInput = strtolower($formData['slug']);
		$slugInput = preg_replace('/[^a-z0-9\-]/', '-', $slugInput);
		$slugInput = trim((string)$slugInput, '-');
		$statusInput = in_array($formData['status'], array('active', 'inactive'), true) ? $formData['status'] : 'inactive';
		$introTextInput = $formData['intro_text'];

		$formData['slug'] = $slugInput;
		$formData['status'] = $statusInput;

		if ($titleInput === '' || mb_strlen($titleInput) > 255) {
			$fieldErrors['title'] = 'Titel ist erforderlich und darf maximal 255 Zeichen lang sein.';
			$errors[] = $fieldErrors['title'];
		}

		if ($slugInput === '' || mb_strlen($slugInput) > 255) {
			$fieldErrors['slug'] = 'Slug ist erforderlich und darf maximal 255 Zeichen lang sein.';
			$errors[] = $fieldErrors['slug'];
		}

		if (mb_strlen($introTextInput) > 1000) {
			$fieldErrors['intro_text'] = 'Die Intro-Text-Kurzfassung darf maximal 1000 Zeichen lang sein.';
			$errors[] = $fieldErrors['intro_text'];
		}

		if (empty($errors)) {
			$checkStmt = $pdo->prepare('SELECT id FROM questionnaires WHERE slug = :slug LIMIT 1');
			$checkStmt->execute(array(':slug' => $slugInput));
			if ($checkStmt->fetch()) {
				$suggestedSlug = '';
				for ($slugSuffix = 2; $slugSuffix <= 25; $slugSuffix++) {
					$candidateSlug = $slugInput.'-'.$slugSuffix;
					$candidateStmt = $pdo->prepare('SELECT id FROM questionnaires WHERE slug = :slug LIMIT 1');
					$candidateStmt->execute(array(':slug' => $candidateSlug));
					if (!$candidateStmt->fetch()) {
						$suggestedSlug = $candidateSlug;
						break;
					}
				}
				$fieldErrors['slug'] = 'Der Slug ist bereits vergeben.'.($suggestedSlug !== '' ? ' Vorschlag: "'.$suggestedSlug.'".' : '');
				$errors[] = $fieldErrors['slug'];
			}
		}

		if (empty($errors)) {
			$rules = array(
				'wizard_progress' => array(
					'current_step' => 'base',
					'completed_steps' => array(),
					'updated_at' => date('c')
				)
			);
			$insertStmt = $pdo->prepare('INSERT INTO questionnaires (slug, title, intro_text, standard_rules_json, status, created_at, updated_at) VALUES (:slug, :title, :intro_text, :standard_rules_json, :status, NOW(), NOW())');
			$insertStmt->execute(array(
				':slug' => $slugInput,
				':title' => $titleInput,
				':intro_text' => $introTextInput,
				':standard_rules_json' => questionnaireEncodeRules($rules),
				':status' => $statusInput
			));
			$newQuestionnaireId = (int)$pdo->lastInsertId();
			$_SESSION['questionnaire_wizard_notice'] = 'Fragebogen wurde erstellt. Du befindest dich jetzt im Wizard.';
			questionnaireRedirectToWizard($newQuestionnaireId, 'base');
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
		$questionnaireId = isset($_POST['questionnaire_id']) ? (int)$_POST['questionnaire_id'] : 0;
		$newStatus = isset($_POST['new_status']) ? trim((string)$_POST['new_status']) : '';
		$allowedStatus = array('active', 'inactive');

		if ($questionnaireId <= 0) {
			$errors[] = 'Ungültige Fragebogen-ID.';
		}
		if (!in_array($newStatus, $allowedStatus, true)) {
			$errors[] = 'Ungültiger Status.';
		}

		if (empty($errors)) {
			$updateStmt = $pdo->prepare('UPDATE questionnaires SET status = :status, updated_at = NOW() WHERE id = :id LIMIT 1');
			$updateStmt->execute(array(':status' => $newStatus, ':id' => $questionnaireId));
			if ($updateStmt->rowCount() > 0) {
				$messages[] = 'Status wurde aktualisiert.';
			} else {
				$errors[] = 'Fragebogen wurde nicht gefunden oder Status unverändert.';
			}
		}
	}

	if ($wizardQuestionnaireId > 0) {
		$loadWizardStmt = $pdo->prepare('SELECT id, slug, title, intro_text, status, standard_rules_json, created_at, updated_at FROM questionnaires WHERE id = :id LIMIT 1');
		$loadWizardStmt->execute(array(':id' => $wizardQuestionnaireId));
		$wizardQuestionnaire = $loadWizardStmt->fetch(PDO::FETCH_ASSOC);

		if (!$wizardQuestionnaire) {
			$errors[] = 'Wizard-Fragebogen wurde nicht gefunden.';
			$wizardQuestionnaireId = 0;
		} else {
			$rules = questionnaireDecodeRules($wizardQuestionnaire['standard_rules_json']);
			if (isset($rules['wizard_progress']) && is_array($rules['wizard_progress'])) {
				$wizardProgress = array_merge($wizardProgress, $rules['wizard_progress']);
			}
			if (isset($rules['global_scale']) && is_array($rules['global_scale'])) {
				$wizardGlobalScale = array_merge($wizardGlobalScale, $rules['global_scale']);
			}
			$_SESSION['questionnaire_wizard_progress_'.$wizardQuestionnaireId] = $wizardProgress;

			if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wizard_action'])) {
				$wizardAction = trim((string)$_POST['wizard_action']);
				$postedWizardId = isset($_POST['wizard_id']) ? (int)$_POST['wizard_id'] : 0;
				if ($postedWizardId !== $wizardQuestionnaireId) {
					$errors[] = 'Wizard-ID stimmt nicht überein.';
				} else {
					if ($wizardAction === 'save_base') {
						$baseTitle = isset($_POST['wizard_title']) ? trim((string)$_POST['wizard_title']) : '';
						$baseIntro = isset($_POST['wizard_intro_text']) ? trim((string)$_POST['wizard_intro_text']) : '';
						if ($baseTitle === '' || mb_strlen($baseTitle) > 255) {
							$errors[] = 'Titel ist erforderlich und darf maximal 255 Zeichen lang sein.';
						}
						if (mb_strlen($baseIntro) > 1000) {
							$errors[] = 'Intro-Text darf maximal 1000 Zeichen lang sein.';
						}
						if (empty($errors)) {
							$updateBaseStmt = $pdo->prepare('UPDATE questionnaires SET title = :title, intro_text = :intro_text, updated_at = NOW() WHERE id = :id LIMIT 1');
							$updateBaseStmt->execute(array(':title' => $baseTitle, ':intro_text' => $baseIntro, ':id' => $wizardQuestionnaireId));
							$wizardQuestionnaire['title'] = $baseTitle;
							$wizardQuestionnaire['intro_text'] = $baseIntro;
							questionnairePersistWizardProgress($pdo, $wizardQuestionnaireId, 'scale', array('base'), $wizardSteps, $wizardProgress);
							$messages[] = 'Basisdaten gespeichert.';
							$wizardStep = 'scale';
						}
					} elseif ($wizardAction === 'save_scale') {
						$scaleType = isset($_POST['global_scale_type']) ? trim((string)$_POST['global_scale_type']) : 'likert';
						$scaleMin = isset($_POST['global_scale_min']) ? (int)$_POST['global_scale_min'] : 1;
						$scaleMax = isset($_POST['global_scale_max']) ? (int)$_POST['global_scale_max'] : 5;
						if (!in_array($scaleType, $allowedScaleTypes, true)) {
							$errors[] = 'Ungültiger globaler Skalentyp.';
						}
						if ($scaleMin >= $scaleMax) {
							$errors[] = 'Globales Skalenminimum muss kleiner als Maximum sein.';
						}
						if ($scaleType === 'binary' && !($scaleMin === 0 && $scaleMax === 1)) {
							$errors[] = 'Für den Typ "binary" müssen Minimum und Maximum 0/1 sein.';
						}
						if (empty($errors)) {
							$currentRules = questionnaireDecodeRules($wizardQuestionnaire['standard_rules_json']);
							$currentRules['global_scale'] = array('type' => $scaleType, 'min' => $scaleMin, 'max' => $scaleMax);
							$saveScaleStmt = $pdo->prepare('UPDATE questionnaires SET standard_rules_json = :rules, updated_at = NOW() WHERE id = :id LIMIT 1');
							$saveScaleStmt->execute(array(':rules' => questionnaireEncodeRules($currentRules), ':id' => $wizardQuestionnaireId));
							$wizardQuestionnaire['standard_rules_json'] = questionnaireEncodeRules($currentRules);
							$wizardGlobalScale = $currentRules['global_scale'];
							questionnairePersistWizardProgress($pdo, $wizardQuestionnaireId, 'items', array('base', 'scale'), $wizardSteps, $wizardProgress);
							$messages[] = 'Skalenkonfiguration gespeichert.';
							$wizardStep = 'items';
						}
					} elseif ($wizardAction === 'mark_preview_done') {
						questionnairePersistWizardProgress($pdo, $wizardQuestionnaireId, 'activation', array('base', 'scale', 'items', 'preview'), $wizardSteps, $wizardProgress);
						$messages[] = 'Vorschau bestätigt.';
						$wizardStep = 'activation';
					} elseif ($wizardAction === 'activate') {
						$wizardStep = 'activation';
					}
				}
			}

			$itemStmt = $pdo->prepare('SELECT id, item_no, item_text, scale_type, likert_min, likert_max, is_reversed, is_required FROM questionnaire_items WHERE questionnaire_id = :id ORDER BY item_no ASC, id ASC');
			$itemStmt->execute(array(':id' => $wizardQuestionnaireId));
			$wizardItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

			$scaleValid = true;
			$itemNumbers = array();
			$hasDuplicateItemNo = false;
			foreach ($wizardItems as $itemRow) {
				$itemNo = (int)$itemRow['item_no'];
				if (isset($itemNumbers[$itemNo])) {
					$hasDuplicateItemNo = true;
				}
				$itemNumbers[$itemNo] = true;

				$scaleType = trim((string)$itemRow['scale_type']);
				$min = (int)$itemRow['likert_min'];
				$max = (int)$itemRow['likert_max'];
				if (!in_array($scaleType, $allowedScaleTypes, true) || $min >= $max) {
					$scaleValid = false;
				}
				if ($scaleType === 'binary' && !($min === 0 && $max === 1)) {
					$scaleValid = false;
				}
			}

			$wizardChecklist = array(
				'min_items' => array(
					'label' => 'Mindestens '.$wizardMinimumItems.' Items',
					'fulfilled' => count($wizardItems) >= $wizardMinimumItems,
					'detail' => count($wizardItems).' von '.$wizardMinimumItems.' vorhanden'
				),
				'valid_scale' => array(
					'label' => 'gültige Skala',
					'fulfilled' => $scaleValid,
					'detail' => $scaleValid ? 'Alle Item-Skalen sind gültig.' : 'Mindestens ein Item hat eine ungültige Skala.'
				),
				'unique_item_no' => array(
					'label' => 'keine doppelten Itemnummern',
					'fulfilled' => !$hasDuplicateItemNo,
					'detail' => !$hasDuplicateItemNo ? 'Alle Itemnummern sind eindeutig.' : 'Doppelte Itemnummern gefunden.'
				)
			);

			if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wizard_action']) && trim((string)$_POST['wizard_action']) === 'activate' && empty($errors)) {
				$allChecklistDone = true;
				foreach ($wizardChecklist as $check) {
					if (empty($check['fulfilled'])) {
						$allChecklistDone = false;
						break;
					}
				}
				if (!$allChecklistDone) {
					$errors[] = 'Aktivierung nicht möglich: Bitte alle Punkte der Validierungs-Checkliste erfüllen.';
				} else {
					$activateStmt = $pdo->prepare('UPDATE questionnaires SET status = :status, updated_at = NOW() WHERE id = :id LIMIT 1');
					$activateStmt->execute(array(':status' => 'active', ':id' => $wizardQuestionnaireId));
					$wizardQuestionnaire['status'] = 'active';
					questionnairePersistWizardProgress($pdo, $wizardQuestionnaireId, 'activation', array_keys($wizardSteps), $wizardSteps, $wizardProgress);
					$messages[] = 'Fragebogen wurde erfolgreich aktiviert.';
				}
			}
		}
	}

	if (isset($_SESSION['questionnaire_wizard_notice'])) {
		$messages[] = (string)$_SESSION['questionnaire_wizard_notice'];
		unset($_SESSION['questionnaire_wizard_notice']);
	}

	$listStmt = $pdo->prepare('SELECT q.id, q.slug, q.title, q.status, q.created_at, q.updated_at, q.standard_rules_json, COUNT(qi.id) AS item_count FROM questionnaires q LEFT JOIN questionnaire_items qi ON qi.questionnaire_id = q.id GROUP BY q.id, q.slug, q.title, q.status, q.created_at, q.updated_at, q.standard_rules_json ORDER BY q.updated_at DESC, q.id DESC');
	$listStmt->execute();
	$questionnaires = $listStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main class="qnr-layout qnr-layout--backend">
	<section class="qnr-card">
		<?php questionnaire_show_backend_overview('Fragebögen', 'Fragebögen erstellen, bearbeiten und aktivieren/deaktivieren.'); ?>
	</section>

	<?php if ($wizardQuestionnaireId > 0 && $wizardQuestionnaire) { ?>
	<section class="qnr-card" aria-labelledby="wizard-heading">
		<h1 id="wizard-heading">Wizard: <?php echo htmlentities((string)$wizardQuestionnaire['title']); ?> <small>(ID <?php echo (int)$wizardQuestionnaire['id']; ?>)</small></h1>
		<p><strong>Aktueller Schritt:</strong> <?php echo htmlentities($wizardSteps[$wizardStep]); ?></p>
		<ol>
			<?php foreach ($wizardSteps as $wizardStepKey => $wizardStepLabel) {
				$isDone = in_array($wizardStepKey, isset($wizardProgress['completed_steps']) && is_array($wizardProgress['completed_steps']) ? $wizardProgress['completed_steps'] : array(), true);
				$isCurrent = $wizardStepKey === $wizardStep;
			?>
				<li>
					<a href="questionnaires.php?wizard_id=<?php echo (int)$wizardQuestionnaire['id']; ?>&amp;step=<?php echo urlencode($wizardStepKey); ?>"><?php echo htmlentities($wizardStepLabel); ?></a>
					<?php echo $isDone ? '✅' : ($isCurrent ? '🟢' : '⚪'); ?>
				</li>
			<?php } ?>
		</ol>

		<?php if ($wizardStep === 'base') { ?>
			<form action="" method="post" class="qnr-form-row">
				<input type="hidden" name="wizard_action" value="save_base">
				<input type="hidden" name="wizard_id" value="<?php echo (int)$wizardQuestionnaire['id']; ?>">
				<div class="qnr-form-row">
					<label for="wizard_title">Titel</label>
					<input class="qnr-input" type="text" id="wizard_title" name="wizard_title" maxlength="255" required value="<?php echo htmlentities((string)$wizardQuestionnaire['title']); ?>">
				</div>
				<div class="qnr-form-row">
					<label for="wizard_intro_text">Intro-Text</label>
					<textarea class="qnr-input" id="wizard_intro_text" name="wizard_intro_text" rows="4" maxlength="1000"><?php echo htmlentities((string)$wizardQuestionnaire['intro_text']); ?></textarea>
				</div>
				<button class="qnr-btn qnr-focusable" type="submit">Weiter zu Skalenkonfiguration</button>
			</form>
		<?php } elseif ($wizardStep === 'scale') { ?>
			<form action="" method="post">
				<input type="hidden" name="wizard_action" value="save_scale">
				<input type="hidden" name="wizard_id" value="<?php echo (int)$wizardQuestionnaire['id']; ?>">
				<div class="qnr-form-row">
					<label for="global_scale_type">Globaler Skalentyp</label>
					<select class="qnr-input" id="global_scale_type" name="global_scale_type">
						<?php foreach ($allowedScaleTypes as $scaleOption) { ?>
							<option value="<?php echo htmlentities($scaleOption); ?>"<?php echo $wizardGlobalScale['type'] === $scaleOption ? ' selected' : ''; ?>><?php echo htmlentities($scaleOption); ?></option>
						<?php } ?>
					</select>
				</div>
				<div class="qnr-form-row">
					<label for="global_scale_min">Minimum</label>
					<input class="qnr-input" type="number" id="global_scale_min" name="global_scale_min" value="<?php echo (int)$wizardGlobalScale['min']; ?>">
				</div>
				<div class="qnr-form-row">
					<label for="global_scale_max">Maximum</label>
					<input class="qnr-input" type="number" id="global_scale_max" name="global_scale_max" value="<?php echo (int)$wizardGlobalScale['max']; ?>">
				</div>
				<button class="qnr-btn qnr-focusable" type="submit">Weiter zum Item-Builder</button>
			</form>
		<?php } elseif ($wizardStep === 'items') { ?>
			<p>Pflege deine Items im Item-Builder und kehre danach zum Wizard zurück.</p>
			<p><a href="questionnaire_items.php?id=<?php echo (int)$wizardQuestionnaire['id']; ?>">Item-Builder öffnen</a></p>
			<p>Aktuell vorhanden: <strong><?php echo count($wizardItems); ?></strong> Items</p>
			<p><a href="questionnaires.php?wizard_id=<?php echo (int)$wizardQuestionnaire['id']; ?>&amp;step=preview">Weiter zur Vorschau</a></p>
		<?php } elseif ($wizardStep === 'preview') { ?>
			<h3>Frontend-Simulation</h3>
			<p><strong>Titel:</strong> <?php echo htmlentities((string)$wizardQuestionnaire['title']); ?></p>
			<p><strong>Intro:</strong> <?php echo nl2br(htmlentities((string)$wizardQuestionnaire['intro_text'])); ?></p>
			<ul>
				<?php foreach (array_slice($wizardItems, 0, 8) as $previewItem) { ?>
					<li>
						<strong><?php echo (int)$previewItem['item_no']; ?>.</strong>
						<?php echo htmlentities((string)$previewItem['item_text']); ?>
						<small>(<?php echo htmlentities((string)$previewItem['scale_type']); ?> <?php echo (int)$previewItem['likert_min']; ?>-<?php echo (int)$previewItem['likert_max']; ?>)</small>
					</li>
				<?php } ?>
			</ul>
			<?php if (count($wizardItems) > 8) { ?>
				<p>… und <?php echo count($wizardItems) - 8; ?> weitere Items.</p>
			<?php } ?>
			<form action="" method="post">
				<input type="hidden" name="wizard_action" value="mark_preview_done">
				<input type="hidden" name="wizard_id" value="<?php echo (int)$wizardQuestionnaire['id']; ?>">
				<button class="qnr-btn qnr-focusable" type="submit">Vorschau bestätigen &amp; weiter</button>
			</form>
		<?php } elseif ($wizardStep === 'activation') { ?>
			<h3>Validierungs-Checkliste</h3>
			<ul>
				<?php foreach ($wizardChecklist as $check) { ?>
					<li>
						<?php echo !empty($check['fulfilled']) ? '✅' : '❌'; ?>
						<strong><?php echo htmlentities((string)$check['label']); ?></strong>
						<small>— <?php echo htmlentities((string)$check['detail']); ?></small>
					</li>
				<?php } ?>
			</ul>
			<form action="" method="post">
				<input type="hidden" name="wizard_action" value="activate">
				<input type="hidden" name="wizard_id" value="<?php echo (int)$wizardQuestionnaire['id']; ?>">
				<button class="qnr-btn qnr-focusable" type="submit"<?php
					$allChecksPassed = true;
					foreach ($wizardChecklist as $check) {
						if (empty($check['fulfilled'])) {
							$allChecksPassed = false;
							break;
						}
					}
					echo $allChecksPassed ? '' : ' disabled';
				?>>Fragebogen aktivieren</button>
			</form>
		<?php } ?>
	</section>
	<?php } ?>

	<section class="qnr-card" aria-labelledby="create-questionnaire-heading">
		<h1 id="create-questionnaire-heading">Neuen Fragebogen anlegen</h1>
		<form action="" method="post" id="questionnaire-create-form" novalidate>
			<section class="qnr-step" data-step="1" aria-labelledby="step-1-heading">
				<h2 id="step-1-heading">Schritt 1: Basisdaten</h2>
				<fieldset>
					<legend>Basisdaten erfassen</legend>
					<div class="qnr-form-row">
						<label for="title">Titel</label>
						<input class="qnr-input" type="text" name="title" id="title" maxlength="255" required aria-describedby="title-help title-error" value="<?php echo htmlentities($formData['title']); ?>">
						<small id="title-help">Maximal 255 Zeichen.</small>
						<p class="qnr-field-error" id="title-error" role="status" aria-live="polite"><?php echo isset($fieldErrors['title']) ? htmlentities($fieldErrors['title']) : ''; ?></p>
					</div>

					<div class="qnr-form-row">
						<label for="slug">Slug (URL-Schlüssel)</label>
						<input class="qnr-input" type="text" name="slug" id="slug" maxlength="255" pattern="[a-z0-9\-]+" required aria-describedby="slug-help slug-error" value="<?php echo htmlentities($formData['slug']); ?>">
						<small id="slug-help">Nur Kleinbuchstaben, Zahlen und Bindestriche.</small>
						<p class="qnr-field-error" id="slug-error" role="status" aria-live="polite"><?php echo isset($fieldErrors['slug']) ? htmlentities($fieldErrors['slug']) : ''; ?></p>
					</div>
				</fieldset>
			</section>

			<section class="qnr-step" data-step="2" aria-labelledby="step-2-heading">
				<h2 id="step-2-heading">Schritt 2: Startkonfiguration</h2>
				<fieldset>
					<legend>Initiale Einstellungen</legend>
					<div class="qnr-form-row">
						<label for="status">Status</label>
						<select class="qnr-input" name="status" id="status" aria-describedby="status-help">
							<option value="inactive"<?php echo $formData['status'] === 'inactive' ? ' selected' : ''; ?>>Inaktiv</option>
							<option value="active"<?php echo $formData['status'] === 'active' ? ' selected' : ''; ?>>Aktiv</option>
						</select>
						<small id="status-help">Standard ist Inaktiv.</small>
					</div>

					<div class="qnr-form-row">
						<label for="intro_text">Intro-Text-Kurzfassung</label>
						<textarea class="qnr-input" name="intro_text" id="intro_text" maxlength="1000" rows="4" aria-describedby="intro-help intro-error"><?php echo htmlentities($formData['intro_text']); ?></textarea>
						<small id="intro-help">Optional, maximal 1000 Zeichen.</small>
						<p class="qnr-field-error" id="intro-error" role="status" aria-live="polite"><?php echo isset($fieldErrors['intro_text']) ? htmlentities($fieldErrors['intro_text']) : ''; ?></p>
					</div>
				</fieldset>
			</section>

			<section class="qnr-step" data-step="3" aria-labelledby="step-3-heading">
				<h2 id="step-3-heading">Schritt 3: Review + Erstellen</h2>
				<fieldset>
					<legend>Überprüfung</legend>
					<div class="qnr-alert qnr-alert--info" id="create-review" aria-live="polite">
						Bitte Eingaben prüfen und anschließend erstellen.
					</div>
					<button class="qnr-btn qnr-focusable" type="submit" name="create_questionnaire" value="1" id="create-submit" disabled>Fragebogen erstellen</button>
				</fieldset>
			</section>
		</form>
	</section>

	<section class="qnr-card" aria-labelledby="feedback-heading">
		<h2 id="feedback-heading">Rückmeldungen</h2>
		<div id="feedback-alerts" tabindex="-1" aria-live="polite">
			<?php if ($autoInstallNotice === '' && empty($errors) && empty($messages)) { ?>
				<p class="qnr-alert qnr-alert--info">Keine Rückmeldungen.</p>
			<?php } ?>
			<?php if ($autoInstallNotice !== '') { ?>
				<p class="qnr-alert qnr-alert--info"><?php echo htmlentities($autoInstallNotice); ?></p>
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
		</div>
	</section>

	<section class="qnr-card">
		<h2>Vorhandene Fragebögen</h2>
		<div class="qnr-table-wrap">
		<table class="tablesorter qnr-table">
			<thead>
				<tr>
					<th>ID</th>
					<th>Slug</th>
					<th>Titel</th>
					<th>Status</th>
					<th>Items</th>
					<th>Aktualisiert</th>
					<th>Aktionen</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($questionnaires as $questionnaire) {
					$questionnaireRules = questionnaireDecodeRules(isset($questionnaire['standard_rules_json']) ? $questionnaire['standard_rules_json'] : null);
					$resumeStep = 'base';
					if (isset($questionnaireRules['wizard_progress']['current_step']) && isset($wizardSteps[$questionnaireRules['wizard_progress']['current_step']])) {
						$resumeStep = $questionnaireRules['wizard_progress']['current_step'];
					}
				?>
				<tr>
					<td><?php echo (int)$questionnaire['id']; ?></td>
					<td><?php echo htmlentities($questionnaire['slug']); ?></td>
					<td><?php echo htmlentities($questionnaire['title']); ?></td>
					<td><?php echo htmlentities($questionnaire['status']); ?></td>
					<td><?php echo (int)$questionnaire['item_count']; ?></td>
					<td><?php echo htmlentities((string)$questionnaire['updated_at']); ?></td>
					<td>
						<a href="questionnaire_edit.php?id=<?php echo (int)$questionnaire['id']; ?>">Bearbeiten</a> |
						<a href="questionnaire_items.php?id=<?php echo (int)$questionnaire['id']; ?>">Items</a> |
						<a href="questionnaires.php?wizard_id=<?php echo (int)$questionnaire['id']; ?>&amp;step=<?php echo urlencode($resumeStep); ?>">Wizard fortsetzen</a> |
						<a href="questionnaires.php?wizard_id=<?php echo (int)$questionnaire['id']; ?>&amp;step=preview">Vorschau öffnen</a> |
						<a href="questionnaire_edit.php?id=<?php echo (int)$questionnaire['id']; ?>#standard_rules_json">Auswertungsschema prüfen</a>
						<form action="" method="post" class="qnr-inline-form">
							<input type="hidden" name="questionnaire_id" value="<?php echo (int)$questionnaire['id']; ?>">
							<input type="hidden" name="new_status" value="<?php echo $questionnaire['status'] === 'active' ? 'inactive' : 'active'; ?>">
							<button class="qnr-btn qnr-btn--secondary qnr-focusable" type="submit" name="toggle_status" value="1"><?php echo $questionnaire['status'] === 'active' ? 'Inaktiv setzen' : 'Aktiv setzen'; ?></button>
						</form>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		</div>
	</section>
</main>
<script>
(function () {
	const form = document.getElementById('questionnaire-create-form');
	if (!form) {
		return;
	}

	const titleInput = document.getElementById('title');
	const slugInput = document.getElementById('slug');
	const introInput = document.getElementById('intro_text');
	const submitButton = document.getElementById('create-submit');
	const reviewBox = document.getElementById('create-review');
	const feedbackAlerts = document.getElementById('feedback-alerts');
	let slugTouched = slugInput.value.trim() !== '';

	const fieldConfig = [
		{input: titleInput, error: document.getElementById('title-error')},
		{input: slugInput, error: document.getElementById('slug-error')},
		{input: introInput, error: document.getElementById('intro-error')}
	];

	const slugify = function (value) {
		return value
			.toLowerCase()
			.replace(/[^a-z0-9-]+/g, '-')
			.replace(/-{2,}/g, '-')
			.replace(/^-+|-+$/g, '');
	};

	const setFieldError = function (input, errorEl) {
		if (!input || !errorEl) {
			return true;
		}
		let message = '';
		if (input.validity.valueMissing) {
			message = 'Dieses Feld ist erforderlich.';
		} else if (input.validity.patternMismatch) {
			message = 'Format ist ungültig.';
		} else if (input.validity.tooLong) {
			message = 'Maximale Zeichenlänge überschritten.';
		}
		errorEl.textContent = message;
		input.setAttribute('aria-invalid', message !== '' ? 'true' : 'false');
		return message === '';
	};

	const updateReview = function () {
		reviewBox.textContent = 'Titel: ' + (titleInput.value.trim() || '—') + ' | Slug: ' + (slugInput.value.trim() || '—');
	};

	const validateForm = function () {
		let valid = true;
		fieldConfig.forEach(function (field) {
			if (!setFieldError(field.input, field.error)) {
				valid = false;
			}
		});
		submitButton.disabled = !valid;
		updateReview();
	};

	titleInput.addEventListener('input', function () {
		if (!slugTouched) {
			slugInput.value = slugify(titleInput.value);
		}
		validateForm();
	});

	slugInput.addEventListener('input', function () {
		slugTouched = true;
		slugInput.value = slugify(slugInput.value);
		validateForm();
	});

	introInput.addEventListener('input', validateForm);
	form.addEventListener('input', validateForm);
	form.addEventListener('submit', function () {
		if (feedbackAlerts) {
			window.setTimeout(function () {
				feedbackAlerts.focus();
			}, 30);
		}
	});

	if (feedbackAlerts && feedbackAlerts.querySelector('.qnr-alert--error, .qnr-alert--success, .qnr-alert--info')) {
		feedbackAlerts.focus();
	}

	validateForm();
}());
</script>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); ?>
