<?php

// Scoring functions for Questionnaire plugin

/**
 * Calculate item, subscale and total scores for a questionnaire.
 *
 * Expected $questionnaireConfig shape (keys are optional unless noted):
 * - items (required): array of item definitions
 *   - item_key (required): unique item key
 *   - likert_min (required): minimum allowed value
 *   - likert_max (required): maximum allowed value
 *   - reverse_coded (optional): bool
 *   - subscale_key (optional): string
 * - minimum_answered_ratio (optional): float, defaults to 0.8
 * - subscales (optional): map subscale_key => [
 *     score_mode => 'mean'|'sum',
 *     minimum_answered_ratio => float
 *   ]
 * - total_score_mode (optional): 'mean'|'sum'
 * - total_score_modes (optional): array of 'mean' and/or 'sum'
 *
 * @param array $questionnaireConfig
 * @param array $responses map of item_key => raw response value
 *
 * @return array
 */
function questionnaireScore(array $questionnaireConfig, array $responses) {
	$items = isset($questionnaireConfig['items']) && is_array($questionnaireConfig['items']) ? $questionnaireConfig['items'] : array();
	$defaultMinimumAnsweredRatio = isset($questionnaireConfig['minimum_answered_ratio']) ? (float)$questionnaireConfig['minimum_answered_ratio'] : 0.8;

	$result = array(
		'status' => 'ok',
		'items' => array(),
		'subscales' => array(),
		'total' => array(),
		'plausibility_issues' => array()
	);

	$subscaleBuckets = array();
	$totalScoredValues = array();
	$totalItemCount = 0;

	foreach ($items as $itemDefinition) {
		if (!isset($itemDefinition['item_key'])) {
			continue;
		}

		$itemKey = $itemDefinition['item_key'];
		$totalItemCount++;

		$minimum = isset($itemDefinition['likert_min']) ? $itemDefinition['likert_min'] : null;
		$maximum = isset($itemDefinition['likert_max']) ? $itemDefinition['likert_max'] : null;

		$itemResult = array(
			'item_key' => $itemKey,
			'raw_value' => array_key_exists($itemKey, $responses) ? $responses[$itemKey] : null,
			'scored_value' => null,
			'is_answered' => false,
			'is_valid' => false,
			'reverse_coded' => !empty($itemDefinition['reverse_coded']),
			'subscale_key' => isset($itemDefinition['subscale_key']) ? $itemDefinition['subscale_key'] : null,
			'likert_min' => $minimum,
			'likert_max' => $maximum,
			'issue' => null
		);

		if ($minimum === null || $maximum === null || !is_numeric($minimum) || !is_numeric($maximum) || $minimum > $maximum) {
			$itemResult['issue'] = 'invalid_item_scale';
			$result['plausibility_issues'][] = array(
				'item_key' => $itemKey,
				'reason' => 'Invalid or incomplete item scale definition.'
			);
			$result['items'][$itemKey] = $itemResult;
			continue;
		}

		$minimum = (float)$minimum;
		$maximum = (float)$maximum;

		if (!array_key_exists($itemKey, $responses) || $responses[$itemKey] === null || $responses[$itemKey] === '') {
			$result['items'][$itemKey] = $itemResult;
			questionnaireAddToSubscaleBucket($subscaleBuckets, $itemResult['subscale_key'], null, false);
			continue;
		}

		$itemResult['is_answered'] = true;
		$rawValue = $responses[$itemKey];

		if (!is_numeric($rawValue)) {
			$itemResult['issue'] = 'non_numeric_response';
			$result['plausibility_issues'][] = array(
				'item_key' => $itemKey,
				'raw_value' => $rawValue,
				'reason' => 'Response is not numeric.'
			);
			$result['items'][$itemKey] = $itemResult;
			questionnaireAddToSubscaleBucket($subscaleBuckets, $itemResult['subscale_key'], null, false);
			continue;
		}

		$rawValue = (float)$rawValue;
		if ($rawValue < $minimum || $rawValue > $maximum) {
			$itemResult['issue'] = 'out_of_range_response';
			$result['plausibility_issues'][] = array(
				'item_key' => $itemKey,
				'raw_value' => $rawValue,
				'likert_min' => $minimum,
				'likert_max' => $maximum,
				'reason' => 'Response outside configured Likert range.'
			);
			$result['items'][$itemKey] = $itemResult;
			questionnaireAddToSubscaleBucket($subscaleBuckets, $itemResult['subscale_key'], null, false);
			continue;
		}

		$itemResult['is_valid'] = true;
		if ($itemResult['reverse_coded']) {
			$itemResult['scored_value'] = ($minimum + $maximum) - $rawValue;
		} else {
			$itemResult['scored_value'] = $rawValue;
		}

		$result['items'][$itemKey] = $itemResult;
		$totalScoredValues[] = $itemResult['scored_value'];
		questionnaireAddToSubscaleBucket($subscaleBuckets, $itemResult['subscale_key'], $itemResult['scored_value'], true);
	}

	$result['subscales'] = questionnaireBuildSubscaleScores(
		$subscaleBuckets,
		isset($questionnaireConfig['subscales']) && is_array($questionnaireConfig['subscales']) ? $questionnaireConfig['subscales'] : array(),
		$defaultMinimumAnsweredRatio
	);

	$result['total'] = questionnaireBuildAggregateScore(
		$totalScoredValues,
		$totalItemCount,
		$defaultMinimumAnsweredRatio,
		questionnaireResolveModes($questionnaireConfig, 'total')
	);

	if ($result['total']['status'] !== 'ok') {
		$result['status'] = 'partially_not_evaluable';
	}

	return $result;
}

function questionnaireAddToSubscaleBucket(array &$subscaleBuckets, $subscaleKey, $scoredValue, $isValid) {
	if ($subscaleKey === null || $subscaleKey === '') {
		return;
	}

	if (!isset($subscaleBuckets[$subscaleKey])) {
		$subscaleBuckets[$subscaleKey] = array(
			'total_items' => 0,
			'answered_values' => array()
		);
	}

	$subscaleBuckets[$subscaleKey]['total_items']++;
	if ($isValid) {
		$subscaleBuckets[$subscaleKey]['answered_values'][] = $scoredValue;
	}
}

function questionnaireBuildSubscaleScores(array $subscaleBuckets, array $subscaleConfig, $defaultMinimumAnsweredRatio) {
	$scores = array();

	foreach ($subscaleBuckets as $subscaleKey => $bucket) {
		$currentConfig = isset($subscaleConfig[$subscaleKey]) && is_array($subscaleConfig[$subscaleKey]) ? $subscaleConfig[$subscaleKey] : array();
		$minimumAnsweredRatio = isset($currentConfig['minimum_answered_ratio']) ? (float)$currentConfig['minimum_answered_ratio'] : (float)$defaultMinimumAnsweredRatio;
		$modes = questionnaireResolveModes($currentConfig, 'subscale');

		$scores[$subscaleKey] = questionnaireBuildAggregateScore(
			$bucket['answered_values'],
			$bucket['total_items'],
			$minimumAnsweredRatio,
			$modes
		);
	}

	return $scores;
}

function questionnaireBuildAggregateScore(array $scoredValues, $totalItems, $minimumAnsweredRatio, array $modes) {
	$answeredItems = count($scoredValues);
	$ratio = $totalItems > 0 ? ($answeredItems / $totalItems) : 0.0;

	$aggregate = array(
		'status' => 'ok',
		'total_items' => (int)$totalItems,
		'answered_items' => $answeredItems,
		'answered_ratio' => $ratio,
		'minimum_answered_ratio' => (float)$minimumAnsweredRatio,
		'sum' => null,
		'mean' => null,
		'score_mode' => $modes,
		'score' => null,
		'scores_by_mode' => array()
	);

	if ($totalItems === 0 || $ratio < $minimumAnsweredRatio) {
		$aggregate['status'] = 'not_evaluable';
		return $aggregate;
	}

	$sum = array_sum($scoredValues);
	$mean = $answeredItems > 0 ? ($sum / $answeredItems) : null;
	$aggregate['sum'] = $sum;
	$aggregate['mean'] = $mean;

	foreach ($modes as $mode) {
		if ($mode === 'sum') {
			$aggregate['scores_by_mode']['sum'] = $sum;
		}
		if ($mode === 'mean') {
			$aggregate['scores_by_mode']['mean'] = $mean;
		}
	}

	if (count($modes) === 1) {
		$aggregate['score'] = $aggregate['scores_by_mode'][$modes[0]];
	}

	return $aggregate;
}

function questionnaireResolveModes(array $config, $context) {
	$modes = array();

	if ($context === 'total') {
		if (isset($config['total_score_modes']) && is_array($config['total_score_modes'])) {
			$modes = $config['total_score_modes'];
		} elseif (isset($config['total_score_mode'])) {
			$modes = array($config['total_score_mode']);
		}
	} else {
		if (isset($config['score_modes']) && is_array($config['score_modes'])) {
			$modes = $config['score_modes'];
		} elseif (isset($config['score_mode'])) {
			$modes = array($config['score_mode']);
		}
	}

	if (empty($modes)) {
		$modes = array('mean');
	}

	$modes = array_values(array_unique(array_filter($modes, function ($mode) {
		return $mode === 'mean' || $mode === 'sum';
	})));

	if (empty($modes)) {
		$modes = array('mean');
	}

	return $modes;
}

function questionnaireDecodeStandardRules(array $questionnaire) {
	$rules = array();
	if (!isset($questionnaire['standard_rules_json']) || trim((string)$questionnaire['standard_rules_json']) === '') {
		return $rules;
	}

	$decoded = json_decode((string)$questionnaire['standard_rules_json'], true);
	if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
		return $rules;
	}

	return $decoded;
}

