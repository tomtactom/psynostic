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

	function renderAlerts(array $errors, array $messages)
	{
		if (empty($errors) && empty($messages)) {
			echo '<p>Keine Rückmeldungen.</p>';
			return;
		}

		if (!empty($errors)) {
			echo '<div style="border:1px solid #b30000;background:#ffe9e9;padding:10px;margin-bottom:12px;">';
			echo '<strong>Fehler:</strong>';
			echo '<ul style="margin:6px 0 0 18px;">';
			foreach ($errors as $errorMessage) {
				echo '<li>'.htmlentities($errorMessage).'</li>';
			}
			echo '</ul>';
			echo '</div>';
		}

		if (!empty($messages)) {
			echo '<div style="border:1px solid #0d6e2e;background:#ebffef;padding:10px;margin-bottom:12px;">';
			echo '<strong>Hinweise:</strong>';
			echo '<ul style="margin:6px 0 0 18px;">';
			foreach ($messages as $message) {
				echo '<li>'.htmlentities($message).'</li>';
			}
			echo '</ul>';
			echo '</div>';
		}
	}

	function validateItemInput(array $input, array $allowedScaleTypes, &$itemData)
	{
		$validationErrors = array();

		$itemData = array(
			'item_id' => isset($input['item_id']) ? (int)$input['item_id'] : 0,
			'item_no' => isset($input['item_no']) ? (int)$input['item_no'] : 0,
			'item_text' => isset($input['item_text']) ? trim((string)$input['item_text']) : '',
			'scale_type' => isset($input['scale_type']) ? trim((string)$input['scale_type']) : '',
			'likert_min' => isset($input['likert_min']) ? (int)$input['likert_min'] : 0,
			'likert_max' => isset($input['likert_max']) ? (int)$input['likert_max'] : 0,
			'is_reversed' => isset($input['is_reversed']) && (string)$input['is_reversed'] === '1' ? 1 : 0,
			'subscale_key' => isset($input['subscale_key']) ? trim((string)$input['subscale_key']) : '',
			'is_required' => isset($input['is_required']) && (string)$input['is_required'] === '1' ? 1 : 0,
		);

		if ($itemData['item_no'] <= 0) {
			$validationErrors[] = 'Item-Nummer muss größer als 0 sein.';
		}
		if ($itemData['item_text'] === '' || mb_strlen($itemData['item_text']) > 20000) {
			$validationErrors[] = 'Itemtext ist erforderlich und darf maximal 20.000 Zeichen lang sein.';
		}
		if (!in_array($itemData['scale_type'], $allowedScaleTypes, true)) {
			$validationErrors[] = 'Ungültiger Skalentyp.';
		}
		if ($itemData['likert_min'] < -100 || $itemData['likert_max'] > 100 || $itemData['likert_min'] >= $itemData['likert_max']) {
			$validationErrors[] = 'Likert-Min/Max sind ungültig (erlaubt -100 bis 100, Min muss kleiner als Max sein).';
		}
		if ($itemData['scale_type'] === 'binary' && !($itemData['likert_min'] === 0 && $itemData['likert_max'] === 1)) {
			$validationErrors[] = 'Beim Skalentyp "binary" müssen Likert-Min/Max exakt 0 und 1 sein.';
		}
		if ($itemData['subscale_key'] !== '' && !preg_match('/^[a-zA-Z0-9_\-]{1,100}$/', $itemData['subscale_key'])) {
			$validationErrors[] = 'Subskalen-Key darf nur Buchstaben, Zahlen, Unterstriche und Bindestriche enthalten (max. 100 Zeichen).';
		}

		return $validationErrors;
	}

	if ($questionnaireId <= 0) {
		die('<p>Ungültige Fragebogen-ID.</p>');
	}

	$questionnaireStmt = $pdo->prepare('SELECT id, title, slug FROM questionnaires WHERE id = :id LIMIT 1');
	$questionnaireStmt->execute(array(':id' => $questionnaireId));
	$questionnaire = $questionnaireStmt->fetch(PDO::FETCH_ASSOC);
	if (!$questionnaire) {
		die('<p>Fragebogen nicht gefunden.</p>');
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
		$bulkAction = isset($_POST['bulk_action_type']) ? trim((string)$_POST['bulk_action_type']) : '';
		$rawIds = isset($_POST['selected_item_ids']) && is_array($_POST['selected_item_ids']) ? $_POST['selected_item_ids'] : array();
		$itemIds = array_values(array_unique(array_filter(array_map('intval', $rawIds), function ($value) {
			return $value > 0;
		})));

		if (empty($itemIds)) {
			$errors[] = 'Für die Sammelaktion müssen mindestens ein Item ausgewählt werden.';
		} else {
			$placeholders = implode(',', array_fill(0, count($itemIds), '?'));
			$params = array_merge(array($questionnaireId), $itemIds);
			$checkSql = 'SELECT id FROM questionnaire_items WHERE questionnaire_id = ? AND id IN ('.$placeholders.')';
			$checkStmt = $pdo->prepare($checkSql);
			$checkStmt->execute($params);
			$existingIds = $checkStmt->fetchAll(PDO::FETCH_COLUMN, 0);
			if (count($existingIds) !== count($itemIds)) {
				$errors[] = 'Mindestens ein ausgewähltes Item ist ungültig.';
			}
		}

		if (empty($errors)) {
			if ($bulkAction === 'mark_required' || $bulkAction === 'mark_optional') {
				$isRequired = $bulkAction === 'mark_required' ? 1 : 0;
				$placeholders = implode(',', array_fill(0, count($itemIds), '?'));
				$params = array_merge(array($isRequired, $questionnaireId), $itemIds);
				$bulkStmt = $pdo->prepare('UPDATE questionnaire_items SET is_required = ? WHERE questionnaire_id = ? AND id IN ('.$placeholders.')');
				$bulkStmt->execute($params);
				$messages[] = 'Sammelaktion ausgeführt: Pflichtstatus wurde aktualisiert.';
			} elseif ($bulkAction === 'set_subscale' || $bulkAction === 'clear_subscale') {
				$subscaleKey = $bulkAction === 'clear_subscale' ? '' : (isset($_POST['bulk_subscale_key']) ? trim((string)$_POST['bulk_subscale_key']) : '');
				if ($bulkAction === 'set_subscale' && $subscaleKey === '') {
					$errors[] = 'Für diese Sammelaktion muss ein Subskalen-Key angegeben werden.';
				}
				if ($subscaleKey !== '' && !preg_match('/^[a-zA-Z0-9_\-]{1,100}$/', $subscaleKey)) {
					$errors[] = 'Subskalen-Key darf nur Buchstaben, Zahlen, Unterstriche und Bindestriche enthalten (max. 100 Zeichen).';
				}
				if (empty($errors)) {
					$placeholders = implode(',', array_fill(0, count($itemIds), '?'));
					$params = array_merge(array($subscaleKey === '' ? null : $subscaleKey, $questionnaireId), $itemIds);
					$bulkStmt = $pdo->prepare('UPDATE questionnaire_items SET subscale_key = ? WHERE questionnaire_id = ? AND id IN ('.$placeholders.')');
					$bulkStmt->execute($params);
					$messages[] = 'Sammelaktion ausgeführt: Subskalen-Key wurde aktualisiert.';
				}
			} else {
				$errors[] = 'Ungültige Sammelaktion.';
			}
		}
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
		$itemData = array();
		$validationErrors = validateItemInput($_POST, $allowedScaleTypes, $itemData);
		$errors = array_merge($errors, $validationErrors);

		if (empty($validationErrors)) {
			$dupStmt = $pdo->prepare('SELECT id FROM questionnaire_items WHERE questionnaire_id = :questionnaire_id AND item_no = :item_no AND id != :id LIMIT 1');
			$dupStmt->execute(array(
				':questionnaire_id' => $questionnaireId,
				':item_no' => $itemData['item_no'],
				':id' => $itemData['item_id']
			));
			if ($dupStmt->fetch()) {
				$errors[] = 'Die Item-Nummer ist bereits vergeben.';
			}
		}

		if (empty($errors)) {
			if ($itemData['item_id'] > 0) {
				$updateStmt = $pdo->prepare('UPDATE questionnaire_items SET item_no = :item_no, item_text = :item_text, scale_type = :scale_type, likert_min = :likert_min, likert_max = :likert_max, is_reversed = :is_reversed, subscale_key = :subscale_key, is_required = :is_required WHERE id = :id AND questionnaire_id = :questionnaire_id LIMIT 1');
				$updateStmt->execute(array(
					':item_no' => $itemData['item_no'],
					':item_text' => $itemData['item_text'],
					':scale_type' => $itemData['scale_type'],
					':likert_min' => $itemData['likert_min'],
					':likert_max' => $itemData['likert_max'],
					':is_reversed' => $itemData['is_reversed'],
					':subscale_key' => $itemData['subscale_key'] === '' ? null : $itemData['subscale_key'],
					':is_required' => $itemData['is_required'],
					':id' => $itemData['item_id'],
					':questionnaire_id' => $questionnaireId
				));
				$messages[] = 'Item wurde aktualisiert.';
			} else {
				$insertStmt = $pdo->prepare('INSERT INTO questionnaire_items (questionnaire_id, item_no, item_text, scale_type, likert_min, likert_max, is_reversed, subscale_key, is_required) VALUES (:questionnaire_id, :item_no, :item_text, :scale_type, :likert_min, :likert_max, :is_reversed, :subscale_key, :is_required)');
				$insertStmt->execute(array(
					':questionnaire_id' => $questionnaireId,
					':item_no' => $itemData['item_no'],
					':item_text' => $itemData['item_text'],
					':scale_type' => $itemData['scale_type'],
					':likert_min' => $itemData['likert_min'],
					':likert_max' => $itemData['likert_max'],
					':is_reversed' => $itemData['is_reversed'],
					':subscale_key' => $itemData['subscale_key'] === '' ? null : $itemData['subscale_key'],
					':is_required' => $itemData['is_required']
				));
				$messages[] = 'Item wurde angelegt.';
			}
		}
	}

	$filterItemNo = isset($_GET['filter_item_no']) ? trim((string)$_GET['filter_item_no']) : '';
	$filterSubscale = isset($_GET['filter_subscale_key']) ? trim((string)$_GET['filter_subscale_key']) : '';
	$filterRequired = isset($_GET['filter_required']) ? trim((string)$_GET['filter_required']) : 'all';
	if (!in_array($filterRequired, array('all', '1', '0'), true)) {
		$filterRequired = 'all';
	}

	$sql = 'SELECT id, item_no, item_text, scale_type, likert_min, likert_max, is_reversed, subscale_key, is_required FROM questionnaire_items WHERE questionnaire_id = :questionnaire_id';
	$params = array(':questionnaire_id' => $questionnaireId);

	if ($filterItemNo !== '' && ctype_digit($filterItemNo)) {
		$sql .= ' AND item_no = :filter_item_no';
		$params[':filter_item_no'] = (int)$filterItemNo;
	}
	if ($filterSubscale !== '') {
		$sql .= ' AND subscale_key LIKE :filter_subscale_key';
		$params[':filter_subscale_key'] = '%'.$filterSubscale.'%';
	}
	if ($filterRequired === '1' || $filterRequired === '0') {
		$sql .= ' AND is_required = :filter_required';
		$params[':filter_required'] = (int)$filterRequired;
	}

	$sql .= ' ORDER BY item_no ASC, id ASC';
	$itemsStmt = $pdo->prepare($sql);
	$itemsStmt->execute($params);
	$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

	$editItemId = isset($_GET['edit_item_id']) ? (int)$_GET['edit_item_id'] : 0;
	$editItem = null;
	if ($editItemId > 0) {
		$editStmt = $pdo->prepare('SELECT id, item_no, item_text, scale_type, likert_min, likert_max, is_reversed, subscale_key, is_required FROM questionnaire_items WHERE id = :id AND questionnaire_id = :questionnaire_id LIMIT 1');
		$editStmt->execute(array(':id' => $editItemId, ':questionnaire_id' => $questionnaireId));
		$editItem = $editStmt->fetch(PDO::FETCH_ASSOC);
		if (!$editItem) {
			$errors[] = 'Das zu bearbeitende Item wurde nicht gefunden.';
		}
	}
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
