<?php
/**
 * Normgruppen-Zuordnung für abgeschlossene Sessions.
 *
 * Erwartete Tabellenstruktur (mindestens):
 * - questionnaire_sessions(id, completion_status)
 * - questionnaire_session_demographics(session_id, field_key, demographic_value)
 * - questionnaire_scores(session_id, score_key, raw_mean, raw_sum)
 * - norm_groups(id, norm_table_id, group_key, demographic_rule_json)
 * - norm_rows(id, norm_group_id, score_key, raw_min, raw_max, t_score, percentile, norm_label)
 */

/**
 * Hauptfunktion:
 * 1) Demografie laden
 * 2) Passende Normgruppe per Regelprüfung auswählen
 * 3) Rohwerte auf Normzeilen mappen
 * 4) Bei fehlender Gruppe auf globale Referenzgruppe zurückfallen
 */
function assign_norms_for_completed_session(PDO $pdo, int $sessionId): array
{
    assert_session_is_completed($pdo, $sessionId);

    $demographics = load_session_demographics($pdo, $sessionId);
    $scores = load_questionnaire_scores($pdo, $sessionId);

    $mappedScores = array();
    $reportFlags = array(
        'used_global_fallback' => false,
        'missing_norm_rows' => array(),
        'unmatched_questionnaires' => array(),
    );

    foreach ($scores as $scoreRow) {
        $questionnaireId = (int) $scoreRow['questionnaire_id'];
        $normGroups = load_norm_groups($pdo, $questionnaireId);

        $selection = select_norm_group_by_demographics($normGroups, $demographics);
        if ($selection['used_global_fallback']) {
            $reportFlags['used_global_fallback'] = true;
            $reportFlags['unmatched_questionnaires'][] = $questionnaireId;
        }

        if ($selection['norm_group'] === null) {
            $mappedScores[] = array(
                'questionnaire_id' => $questionnaireId,
                'scale_key' => $scoreRow['scale_key'],
                'raw_score' => (float) $scoreRow['raw_score'],
                'norm_group_id' => null,
                'norm_row_id' => null,
                't_score' => null,
                'percentile' => null,
                'interpretation' => null,
                'match_status' => 'no_norm_group',
            );
            continue;
        }

        $normGroup = $selection['norm_group'];
        $normRow = map_raw_score_to_norm_row($pdo, $normGroup['id'], $questionnaireId, $scoreRow['scale_key'], (float) $scoreRow['raw_score']);

        if ($normRow === null) {
            $reportFlags['missing_norm_rows'][] = array(
                'questionnaire_id' => $questionnaireId,
                'scale_key' => $scoreRow['scale_key'],
                'raw_score' => (float) $scoreRow['raw_score'],
                'norm_group_id' => (int) $normGroup['id'],
            );
        }

        $mappedScores[] = array(
            'questionnaire_id' => $questionnaireId,
            'scale_key' => $scoreRow['scale_key'],
            'raw_score' => (float) $scoreRow['raw_score'],
            'norm_group_id' => (int) $normGroup['id'],
            'norm_row_id' => $normRow ? (int) $normRow['id'] : null,
            't_score' => $normRow ? (float) $normRow['t_score'] : null,
            'percentile' => $normRow ? (float) $normRow['percentile'] : null,
            'interpretation' => $normRow ? $normRow['interpretation'] : null,
            'match_status' => $normRow ? 'ok' : 'missing_norm_row',
        );
    }

    return array(
        'session_id' => $sessionId,
        'demographics' => $demographics,
        'mapped_scores' => $mappedScores,
        'report_flags' => $reportFlags,
    );
}

function assert_session_is_completed(PDO $pdo, int $sessionId): void
{
    $stmt = $pdo->prepare('SELECT completion_status FROM questionnaire_sessions WHERE id = :id LIMIT 1');
    $stmt->execute(array(':id' => $sessionId));
    $status = $stmt->fetchColumn();

    if ($status === false) {
        throw new RuntimeException('Session nicht gefunden: '.$sessionId);
    }

    if ($status !== 'completed') {
        throw new RuntimeException('Session ist nicht abgeschlossen (completion_status='.$status.').');
    }
}