function questionnaireResolveQualityConfig(array $questionnaire) {
	$rules = questionnaireDecodeStandardRules($questionnaire);
	$quality = isset($rules['quality_parameters']) && is_array($rules['quality_parameters']) ? $rules['quality_parameters'] : array();

	$resolved = array(
		'total_minimum_answered_ratio' => isset($quality['total_minimum_answered_ratio']) ? max(0.0, min(1.0, (float)$quality['total_minimum_answered_ratio'])) : 0.8,
		'subscale_minimum_answered_ratio' => array(),
		'speeding' => array(
			'min_seconds' => isset($quality['speeding']['min_seconds']) ? max(0, (int)$quality['speeding']['min_seconds']) : null,
			'max_seconds' => isset($quality['speeding']['max_seconds']) ? max(0, (int)$quality['speeding']['max_seconds']) : null
		),
		'inconsistency_pairs' => array(),
		'reliability' => array(
			'enabled' => !empty($quality['reliability']['enabled'])
		)
	);

	if (isset($quality['subscale_minimum_answered_ratio']) && is_array($quality['subscale_minimum_answered_ratio'])) {
		foreach ($quality['subscale_minimum_answered_ratio'] as $key => $value) {
			$subscaleKey = trim((string)$key);
			if ($subscaleKey === '') {
				continue;
			}
			$resolved['subscale_minimum_answered_ratio'][$subscaleKey] = max(0.0, min(1.0, (float)$value));
		}
	}

	if (isset($quality['inconsistency_pairs']) && is_array($quality['inconsistency_pairs'])) {
		foreach ($quality['inconsistency_pairs'] as $pair) {
			if (!is_array($pair)) {
				continue;
			}
			$left = isset($pair['item_no_left']) ? (int)$pair['item_no_left'] : 0;
			$right = isset($pair['item_no_right']) ? (int)$pair['item_no_right'] : 0;
			$maxDiff = isset($pair['max_abs_diff']) ? (float)$pair['max_abs_diff'] : 2.0;
			if ($left <= 0 || $right <= 0 || $maxDiff < 0) {
				continue;
			}
			$resolved['inconsistency_pairs'][] = array(
				'item_no_left' => $left,
				'item_no_right' => $right,
				'max_abs_diff' => $maxDiff
			);
		}
	}

	return $resolved;
}

function questionnaireEvaluateQualityFromData(array $items, array $responsesByItemId, $startedAt, $finishedAt, array $qualityConfig) {
	$totalItems = count($items);
	$answeredItems = 0;
	$itemNoToRaw = array();
	$subscaleStats = array();
	foreach ($items as $item) {
		$itemId = isset($item['id']) ? (int)$item['id'] : 0;
		if ($itemId <= 0) {
			continue;
		}
		$subscaleKey = isset($item['subscale_key']) ? trim((string)$item['subscale_key']) : '';
		if ($subscaleKey !== '') {
			if (!isset($subscaleStats[$subscaleKey])) {
				$subscaleStats[$subscaleKey] = array('total' => 0, 'answered' => 0);
			}
			$subscaleStats[$subscaleKey]['total']++;
		}

		if (!array_key_exists($itemId, $responsesByItemId) || $responsesByItemId[$itemId] === null || $responsesByItemId[$itemId] === '') {
			continue;
		}
		$answeredItems++;
		$itemNo = isset($item['item_no']) ? (int)$item['item_no'] : 0;
		if ($itemNo > 0) {
			$itemNoToRaw[$itemNo] = (float)$responsesByItemId[$itemId];
		}
		if ($subscaleKey !== '') {
			$subscaleStats[$subscaleKey]['answered']++;
		}
	}

	$warnings = array();
	$totalRatio = $totalItems > 0 ? ($answeredItems / $totalItems) : 0.0;
	if ($totalItems > 0 && $totalRatio < $qualityConfig['total_minimum_answered_ratio']) {
		$warnings[] = 'Interpretation eingeschränkt wegen hoher Missing-Rate (gesamt).';
	}

	foreach ($qualityConfig['subscale_minimum_answered_ratio'] as $subscaleKey => $minRatio) {
		if (!isset($subscaleStats[$subscaleKey]) || $subscaleStats[$subscaleKey]['total'] <= 0) {
			continue;
		}
		$currentRatio = $subscaleStats[$subscaleKey]['answered'] / $subscaleStats[$subscaleKey]['total'];
		if ($currentRatio < $minRatio) {
			$warnings[] = 'Interpretation der Subskala "'.$subscaleKey.'" eingeschränkt (zu viele fehlende Antworten).';
		}
	}

	$durationSeconds = null;
	if ($startedAt !== null && $finishedAt !== null) {
		$startedTs = strtotime((string)$startedAt);
		$finishedTs = strtotime((string)$finishedAt);
		if ($startedTs !== false && $finishedTs !== false && $finishedTs >= $startedTs) {
			$durationSeconds = (int)($finishedTs - $startedTs);
		}
	}

	$minSeconds = $qualityConfig['speeding']['min_seconds'];
	$maxSeconds = $qualityConfig['speeding']['max_seconds'];
	if ($durationSeconds !== null && $minSeconds !== null && $durationSeconds < $minSeconds) {
		$warnings[] = 'Speeding-Flag: Antwortzeit liegt unter dem erlaubten Zeitfenster.';
	}
	if ($durationSeconds !== null && $maxSeconds !== null && $maxSeconds > 0 && $durationSeconds > $maxSeconds) {
		$warnings[] = 'Antwortzeit außerhalb des erlaubten Zeitfensters (zu lang).';
	}

	foreach ($qualityConfig['inconsistency_pairs'] as $pair) {
		$left = $pair['item_no_left'];
		$right = $pair['item_no_right'];
		if (!isset($itemNoToRaw[$left]) || !isset($itemNoToRaw[$right])) {
			continue;
		}
		$absDiff = abs($itemNoToRaw[$left] - $itemNoToRaw[$right]);
		if ($absDiff > $pair['max_abs_diff']) {
			$warnings[] = 'Inconsistency-Flag: auffällige Antwortdifferenz zwischen Item '.$left.' und '.$right.'.';
		}
	}

	$metrics = array(
		'answered_ratio_total' => $totalRatio,
		'answered_items_total' => $answeredItems,
		'total_items' => $totalItems,
		'duration_seconds' => $durationSeconds,
		'reliability' => array(
			'enabled' => !empty($qualityConfig['reliability']['enabled']),
			'label' => !empty($qualityConfig['reliability']['enabled']) ? 'Interne Konsistenz wird in der Forschungsansicht berechnet.' : null
		)
	);

	return array(
		'warnings' => array_values(array_unique($warnings)),
		'metrics' => $metrics
	);
}

function questionnaireEvaluateSessionQuality(PDO $pdo, array $questionnaire, $sessionId) {
	if (!isset($questionnaire['id']) || (int)$questionnaire['id'] <= 0) {
		return array(
			'warnings' => array('Qualitätsprüfung übersprungen: Fragebogen-ID fehlt.'),
			'metrics' => array(
				'answered_ratio_total' => 0,
				'answered_items_total' => 0,
				'total_items' => 0,
				'duration_seconds' => null,
				'reliability' => array(
					'enabled' => false,
					'label' => null
				)
			)
		);
	}

	$itemStmt = $pdo->prepare('SELECT id, item_no, subscale_key FROM questionnaire_items WHERE questionnaire_id = :questionnaire_id');
	$itemStmt->execute(array(':questionnaire_id' => (int)$questionnaire['id']));
	$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

	$answerStmt = $pdo->prepare('SELECT item_id, raw_value FROM questionnaire_answers WHERE session_id = :session_id');
	$answerStmt->execute(array(':session_id' => (int)$sessionId));
	$rows = $answerStmt->fetchAll(PDO::FETCH_ASSOC);
	$responsesByItemId = array();
	foreach ($rows as $row) {
		$responsesByItemId[(int)$row['item_id']] = $row['raw_value'];
	}

	$sessionStmt = $pdo->prepare('SELECT started_at, finished_at FROM questionnaire_sessions WHERE id = :id LIMIT 1');
	$sessionStmt->execute(array(':id' => (int)$sessionId));
	$session = $sessionStmt->fetch(PDO::FETCH_ASSOC);

	$qualityConfig = questionnaireResolveQualityConfig($questionnaire);
	return questionnaireEvaluateQualityFromData(
		$items,
		$responsesByItemId,
		$session ? $session['started_at'] : null,
		$session ? $session['finished_at'] : null,
		$qualityConfig
	);
}

// Functions for Plugin Questionnaire

function questionnaire_get_urls() {
	global $options;
	return [
		'questionnaires' => $options['siteurl'].'/backend/questionnaires.php',
		'normtables' => $options['siteurl'].'/backend/normtables.php',
		'results' => $options['siteurl'].'/backend/results.php',
		'frontend_overview' => $options['siteurl'].'/questionnaires',
		'frontend_fill' => $options['siteurl'].'/questionnaire',
		'frontend_result' => $options['siteurl'].'/questionnaire_result.php'
	];
}

function questionnaire_show_backend_overview($title, $description) {
	$urls = questionnaire_get_urls();
	echo '<h1>'.$title.'</h1>';
	echo '<p>'.$description.'</p>';
	echo '<ul>';
	echo '<li><a href="'.$urls['questionnaires'].'">Fragebögen</a></li>';
	echo '<li><a href="'.$urls['normtables'].'">Normtabellen</a></li>';
	echo '<li><a href="'.$urls['results'].'">Ergebnisse</a></li>';
	echo '<li><a href="'.$urls['frontend_overview'].'">Frontend-Übersicht</a></li>';
	echo '</ul>';
}

