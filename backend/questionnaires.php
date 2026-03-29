<?php
	$show_only_user = 'min_manager';
	$title = 'Fragebögen';
	$description = 'Verwalte Fragebögen im Backend';
	$keywords = 'fragebogen, backend, verwaltung';
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');

	$errors = array();
	$messages = array();

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_questionnaire'])) {
		$titleInput = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
		$slugInput = isset($_POST['slug']) ? trim((string)$_POST['slug']) : '';
		$slugInput = strtolower($slugInput);
		$slugInput = preg_replace('/[^a-z0-9\-]/', '-', $slugInput);
		$slugInput = trim((string)$slugInput, '-');

		if ($titleInput === '' || mb_strlen($titleInput) > 255) {
			$errors[] = 'Titel ist erforderlich und darf maximal 255 Zeichen lang sein.';
		}

		if ($slugInput === '' || mb_strlen($slugInput) > 255) {
			$errors[] = 'Slug ist erforderlich und darf maximal 255 Zeichen lang sein.';
		}

		if (empty($errors)) {
			$checkStmt = $pdo->prepare('SELECT id FROM questionnaires WHERE slug = :slug LIMIT 1');
			$checkStmt->execute(array(':slug' => $slugInput));
			if ($checkStmt->fetch()) {
				$errors[] = 'Der Slug ist bereits vergeben.';
			}
		}

		if (empty($errors)) {
			$insertStmt = $pdo->prepare('INSERT INTO questionnaires (slug, title, intro_text, standard_rules_json, status, created_at, updated_at) VALUES (:slug, :title, :intro_text, :standard_rules_json, :status, NOW(), NOW())');
			$insertStmt->execute(array(
				':slug' => $slugInput,
				':title' => $titleInput,
				':intro_text' => '',
				':standard_rules_json' => null,
				':status' => 'inactive'
			));
			$messages[] = 'Fragebogen wurde erstellt.';
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
<article>
	<section>
		<?php questionnaire_show_backend_overview('Fragebögen', 'Fragebögen erstellen, bearbeiten und aktivieren/deaktivieren.'); ?>
	</section>

	<section>
		<h1>Neuen Fragebogen anlegen</h1>
		<form action="" method="post">
			<label for="title">Titel</label><br>
			<input type="text" name="title" id="title" maxlength="255" required><br><br>

			<label for="slug">Slug (URL-Schlüssel)</label><br>
			<input type="text" name="slug" id="slug" maxlength="255" pattern="[a-z0-9\-]+" required><br><br>

			<button type="submit" name="create_questionnaire" value="1">Fragebogen erstellen</button>
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
		<h2>Vorhandene Fragebögen</h2>
		<table class="tablesorter" border="1" cellpadding="6" cellspacing="0">
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
						<form action="" method="post" style="display:inline; margin-left:10px;">
							<input type="hidden" name="questionnaire_id" value="<?php echo (int)$questionnaire['id']; ?>">
							<input type="hidden" name="new_status" value="<?php echo $questionnaire['status'] === 'active' ? 'inactive' : 'active'; ?>">
							<button type="submit" name="toggle_status" value="1"><?php echo $questionnaire['status'] === 'active' ? 'Inaktiv setzen' : 'Aktiv setzen'; ?></button>
						</form>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); ?>