function load_session_demographics(PDO $pdo, int $sessionId): array
{
    $stmt = $pdo->prepare(
        'SELECT field_key, demographic_value
         FROM questionnaire_session_demographics
         WHERE session_id = :session_id'
    );
    $stmt->execute(array(':session_id' => $sessionId));

    $demographics = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fieldKey = isset($row['field_key']) ? $row['field_key'] : null;
        if ($fieldKey === null) {
            continue;
        }
        $demographics[$fieldKey] = cast_demographic_value($row['demographic_value']);
    }

    return $demographics;
}

function cast_demographic_value($value)
{
    if (is_numeric($value)) {
        return $value + 0;
    }

    $lower = strtolower((string) $value);
    if ($lower === 'true') {
        return true;
    }
    if ($lower === 'false') {
        return false;
    }

    return $value;
}

function load_questionnaire_scores(PDO $pdo, int $sessionId): array
{
    $stmt = $pdo->prepare(
        'SELECT qs.session_id, s.questionnaire_id, qs.score_key, qs.raw_mean, qs.raw_sum
         FROM questionnaire_scores qs
         INNER JOIN questionnaire_sessions s ON s.id = qs.session_id
         WHERE qs.session_id = :session_id'
    );
    $stmt->execute(array(':session_id' => $sessionId));

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $mapped = array();
    foreach ($rows as $row) {
        $scoreKey = isset($row['score_key']) ? (string)$row['score_key'] : '';
        if ($scoreKey === '') {
            continue;
        }
        $rawScore = $row['raw_mean'] !== null ? (float)$row['raw_mean'] : null;
        if ($rawScore === null && $row['raw_sum'] !== null) {
            $rawScore = (float)$row['raw_sum'];
        }
        if ($rawScore === null) {
            continue;
        }
        $mapped[] = array(
            'questionnaire_id' => (int)$row['questionnaire_id'],
            'scale_key' => $scoreKey,
            'raw_score' => $rawScore
        );
    }

    return $mapped;
}

function load_norm_groups(PDO $pdo, int $questionnaireId): array
{
    $stmt = $pdo->prepare(
        'SELECT ng.id, nt.questionnaire_id, ng.group_key, ng.demographic_rule_json, nt.is_active
         FROM norm_groups ng
         INNER JOIN norm_tables nt ON nt.id = ng.norm_table_id
         WHERE nt.questionnaire_id = :questionnaire_id'
    );
    $stmt->execute(array(':questionnaire_id' => $questionnaireId));

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $rule = json_decode((string) $row['demographic_rule_json'], true);
        $row['rule'] = is_array($rule) ? $rule : array('op' => 'all', 'conditions' => array());
    }

    return $rows;
}

/**
 * Priorisierung:
 * 1) Nur Gruppen mit erfüllter Regel
 * 2) Höchster Spezifitäts-Score gewinnt (mehr/bindendere Bedingungen)
 * 3) Bei Gleichstand niedrigste Gruppen-ID (stabil)
 * 4) Wenn keine Gruppe passt -> globale Referenzgruppe
 */
function select_norm_group_by_demographics(array $normGroups, array $demographics): array
{
    $candidates = array();
    $globalFallback = null;

    foreach ($normGroups as $group) {
        if ($globalFallback === null && (!isset($group['rule']['op']) || $group['rule']['op'] === 'all')) {
            $globalFallback = $group;
        }

        if (evaluate_demographic_rule($group['rule'], $demographics)) {
            $candidates[] = array(
                'group' => $group,
                'specificity' => calculate_rule_specificity($group['rule']),
            );
        }
    }

    if (!empty($candidates)) {
        usort($candidates, function ($a, $b) {
            if ($a['specificity'] === $b['specificity']) {
                return (int) $a['group']['id'] <=> (int) $b['group']['id'];
            }
            return $b['specificity'] <=> $a['specificity'];
        });

        return array(
            'norm_group' => $candidates[0]['group'],
            'used_global_fallback' => false,
        );
    }

    return array(
        'norm_group' => $globalFallback,
        'used_global_fallback' => $globalFallback !== null,
    );
}