function questionnaireRenderBackendQualityWarnings(PDO $pdo) {
	$stmt = $pdo->query('
		SELECT
			qs.id AS session_id,
			qs.questionnaire_id,
			qs.started_at,
			qs.finished_at,
			q.title,
			q.standard_rules_json
		FROM questionnaire_sessions qs
		INNER JOIN questionnaires q ON q.id = qs.questionnaire_id
		WHERE qs.completion_status = "completed"
		ORDER BY qs.finished_at DESC, qs.id DESC
		LIMIT 20
	');
	$sessions = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
	if (empty($sessions)) {
		echo '<p>Keine abgeschlossenen Durchführungen vorhanden.</p>';
		return;
	}

	echo '<h2>Letzte Qualitätswarnungen</h2>';
	echo '<div class="qnr-table-wrap"><table class="qnr-table">';
	echo '<tr><th>Session</th><th>Fragebogen</th><th>Beendet</th><th>Warnhinweise</th></tr>';
	foreach ($sessions as $session) {
		$quality = questionnaireEvaluateSessionQuality($pdo, $session, (int)$session['session_id']);
		echo '<tr>';
		echo '<td>#'.(int)$session['session_id'].'</td>';
		echo '<td>'.htmlentities((string)$session['title']).'</td>';
		echo '<td>'.htmlentities((string)$session['finished_at']).'</td>';
		if (empty($quality['warnings'])) {
			echo '<td>Keine Warnhinweise</td>';
		} else {
			echo '<td><ul class="qnr-list-compact">';
			foreach ($quality['warnings'] as $warning) {
				echo '<li>'.htmlentities((string)$warning).'</li>';
			}
			echo '</ul></td>';
		}
		echo '</tr>';
	}
	echo '</table></div>';
}

function questionnaire_show_frontend($view = 'overview') {
	global $pdo;

	if ($view === 'overview') {
		$questionnaires = questionnaireLoadFrontendQuestionnaires($pdo);
		echo '<main class="qnr-layout qnr-layout--frontend">';
		echo '<section class="qnr-card">';
		echo '<h1>Fragebögen</h1>';
		if (empty($questionnaires)) {
			echo '<p>Aktuell sind keine aktiven Fragebögen verfügbar.</p>';
		} else {
			echo '<ul>';
			foreach ($questionnaires as $entry) {
				$slug = isset($entry['slug']) ? (string)$entry['slug'] : '';
				echo '<li><a href="/questionnaire?slug='.urlencode($slug).'">'.htmlentities((string)$entry['title']).'</a></li>';
			}
			echo '</ul>';
		}
		echo '</section>';
		echo '</main>';
		return;
	}

	if ($view === 'result') {
		$sessionId = isset($_GET['session']) ? (int)$_GET['session'] : 0;
		$result = $sessionId > 0 ? questionnaireLoadResultBySession($pdo, $sessionId) : null;
		echo '<main class="qnr-layout qnr-layout--frontend">';
		echo '<section class="qnr-card" aria-labelledby="questionnaire-step-heading">';
		echo '<h1 id="questionnaire-step-heading">Auswertung</h1>';
		if (!$result) {
			echo '<p>Die Auswertung ist nicht verfügbar. Bitte schließen Sie zuerst einen Fragebogen vollständig ab.</p>';
			echo '<p><a href="/questionnaires">Zur Fragebogen-Übersicht</a></p>';
			echo '</section></main>';
			return;
		}
		$totalInterpretation = 'Die Werte werden deskriptiv dargestellt und ersetzen keine professionelle Beurteilung.';
		if (isset($result['total_mean']) && $result['total_mean'] !== null) {
			$meanValue = (float)$result['total_mean'];
			if ($meanValue < 2.5) {
				$totalInterpretation = 'Im Mittel liegen die Antworten eher im unteren Bereich der Skala.';
			} elseif ($meanValue < 3.5) {
				$totalInterpretation = 'Im Mittel liegen die Antworten im mittleren Bereich der Skala.';
			} else {
				$totalInterpretation = 'Im Mittel liegen die Antworten eher im oberen Bereich der Skala.';
			}
			$totalInterpretation .= ' Diese Einordnung ersetzt keine professionelle Beurteilung.';
		}

		echo '<p>Session-ID: '.(int)$result['session_id'].'</p>';
		echo '<section aria-label="Ergebnisse">';
		echo '<article><h3>Gesamtergebnis</h3>';
		echo '<p><strong>Gesamtscore (Mittelwert):</strong> '.htmlentities((string)$result['total_mean']).'</p>';
		echo '<p><strong>Gesamtscore (Summe):</strong> '.htmlentities((string)$result['total_sum']).'</p>';
		echo '<p>'.htmlentities($totalInterpretation).'</p></article>';
		echo '<article><h3>Subskalen</h3>';
		if (!empty($result['subscales'])) {
			echo '<ul>';
			foreach ($result['subscales'] as $subscale) {
				echo '<li><strong>'.htmlentities((string)$subscale['score_key']).'</strong>: Mittelwert '.htmlentities((string)$subscale['raw_mean']).', Summe '.htmlentities((string)$subscale['raw_sum']).'</li>';
			}
			echo '</ul>';
		} else {
			echo '<p>Für diesen Fragebogen sind keine Subskalen ausgewiesen.</p>';
		}
		echo '</article>';
		echo '<article><h3>Qualitätsindikatoren</h3>';
		if (isset($result['quality']) && is_array($result['quality']) && !empty($result['quality']['warnings'])) {
			echo '<ul>';
			foreach ($result['quality']['warnings'] as $warning) {
				echo '<li>'.htmlentities((string)$warning).'</li>';
			}
			echo '</ul>';
		} else {
			echo '<p>Keine besonderen Qualitätshinweise.</p>';
		}
		echo '</article>';
		echo '<p><a href="/questionnaires">Zur Fragebogen-Übersicht</a></p>';
		echo '</section></section></main>';
		return;
	}

	$slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
	if ($slug === '') {
		echo '<h1>Fragebogen</h1><p>Kein Fragebogen-Slug angegeben.</p><p><a href="/questionnaires">Zur Übersicht</a></p>';
		return;
	}
	$questionnaire = questionnaireLoadBySlug($pdo, $slug);
	if (!$questionnaire) {
		echo '<h1>Fragebogen</h1><p>Der angefragte Fragebogen ist nicht verfügbar.</p><p><a href="/questionnaires">Zur Übersicht</a></p>';
		return;
	}

	if (!isset($_SESSION['questionnaire_flow']) || !is_array($_SESSION['questionnaire_flow'])) {
		$_SESSION['questionnaire_flow'] = array();
	}
	if (!isset($_SESSION['questionnaire_flow'][$slug]) || !is_array($_SESSION['questionnaire_flow'][$slug])) {
		$_SESSION['questionnaire_flow'][$slug] = array();
	}
	$flow = &$_SESSION['questionnaire_flow'][$slug];
	if (!isset($flow['csrf_token']) || !is_string($flow['csrf_token']) || $flow['csrf_token'] === '') {
		$flow['csrf_token'] = bin2hex(random_bytes(32));
	}

	$demographicFields = questionnaireLoadDemographicFields($pdo, (int)$questionnaire['id']);
	$items = questionnaireLoadItems($pdo, (int)$questionnaire['id']);
	$errors = array();
	$postedDemographics = array();
	$postedItems = array();

	$currentStep = isset($_GET['step']) ? (string)$_GET['step'] : 'intro';
	if (!in_array($currentStep, array('intro', 'demographics', 'items'), true)) {
		$currentStep = 'intro';
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$action = isset($_POST['action']) ? (string)$_POST['action'] : '';
		$postedCsrf = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
		if (!hash_equals($flow['csrf_token'], $postedCsrf)) {
			$errors[] = questionnaireBuildValidationError('Ungültiges Formular-Token. Bitte lade die Seite neu.', 'system');
			$currentStep = 'intro';
		} else {
			if ($action === 'start') {
				$sessionId = questionnaireStartSession($pdo, (int)$questionnaire['id']);
				$flow['session_id'] = $sessionId;
				$flow['questionnaire_id'] = (int)$questionnaire['id'];
				$flow['completion_token'] = bin2hex(random_bytes(32));
				$currentStep = 'demographics';
			}
			if ($action === 'save_demographics') {
				$postedDemographics = questionnaireReadDemographicsFromPost($demographicFields);
				$demographicValidation = questionnaireValidateDemographics($demographicFields, $postedDemographics);
				$errors = array_merge($errors, $demographicValidation['errors']);
				if (empty($errors)) {
					$flow['pending_demographics'] = $demographicValidation['values'];
					$currentStep = 'items';
				} else {
					$currentStep = 'demographics';
				}
			}
			if ($action === 'back_to_demographics') {
				$postedDemographics = questionnaireReadDemographicsFromPost($demographicFields);
				$postedItems = questionnaireReadItemsFromPost($items);
				$demographicValidation = questionnaireValidateDemographics($demographicFields, $postedDemographics);
				$flow['pending_demographics'] = $demographicValidation['values'];
				$flow['pending_items'] = $postedItems;
				$currentStep = 'demographics';
			}
			if ($action === 'finish') {
				$postedDemographics = questionnaireReadDemographicsFromPost($demographicFields);
				$postedItems = questionnaireReadItemsFromPost($items);
				$demographicValidation = questionnaireValidateDemographics($demographicFields, $postedDemographics);
				$itemValidation = questionnaireValidateItems($questionnaire, $items, $postedItems);
				$errors = array_merge($errors, $demographicValidation['errors'], $itemValidation['errors']);
				$postedCompletionToken = isset($_POST['completion_token']) ? (string)$_POST['completion_token'] : '';
				if (!isset($flow['session_id']) || (int)$flow['session_id'] <= 0) {
					$errors[] = questionnaireBuildValidationError('Keine laufende Durchführung gefunden. Bitte starte den Fragebogen neu.', 'system');
				}
				if (!isset($flow['completion_token']) || !hash_equals((string)$flow['completion_token'], $postedCompletionToken)) {
					$errors[] = questionnaireBuildValidationError('Ungültiges Abschluss-Token. Bitte starte die Durchführung neu.', 'system');
				}
				if (empty($errors)) {
					$result = questionnaireCompleteSessionIdempotent($pdo, (int)$flow['session_id'], $questionnaire, $demographicValidation['values'], $itemValidation['values']);
					header('Location: /questionnaire_result.php?session='.(int)$result['session_id']);
					exit;
				}
				$currentStep = 'items';
			}
		}
	}

	$collectedErrors = questionnaireCollectFieldErrors($errors);
	echo '<main class="qnr-layout qnr-layout--frontend">';
	echo '<section class="qnr-card">';
	echo '<h1>'.htmlentities((string)$questionnaire['title']).'</h1>';
	echo '<p><a href="/questionnaires">&larr; Zur Übersicht</a></p>';
	echo '</section>';
	if (!empty($errors)) {
		echo '<section class="qnr-card">';
		questionnaireRenderErrorSummary($collectedErrors['summary']);
		echo '<div id="questionnaire-errors" class="qnr-alert qnr-alert--error" role="alert" aria-live="assertive" tabindex="-1"><ul>';
		foreach ($collectedErrors['general'] as $error) {
			echo '<li>'.htmlentities((string)$error['message']).'</li>';
		}
		echo '</ul></div></section>';
	}

	$baseUrl = '/questionnaire?slug='.urlencode($slug);
	if ($currentStep === 'intro') {
		echo '<section class="qnr-card"><h2 id="questionnaire-step-heading" tabindex="-1">Einführung</h2>';
		echo '<p>'.nl2br(htmlentities((string)$questionnaire['intro_text'])).'</p>';
		echo '<form method="post" action="'.$baseUrl.'&step=demographics">';
		echo '<input type="hidden" name="action" value="start">';
		echo '<input type="hidden" name="csrf_token" value="'.htmlentities($flow['csrf_token']).'">';
		echo '<button class="qnr-btn qnr-focusable" type="submit">Fragebogen starten</button></form></section></main>';
		questionnaireRenderFrontendValidationScript();
		questionnaireRenderFrontendFocusScript();
		return;
	}
	if ($currentStep === 'demographics') {
		$values = !empty($postedDemographics) ? $postedDemographics : (isset($flow['pending_demographics']) && is_array($flow['pending_demographics']) ? $flow['pending_demographics'] : array());
		echo '<section class="qnr-card"><h2 id="questionnaire-step-heading" tabindex="-1">Demografische Angaben</h2>';
		echo '<form method="post" action="'.$baseUrl.'&step=items">';
		echo '<input type="hidden" name="action" value="save_demographics">';
		echo '<input type="hidden" name="csrf_token" value="'.htmlentities($flow['csrf_token']).'">';
		questionnaireRenderDemographicInputs($demographicFields, $values, $collectedErrors['field_keys']);
		echo '<div class="qnr-form-actions"><button class="qnr-btn qnr-focusable" type="submit">Weiter zu den Items</button></div>';
		echo '</form></section></main>';
		questionnaireRenderFrontendValidationScript();
		questionnaireRenderFrontendFocusScript();
		return;
	}

	$demoValues = !empty($postedDemographics) ? $postedDemographics : (isset($flow['pending_demographics']) && is_array($flow['pending_demographics']) ? $flow['pending_demographics'] : array());
	$itemValues = !empty($postedItems) ? $postedItems : (isset($flow['pending_items']) && is_array($flow['pending_items']) ? $flow['pending_items'] : array());
	echo '<section class="qnr-card"><h2 id="questionnaire-step-heading" tabindex="-1">Items beantworten</h2>';
	echo '<form method="post" action="'.$baseUrl.'&step=items">';
	echo '<input type="hidden" name="action" value="finish">';
	echo '<input type="hidden" name="csrf_token" value="'.htmlentities($flow['csrf_token']).'">';
	echo '<input type="hidden" name="completion_token" value="'.htmlentities((string)$flow['completion_token']).'">';
	questionnaireRenderHiddenDemographics($demographicFields, $demoValues);
	questionnaireRenderItems($questionnaire, $items, $itemValues, $collectedErrors['item_ids']);
	echo '<div class="qnr-form-actions">';
	echo '<button class="qnr-btn qnr-btn--secondary qnr-focusable" type="submit" name="action" value="back_to_demographics" formaction="'.$baseUrl.'&step=demographics">Zurück</button>';
	echo '<button class="qnr-btn qnr-focusable" type="submit">Abschließen</button></div>';
	echo '</form></section></main>';
	questionnaireRenderFrontendValidationScript();
	questionnaireRenderFrontendFocusScript();
	questionnaireRenderFrontendItemsEnhancementScript();
}

function questionnaireRenderFrontendFocusScript() {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		return;
	}

	echo '<script>';
	echo '(function () {';
	echo 'var errorRegion = document.getElementById("questionnaire-errors");';
	echo 'if (errorRegion) { errorRegion.focus(); return; }';
	echo 'var heading = document.getElementById("questionnaire-step-heading");';
	echo 'if (heading) { heading.focus(); }';
	echo '}());';
	echo '</script>';
}

