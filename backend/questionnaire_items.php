<?php
	$show_only_user = 'min_manager';
	$title = 'Fragebogen-Items';
	$description = 'Items eines Fragebogens verwalten';
	$keywords = 'fragebogen, items, backend';
	$questionnaireId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$errors = array();
	$messages = array();
	$itemCsvColumns = array('item_no', 'item_text', 'is_reversed', 'subscale_key', 'is_required');

	function questionnaireItemsDownloadTemplate(array $columns)
	{
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="questionnaire-items-template-v1.csv"');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columns);
		fputcsv($output, array('1', 'Ich fühle mich heute ausgeglichen.', '0', 'wohlbefinden', '1'));
		fputcsv($output, array('2', 'Ich habe in letzter Zeit schlecht geschlafen.', '1', 'stress', '1'));
		fclose($output);
		exit;
	}

	if (isset($_GET['download_items_template']) && $_GET['download_items_template'] === '1') {
		questionnaireItemsDownloadTemplate($itemCsvColumns);
	}

	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');

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

	function validateItemInput(array $input, &$itemData)
	{
		$validationErrors = array();

		$itemData = array(
			'item_id' => isset($input['item_id']) ? (int)$input['item_id'] : 0,
			'item_no' => isset($input['item_no']) ? (int)$input['item_no'] : 0,
			'item_text' => isset($input['item_text']) ? trim((string)$input['item_text']) : '',

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
		if ($itemData['subscale_key'] !== '' && !preg_match('/^[a-zA-Z0-9_\-]{1,100}$/', $itemData['subscale_key'])) {
			$validationErrors[] = 'Subskalen-Key darf nur Buchstaben, Zahlen, Unterstriche und Bindestriche enthalten (max. 100 Zeichen).';
		}

		return $validationErrors;
	}

	function normalizeCsvHeader(array $headerRow)
	{
		$header = array();
		foreach ($headerRow as $columnName) {
			$header[] = trim((string)$columnName);
		}
		return $header;
	}

	function csvRowToItemPayload(array $csvRow, array $headerMap)
	{
		$payload = array(
			'item_id' => 0,
			'item_no' => 0,
			'item_text' => '',
			'is_reversed' => 0,
			'subscale_key' => '',
			'is_required' => 1
		);

		foreach ($headerMap as $index => $columnName) {
			$payload[$columnName] = isset($csvRow[$index]) ? trim((string)$csvRow[$index]) : '';
		}

		$payload['is_reversed'] = ($payload['is_reversed'] === '1' || strtolower((string)$payload['is_reversed']) === 'true') ? '1' : '0';
		$payload['is_required'] = ($payload['is_required'] === '0' || strtolower((string)$payload['is_required']) === 'false') ? '0' : '1';
		return $payload;
	}

	function questionnaireItemsPageOwnershipColumn(PDO $pdo)
	{
		$columns = array('created_by', 'owner_id', 'user_id', 'manager_id');
		foreach ($columns as $column) {
			$stmt = $pdo->prepare('SHOW COLUMNS FROM questionnaires LIKE :column_name');
			$stmt->execute(array(':column_name' => $column));
			if ($stmt->fetch(PDO::FETCH_ASSOC)) {
				return $column;
			}
		}

		return null;
	}

	if ($questionnaireId <= 0) {
		die('<p>Ungültige Fragebogen-ID.</p>');
	}

	$questionnaireStmt = $pdo->prepare('SELECT id, title, slug FROM questionnaires WHERE id = :id LIMIT 1');
	$questionnaireStmt->execute(array(':id' => $questionnaireId));
	$questionnaire = $questionnaireStmt->fetch(PDO::FETCH_ASSOC);
	if (!$questionnaire) {
		return null;
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_items_csv'])) {
		if (!isset($_FILES['items_csv']) || (int)$_FILES['items_csv']['error'] !== UPLOAD_ERR_OK) {
			$errors[] = 'CSV-Datei konnte nicht hochgeladen werden.';
		} else {
			$extension = strtolower(pathinfo((string)$_FILES['items_csv']['name'], PATHINFO_EXTENSION));
			if ($extension !== 'csv') {
				$errors[] = 'Bitte eine CSV-Datei mit Endung .csv hochladen.';
			} else {
				$handle = fopen($_FILES['items_csv']['tmp_name'], 'r');
				if ($handle === false) {
					$errors[] = 'CSV-Datei konnte nicht gelesen werden.';
				} else {
					$header = fgetcsv($handle);
					if ($header === false) {
						$errors[] = 'CSV-Datei ist leer.';
					} else {
						$header = normalizeCsvHeader($header);
						$missingColumns = array_diff($itemCsvColumns, $header);
						if (!empty($missingColumns)) {
							$errors[] = 'CSV enthält nicht alle Pflichtspalten: '.implode(', ', $missingColumns);
						} else {
							$rowNumber = 1;
							$createdCount = 0;
							$updatedCount = 0;
							$importMode = isset($_POST['csv_import_mode']) && $_POST['csv_import_mode'] === 'update' ? 'update' : 'create';

							while (($row = fgetcsv($handle)) !== false) {
								$rowNumber++;
								if ($row === array(null) || (count($row) === 1 && trim((string)$row[0]) === '')) {
									continue;
								}

								$itemPayload = csvRowToItemPayload($row, $header);
								$itemData = array();
								$rowErrors = validateItemInput($itemPayload, $itemData);
								if (!empty($rowErrors)) {
									foreach ($rowErrors as $rowError) {
										$errors[] = 'CSV Zeile '.$rowNumber.': '.$rowError;
									}
									continue;
								}

								$existingByNumberStmt = $pdo->prepare('SELECT id FROM questionnaire_items WHERE questionnaire_id = :questionnaire_id AND item_no = :item_no LIMIT 1');
								$existingByNumberStmt->execute(array(':questionnaire_id' => $questionnaireId, ':item_no' => $itemData['item_no']));
								$existingItemId = (int)$existingByNumberStmt->fetchColumn();

								if ($existingItemId > 0 && $importMode === 'create') {
									$errors[] = 'CSV Zeile '.$rowNumber.': Item-Nummer '.$itemData['item_no'].' existiert bereits. Nutze Modus "Bestehende aktualisieren".';
									continue;
								}

								if ($existingItemId > 0 && $importMode === 'update') {
									$updateStmt = $pdo->prepare('UPDATE questionnaire_items SET item_text = :item_text, is_reversed = :is_reversed, subscale_key = :subscale_key, is_required = :is_required WHERE id = :id AND questionnaire_id = :questionnaire_id LIMIT 1');
									$updateStmt->execute(array(
										':item_text' => $itemData['item_text'],
										':is_reversed' => $itemData['is_reversed'],
										':subscale_key' => $itemData['subscale_key'] === '' ? null : $itemData['subscale_key'],
										':is_required' => $itemData['is_required'],
										':id' => $existingItemId,
										':questionnaire_id' => $questionnaireId
									));
									$updatedCount++;
								} else {
									$insertStmt = $pdo->prepare('INSERT INTO questionnaire_items (questionnaire_id, item_no, item_text, is_reversed, subscale_key, is_required) VALUES (:questionnaire_id, :item_no, :item_text, :is_reversed, :subscale_key, :is_required)');
									$insertStmt->execute(array(
										':questionnaire_id' => $questionnaireId,
										':item_no' => $itemData['item_no'],
										':item_text' => $itemData['item_text'],
										':is_reversed' => $itemData['is_reversed'],
										':subscale_key' => $itemData['subscale_key'] === '' ? null : $itemData['subscale_key'],
										':is_required' => $itemData['is_required']
									));
									$createdCount++;
								}
							}

							if (empty($errors)) {
								$messages[] = 'CSV-Import erfolgreich: '.$createdCount.' neu, '.$updatedCount.' aktualisiert.';
							}
						}
					}
					fclose($handle);
				}
			}
		}
	}

	$ownerColumn = questionnaireItemsPageOwnershipColumn($pdo);
	if ($ownerColumn === null) {
		return $questionnaire;
	}

	$ownerStmt = $pdo->prepare('SELECT '.$ownerColumn.' FROM questionnaires WHERE id = :id LIMIT 1');
	$ownerStmt->execute(array(':id' => $questionnaireId));
	$ownerId = (int)$ownerStmt->fetchColumn();
	if ($ownerId > 0 && $ownerId !== (int)$user['id']) {
		return false;
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_item'])) {
		$itemData = array();
		$validationErrors = validateItemInput($_POST, $itemData);
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
				$updateStmt = $pdo->prepare('UPDATE questionnaire_items SET item_no = :item_no, item_text = :item_text, is_reversed = :is_reversed, subscale_key = :subscale_key, is_required = :is_required WHERE id = :id AND questionnaire_id = :questionnaire_id LIMIT 1');
				$updateStmt->execute(array(
					':item_no' => $itemData['item_no'],
					':item_text' => $itemData['item_text'],

					':is_reversed' => $itemData['is_reversed'],
					':subscale_key' => $itemData['subscale_key'] === '' ? null : $itemData['subscale_key'],
					':is_required' => $itemData['is_required'],
					':id' => $itemData['item_id'],
					':questionnaire_id' => $questionnaireId
				));
				$messages[] = 'Item wurde aktualisiert.';
			} else {
				$insertStmt = $pdo->prepare('INSERT INTO questionnaire_items (questionnaire_id, item_no, item_text, is_reversed, subscale_key, is_required) VALUES (:questionnaire_id, :item_no, :item_text, :is_reversed, :subscale_key, :is_required)');
				$insertStmt->execute(array(
					':questionnaire_id' => $questionnaireId,
					':item_no' => $itemData['item_no'],
					':item_text' => $itemData['item_text'],

					':is_reversed' => $itemData['is_reversed'],
					':subscale_key' => $itemData['subscale_key'] === '' ? null : $itemData['subscale_key'],
					':is_required' => $itemData['is_required']
				));
				$messages[] = 'Item wurde angelegt.';
			}
		}
	}