function map_raw_score_to_norm_row(PDO $pdo, int $normGroupId, int $questionnaireId, string $scaleKey, float $rawScore): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, t_score, percentile, norm_label AS interpretation
         FROM norm_rows
         WHERE norm_group_id = :norm_group_id
           AND score_key = :scale_key
           AND :raw_score >= raw_min
           AND :raw_score <= raw_max
         ORDER BY raw_min DESC
         LIMIT 1'
    );
    $stmt->execute(array(
        ':norm_group_id' => $normGroupId,
        ':scale_key' => $scaleKey,
        ':raw_score' => $rawScore,
    ));

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Rekursive Regel-Auswertung gemäß dokumentiertem JSON-Schema.
 */
function evaluate_demographic_rule(array $rule, array $demographics): bool
{
    if (!isset($rule['op'])) {
        return true;
    }

    $op = $rule['op'];

    if ($op === 'all') {
        $conditions = isset($rule['conditions']) && is_array($rule['conditions']) ? $rule['conditions'] : array();
        foreach ($conditions as $condition) {
            if (!evaluate_demographic_rule($condition, $demographics)) {
                return false;
            }
        }
        return true;
    }

    if ($op === 'any') {
        $conditions = isset($rule['conditions']) && is_array($rule['conditions']) ? $rule['conditions'] : array();
        if (empty($conditions)) {
            return true;
        }
        foreach ($conditions as $condition) {
            if (evaluate_demographic_rule($condition, $demographics)) {
                return true;
            }
        }
        return false;
    }

    if ($op === 'not') {
        if (!isset($rule['condition']) || !is_array($rule['condition'])) {
            return false;
        }
        return !evaluate_demographic_rule($rule['condition'], $demographics);
    }

    if ($op === 'predicate') {
        return evaluate_predicate($rule, $demographics);
    }

    return false;
}

function evaluate_predicate(array $predicate, array $demographics): bool
{
    if (!isset($predicate['field']) || !array_key_exists($predicate['field'], $demographics)) {
        return false;
    }

    $actual = $demographics[$predicate['field']];
    $operator = $predicate['operator'] ?? 'eq';

    if ($operator === 'exists') {
        return true;
    }

    $value = $predicate['value'] ?? null;

    switch ($operator) {
        case 'eq':
            return $actual == $value;
        case 'neq':
            return $actual != $value;
        case 'gt':
            return $actual > $value;
        case 'gte':
            return $actual >= $value;
        case 'lt':
            return $actual < $value;
        case 'lte':
            return $actual <= $value;
        case 'in':
            return is_array($value) && in_array($actual, $value, true);
        case 'between':
            return is_array($value)
                && count($value) === 2
                && $actual >= $value[0]
                && $actual <= $value[1];
        default:
            return false;
    }
}

/**
 * Spezifitäts-Heuristik für Konfliktauflösung.
 * - Mehr Bedingungen => höher
 * - Exakte Vergleiche (eq/in) werden stärker gewichtet als Bereichsoperatoren
 */
function calculate_rule_specificity(array $rule): int
{
    if (!isset($rule['op'])) {
        return 0;
    }

    if ($rule['op'] === 'predicate') {
        $operator = $rule['operator'] ?? 'eq';
        if ($operator === 'eq' || $operator === 'in') {
            return 3;
        }
        if ($operator === 'between' || $operator === 'gte' || $operator === 'lte') {
            return 2;
        }
        return 1;
    }

    if ($rule['op'] === 'not') {
        return 1 + calculate_rule_specificity($rule['condition'] ?? array());
    }

    $conditions = isset($rule['conditions']) && is_array($rule['conditions']) ? $rule['conditions'] : array();
    $score = 0;
    foreach ($conditions as $condition) {
        $score += calculate_rule_specificity($condition);
    }

    return $score;
}