function questionnaireRenderFrontendValidationScript() {
	echo '<script>';
	echo '(function () {';
	echo 'var forms = document.querySelectorAll("main.qnr-layout--frontend form");';
	echo 'if (!forms.length) { return; }';
	echo 'var setError = function (field, message) {';
	echo 'if (!field) { return; }';
	echo 'var errorId = field.id ? field.id + "_error" : "";';
	echo 'var errorNode = errorId ? document.getElementById(errorId) : null;';
	echo 'field.setAttribute("aria-invalid", message ? "true" : "false");';
	echo 'if (errorNode) { errorNode.textContent = message || ""; }';
	echo '};';
	echo 'var readMessage = function (field) {';
	echo 'if (!field || !field.validity) { return ""; }';
	echo 'if (field.validity.valueMissing) { return "Dieses Feld ist erforderlich."; }';
	echo 'if (field.validity.rangeUnderflow || field.validity.rangeOverflow) { return "Wert liegt außerhalb des erlaubten Bereichs."; }';
	echo 'if (field.validity.tooLong) { return "Maximale Zeichenlänge überschritten."; }';
	echo 'if (field.validity.patternMismatch || field.validity.typeMismatch || field.validity.badInput) { return "Bitte Eingabeformat prüfen."; }';
	echo 'return "";';
	echo '};';
	echo 'var validateRadioGroup = function (form, radioInput) {';
	echo 'if (!radioInput || !radioInput.name) { return true; }';
	echo 'var radios = form.querySelectorAll(\'input[type="radio"][name="\' + CSS.escape(radioInput.name) + \'"]\');';
	echo 'if (!radios.length) { return true; }';
	echo 'var required = radios[0].required;';
	echo 'var hasChecked = Array.prototype.some.call(radios, function (radio) { return radio.checked; });';
	echo 'var first = radios[0];';
	echo 'var message = (!hasChecked && required) ? "Bitte eine Option auswählen." : "";';
	echo 'radios.forEach(function (radio) { setError(radio, message); });';
	echo 'return message === "";';
	echo '};';
	echo 'forms.forEach(function (form) {';
	echo 'form.addEventListener("input", function (event) {';
	echo 'var target = event.target;';
	echo 'if (!target) { return; }';
	echo 'if (target.type === "radio") { validateRadioGroup(form, target); return; }';
	echo 'setError(target, readMessage(target));';
	echo '});';
	echo 'form.addEventListener("submit", function (event) {';
	echo 'var firstInvalid = null;';
	echo 'var valid = true;';
	echo 'Array.prototype.forEach.call(form.elements, function (field) {';
	echo 'if (!(field instanceof HTMLElement) || field.disabled || field.type === "hidden" || field.type === "submit") { return; }';
	echo 'if (field.type === "radio") { if (!validateRadioGroup(form, field) && !firstInvalid) { firstInvalid = field; valid = false; } return; }';
	echo 'var msg = readMessage(field);';
	echo 'setError(field, msg);';
	echo 'if (msg && !firstInvalid) { firstInvalid = field; valid = false; }';
	echo '});';
	echo 'if (!valid && firstInvalid) { event.preventDefault(); firstInvalid.focus(); }';
	echo '});';
	echo '});';
	echo '}());';
	echo '</script>';
}

function questionnaireLoadActiveQuestionnaire(PDO $pdo) {
	$stmt = $pdo->prepare('SELECT id, slug, title, intro_text, standard_rules_json, scale_type, likert_min, likert_max, scale_labels_json, raw_mapping_json, needs_manual_scale_cleanup FROM questionnaires WHERE status = :status ORDER BY updated_at DESC, id DESC LIMIT 1');
	$stmt->execute(array(':status' => 'active'));
	return $stmt->fetch(PDO::FETCH_ASSOC);
}