$user = check_user();
$questionnaireId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

	$sql = 'SELECT id, item_no, item_text, is_reversed, subscale_key, is_required FROM questionnaire_items WHERE questionnaire_id = :questionnaire_id';
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
		$editStmt = $pdo->prepare('SELECT id, item_no, item_text, is_reversed, subscale_key, is_required FROM questionnaire_items WHERE id = :id AND questionnaire_id = :questionnaire_id LIMIT 1');
		$editStmt->execute(array(':id' => $editItemId, ':questionnaire_id' => $questionnaireId));
		$editItem = $editStmt->fetch(PDO::FETCH_ASSOC);
		if (!$editItem) {
			$errors[] = 'Das zu bearbeitende Item wurde nicht gefunden.';
		}
	}
?>
<main class="qnr-layout qnr-layout--backend">
	<section class="qnr-card">
		<h1>Item-Builder</h1>
		<p><a href="questionnaires.php">&laquo; Zurück zur Fragebogenliste</a> | <a href="questionnaire_edit.php?id=<?php echo (int)$questionnaireId; ?>">Stammdaten bearbeiten</a></p>
		<p><strong>Fragebogen:</strong> <?php echo htmlentities($questionnaire['title']); ?> (<?php echo htmlentities($questionnaire['slug']); ?>)</p>
		<p id="save-status" class="qnr-alert qnr-alert--info" aria-live="polite">Lade Items …</p>
		<p id="item-builder-error" class="qnr-alert qnr-alert--error" hidden></p>
	</section>

	<section class="qnr-card">
		<div class="qnr-inline-controls">
			<button type="button" id="add-item" class="qnr-btn qnr-focusable">Item hinzufügen</button>
		</div>
		<div id="items-list" class="item-list" aria-live="polite"></div>
	</section>

	<section class="qnr-card">
		<h2>Neues Item</h2>
		<form action="" method="post" id="new-item-form">
			<input type="hidden" name="item_id" value="0">
			<div class="qnr-grid qnr-grid--2">
			<div class="qnr-form-row">
				<label>Item-Nr.</label>
				<div class="qnr-inline-controls">
					<input class="qnr-input" type="number" name="item_no" id="new_item_no" min="1" required>
					<button type="button" class="qnr-btn qnr-btn--secondary qnr-focusable" id="autofill_item_no">Nächste freie Nummer</button>
				</div>
			</div>

			</div>

			<div class="qnr-form-row">
				<label>Itemtext</label>
				<textarea class="qnr-textarea" name="item_text" id="new_item_text" rows="4" required></textarea>
				<small id="item_text_counter">0 Zeichen</small>
			</div>

			<div class="qnr-form-row">
				<label>Subskala (optional)</label>
				<input class="qnr-input" type="text" maxlength="100" data-field="subscale_key">
			</div>
			<div class="qnr-form-row-inline">
				<label>Invertiert</label>
				<input type="checkbox" value="1" data-field="is_reversed">
			</div>
		</div>
	</article>
