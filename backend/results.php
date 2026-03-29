<?php
	$show_only_user = 'min_manager';
	$title = 'Ergebnisse';
	$description = 'Sieh Ergebnisse von Fragebögen ein';
	$keywords = 'ergebnisse, fragebogen, backend';
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');

	$errors = array();
	$messages = array();
	$activeUser = check_user();

	$filterQuestionnaireId = isset($_GET['questionnaire_id']) ? (int)$_GET['questionnaire_id'] : 0;
	$filterUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
	$filterDateFrom = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : '';
	$filterDateTo = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : '';

	if ($filterDateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDateFrom)) {
		$errors[] = 'Ungültiges Von-Datum. Bitte YYYY-MM-DD verwenden.';
		$filterDateFrom = '';
	}
	if ($filterDateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDateTo)) {
		$errors[] = 'Ungültiges Bis-Datum. Bitte YYYY-MM-DD verwenden.';
		$filterDateTo = '';
	}
	if ($filterDateFrom !== '' && $filterDateTo !== '' && $filterDateFrom > $filterDateTo) {
		$errors[] = 'Der Zeitraum ist ungültig: Von-Datum liegt nach Bis-Datum.';
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_session'])) {
		$deleteSessionId = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
		if ($deleteSessionId <= 0) {
			$errors[] = 'Ungültige Session-ID.';
		} else {
			try {
				$pdo->beginTransaction();

				$pdo->exec('CREATE TABLE IF NOT EXISTS questionnaire_session_deletion_audit (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					session_id INT(10) UNSIGNED NOT NULL,
					questionnaire_id INT(10) UNSIGNED DEFAULT NULL,
					target_user_id INT(10) UNSIGNED DEFAULT NULL,
					deleted_by_user_id INT(10) UNSIGNED NOT NULL,
					deleted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					deleted_entities_json LONGTEXT,
					PRIMARY KEY (id),
					KEY idx_questionnaire_session_deletion_audit_session_id (session_id),
					KEY idx_questionnaire_session_deletion_audit_deleted_by_user_id (deleted_by_user_id)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

				$sessionStmt = $pdo->prepare('SELECT id, questionnaire_id, user_id FROM questionnaire_sessions WHERE id = :id LIMIT 1 FOR UPDATE');
				$sessionStmt->execute(array(':id' => $deleteSessionId));
				$sessionRow = $sessionStmt->fetch(PDO::FETCH_ASSOC);

				if (!$sessionRow) {
					throw new RuntimeException('Session wurde nicht gefunden.');
				}

				$countTables = array(
					'questionnaire_answers',
					'questionnaire_scores',
					'questionnaire_reports',
					'questionnaire_session_demographics'
				);
				$deletedCounts = array();
				foreach ($countTables as $tableName) {
					$countStmt = $pdo->prepare('SELECT COUNT(*) AS amount FROM '.$tableName.' WHERE session_id = :session_id');
					$countStmt->execute(array(':session_id' => $deleteSessionId));
					$deletedCounts[$tableName] = (int)$countStmt->fetchColumn();
				}

				$deleteDemographicsStmt = $pdo->prepare('DELETE FROM questionnaire_session_demographics WHERE session_id = :session_id');
				$deleteDemographicsStmt->execute(array(':session_id' => $deleteSessionId));

				$deleteAnswersStmt = $pdo->prepare('DELETE FROM questionnaire_answers WHERE session_id = :session_id');
				$deleteAnswersStmt->execute(array(':session_id' => $deleteSessionId));

				$deleteScoresStmt = $pdo->prepare('DELETE FROM questionnaire_scores WHERE session_id = :session_id');
				$deleteScoresStmt->execute(array(':session_id' => $deleteSessionId));

				$deleteReportsStmt = $pdo->prepare('DELETE FROM questionnaire_reports WHERE session_id = :session_id');
				$deleteReportsStmt->execute(array(':session_id' => $deleteSessionId));

				$deleteSessionStmt = $pdo->prepare('DELETE FROM questionnaire_sessions WHERE id = :session_id LIMIT 1');
				$deleteSessionStmt->execute(array(':session_id' => $deleteSessionId));
				if ($deleteSessionStmt->rowCount() < 1) {
					throw new RuntimeException('Session konnte nicht gelöscht werden.');
				}

				$deletedCounts['questionnaire_sessions'] = 1;
				$auditStmt = $pdo->prepare('INSERT INTO questionnaire_session_deletion_audit (session_id, questionnaire_id, target_user_id, deleted_by_user_id, deleted_entities_json) VALUES (:session_id, :questionnaire_id, :target_user_id, :deleted_by_user_id, :deleted_entities_json)');
				$auditStmt->execute(array(
					':session_id' => $deleteSessionId,
					':questionnaire_id' => isset($sessionRow['questionnaire_id']) ? (int)$sessionRow['questionnaire_id'] : null,
					':target_user_id' => isset($sessionRow['user_id']) ? (int)$sessionRow['user_id'] : null,
					':deleted_by_user_id' => (int)$activeUser['id'],
					':deleted_entities_json' => json_encode($deletedCounts)
				));

				$pdo->commit();
				$messages[] = 'Session #'.$deleteSessionId.' wurde inkl. abhängiger Daten gelöscht und im Audit-Log dokumentiert.';
			} catch (Throwable $e) {
				if ($pdo->inTransaction()) {
					$pdo->rollBack();
				}
				$errors[] = 'Löschen fehlgeschlagen: '.$e->getMessage();
			}
		}
	}

	$questionnairesStmt = $pdo->prepare('SELECT id, title, slug FROM questionnaires ORDER BY title ASC, id ASC');
	$questionnairesStmt->execute();
	$questionnaires = $questionnairesStmt->fetchAll(PDO::FETCH_ASSOC);

	$usersStmt = $pdo->prepare('SELECT id, username, email, vorname, nachname FROM users ORDER BY username ASC, id ASC');
	$usersStmt->execute();
	$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

	$conditions = array();
	$params = array();
	if ($filterQuestionnaireId > 0) {
		$conditions[] = 's.questionnaire_id = :questionnaire_id';
		$params[':questionnaire_id'] = $filterQuestionnaireId;
	}
	if ($filterUserId > 0) {
		$conditions[] = 's.user_id = :user_id';
		$params[':user_id'] = $filterUserId;
	}
	if ($filterDateFrom !== '') {
		$conditions[] = 'DATE(COALESCE(s.finished_at, s.started_at)) >= :date_from';
		$params[':date_from'] = $filterDateFrom;
	}
	if ($filterDateTo !== '') {
		$conditions[] = 'DATE(COALESCE(s.finished_at, s.started_at)) <= :date_to';
		$params[':date_to'] = $filterDateTo;
	}

	$sql = 'SELECT
		s.id,
		s.questionnaire_id,
		s.user_id,
		s.started_at,
		s.finished_at,
		s.completion_status,
		q.title AS questionnaire_title,
		q.slug AS questionnaire_slug,
		u.username,
		u.email,
		u.vorname,
		u.nachname,
		SUM(CASE WHEN qs.score_type = "total" THEN 1 ELSE 0 END) AS total_score_rows,
		AVG(CASE WHEN qs.score_type = "total" THEN qs.raw_mean END) AS total_raw_mean,
		AVG(CASE WHEN qs.score_type = "total" THEN qs.raw_sum END) AS total_raw_sum,
		SUM(CASE WHEN qs.score_type = "subscale" THEN 1 ELSE 0 END) AS subscale_count,
		COUNT(DISTINCT qr.id) AS report_count
	FROM questionnaire_sessions s
	INNER JOIN questionnaires q ON q.id = s.questionnaire_id
	LEFT JOIN users u ON u.id = s.user_id
	LEFT JOIN questionnaire_scores qs ON qs.session_id = s.id
	LEFT JOIN questionnaire_reports qr ON qr.session_id = s.id';

	if (!empty($conditions)) {
		$sql .= ' WHERE '.implode(' AND ', $conditions);
	}
	$sql .= ' GROUP BY s.id, s.questionnaire_id, s.user_id, s.started_at, s.finished_at, s.completion_status, q.title, q.slug, u.username, u.email, u.vorname, u.nachname';
	$sql .= ' ORDER BY COALESCE(s.finished_at, s.started_at) DESC, s.id DESC';

	$listStmt = $pdo->prepare($sql);
	$listStmt->execute($params);
	$sessions = $listStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<article>
	<section>
		<?php questionnaire_show_backend_overview('Ergebnisse', 'Auswertung, Filterung und Löschung von Fragebogen-Sessions.'); ?>
	</section>

	<section>
		<h2>Filter</h2>
		<form action="" method="get">
			<label for="questionnaire_id">Fragebogen</label><br>
			<select name="questionnaire_id" id="questionnaire_id">
				<option value="0">Alle Fragebögen</option>
				<?php foreach ($questionnaires as $questionnaireOption) { ?>
					<option value="<?php echo (int)$questionnaireOption['id']; ?>" <?php echo $filterQuestionnaireId === (int)$questionnaireOption['id'] ? 'selected' : ''; ?>><?php echo htmlentities((string)$questionnaireOption['title']); ?> (<?php echo htmlentities((string)$questionnaireOption['slug']); ?>)</option>
				<?php } ?>
			</select><br><br>

			<label for="user_id">Nutzer</label><br>
			<select name="user_id" id="user_id">
				<option value="0">Alle Nutzer</option>
				<?php foreach ($users as $userOption) { ?>
					<?php $userDisplayName = trim((string)$userOption['vorname'].' '.(string)$userOption['nachname']); ?>
					<option value="<?php echo (int)$userOption['id']; ?>" <?php echo $filterUserId === (int)$userOption['id'] ? 'selected' : ''; ?>><?php echo htmlentities($userDisplayName !== '' ? $userDisplayName : (string)$userOption['username']); ?> (@<?php echo htmlentities((string)$userOption['username']); ?>)</option>
				<?php } ?>
			</select><br><br>

			<label for="date_from">Zeitraum von</label><br>
			<input type="date" name="date_from" id="date_from" value="<?php echo htmlentities($filterDateFrom); ?>"><br><br>

			<label for="date_to">Zeitraum bis</label><br>
			<input type="date" name="date_to" id="date_to" value="<?php echo htmlentities($filterDateTo); ?>"><br><br>

			<button type="submit">Filtern</button>
			<a href="results.php" style="margin-left:10px;">Filter zurücksetzen</a>
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
		<h2>Sessions</h2>
		<?php if (empty($sessions)) { ?>
			<p>Keine Sessions für die gewählten Filter gefunden.</p>
		<?php } else { ?>
			<table class="tablesorter" border="1" cellpadding="6" cellspacing="0">
				<thead>
					<tr>
						<th>Session</th>
						<th>Fragebogen</th>
						<th>Nutzerbezug</th>
						<th>Abschlussdatum</th>
						<th>Score-Zusammenfassung</th>
						<th>Report-Status</th>
						<th>Aktion</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($sessions as $session) { ?>
						<?php
							$personDisplay = 'Gast/Nicht zugeordnet';
							if (!empty($session['user_id'])) {
								$name = trim((string)$session['vorname'].' '.(string)$session['nachname']);
								if ($name === '') {
									$name = (string)$session['username'];
								}
								$personDisplay = $name.' (@'.(string)$session['username'].', '.(string)$session['email'].')';
							}
							$completionDate = $session['finished_at'] !== null ? (string)$session['finished_at'] : 'Noch nicht abgeschlossen';
							$scoreSummaryParts = array();
							if ((int)$session['total_score_rows'] > 0) {
								$scoreSummaryParts[] = 'Total-Mean: '.number_format((float)$session['total_raw_mean'], 2, ',', '.');
								$scoreSummaryParts[] = 'Total-Sum: '.number_format((float)$session['total_raw_sum'], 2, ',', '.');
							}
							$scoreSummaryParts[] = 'Subskalen: '.(int)$session['subscale_count'];
							$scoreSummary = implode(' | ', $scoreSummaryParts);
							$reportStatus = (int)$session['report_count'] > 0 ? 'Vorhanden ('.(int)$session['report_count'].')' : 'Fehlt';
						?>
						<tr>
							<td>#<?php echo (int)$session['id']; ?><br><small>Status: <?php echo htmlentities((string)$session['completion_status']); ?></small></td>
							<td><?php echo htmlentities((string)$session['questionnaire_title']); ?><br><small><?php echo htmlentities((string)$session['questionnaire_slug']); ?></small></td>
							<td><?php echo htmlentities($personDisplay); ?></td>
							<td><?php echo htmlentities($completionDate); ?></td>
							<td><?php echo htmlentities($scoreSummary); ?></td>
							<td><?php echo htmlentities($reportStatus); ?></td>
							<td>
								<form action="" method="post" onsubmit="return confirm('Session inkl. Antworten, Scores und Reports wirklich löschen?');">
									<input type="hidden" name="session_id" value="<?php echo (int)$session['id']; ?>">
									<button type="submit" name="delete_session" value="1">Löschen</button>
								</form>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		<?php } ?>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); ?>