function questionnaireLoadFrontendQuestionnaires(PDO $pdo) {
	$stmt = $pdo->prepare('SELECT id, slug, title, intro_text, scale_type, likert_min, likert_max FROM questionnaires WHERE status = :status ORDER BY updated_at DESC, id DESC');
	$stmt->execute(array(':status' => 'active'));
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function questionnaireLoadBySlug(PDO $pdo, $slug) {
	$stmt = $pdo->prepare('SELECT id, slug, title, intro_text, standard_rules_json, scale_type, likert_min, likert_max, scale_labels_json, raw_mapping_json, needs_manual_scale_cleanup FROM questionnaires WHERE slug = :slug AND status = :status LIMIT 1');
	$stmt->execute(array(
		':slug' => (string)$slug,
		':status' => 'active'
	));
	return $stmt->fetch(PDO::FETCH_ASSOC);
}

function questionnaireLoadDemographicFields(PDO $pdo, $questionnaireId) {
	$stmt = $pdo->prepare('SELECT id, field_key, label, field_type, is_required, allowed_values_json FROM questionnaire_demographic_fields WHERE questionnaire_id = :questionnaire_id ORDER BY id ASC');
	$stmt->execute(array(':questionnaire_id' => (int)$questionnaireId));
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function questionnaireLoadItems(PDO $pdo, $questionnaireId) {
	$stmt = $pdo->prepare('SELECT id, item_no, item_text, is_reversed, subscale_key, is_required FROM questionnaire_items WHERE questionnaire_id = :questionnaire_id ORDER BY item_no ASC, id ASC');
	$stmt->execute(array(':questionnaire_id' => (int)$questionnaireId));
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function questionnaireStartSession(PDO $pdo, $questionnaireId) {
	$userId = isset($_SESSION['userid']) && (int)$_SESSION['userid'] > 0 ? (int)$_SESSION['userid'] : null;
	$stmt = $pdo->prepare('INSERT INTO questionnaire_sessions (questionnaire_id, user_id, started_at, completion_status) VALUES (:questionnaire_id, :user_id, NOW(), :completion_status)');
	$stmt->bindValue(':questionnaire_id', (int)$questionnaireId, PDO::PARAM_INT);
	if ($userId === null) {
		$stmt->bindValue(':user_id', null, PDO::PARAM_NULL);
	} else {
		$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
	}
	$stmt->bindValue(':completion_status', 'in_progress', PDO::PARAM_STR);
	$stmt->execute();
	return (int)$pdo->lastInsertId();
}

function questionnaireReadDemographicsFromPost(array $fields) {
	$values = array();
	foreach ($fields as $field) {
		$key = isset($field['field_key']) ? (string)$field['field_key'] : '';
		if ($key === '') {
			continue;
		}
		$values[$key] = isset($_POST['demographics'][$key]) ? trim((string)$_POST['demographics'][$key]) : '';
	}
	return $values;
}

function questionnaireReadItemsFromPost(array $items) {
	$values = array();
	foreach ($items as $item) {
		$itemId = isset($item['id']) ? (int)$item['id'] : 0;
		if ($itemId <= 0) {
			continue;
		}
		$raw = isset($_POST['responses'][$itemId]) ? (string)$_POST['responses'][$itemId] : '';
		$values[$itemId] = trim($raw);
	}
	return $values;
}

function questionnaireValidateDemographics(array $fields, array $rawValues) {
	$errors = array();
	$values = array();

	foreach ($fields as $field) {
		$key = isset($field['field_key']) ? (string)$field['field_key'] : '';
		if ($key === '') {
			continue;
		}
		$label = isset($field['label']) && (string)$field['label'] !== '' ? (string)$field['label'] : $key;
		$type = isset($field['field_type']) ? strtolower((string)$field['field_type']) : 'text';
		$isRequired = isset($field['is_required']) && (int)$field['is_required'] === 1;
		$value = array_key_exists($key, $rawValues) ? trim((string)$rawValues[$key]) : '';

		if ($isRequired && $value === '') {
			$errors[] = questionnaireBuildValidationError(
				'Pflichtfeld fehlt: '.$label,
				'required',
				array('field_key' => $key, 'target_id' => 'demo_'.$key)
			);
			continue;
		}
		if ($value === '') {
			$values[$key] = '';
			continue;
		}

		$rules = null;
		if (isset($field['allowed_values_json']) && $field['allowed_values_json'] !== null && trim((string)$field['allowed_values_json']) !== '') {
			$rules = json_decode((string)$field['allowed_values_json'], true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$errors[] = questionnaireBuildValidationError(
					'Ungültige Feldkonfiguration bei '.$label.'.',
					'format',
					array('field_key' => $key, 'target_id' => 'demo_'.$key)
				);
				continue;
			}
		}

		if ($type === 'integer' || $type === 'number') {
			if (!preg_match('/^-?\d+$/', $value)) {
				$errors[] = questionnaireBuildValidationError(
					$label.' muss eine ganze Zahl sein.',
					'format',
					array('field_key' => $key, 'target_id' => 'demo_'.$key)
				);
				continue;
			}
			$number = (int)$value;
			if (is_array($rules)) {
				if (isset($rules['min']) && $number < (int)$rules['min']) {
					$errors[] = questionnaireBuildValidationError(
						$label.' ist zu klein.',
						'range',
						array('field_key' => $key, 'target_id' => 'demo_'.$key)
					);
				}
				if (isset($rules['max']) && $number > (int)$rules['max']) {
					$errors[] = questionnaireBuildValidationError(
						$label.' ist zu groß.',
						'range',
						array('field_key' => $key, 'target_id' => 'demo_'.$key)
					);
				}
			}
			$values[$key] = (string)$number;
			continue;
		}

		if (is_array($rules)) {
			$allowed = array();
			if (array_values($rules) === $rules) {
				$allowed = $rules;
			} elseif (isset($rules['options']) && is_array($rules['options'])) {
				$allowed = $rules['options'];
			}
			if (!empty($allowed) && !in_array($value, $allowed, true)) {
				$errors[] = questionnaireBuildValidationError(
					$label.' enthält einen ungültigen Wert.',
					'format',
					array('field_key' => $key, 'target_id' => 'demo_'.$key)
				);
				continue;
			}
			if (isset($rules['regex']) && is_string($rules['regex']) && $rules['regex'] !== '') {
				if (@preg_match($rules['regex'], '') === false || preg_match($rules['regex'], $value) !== 1) {
					$errors[] = questionnaireBuildValidationError(
						$label.' entspricht nicht dem erforderlichen Format.',
						'format',
						array('field_key' => $key, 'target_id' => 'demo_'.$key)
					);
					continue;
				}
			}
		}
		if (mb_strlen($value) > 255) {
			$errors[] = questionnaireBuildValidationError(
				$label.' ist zu lang (max. 255 Zeichen).',
				'range',
				array('field_key' => $key, 'target_id' => 'demo_'.$key)
			);
			continue;
		}
		$values[$key] = $value;
	}

	return array('errors' => $errors, 'values' => $values);
}

function questionnaireValidateItems(array $questionnaire, array $items, array $rawValues) {
	$errors = array();
	$values = array();

	foreach ($items as $item) {
		$itemId = isset($item['id']) ? (int)$item['id'] : 0;
		if ($itemId <= 0) {
			continue;
		}
		$isRequired = isset($item['is_required']) && (int)$item['is_required'] === 1;
		$raw = array_key_exists($itemId, $rawValues) ? trim((string)$rawValues[$itemId]) : '';
		$itemLabel = 'Item '.(int)$item['item_no'];

		if ($isRequired && $raw === '') {
			$errors[] = questionnaireBuildValidationError(
				$itemLabel.' ist ein Pflichtfeld.',
				'required',
				array('item_id' => $itemId, 'target_id' => 'item_'.$itemId)
			);
			continue;
		}
		if ($raw === '') {
			$values[$itemId] = null;
			continue;
		}
		if (!preg_match('/^-?\d+$/', $raw)) {
			$errors[] = questionnaireBuildValidationError(
				$itemLabel.' muss eine ganze Zahl sein.',
				'format',
				array('item_id' => $itemId, 'target_id' => 'item_'.$itemId)
			);
			continue;
		}
		$value = (int)$raw;
		$min = isset($questionnaire['likert_min']) ? (int)$questionnaire['likert_min'] : 1;
		$max = isset($questionnaire['likert_max']) ? (int)$questionnaire['likert_max'] : 5;
		if ($value < $min || $value > $max) {
			$errors[] = questionnaireBuildValidationError(
				$itemLabel.' liegt außerhalb des erlaubten Bereichs ('.$min.' bis '.$max.').',
				'range',
				array('item_id' => $itemId, 'target_id' => 'item_'.$itemId)
			);
			continue;
		}
		if (isset($questionnaire['scale_type']) && (string)$questionnaire['scale_type'] === 'binary' && !($value === 0 || $value === 1)) {
			$errors[] = questionnaireBuildValidationError(
				$itemLabel.' muss bei binärer Skala 0 oder 1 sein.',
				'format',
				array('item_id' => $itemId, 'target_id' => 'item_'.$itemId)
			);
			continue;
		}
		$values[$itemId] = $value;
	}

	return array('errors' => $errors, 'values' => $values);
}

function questionnaireRenderDemographicInputs(array $fields, array $values, array $errorsByFieldKey = array()) {
	if (empty($fields)) {
		echo '<p>Keine demografischen Pflichtangaben konfiguriert.</p>';
		return;
	}
	echo '<p class="required-note"><span class="required-marker" aria-hidden="true">*</span> Pflichtfeld (muss ausgefüllt werden)</p>';
	echo '<div class="form-group">';
	foreach ($fields as $field) {
		$key = isset($field['field_key']) ? (string)$field['field_key'] : '';
		if ($key === '') {
			continue;
		}
		$label = isset($field['label']) ? (string)$field['label'] : $key;
		$type = isset($field['field_type']) ? strtolower((string)$field['field_type']) : 'text';
		$isRequired = isset($field['is_required']) && (int)$field['is_required'] === 1;
		$current = array_key_exists($key, $values) ? (string)$values[$key] : '';
		$fieldId = 'demo_'.htmlentities($key);
		$hintId = 'demo_'.htmlentities($key).'_hint';
		$errorId = 'demo_'.htmlentities($key).'_error';
		$fieldError = isset($errorsByFieldKey[$key]['message']) ? (string)$errorsByFieldKey[$key]['message'] : '';
		$describedBy = $hintId.($fieldError !== '' ? ' '.$errorId : '');
		$invalidAttr = $fieldError !== '' ? ' aria-invalid="true"' : '';
		$requiredText = $isRequired ? '<span class="required-marker" aria-hidden="true">*</span><span class="required-text"> Pflichtfeld</span>' : '';
		$rules = array();
		if (isset($field['allowed_values_json']) && $field['allowed_values_json'] !== null && trim((string)$field['allowed_values_json']) !== '') {
			$decodedRules = json_decode((string)$field['allowed_values_json'], true);
			if (is_array($decodedRules)) {
				$rules = $decodedRules;
			}
		}
		echo '<div class="qnr-form-row form-field">';
		echo '<label for="'.$fieldId.'">'.htmlentities($label).$requiredText.'</label>';
		if ($type === 'integer' || $type === 'number') {
			$minAttr = isset($rules['min']) && is_numeric($rules['min']) ? ' min="'.(float)$rules['min'].'"' : '';
			$maxAttr = isset($rules['max']) && is_numeric($rules['max']) ? ' max="'.(float)$rules['max'].'"' : '';
			$stepAttr = $type === 'integer' ? ' step="1"' : ' step="any"';
			$inputMode = $type === 'integer' ? 'numeric' : 'decimal';
			echo '<input class="qnr-input" type="number" id="'.$fieldId.'" name="demographics['.htmlentities($key).']" value="'.htmlentities($current).'" inputmode="'.$inputMode.'"'.$minAttr.$maxAttr.$stepAttr.' aria-describedby="'.$describedBy.'" '.($isRequired ? 'required' : '').$invalidAttr.'>';
			echo '<p class="field-hint" id="'.$hintId.'">Numerischer Wert'.($isRequired ? ', erforderlich' : ', optional').'.</p>';
		} else {
			$hintText = 'Textfeld, maximal 255 Zeichen'.($isRequired ? ', erforderlich' : ', optional').'.';
			echo '<input class="qnr-input" type="text" id="'.$fieldId.'" name="demographics['.htmlentities($key).']" maxlength="255" value="'.htmlentities($current).'" aria-describedby="'.$describedBy.'" '.($isRequired ? 'required' : '').$invalidAttr.'>';
			echo '<p class="field-hint" id="'.$hintId.'">'.$hintText.'</p>';
		}
		echo '<p class="qnr-field-error" id="'.$errorId.'" role="status" aria-live="polite">'.htmlentities($fieldError).'</p>';
		echo '</div>';
	}
	echo '</div>';
}

function questionnaireRenderHiddenDemographics(array $fields, array $values) {
	foreach ($fields as $field) {
		$key = isset($field['field_key']) ? (string)$field['field_key'] : '';
		if ($key === '') {
			continue;
		}
		$current = array_key_exists($key, $values) ? (string)$values[$key] : '';
		echo '<input type="hidden" name="demographics['.htmlentities($key).']" value="'.htmlentities($current).'">';
	}
}

function questionnaireRenderItems(array $questionnaire, array $items, array $values, array $errorsByItemId = array()) {
	if (empty($items)) {
		echo '<p>Für diesen Fragebogen sind keine Items konfiguriert.</p>';
		return;
	}
	$firstItem = reset($items);
	$globalMin = isset($firstItem['likert_min']) ? (int)$firstItem['likert_min'] : null;
	$globalMax = isset($firstItem['likert_max']) ? (int)$firstItem['likert_max'] : null;
	$isUniformScale = $globalMin !== null && $globalMax !== null;
	foreach ($items as $item) {
		if ((int)$item['likert_min'] !== $globalMin || (int)$item['likert_max'] !== $globalMax) {
			$isUniformScale = false;
			break;
		}
	}

	echo '<p class="required-note"><span class="required-marker" aria-hidden="true">*</span> Pflichtfeld (muss beantwortet werden)</p>';
	echo '<section class="qnr-items-header'.($isUniformScale ? ' qnr-items-header--sticky' : '').'" aria-labelledby="qnr-items-header-title">';
	echo '<h3 id="qnr-items-header-title">Hinweis zur Beantwortung</h3>';
	if ($isUniformScale) {
		echo '<p>Bitte beantworte jedes Item auf der Skala von '.$globalMin.' bis '.$globalMax.'. ';
		echo '<strong>'.$globalMin.' = trifft gar nicht zu</strong>, <strong>'.$globalMax.' = trifft völlig zu</strong>.</p>';
	} else {
		echo '<p>Bitte beantworte jedes Item gemäß der jeweiligen Skala. Die Skalenanker lauten links <strong>trifft gar nicht zu</strong> und rechts <strong>trifft völlig zu</strong>.</p>';
	}
	echo '</section>';
	echo '<div class="form-group">';
	foreach ($items as $item) {
		$itemId = (int)$item['id'];
		$min = isset($questionnaire['likert_min']) ? (int)$questionnaire['likert_min'] : 1;
		$max = isset($questionnaire['likert_max']) ? (int)$questionnaire['likert_max'] : 5;
		$isRequired = isset($item['is_required']) && (int)$item['is_required'] === 1;
		$current = array_key_exists($itemId, $values) ? (string)$values[$itemId] : '';
		$legendId = 'item_'.$itemId.'_legend';
		$hintId = 'item_'.$itemId.'_hint';
		$errorId = 'item_'.$itemId.'_error';
		$fieldError = isset($errorsByItemId[$itemId]['message']) ? (string)$errorsByItemId[$itemId]['message'] : '';
		echo '<fieldset class="qnr-form-row form-field qnr-item-card" id="item_'.$itemId.'" data-item-id="'.$itemId.'">';
		echo '<legend id="'.$legendId.'">'.(int)$item['item_no'].'. '.htmlentities((string)$item['item_text']).($isRequired ? ' <span class="required-marker" aria-hidden="true">*</span><span class="required-text"> Pflichtfeld</span>' : '').'</legend>';
		if (!$isUniformScale) {
			echo '<p class="field-hint" id="'.$hintId.'">Skala '.$min.' bis '.$max.'.</p>';
		} else {
			echo '<p class="field-hint sr-only" id="'.$hintId.'">Skala '.$min.' bis '.$max.'.</p>';
		}
		echo '<div class="likert-scale">';
		echo '<span class="likert-anchor likert-anchor-left">trifft gar nicht zu</span>';
		$describedBy = $hintId.($fieldError !== '' ? ' '.$errorId : '');
		echo '<div class="radio-group" role="radiogroup" aria-labelledby="'.$legendId.'" aria-describedby="'.$describedBy.'" data-item-id="'.$itemId.'">';
		for ($value = $min; $value <= $max; $value++) {
			$checked = ($current !== '' && (int)$current === $value) ? 'checked' : '';
			$radioId = 'item_'.$itemId.'_value_'.$value;
			$invalidAttr = $fieldError !== '' ? ' aria-invalid="true"' : '';
			echo '<div class="radio-option"><input type="radio" id="'.$radioId.'" name="responses['.$itemId.']" value="'.$value.'" '.$checked.' aria-describedby="'.$describedBy.'" '.($isRequired ? 'required' : '').$invalidAttr.'><label for="'.$radioId.'">'.$value.'</label></div>';
		}
		echo '</div>';
		echo '<span class="likert-anchor likert-anchor-right">trifft völlig zu</span>';
		echo '</div>';
		echo '<p class="qnr-field-error" id="'.$errorId.'" role="status" aria-live="polite">'.htmlentities($fieldError).'</p>';
		echo '</fieldset>';
	}
	echo '</div>';
}

function questionnaireRenderFrontendItemsEnhancementScript() {
	echo '<script>';
	echo '(function () {';
	echo 'var groups = document.querySelectorAll(".radio-group[role=\'radiogroup\']");';
	echo 'if (!groups.length) { return; }';
	echo 'var updateLastAnswered = function (radio) {';
	echo 'if (!radio) { return; }';
	echo 'var card = radio.closest(".qnr-item-card");';
	echo 'if (!card) { return; }';
	echo 'document.querySelectorAll(".qnr-item-card.is-last-answered").forEach(function (node) { node.classList.remove("is-last-answered"); });';
	echo 'card.classList.add("is-last-answered");';
	echo 'if (window.sessionStorage) { sessionStorage.setItem("qnr_last_answered_item", card.id || ""); }';
	echo '};';
	echo 'var restoreLastAnswered = function () {';
	echo 'if (!window.sessionStorage) { return; }';
	echo 'var itemId = sessionStorage.getItem("qnr_last_answered_item");';
	echo 'if (!itemId) { return; }';
	echo 'var card = document.getElementById(itemId);';
	echo 'if (card && card.classList.contains("qnr-item-card")) { card.classList.add("is-last-answered"); }';
	echo '};';
	echo 'var moveFocus = function (radios, currentIndex, direction) {';
	echo 'if (!radios.length) { return; }';
	echo 'var nextIndex = (currentIndex + direction + radios.length) % radios.length;';
	echo 'radios[nextIndex].checked = true;';
	echo 'radios[nextIndex].focus();';
	echo 'radios[nextIndex].dispatchEvent(new Event("input", { bubbles: true }));';
	echo 'radios[nextIndex].dispatchEvent(new Event("change", { bubbles: true }));';
	echo '};';
	echo 'groups.forEach(function (group) {';
	echo 'var radios = Array.prototype.slice.call(group.querySelectorAll(\'input[type="radio"]\'));';
	echo 'if (!radios.length) { return; }';
	echo 'group.addEventListener("keydown", function (event) {';
	echo 'var target = event.target;';
	echo 'if (!target || target.type !== "radio") { return; }';
	echo 'var index = radios.indexOf(target);';
	echo 'if (index < 0) { return; }';
	echo 'if (event.key === "ArrowRight" || event.key === "ArrowDown") { event.preventDefault(); moveFocus(radios, index, 1); }';
	echo 'if (event.key === "ArrowLeft" || event.key === "ArrowUp") { event.preventDefault(); moveFocus(radios, index, -1); }';
	echo 'if (event.key === "Home") { event.preventDefault(); radios[0].checked = true; radios[0].focus(); radios[0].dispatchEvent(new Event("change", { bubbles: true })); }';
	echo 'if (event.key === "End") { event.preventDefault(); radios[radios.length - 1].checked = true; radios[radios.length - 1].focus(); radios[radios.length - 1].dispatchEvent(new Event("change", { bubbles: true })); }';
	echo '});';
	echo 'group.addEventListener("change", function (event) { if (event.target && event.target.type === "radio") { updateLastAnswered(event.target); } });';
	echo '});';
	echo 'restoreLastAnswered();';
	echo '}());';
	echo '</script>';
}

function questionnaireBuildValidationError($message, $errorType, array $context = array()) {
	$sanitizedMessage = questionnaireSanitizeMessage($message);
	$error = array(
		'message' => $sanitizedMessage,
		'error_type' => in_array($errorType, array('required', 'range', 'format', 'system'), true) ? $errorType : 'format'
	);
	if (isset($context['field_key']) && (string)$context['field_key'] !== '') {
		$error['field_key'] = (string)$context['field_key'];
	}
	if (isset($context['item_id']) && (int)$context['item_id'] > 0) {
		$error['item_id'] = (int)$context['item_id'];
	}
	if (isset($context['target_id']) && (string)$context['target_id'] !== '') {
		$error['target_id'] = (string)$context['target_id'];
	}
	return $error;
}

function questionnaireSanitizeMessage($message) {
	$normalized = trim((string)$message);
	$normalized = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $normalized);
	return trim((string)$normalized);
}

function questionnaireCollectFieldErrors(array $errors) {
	$result = array(
		'general' => array(),
		'field_keys' => array(),
		'item_ids' => array(),
		'summary' => array()
	);
	foreach ($errors as $error) {
		if (!is_array($error)) {
			$result['general'][] = questionnaireBuildValidationError((string)$error, 'system');
			continue;
		}
		$normalized = questionnaireBuildValidationError(
			isset($error['message']) ? (string)$error['message'] : '',
			isset($error['error_type']) ? (string)$error['error_type'] : 'format',
			$error
		);
		$isFieldError = false;
		if (isset($normalized['field_key'])) {
			$isFieldError = true;
			if (!isset($result['field_keys'][$normalized['field_key']])) {
				$result['field_keys'][$normalized['field_key']] = $normalized;
			}
		}
		if (isset($normalized['item_id'])) {
			$isFieldError = true;
			if (!isset($result['item_ids'][$normalized['item_id']])) {
				$result['item_ids'][$normalized['item_id']] = $normalized;
			}
		}
		if ($isFieldError && isset($normalized['target_id']) && (string)$normalized['target_id'] !== '') {
			$result['summary'][] = $normalized;
		} elseif (!$isFieldError) {
			$result['general'][] = $normalized;
		}
	}
	return $result;
}

function questionnaireGetErrorPresentation($errorType) {
	if ($errorType === 'required') {
		return array('icon' => '⛔', 'label' => 'Pflichtfeldfehler', 'color' => '#b71c1c');
	}
	if ($errorType === 'range') {
		return array('icon' => '📏', 'label' => 'Bereichsfehler', 'color' => '#e65100');
	}
	return array('icon' => '⚠️', 'label' => 'Formatfehler', 'color' => '#1565c0');
}

function questionnaireRenderErrorSummary(array $summaryErrors) {
	if (empty($summaryErrors)) {
		return;
	}
	echo '<div style="border:2px solid #b71c1c; background:#fff5f5; padding:12px; margin:10px 0;" aria-labelledby="questionnaire-error-summary-title">';
	echo '<h3 id="questionnaire-error-summary-title" style="margin:0 0 8px 0;">Bitte korrigiere folgende Felder:</h3>';
	echo '<ul style="margin:0; padding-left:20px;">';
	foreach ($summaryErrors as $error) {
		$presentation = questionnaireGetErrorPresentation(isset($error['error_type']) ? (string)$error['error_type'] : 'format');
		$targetId = isset($error['target_id']) ? (string)$error['target_id'] : '';
		if ($targetId === '') {
			continue;
		}
		echo '<li><a href="#'.htmlentities($targetId).'" style="color:'.htmlentities($presentation['color']).';">';
		echo htmlentities($presentation['icon'].' '.$presentation['label'].': '.(string)$error['message']);
		echo '</a></li>';
	}
	echo '</ul>';
	echo '</div>';
}

function questionnaireCompleteSessionIdempotent(PDO $pdo, $sessionId, array $questionnaire, array $demographics, array $responses) {
	$pdo->beginTransaction();
	try {
		$sessionStmt = $pdo->prepare('SELECT id, questionnaire_id, finished_at, completion_status FROM questionnaire_sessions WHERE id = :id FOR UPDATE');
		$sessionStmt->execute(array(':id' => (int)$sessionId));
		$session = $sessionStmt->fetch(PDO::FETCH_ASSOC);
		if (!$session) {
			throw new RuntimeException('Session nicht gefunden.');
		}
		if ((int)$session['questionnaire_id'] !== (int)$questionnaire['id']) {
			throw new RuntimeException('Session passt nicht zum Fragebogen.');
		}

		if ($session['finished_at'] !== null || $session['completion_status'] === 'completed') {
			$result = questionnaireLoadStoredResult($pdo, (int)$sessionId);
			$result['quality'] = questionnaireEvaluateSessionQuality($pdo, $questionnaire, (int)$sessionId);
			$result['already_completed'] = true;
			$pdo->commit();
			return $result;
		}

		$deleteDemoStmt = $pdo->prepare('DELETE FROM questionnaire_session_demographics WHERE session_id = :session_id');
		$deleteDemoStmt->execute(array(':session_id' => (int)$sessionId));
		$insertDemoStmt = $pdo->prepare('INSERT INTO questionnaire_session_demographics (session_id, field_key, demographic_value) VALUES (:session_id, :field_key, :demographic_value)');
		foreach ($demographics as $fieldKey => $fieldValue) {
			$insertDemoStmt->execute(array(
				':session_id' => (int)$sessionId,
				':field_key' => (string)$fieldKey,
				':demographic_value' => (string)$fieldValue
			));
		}

		$itemStmt = $pdo->prepare('SELECT id, is_reversed, subscale_key FROM questionnaire_items WHERE questionnaire_id = :questionnaire_id');
		$itemStmt->execute(array(':questionnaire_id' => (int)$questionnaire['id']));
		$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
		$itemMap = array();
		foreach ($items as $item) {
			$itemMap[(int)$item['id']] = $item;
		}

		$deleteAnswerStmt = $pdo->prepare('DELETE FROM questionnaire_answers WHERE session_id = :session_id');
		$deleteAnswerStmt->execute(array(':session_id' => (int)$sessionId));
		$insertAnswerStmt = $pdo->prepare('INSERT INTO questionnaire_answers (session_id, item_id, raw_value, scored_value) VALUES (:session_id, :item_id, :raw_value, :scored_value)');

		$totalSum = 0.0;
		$totalCount = 0;
		$subscaleBuckets = array();
		$questionnaireMin = isset($questionnaire['likert_min']) ? (float)$questionnaire['likert_min'] : 1.0;
		$questionnaireMax = isset($questionnaire['likert_max']) ? (float)$questionnaire['likert_max'] : 5.0;
		foreach ($responses as $itemId => $rawValue) {
			if (!isset($itemMap[(int)$itemId]) || $rawValue === null) {
				continue;
			}
			$item = $itemMap[(int)$itemId];
			$rawFloat = (float)$rawValue;
			$scoredValue = (int)$item['is_reversed'] === 1 ? (($questionnaireMin + $questionnaireMax) - $rawFloat) : $rawFloat;
			$insertAnswerStmt->execute(array(
				':session_id' => (int)$sessionId,
				':item_id' => (int)$itemId,
				':raw_value' => $rawFloat,
				':scored_value' => $scoredValue
			));
			$totalSum += $scoredValue;
			$totalCount++;
			$subscaleKey = isset($item['subscale_key']) ? trim((string)$item['subscale_key']) : '';
			if ($subscaleKey !== '') {
				if (!isset($subscaleBuckets[$subscaleKey])) {
					$subscaleBuckets[$subscaleKey] = array('sum' => 0.0, 'count' => 0);
				}
				$subscaleBuckets[$subscaleKey]['sum'] += $scoredValue;
				$subscaleBuckets[$subscaleKey]['count']++;
			}
		}

		$deleteScoreStmt = $pdo->prepare('DELETE FROM questionnaire_scores WHERE session_id = :session_id');
		$deleteScoreStmt->execute(array(':session_id' => (int)$sessionId));
		$insertScoreStmt = $pdo->prepare('INSERT INTO questionnaire_scores (session_id, score_type, score_key, raw_mean, raw_sum, n_answered) VALUES (:session_id, :score_type, :score_key, :raw_mean, :raw_sum, :n_answered)');
		$totalMean = $totalCount > 0 ? ($totalSum / $totalCount) : null;
		$insertScoreStmt->execute(array(
			':session_id' => (int)$sessionId,
			':score_type' => 'total',
			':score_key' => 'total',
			':raw_mean' => $totalMean,
			':raw_sum' => $totalCount > 0 ? $totalSum : null,
			':n_answered' => $totalCount > 0 ? $totalCount : null
		));

		foreach ($subscaleBuckets as $subscaleKey => $bucket) {
			$mean = $bucket['count'] > 0 ? ($bucket['sum'] / $bucket['count']) : null;
			$insertScoreStmt->execute(array(
				':session_id' => (int)$sessionId,
				':score_type' => 'subscale',
				':score_key' => $subscaleKey,
				':raw_mean' => $mean,
				':raw_sum' => $bucket['count'] > 0 ? $bucket['sum'] : null,
				':n_answered' => $bucket['count'] > 0 ? $bucket['count'] : null
			));
		}

		$updateSessionStmt = $pdo->prepare('UPDATE questionnaire_sessions SET completion_status = :completion_status, finished_at = NOW() WHERE id = :id');
		$updateSessionStmt->execute(array(
			':completion_status' => 'completed',
			':id' => (int)$sessionId
		));

		$result = questionnaireLoadStoredResult($pdo, (int)$sessionId);
		$result['quality'] = questionnaireEvaluateSessionQuality($pdo, $questionnaire, (int)$sessionId);
		$result['already_completed'] = false;
		$pdo->commit();
		return $result;
	} catch (Throwable $e) {
		if ($pdo->inTransaction()) {
			$pdo->rollBack();
		}
		throw $e;
	}
}

function questionnaireLoadStoredResult(PDO $pdo, $sessionId) {
	$stmt = $pdo->prepare('SELECT score_type, score_key, raw_mean, raw_sum FROM questionnaire_scores WHERE session_id = :session_id ORDER BY score_type ASC, score_key ASC');
	$stmt->execute(array(':session_id' => (int)$sessionId));
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$result = array(
		'session_id' => (int)$sessionId,
		'total_mean' => null,
		'total_sum' => null,
		'subscales' => array()
	);
	foreach ($rows as $row) {
		if ($row['score_type'] === 'total' && $row['score_key'] === 'total') {
			$result['total_mean'] = $row['raw_mean'];
			$result['total_sum'] = $row['raw_sum'];
		} elseif ($row['score_type'] === 'subscale') {
			$result['subscales'][] = $row;
		}
	}
	return $result;
}

function questionnaireLoadResultBySession(PDO $pdo, $sessionId) {
	$sessionStmt = $pdo->prepare('
		SELECT qs.id, qs.questionnaire_id, qs.completion_status, qs.finished_at, q.slug, q.title, q.standard_rules_json
		FROM questionnaire_sessions qs
		INNER JOIN questionnaires q ON q.id = qs.questionnaire_id
		WHERE qs.id = :id
		LIMIT 1
	');
	$sessionStmt->execute(array(':id' => (int)$sessionId));
	$session = $sessionStmt->fetch(PDO::FETCH_ASSOC);
	if (!$session || (string)$session['completion_status'] !== 'completed' || empty($session['finished_at'])) {
		return null;
	}

	$result = questionnaireLoadStoredResult($pdo, (int)$sessionId);
	$result['questionnaire'] = array(
		'id' => (int)$session['questionnaire_id'],
		'slug' => (string)$session['slug'],
		'title' => (string)$session['title']
	);
	$result['quality'] = questionnaireEvaluateSessionQuality($pdo, $session, (int)$sessionId);
	return $result;
}


function questionnaireEnsureSchema(PDO $pdo) {
	static $alreadyChecked = false;
	if ($alreadyChecked) {
		return array('checked' => true, 'applied' => false, 'created_tables' => array());
	}
	$alreadyChecked = true;

	$driver = strtolower((string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
	if ($driver !== 'mysql') {
		return array('checked' => false, 'applied' => false, 'created_tables' => array());
	}

	$tableDefinitions = questionnaireSchemaTableDefinitions();
	$missingTables = array();
	foreach (array_keys($tableDefinitions) as $tableName) {
		$tableStmt = $pdo->prepare('SHOW TABLES LIKE :table_name');
		$tableStmt->execute(array(':table_name' => $tableName));
		if (!$tableStmt->fetch()) {
			$missingTables[] = $tableName;
		}
	}

	$applied = false;
	if (!empty($missingTables)) {
		$pdo->beginTransaction();
		try {
			foreach ($tableDefinitions as $tableSql) {
				$pdo->exec($tableSql);
			}
			$pdo->commit();
			$applied = true;
		} catch (Throwable $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			throw $e;
		}
	}

	$migrationApplied = questionnaireRunScaleMigration($pdo);
	return array('checked' => true, 'applied' => ($applied || $migrationApplied), 'created_tables' => $missingTables);
}

function questionnaireRunScaleMigration(PDO $pdo) {
	$changed = false;
	$columns = array(
		"ALTER TABLE questionnaires ADD COLUMN `scale_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'likert' AFTER `standard_rules_json`",
		"ALTER TABLE questionnaires ADD COLUMN `likert_min` int(10) NOT NULL DEFAULT 1 AFTER `scale_type`",
		"ALTER TABLE questionnaires ADD COLUMN `likert_max` int(10) NOT NULL DEFAULT 5 AFTER `likert_min`",
		"ALTER TABLE questionnaires ADD COLUMN `scale_labels_json` longtext COLLATE utf8mb4_unicode_ci NULL AFTER `likert_max`",
		"ALTER TABLE questionnaires ADD COLUMN `raw_mapping_json` longtext COLLATE utf8mb4_unicode_ci NULL AFTER `scale_labels_json`",
		"ALTER TABLE questionnaires ADD COLUMN `needs_manual_scale_cleanup` tinyint(1) NOT NULL DEFAULT 0 AFTER `raw_mapping_json`"
	);
	foreach ($columns as $sql) {
		try {
			$pdo->exec($sql);
			$changed = true;
		} catch (Throwable $e) {
		}
	}
	try {
		$pdo->exec('CREATE TABLE IF NOT EXISTS `questionnaire_scale_options` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`questionnaire_id` int(10) unsigned NOT NULL,
			`raw_value` decimal(10,4) NOT NULL,
			`mapped_value` decimal(10,4) NOT NULL,
			`option_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uniq_questionnaire_scale_options_raw` (`questionnaire_id`,`raw_value`),
			KEY `idx_questionnaire_scale_options_questionnaire_id` (`questionnaire_id`),
			CONSTRAINT `fk_questionnaire_scale_options_questionnaire_id`
				FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
	} catch (Throwable $e) {
	}

	$backfillSql = "
		UPDATE questionnaires q
		LEFT JOIN (
			SELECT a.questionnaire_id, a.scale_type, a.likert_min, a.likert_max
			FROM questionnaire_items a
			INNER JOIN (
				SELECT questionnaire_id, scale_type, likert_min, likert_max, COUNT(*) AS cnt
				FROM questionnaire_items
				GROUP BY questionnaire_id, scale_type, likert_min, likert_max
			) b ON b.questionnaire_id = a.questionnaire_id AND b.scale_type = a.scale_type AND b.likert_min = a.likert_min AND b.likert_max = a.likert_max
			INNER JOIN (
				SELECT questionnaire_id, MAX(cnt) AS max_cnt
				FROM (
					SELECT questionnaire_id, scale_type, likert_min, likert_max, COUNT(*) AS cnt
					FROM questionnaire_items
					GROUP BY questionnaire_id, scale_type, likert_min, likert_max
				) c
				GROUP BY questionnaire_id
			) d ON d.questionnaire_id = b.questionnaire_id AND d.max_cnt = b.cnt
			GROUP BY a.questionnaire_id, a.scale_type, a.likert_min, a.likert_max
		) top_scale ON top_scale.questionnaire_id = q.id
		LEFT JOIN (
			SELECT questionnaire_id, COUNT(DISTINCT CONCAT(scale_type, ':', likert_min, ':', likert_max)) AS distinct_scale_count
			FROM questionnaire_items
			GROUP BY questionnaire_id
		) consistency ON consistency.questionnaire_id = q.id
		SET
			q.scale_type = COALESCE(top_scale.scale_type, q.scale_type),
			q.likert_min = COALESCE(top_scale.likert_min, q.likert_min),
			q.likert_max = COALESCE(top_scale.likert_max, q.likert_max),
			q.needs_manual_scale_cleanup = CASE WHEN COALESCE(consistency.distinct_scale_count, 0) > 1 THEN 1 ELSE 0 END
	";
	try {
		$stmt = $pdo->prepare($backfillSql);
		$stmt->execute();
		$changed = true;
	} catch (Throwable $e) {
	}
	return $changed;
}

function questionnaireSchemaTableDefinitions() {
	return array(
		'questionnaires' => 'CREATE TABLE IF NOT EXISTS `questionnaires` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
			`title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
			`intro_text` text COLLATE utf8mb4_unicode_ci,
			`standard_rules_json` longtext COLLATE utf8mb4_unicode_ci,
			`scale_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'likert\',
			`likert_min` int(10) NOT NULL DEFAULT 1,
			`likert_max` int(10) NOT NULL DEFAULT 5,
			`scale_labels_json` longtext COLLATE utf8mb4_unicode_ci,
			`raw_mapping_json` longtext COLLATE utf8mb4_unicode_ci,
			`needs_manual_scale_cleanup` tinyint(1) NOT NULL DEFAULT 0,
			`status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
			`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`updated_at` timestamp NULL DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uniq_questionnaires_slug` (`slug`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
		'questionnaire_items' => 'CREATE TABLE IF NOT EXISTS `questionnaire_items` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`questionnaire_id` int(10) unsigned NOT NULL,
			`item_no` int(10) unsigned NOT NULL,
			`item_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
			`scale_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'likert\',
			`likert_min` int(10) NOT NULL,
			`likert_max` int(10) NOT NULL,
			`is_reversed` tinyint(1) NOT NULL DEFAULT 0,
			`subscale_key` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
			`is_required` tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (`id`),
			KEY `idx_questionnaire_items_questionnaire_id` (`questionnaire_id`),
			CONSTRAINT `fk_questionnaire_items_questionnaire_id`
				FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
		'questionnaire_scale_options' => 'CREATE TABLE IF NOT EXISTS `questionnaire_scale_options` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`questionnaire_id` int(10) unsigned NOT NULL,
			`raw_value` decimal(10,4) NOT NULL,
			`mapped_value` decimal(10,4) NOT NULL,
			`option_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uniq_questionnaire_scale_options_raw` (`questionnaire_id`,`raw_value`),
			KEY `idx_questionnaire_scale_options_questionnaire_id` (`questionnaire_id`),
			CONSTRAINT `fk_questionnaire_scale_options_questionnaire_id`
				FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
		'questionnaire_demographic_fields' => 'CREATE TABLE IF NOT EXISTS `questionnaire_demographic_fields` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`questionnaire_id` int(10) unsigned NOT NULL,
			`field_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
			`label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
			`field_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
			`is_required` tinyint(1) NOT NULL DEFAULT 0,
			`allowed_values_json` longtext COLLATE utf8mb4_unicode_ci,
			PRIMARY KEY (`id`),
			KEY `idx_questionnaire_demographic_fields_questionnaire_id` (`questionnaire_id`),
			CONSTRAINT `fk_questionnaire_demographic_fields_questionnaire_id`
				FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
		'questionnaire_sessions' => 'CREATE TABLE IF NOT EXISTS `questionnaire_sessions` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`questionnaire_id` int(10) unsigned NOT NULL,
			`user_id` int(10) unsigned DEFAULT NULL,
			`started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`finished_at` timestamp NULL DEFAULT NULL,
			`completion_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
			PRIMARY KEY (`id`),
			KEY `idx_questionnaire_sessions_questionnaire_id` (`questionnaire_id`),
			KEY `idx_questionnaire_sessions_user_id` (`user_id`),
			CONSTRAINT `fk_questionnaire_sessions_questionnaire_id`
				FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`) ON DELETE CASCADE,
			CONSTRAINT `fk_questionnaire_sessions_user_id`
				FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
		'questionnaire_session_demographics' => 'CREATE TABLE IF NOT EXISTS `questionnaire_session_demographics` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`session_id` int(10) unsigned NOT NULL,
			`field_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
			`demographic_value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
			PRIMARY KEY (`id`),
			KEY `idx_questionnaire_session_demographics_session_id` (`session_id`),
			KEY `idx_questionnaire_session_demographics_field_key` (`field_key`),
			CONSTRAINT `fk_questionnaire_session_demographics_session_id`
				FOREIGN KEY (`session_id`) REFERENCES `questionnaire_sessions` (`id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
		'questionnaire_answers' => 'CREATE TABLE IF NOT EXISTS `questionnaire_answers` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`session_id` int(10) unsigned NOT NULL,
			`item_id` int(10) unsigned NOT NULL,
			`raw_value` decimal(10,4) DEFAULT NULL,
			`scored_value` decimal(10,4) DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `idx_questionnaire_answers_session_id` (`session_id`),
			CONSTRAINT `fk_questionnaire_answers_session_id`
				FOREIGN KEY (`session_id`) REFERENCES `questionnaire_sessions` (`id`) ON DELETE CASCADE,
			CONSTRAINT `fk_questionnaire_answers_item_id`
				FOREIGN KEY (`item_id`) REFERENCES `questionnaire_items` (`id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
		'questionnaire_scores' => 'CREATE TABLE IF NOT EXISTS `questionnaire_scores` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`session_id` int(10) unsigned NOT NULL,
			`score_type` enum(\'total\',\'subscale\') COLLATE utf8mb4_unicode_ci NOT NULL,
			`score_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
			`raw_mean` decimal(10,4) DEFAULT NULL,
			`raw_sum` decimal(10,4) DEFAULT NULL,
			`n_answered` int(10) unsigned DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `idx_questionnaire_scores_session_id` (`session_id`),
			KEY `idx_questionnaire_scores_score_key` (`score_key`),
			CONSTRAINT `fk_questionnaire_scores_session_id`
				FOREIGN KEY (`session_id`) REFERENCES `questionnaire_sessions` (`id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
		'norm_tables' => 'CREATE TABLE IF NOT EXISTS `norm_tables` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`questionnaire_id` int(10) unsigned NOT NULL,
			`name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
			`csv_schema_version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
			`uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`is_active` tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (`id`),
			KEY `idx_norm_tables_questionnaire_id` (`questionnaire_id`),
			CONSTRAINT `fk_norm_tables_questionnaire_id`
				FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
		'norm_groups' => 'CREATE TABLE IF NOT EXISTS `norm_groups` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`norm_table_id` int(10) unsigned NOT NULL,
			`group_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
			`demographic_rule_json` longtext COLLATE utf8mb4_unicode_ci,
			PRIMARY KEY (`id`),
			KEY `idx_norm_groups_norm_table_id` (`norm_table_id`),
			CONSTRAINT `fk_norm_groups_norm_table_id`
				FOREIGN KEY (`norm_table_id`) REFERENCES `norm_tables` (`id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
		'norm_rows' => 'CREATE TABLE IF NOT EXISTS `norm_rows` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`norm_group_id` int(10) unsigned NOT NULL,
			`score_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
			`raw_min` decimal(10,4) DEFAULT NULL,
			`raw_max` decimal(10,4) DEFAULT NULL,
			`norm_label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
			`norm_value` decimal(10,4) DEFAULT NULL,
			`percentile` decimal(6,2) DEFAULT NULL,
			`t_score` decimal(6,2) DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `idx_norm_rows_norm_group_id` (`norm_group_id`),
			KEY `idx_norm_rows_score_key` (`score_key`),
			CONSTRAINT `fk_norm_rows_norm_group_id`
				FOREIGN KEY (`norm_group_id`) REFERENCES `norm_groups` (`id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
		'questionnaire_reports' => 'CREATE TABLE IF NOT EXISTS `questionnaire_reports` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`session_id` int(10) unsigned NOT NULL,
			`report_text_apa7` longtext COLLATE utf8mb4_unicode_ci,
			`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			KEY `idx_questionnaire_reports_session_id` (`session_id`),
			CONSTRAINT `fk_questionnaire_reports_session_id`
				FOREIGN KEY (`session_id`) REFERENCES `questionnaire_sessions` (`id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
	);
}