</template>

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
<style>
	.item-list {
		display: grid;
		gap: 12px;
	}
	.item-row__header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		gap: 10px;
	}
	.item-row__actions {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
	}
	.qnr-inline-controls {
		display: flex;
		justify-content: flex-end;
		margin-bottom: 12px;
	}
</style>

<script>
	(function () {
		var itemNoInput = document.getElementById('new_item_no');
		var autoFillBtn = document.getElementById('autofill_item_no');
		var textInput = document.getElementById('new_item_text');
		var textCounter = document.getElementById('item_text_counter');

		var knownNumbers = [<?php
			$itemNumbers = array();
			foreach ($items as $existingItem) {
				$itemNumbers[] = (int)$existingItem['item_no'];
			}
			echo implode(',', $itemNumbers);
		?>];

	function setStatus(text, kind) {
		saveStatus.textContent = text;
		saveStatus.className = 'qnr-alert ' + (kind || 'qnr-alert--info');
	}

	function setError(message) {
		if (!message) {
			errorBox.hidden = true;
			errorBox.textContent = '';
			return;
		}
		errorBox.hidden = false;
		errorBox.textContent = message;
	}

	function postApi(payload) {
		return fetch(apiUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			credentials: 'same-origin',
			body: JSON.stringify(payload)
		}).then(function (response) {
			return response.json().then(function (data) {
				if (!response.ok || !data.ok) {
					throw new Error(data && data.error ? data.error : 'Unbekannter API-Fehler');
				}
				return data;
			});
		});
	}

	function normalizeItem(raw) {
		return {
			id: raw.id ? Number(raw.id) : null,
			client_id: raw.client_id || nextClientId(),
			item_no: raw.item_no ? Number(raw.item_no) : 0,
			item_text: raw.item_text || '',
			subscale_key: raw.subscale_key || '',
			is_reversed: Number(raw.is_reversed) === 1 ? 1 : 0
		};
	}

	function scheduleSave() {
		pendingSave = true;
		setStatus('Ungespeicherte Änderungen …', 'qnr-alert--info');
		if (saveTimer) {
			clearTimeout(saveTimer);
		}
		saveTimer = setTimeout(function () {
			saveAllChanges();
		}, 1200);
	}


		items.forEach(function (item, index) {
			var fragment = template.content.cloneNode(true);
			var row = fragment.querySelector('.item-row');
			row.dataset.index = index;
			row.querySelector('.item-row__index').textContent = 'Item #' + (index + 1);

			var textEl = row.querySelector('[data-field="item_text"]');
			textEl.value = item.item_text;
			textEl.addEventListener('input', function () {
				items[index].item_text = textEl.value;
				scheduleSave();
			});

			var subscaleEl = row.querySelector('[data-field="subscale_key"]');
			subscaleEl.value = item.subscale_key;
			subscaleEl.addEventListener('input', function () {
				items[index].subscale_key = subscaleEl.value;
				scheduleSave();
			});

			var reversedEl = row.querySelector('[data-field="is_reversed"]');
			reversedEl.checked = item.is_reversed === 1;
			reversedEl.addEventListener('change', function () {
				items[index].is_reversed = reversedEl.checked ? 1 : 0;
				scheduleSave();
			});

			row.querySelector('[data-action="clone"]').addEventListener('click', function () {
				var copy = normalizeItem(item);
				copy.id = null;
				copy.client_id = nextClientId();
				items.splice(index + 1, 0, copy);
				render();
				scheduleSave();
			});

			row.querySelector('[data-action="delete"]').addEventListener('click', function () {
				deleteItem(index);
			});

			row.querySelector('[data-action="move_up"]').addEventListener('click', function () {
				if (index === 0) {
					return;
				}
				var moved = items.splice(index, 1)[0];
				items.splice(index - 1, 0, moved);
				render();
				scheduleSave();
			});

			row.querySelector('[data-action="move_down"]').addEventListener('click', function () {
				if (index >= items.length - 1) {
					return;
				}
				var moved = items.splice(index, 1)[0];
				items.splice(index + 1, 0, moved);
				render();
				scheduleSave();
			});

			listEl.appendChild(fragment);
		});
	}

	function deleteItem(index) {
		var item = items[index];
		if (!item) {
			return;
		}
		if (item.id) {
			setStatus('Lösche Item …', 'qnr-alert--info');
			postApi({
				action: 'delete',
				questionnaire_id: questionnaireId,
				csrf_token: csrfToken,
				item_id: item.id
			}).then(function () {
				items.splice(index, 1);
				render();
				scheduleSave();
			}).catch(function (error) {
				setError(error.message);
				setStatus('Speichern fehlgeschlagen', 'qnr-alert--error');
			});
		} else {
			items.splice(index, 1);
			render();
			scheduleSave();
		}
	}

	})();
</script>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); ?>
