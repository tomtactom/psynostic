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
<article>
	<section>
		<h1>Item-Verwaltung</h1>
		<p><a href="questionnaires.php">&laquo; Zurück zur Fragebogenliste</a> | <a href="questionnaire_edit.php?id=<?php echo (int)$questionnaireId; ?>">Stammdaten bearbeiten</a></p>
		<p><strong>Fragebogen:</strong> <?php echo htmlentities($questionnaire['title']); ?> (<?php echo htmlentities($questionnaire['slug']); ?>)</p>
	</section>

	<section>
		<h2>Neues Item</h2>
		<form action="" method="post">
			<input type="hidden" name="item_id" value="0">
			<label>Item-Nr.</label><br>
			<input type="number" name="item_no" min="1" required><br><br>

			<label>Itemtext</label><br>
			<textarea name="item_text" rows="4" cols="80" required></textarea><br><br>

			<label>Skalentyp</label><br>
			<select name="scale_type" required>
				<option value="likert">likert</option>
				<option value="binary">binary</option>
				<option value="custom">custom</option>
			</select><br><br>

			<label>Likert-Min</label><br>
			<input type="number" name="likert_min" required><br><br>

			<label>Likert-Max</label><br>
			<input type="number" name="likert_max" required><br><br>

			<label>Reverse</label>
			<input type="checkbox" name="is_reversed" value="1"><br><br>

			<label>Subskalen-Key</label><br>
			<input type="text" name="subscale_key" maxlength="100"><br><br>

			<label>Pflichtfeld</label>
			<input type="checkbox" name="is_required" value="1" checked><br><br>

			<button type="submit" name="save_item" value="1">Item speichern</button>
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

	<section>
		<h2>Vorhandene Items</h2>
		<?php foreach ($items as $item) { ?>
			<form action="" method="post" style="border:1px solid #ccc; padding:10px; margin-bottom:15px;">
				<input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
				<label>Item-Nr.</label><br>
				<input type="number" name="item_no" min="1" value="<?php echo (int)$item['item_no']; ?>" required><br><br>

				<label>Itemtext</label><br>
				<textarea name="item_text" rows="4" cols="80" required><?php echo htmlentities((string)$item['item_text']); ?></textarea><br><br>

				<label>Skalentyp</label><br>
				<select name="scale_type" required>
					<?php foreach ($allowedScaleTypes as $scaleTypeOption) { ?>
						<option value="<?php echo htmlentities($scaleTypeOption); ?>" <?php echo $item['scale_type'] === $scaleTypeOption ? 'selected' : ''; ?>><?php echo htmlentities($scaleTypeOption); ?></option>
					<?php } ?>
				</select><br><br>

				<label>Likert-Min</label><br>
				<input type="number" name="likert_min" value="<?php echo (int)$item['likert_min']; ?>" required><br><br>

				<label>Likert-Max</label><br>
				<input type="number" name="likert_max" value="<?php echo (int)$item['likert_max']; ?>" required><br><br>

				<label>Reverse</label>
				<input type="checkbox" name="is_reversed" value="1" <?php echo (int)$item['is_reversed'] === 1 ? 'checked' : ''; ?>><br><br>

				<label>Subskalen-Key</label><br>
				<input type="text" name="subscale_key" maxlength="100" value="<?php echo htmlentities((string)$item['subscale_key']); ?>"><br><br>

				<label>Pflichtfeld</label>
				<input type="checkbox" name="is_required" value="1" <?php echo (int)$item['is_required'] === 1 ? 'checked' : ''; ?>><br><br>

				<button type="submit" name="save_item" value="1">Änderungen speichern</button>
				<button type="submit" name="delete_item" value="1" onclick="return confirm('Item wirklich löschen?');">Löschen</button>
			</form>
		<?php } ?>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); ?>
