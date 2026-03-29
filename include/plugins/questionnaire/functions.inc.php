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

// Functions for Plugin Questionnaire

function questionnaire_get_urls() {
	global $options;
	return [
		'questionnaires' => $options['siteurl'].'/backend/questionnaires.php',
		'normtables' => $options['siteurl'].'/backend/normtables.php',
		'results' => $options['siteurl'].'/backend/results.php',
		'frontend' => $options['siteurl'].'/website/questionnaire.php'
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
	echo '<li><a href="'.$urls['frontend'].'">Frontend-Ansicht</a></li>';
	echo '</ul>';
}

function questionnaire_show_frontend() {
	echo '<h1>Fragebogen</h1>';
	echo '<p>Hier können Fragebögen bereitgestellt und ausgefüllt werden.</p>';
}