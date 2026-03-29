<?php
	$show_only_user = 'min_manager';
	$title = 'Fragebogen-Items';
	$description = 'Items eines Fragebogens verwalten';
	$keywords = 'fragebogen, items, backend';
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');

	$questionnaireId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$errors = array();
	$messages = array();
	$allowedScaleTypes = array('likert', 'binary', 'custom');

	if ($questionnaireId <= 0) {
		die('<p>Ungültige Fragebogen-ID.</p>');
	}

	$questionnaireStmt = $pdo->prepare('SELECT id, title, slug FROM questionnaires WHERE id = :id LIMIT 1');
	$questionnaireStmt->execute(array(':id' => $questionnaireId));
	$questionnaire = $questionnaireStmt->fetch(PDO::FETCH_ASSOC);
	if (!$questionnaire) {
		die('<p>Fragebogen nicht gefunden.</p>');
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item'])) {
		$itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
		if ($itemId <= 0) {
			$errors[] = 'Ungültige Item-ID.';
		} else {
			$deleteStmt = $pdo->prepare('DELETE FROM questionnaire_items WHERE id = :item_id AND questionnaire_id = :questionnaire_id LIMIT 1');
			$deleteStmt->execute(array(':item_id' => $itemId, ':questionnaire_id' => $questionnaireId));
			if ($deleteStmt->rowCount() > 0) {
				$messages[] = 'Item wurde gelöscht.';
			} else {
				$errors[] = 'Item konnte nicht gelöscht werden.';
			}
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_item'])) {
		$itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
		$itemNo = isset($_POST['item_no']) ? (int)$_POST['item_no'] : 0;
		$itemText = isset($_POST['item_text']) ? trim((string)$_POST['item_text']) : '';
		$scaleType = isset($_POST['scale_type']) ? trim((string)$_POST['scale_type']) : '';
		$likertMin = isset($_POST['likert_min']) ? (int)$_POST['likert_min'] : 0;
		$likertMax = isset($_POST['likert_max']) ? (int)$_POST['likert_max'] : 0;
		$isReversed = isset($_POST['is_reversed']) && (string)$_POST['is_reversed'] === '1' ? 1 : 0;
		$subscaleKey = isset($_POST['subscale_key']) ? trim((string)$_POST['subscale_key']) : '';
		$isRequired = isset($_POST['is_required']) && (string)$_POST['is_required'] === '1' ? 1 : 0;

		if ($itemNo <= 0) {
			$errors[] = 'Item-Nummer muss größer als 0 sein.';
		}
		if ($itemText === '' || mb_strlen($itemText) > 20000) {
			$errors[] = 'Itemtext ist erforderlich und darf maximal 20.000 Zeichen lang sein.';
		}
		if (!in_array($scaleType, $allowedScaleTypes, true)) {
			$errors[] = 'Ungültiger Skalentyp.';
		}
		if ($likertMin < -100 || $likertMax > 100 || $likertMin >= $likertMax) {
			$errors[] = 'Likert-Min/Max sind ungültig (erlaubt -100 bis 100, Min muss kleiner als Max sein).';
		}
		if ($scaleType === 'binary' && !($likertMin === 0 && $likertMax === 1)) {
			$errors[] = 'Beim Skalentyp "binary" müssen Likert-Min/Max exakt 0 und 1 sein.';
		}
		if ($subscaleKey !== '' && (!preg_match('/^[a-zA-Z0-9_\-]{1,100}$/', $subscaleKey))) {
			$errors[] = 'Subskalen-Key darf nur Buchstaben, Zahlen, Unterstriche und Bindestriche enthalten (max. 100 Zeichen).';
		}

		if (empty($errors)) {
			$dupStmt = $pdo->prepare('SELECT id FROM questionnaire_items WHERE questionnaire_id = :questionnaire_id AND item_no = :item_no AND id != :id LIMIT 1');
			$dupStmt->execute(array(
				':questionnaire_id' => $questionnaireId,
				':item_no' => $itemNo,
				':id' => $itemId
			));
			if ($dupStmt->fetch()) {
				$errors[] = 'Die Item-Nummer ist bereits vergeben.';
			}
		}

		if (empty($errors)) {
			if ($itemId > 0) {
				$updateStmt = $pdo->prepare('UPDATE questionnaire_items SET item_no = :item_no, item_text = :item_text, scale_type = :scale_type, likert_min = :likert_min, likert_max = :likert_max, is_reversed = :is_reversed, subscale_key = :subscale_key, is_required = :is_required WHERE id = :id AND questionnaire_id = :questionnaire_id LIMIT 1');
				$updateStmt->execute(array(
					':item_no' => $itemNo,
					':item_text' => $itemText,
					':scale_type' => $scaleType,
					':likert_min' => $likertMin,
					':likert_max' => $likertMax,
					':is_reversed' => $isReversed,
					':subscale_key' => $subscaleKey === '' ? null : $subscaleKey,
					':is_required' => $isRequired,
					':id' => $itemId,
					':questionnaire_id' => $questionnaireId
				));
				$messages[] = 'Item wurde aktualisiert.';
			} else {
				$insertStmt = $pdo->prepare('INSERT INTO questionnaire_items (questionnaire_id, item_no, item_text, scale_type, likert_min, likert_max, is_reversed, subscale_key, is_required) VALUES (:questionnaire_id, :item_no, :item_text, :scale_type, :likert_min, :likert_max, :is_reversed, :subscale_key, :is_required)');
				$insertStmt->execute(array(
					':questionnaire_id' => $questionnaireId,
					':item_no' => $itemNo,
					':item_text' => $itemText,
					':scale_type' => $scaleType,
					':likert_min' => $likertMin,
					':likert_max' => $likertMax,
					':is_reversed' => $isReversed,
					':subscale_key' => $subscaleKey === '' ? null : $subscaleKey,
					':is_required' => $isRequired
				));
				$messages[] = 'Item wurde angelegt.';
			}
		}
	}

	$itemsStmt = $pdo->prepare('SELECT id, item_no, item_text, scale_type, likert_min, likert_max, is_reversed, subscale_key, is_required FROM questionnaire_items WHERE questionnaire_id = :questionnaire_id ORDER BY item_no ASC, id ASC');
	$itemsStmt->execute(array(':questionnaire_id' => $questionnaireId));
	$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<article class="qnr-layout qnr-layout--backend">
	<section class="qnr-card">
		<h1>Item-Verwaltung</h1>
		<p><a href="questionnaires.php">&laquo; Zurück zur Fragebogenliste</a> | <a href="questionnaire_edit.php?id=<?php echo (int)$questionnaireId; ?>">Stammdaten bearbeiten</a></p>
		<p><strong>Fragebogen:</strong> <?php echo htmlentities($questionnaire['title']); ?> (<?php echo htmlentities($questionnaire['slug']); ?>)</p>
	</section>

	<section class="qnr-card">
		<h2>Neues Item</h2>
		<form action="" method="post">
			<input type="hidden" name="item_id" value="0">
			<div class="qnr-grid qnr-grid--2">
			<div class="qnr-form-row">
				<label>Item-Nr.</label>
				<input class="qnr-input" type="number" name="item_no" min="1" required>
			</div>

			<div class="qnr-form-row">
				<label>Skalentyp</label>
				<select class="qnr-select" name="scale_type" required>
				<option value="likert">likert</option>
				<option value="binary">binary</option>
				<option value="custom">custom</option>
				</select>
			</div>

			<div class="qnr-form-row">
				<label>Likert-Min</label>
				<input class="qnr-input" type="number" name="likert_min" required>
			</div>

			<div class="qnr-form-row">
				<label>Likert-Max</label>
				<input class="qnr-input" type="number" name="likert_max" required>
			</div>
			</div>

			<div class="qnr-form-row">
				<label>Itemtext</label>
				<textarea class="qnr-textarea" name="item_text" rows="4" required></textarea>
			</div>

			<div class="qnr-form-row">
				<label>Subskalen-Key</label>
				<input class="qnr-input" type="text" name="subscale_key" maxlength="100">
			</div>

			<div class="qnr-form-row-inline">
				<label for="new_reverse">Reverse</label>
				<input id="new_reverse" type="checkbox" name="is_reversed" value="1">
			</div>

			<div class="qnr-form-row-inline">
				<label for="new_required">Pflichtfeld</label>
				<input id="new_required" type="checkbox" name="is_required" value="1" checked>
			</div>

			<button class="qnr-btn qnr-focusable" type="submit" name="save_item" value="1">Item speichern</button>
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

	<section class="qnr-card">
		<h2>Vorhandene Items</h2>
		<?php foreach ($items as $item) { ?>
			<form action="" method="post" class="qnr-card">
				<input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
				<div class="qnr-grid qnr-grid--2">
				<div class="qnr-form-row">
					<label>Item-Nr.</label>
					<input class="qnr-input" type="number" name="item_no" min="1" value="<?php echo (int)$item['item_no']; ?>" required>
				</div>

				<div class="qnr-form-row">
					<label>Skalentyp</label>
					<select class="qnr-select" name="scale_type" required>
					<?php foreach ($allowedScaleTypes as $scaleTypeOption) { ?>
						<option value="<?php echo htmlentities($scaleTypeOption); ?>" <?php echo $item['scale_type'] === $scaleTypeOption ? 'selected' : ''; ?>><?php echo htmlentities($scaleTypeOption); ?></option>
					<?php } ?>
					</select>
				</div>

				<div class="qnr-form-row">
					<label>Likert-Min</label>
					<input class="qnr-input" type="number" name="likert_min" value="<?php echo (int)$item['likert_min']; ?>" required>
				</div>

				<div class="qnr-form-row">
					<label>Likert-Max</label>
					<input class="qnr-input" type="number" name="likert_max" value="<?php echo (int)$item['likert_max']; ?>" required>
				</div>
				</div>

				<div class="qnr-form-row">
					<label>Itemtext</label>
					<textarea class="qnr-textarea" name="item_text" rows="4" required><?php echo htmlentities((string)$item['item_text']); ?></textarea>
				</div>

				<div class="qnr-form-row">
					<label>Subskalen-Key</label>
					<input class="qnr-input" type="text" name="subscale_key" maxlength="100" value="<?php echo htmlentities((string)$item['subscale_key']); ?>">
				</div>

				<div class="qnr-form-row-inline">
					<label for="reverse_<?php echo (int)$item['id']; ?>">Reverse</label>
					<input id="reverse_<?php echo (int)$item['id']; ?>" type="checkbox" name="is_reversed" value="1" <?php echo (int)$item['is_reversed'] === 1 ? 'checked' : ''; ?>>
				</div>

				<div class="qnr-form-row-inline">
					<label for="required_<?php echo (int)$item['id']; ?>">Pflichtfeld</label>
					<input id="required_<?php echo (int)$item['id']; ?>" type="checkbox" name="is_required" value="1" <?php echo (int)$item['is_required'] === 1 ? 'checked' : ''; ?>>
				</div>

				<button class="qnr-btn qnr-focusable" type="submit" name="save_item" value="1">Änderungen speichern</button>
				<button class="qnr-btn qnr-btn--secondary qnr-focusable" type="submit" name="delete_item" value="1" onclick="return confirm('Item wirklich löschen?');">Löschen</button>
			</form>
		<?php } ?>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); ?>
