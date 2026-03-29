<?php
	$show_only_user = 'min_manager';
	$title = 'Fragebögen – Übersicht';
	$description = 'Übersicht und Verwaltung der Fragebögen im Backend';
	$keywords = 'fragebogen, backend, verwaltung';
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');

	$errors = array();
	$messages = array();
	$fieldErrors = array();
	$formData = array(
		'title' => '',
		'slug' => '',
		'status' => 'inactive',
		'intro_text' => ''
	);

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
			$insertStmt = $pdo->prepare('INSERT INTO questionnaires (slug, title, intro_text, standard_rules_json, status, created_at, updated_at) VALUES (:slug, :title, :intro_text, :standard_rules_json, :status, NOW(), NOW())');
			$insertStmt->execute(array(
				':slug' => $slugInput,
				':title' => $titleInput,
				':intro_text' => $introTextInput,
				':standard_rules_json' => null,
				':status' => $statusInput
			));
			$messages[] = 'Fragebogen wurde erstellt.';
			$formData = array(
				'title' => '',
				'slug' => '',
				'status' => 'inactive',
				'intro_text' => ''
			);
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

	$listStmt = $pdo->prepare('SELECT q.id, q.slug, q.title, q.status, q.created_at, q.updated_at, COUNT(qi.id) AS item_count FROM questionnaires q LEFT JOIN questionnaire_items qi ON qi.questionnaire_id = q.id GROUP BY q.id, q.slug, q.title, q.status, q.created_at, q.updated_at ORDER BY q.updated_at DESC, q.id DESC');
	$listStmt->execute();
	$questionnaires = $listStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main class="qnr-layout qnr-layout--backend">
	<section class="qnr-card">
		<?php questionnaire_show_backend_overview('Fragebögen – Übersicht', 'Fragebögen erstellen, bearbeiten und aktivieren/deaktivieren.'); ?>
	</section>

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
				<?php foreach ($questionnaires as $questionnaire) { ?>
				<tr>
					<td><?php echo (int)$questionnaire['id']; ?></td>
					<td><?php echo htmlentities($questionnaire['slug']); ?></td>
					<td><?php echo htmlentities($questionnaire['title']); ?></td>
					<td><?php echo htmlentities($questionnaire['status']); ?></td>
					<td><?php echo (int)$questionnaire['item_count']; ?></td>
					<td><?php echo htmlentities((string)$questionnaire['updated_at']); ?></td>
					<td>
						<a href="questionnaire_edit.php?id=<?php echo (int)$questionnaire['id']; ?>">Bearbeiten</a> |
						<a href="questionnaire_items.php?id=<?php echo (int)$questionnaire['id']; ?>">Items</a>
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
