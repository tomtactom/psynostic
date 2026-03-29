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
<article>
	<section>
		<h1>Item-Verwaltung</h1>
		<p><a href="questionnaires.php">&laquo; Zurück zur Fragebogenliste</a> | <a href="questionnaire_edit.php?id=<?php echo (int)$questionnaireId; ?>">Stammdaten bearbeiten</a></p>
		<p><strong>Fragebogen:</strong> <?php echo htmlentities($questionnaire['title']); ?> (<?php echo htmlentities($questionnaire['slug']); ?>)</p>
	</section>

	<section>
		<h2>Rückmeldungen</h2>
		<?php renderAlerts($errors, $messages); ?>
	</section>

	<section>
		<h2>Suche / Filter</h2>
		<form method="get" action="">
			<input type="hidden" name="id" value="<?php echo (int)$questionnaireId; ?>">
			<label>Item-Nr.</label>
			<input type="number" min="1" name="filter_item_no" value="<?php echo htmlentities($filterItemNo); ?>">
			<label>Subskalen-Key</label>
			<input type="text" maxlength="100" name="filter_subscale_key" value="<?php echo htmlentities($filterSubscale); ?>">
			<label>Pflichtstatus</label>
			<select name="filter_required">
				<option value="all" <?php echo $filterRequired === 'all' ? 'selected' : ''; ?>>alle</option>
				<option value="1" <?php echo $filterRequired === '1' ? 'selected' : ''; ?>>nur Pflicht</option>
				<option value="0" <?php echo $filterRequired === '0' ? 'selected' : ''; ?>>nur optional</option>
			</select>
			<button type="submit">Filter anwenden</button>
			<a href="questionnaire_items.php?id=<?php echo (int)$questionnaireId; ?>">Zurücksetzen</a>
		</form>
	</section>

	<section>
		<h2>Vorhandene Items</h2>
		<form action="" method="post" onsubmit="return confirm('Sammelaktion wirklich für die ausgewählten Items ausführen?');">
			<table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse;">
				<thead>
					<tr>
						<th><input type="checkbox" onclick="var c=document.querySelectorAll('.bulk-item-select'); for (var i=0;i<c.length;i++){c[i].checked=this.checked;}"></th>
						<th>Nr</th>
						<th>Text</th>
						<th>Skala</th>
						<th>Pflicht</th>
						<th>Subskala</th>
						<th>Aktionen</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($items)) { ?>
						<tr>
							<td colspan="7">Keine Items gefunden.</td>
						</tr>
					<?php } ?>
					<?php foreach ($items as $item) { ?>
						<tr>
							<td><input class="bulk-item-select" type="checkbox" name="selected_item_ids[]" value="<?php echo (int)$item['id']; ?>"></td>
							<td><?php echo (int)$item['item_no']; ?></td>
							<td><?php echo nl2br(htmlentities((string)$item['item_text'])); ?></td>
							<td>
								<?php echo htmlentities((string)$item['scale_type']); ?>
								(<?php echo (int)$item['likert_min']; ?> bis <?php echo (int)$item['likert_max']; ?>)
								<?php echo (int)$item['is_reversed'] === 1 ? ' · reverse' : ''; ?>
							</td>
							<td><?php echo (int)$item['is_required'] === 1 ? 'Ja' : 'Nein'; ?></td>
							<td><?php echo $item['subscale_key'] === null || $item['subscale_key'] === '' ? '-' : htmlentities((string)$item['subscale_key']); ?></td>
							<td>
								<a href="questionnaire_items.php?id=<?php echo (int)$questionnaireId; ?>&edit_item_id=<?php echo (int)$item['id']; ?>">Bearbeiten</a>
								|
								<button type="submit" form="delete-item-<?php echo (int)$item['id']; ?>">Löschen</button>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>

			<div style="margin-top:12px; border:1px solid #ccc; padding:10px;">
				<strong>Sammelaktionen</strong><br><br>
				<label>Aktion</label>
				<select name="bulk_action_type" required>
					<option value="mark_required">Als Pflicht markieren</option>
					<option value="mark_optional">Als optional markieren</option>
					<option value="set_subscale">Subskalen-Key setzen</option>
					<option value="clear_subscale">Subskalen-Key leeren</option>
				</select>
				<label>Subskalen-Key (für „setzen“)</label>
				<input type="text" name="bulk_subscale_key" maxlength="100">
				<button type="submit" name="bulk_action" value="1">Für Auswahl ausführen</button>
			</div>
		</form>

		<?php foreach ($items as $item) { ?>
			<form id="delete-item-<?php echo (int)$item['id']; ?>" action="" method="post" onsubmit="return confirm('Item wirklich löschen?');" style="display:none;">
				<input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
				<input type="hidden" name="delete_item" value="1">
			</form>
		<?php } ?>
	</section>

	<section>
		<h2><?php echo $editItem ? 'Item bearbeiten' : 'Neues Item'; ?></h2>
		<form action="" method="post">
			<input type="hidden" name="item_id" value="<?php echo $editItem ? (int)$editItem['id'] : 0; ?>">
			<label>Item-Nr.</label><br>
			<input type="number" name="item_no" min="1" value="<?php echo $editItem ? (int)$editItem['item_no'] : ''; ?>" required><br><br>

			<label>Itemtext</label><br>
			<textarea name="item_text" rows="4" cols="80" required><?php echo $editItem ? htmlentities((string)$editItem['item_text']) : ''; ?></textarea><br><br>

			<label>Skalentyp</label><br>
			<select name="scale_type" required>
				<?php foreach ($allowedScaleTypes as $scaleTypeOption) { ?>
					<option value="<?php echo htmlentities($scaleTypeOption); ?>" <?php echo $editItem && $editItem['scale_type'] === $scaleTypeOption ? 'selected' : ''; ?>><?php echo htmlentities($scaleTypeOption); ?></option>
				<?php } ?>
			</select><br><br>

			<label>Likert-Min</label><br>
			<input type="number" name="likert_min" value="<?php echo $editItem ? (int)$editItem['likert_min'] : '1'; ?>" required><br><br>

			<label>Likert-Max</label><br>
			<input type="number" name="likert_max" value="<?php echo $editItem ? (int)$editItem['likert_max'] : '5'; ?>" required><br><br>

			<label>Reverse</label>
			<input type="checkbox" name="is_reversed" value="1" <?php echo $editItem && (int)$editItem['is_reversed'] === 1 ? 'checked' : ''; ?>><br><br>

			<label>Subskalen-Key</label><br>
			<input type="text" name="subscale_key" maxlength="100" value="<?php echo $editItem ? htmlentities((string)$editItem['subscale_key']) : ''; ?>"><br><br>

			<label>Pflichtfeld</label>
			<input type="checkbox" name="is_required" value="1" <?php echo (!$editItem || (int)$editItem['is_required'] === 1) ? 'checked' : ''; ?>><br><br>

			<button type="submit" name="save_item" value="1"><?php echo $editItem ? 'Änderungen speichern' : 'Item anlegen'; ?></button>
			<?php if ($editItem) { ?>
				<a href="questionnaire_items.php?id=<?php echo (int)$questionnaireId; ?>">Bearbeitung abbrechen</a>
			<?php } ?>
		</form>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); ?>
