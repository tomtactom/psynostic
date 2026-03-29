<?php
@session_start();

$show_only_user = 'min_manager';
require_once($_SERVER['DOCUMENT_ROOT'].'/include/database/database.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/include/functions.inc.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/include/settings.inc.php');

header('Content-Type: application/json; charset=utf-8');

function questionnaireItemsApiRespond($statusCode, array $payload)
{
	http_response_code($statusCode);
	echo json_encode($payload);
	exit;
}

function questionnaireItemsNormalizeString($value)
{
	return trim((string)$value);
}

function questionnaireItemsBuildOwnershipColumn(PDO $pdo)
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

function questionnaireItemsLoadQuestionnaire(PDO $pdo, $questionnaireId)
{
	$stmt = $pdo->prepare('SELECT id, slug, title FROM questionnaires WHERE id = :id LIMIT 1');
	$stmt->execute(array(':id' => $questionnaireId));
	$questionnaire = $stmt->fetch(PDO::FETCH_ASSOC);
	return $questionnaire ? $questionnaire : null;
}

function questionnaireItemsEnsureOwnership(PDO $pdo, array $user, $questionnaireId)
{
	$questionnaire = questionnaireItemsLoadQuestionnaire($pdo, $questionnaireId);
	if (!$questionnaire) {
		questionnaireItemsApiRespond(404, array('ok' => false, 'error' => 'Fragebogen nicht gefunden.'));
	}

	if ($user['role'] === 'adminstrator') {
		return $questionnaire;
	}

	$ownerColumn = questionnaireItemsBuildOwnershipColumn($pdo);
	if ($ownerColumn === null) {
		return $questionnaire;
	}

	$ownerSql = 'SELECT '.$ownerColumn.' AS owner_id FROM questionnaires WHERE id = :id LIMIT 1';
	$ownerStmt = $pdo->prepare($ownerSql);
	$ownerStmt->execute(array(':id' => $questionnaireId));
	$ownerId = (int)$ownerStmt->fetchColumn();
	if ($ownerId > 0 && $ownerId !== (int)$user['id']) {
		questionnaireItemsApiRespond(403, array('ok' => false, 'error' => 'Keine Berechtigung für diesen Fragebogen.'));
	}

	return $questionnaire;
}

function questionnaireItemsFetchItems(PDO $pdo, $questionnaireId)
{
	$stmt = $pdo->prepare('SELECT id, questionnaire_id, item_no, item_text, subscale_key, is_reversed FROM questionnaire_items WHERE questionnaire_id = :questionnaire_id ORDER BY item_no ASC, id ASC');
	$stmt->execute(array(':questionnaire_id' => $questionnaireId));
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$items = array();
	foreach ($rows as $row) {
		$items[] = array(
			'id' => (int)$row['id'],
			'questionnaire_id' => (int)$row['questionnaire_id'],
			'item_no' => (int)$row['item_no'],
			'item_text' => (string)$row['item_text'],
			'subscale_key' => $row['subscale_key'] === null ? '' : (string)$row['subscale_key'],
			'is_reversed' => (int)$row['is_reversed'] === 1 ? 1 : 0
		);
	}
	return $items;
}

function questionnaireItemsValidateDraft(array $draft)
{
	$errors = array();
	$itemText = isset($draft['item_text']) ? trim((string)$draft['item_text']) : '';
	$subscaleKey = isset($draft['subscale_key']) ? trim((string)$draft['subscale_key']) : '';
	$isReversed = isset($draft['is_reversed']) && ((string)$draft['is_reversed'] === '1' || (int)$draft['is_reversed'] === 1) ? 1 : 0;

	if ($itemText === '' || mb_strlen($itemText) > 20000) {
		$errors[] = 'Itemtext ist erforderlich und darf maximal 20.000 Zeichen lang sein.';
	}

	if ($subscaleKey !== '' && !preg_match('/^[a-zA-Z0-9_\-]{1,100}$/', $subscaleKey)) {
		$errors[] = 'Subskala darf nur Buchstaben, Zahlen, Unterstriche und Bindestriche enthalten (max. 100 Zeichen).';
	}

	return array(
		'errors' => $errors,
		'item_text' => $itemText,
		'subscale_key' => $subscaleKey,
		'is_reversed' => $isReversed
	);
}

$user = check_user();

if (!isset($_SESSION['questionnaire_items_csrf']) || !is_string($_SESSION['questionnaire_items_csrf']) || $_SESSION['questionnaire_items_csrf'] === '') {
	$_SESSION['questionnaire_items_csrf'] = bin2hex(random_bytes(32));
}

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : 'GET';
$rawBody = file_get_contents('php://input');
$payload = json_decode((string)$rawBody, true);
if (!is_array($payload)) {
	$payload = $_POST;
}

$action = isset($payload['action']) ? questionnaireItemsNormalizeString($payload['action']) : '';
$questionnaireId = isset($payload['questionnaire_id']) ? (int)$payload['questionnaire_id'] : 0;

if ($questionnaireId <= 0) {
	questionnaireItemsApiRespond(400, array('ok' => false, 'error' => 'Ungültige Fragebogen-ID.'));
}

$questionnaire = questionnaireItemsEnsureOwnership($pdo, $user, $questionnaireId);

if ($action === 'list') {
	questionnaireItemsApiRespond(200, array(
		'ok' => true,
		'questionnaire' => $questionnaire,
		'csrf_token' => (string)$_SESSION['questionnaire_items_csrf'],
		'items' => questionnaireItemsFetchItems($pdo, $questionnaireId)
	));
}

if ($method !== 'POST') {
	questionnaireItemsApiRespond(405, array('ok' => false, 'error' => 'Nur POST wird für diese Aktion unterstützt.'));
}

$csrfToken = isset($payload['csrf_token']) ? (string)$payload['csrf_token'] : '';
if (!hash_equals((string)$_SESSION['questionnaire_items_csrf'], $csrfToken)) {
	questionnaireItemsApiRespond(403, array('ok' => false, 'error' => 'CSRF-Token ungültig.'));
}

if ($action === 'delete') {
	$itemId = isset($payload['item_id']) ? (int)$payload['item_id'] : 0;
	if ($itemId <= 0) {
		questionnaireItemsApiRespond(400, array('ok' => false, 'error' => 'Ungültige Item-ID.'));
	}
	$deleteStmt = $pdo->prepare('DELETE FROM questionnaire_items WHERE id = :id AND questionnaire_id = :questionnaire_id LIMIT 1');
	$deleteStmt->execute(array(':id' => $itemId, ':questionnaire_id' => $questionnaireId));
	questionnaireItemsApiRespond(200, array(
		'ok' => true,
		'deleted' => $deleteStmt->rowCount() > 0,
		'items' => questionnaireItemsFetchItems($pdo, $questionnaireId)
	));
}

if ($action === 'reorder') {
	$itemOrder = isset($payload['item_order']) && is_array($payload['item_order']) ? $payload['item_order'] : array();
	$itemOrder = array_values(array_filter(array_map('intval', $itemOrder), function ($value) {
		return $value > 0;
	}));
	if (empty($itemOrder)) {
		questionnaireItemsApiRespond(400, array('ok' => false, 'error' => 'Mindestens eine Item-ID für reorder erforderlich.'));
	}

	$placeholders = implode(',', array_fill(0, count($itemOrder), '?'));
	$checkSql = 'SELECT id FROM questionnaire_items WHERE questionnaire_id = ? AND id IN ('.$placeholders.')';
	$checkStmt = $pdo->prepare($checkSql);
	$checkStmt->execute(array_merge(array($questionnaireId), $itemOrder));
	$existingIds = $checkStmt->fetchAll(PDO::FETCH_COLUMN, 0);
	if (count($existingIds) !== count($itemOrder)) {
		questionnaireItemsApiRespond(400, array('ok' => false, 'error' => 'Ungültige Item-Reihenfolge: mindestens eine Item-ID gehört nicht zum Fragebogen.'));
	}

	try {
		$pdo->beginTransaction();
		$updateStmt = $pdo->prepare('UPDATE questionnaire_items SET item_no = :item_no WHERE id = :id AND questionnaire_id = :questionnaire_id');
		$position = 1;
		foreach ($itemOrder as $itemId) {
			$updateStmt->execute(array(':item_no' => $position, ':id' => $itemId, ':questionnaire_id' => $questionnaireId));
			$position++;
		}
		$pdo->commit();
	} catch (Exception $e) {
		if ($pdo->inTransaction()) {
			$pdo->rollBack();
		}
		questionnaireItemsApiRespond(500, array('ok' => false, 'error' => 'Reihenfolge konnte nicht gespeichert werden.'));
	}

	questionnaireItemsApiRespond(200, array('ok' => true, 'items' => questionnaireItemsFetchItems($pdo, $questionnaireId)));
}

if ($action === 'upsert_batch') {
	$drafts = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : array();
	if (empty($drafts)) {
		questionnaireItemsApiRespond(400, array('ok' => false, 'error' => 'Keine Items zum Speichern übergeben.'));
	}

	$validatedDrafts = array();
	foreach ($drafts as $index => $draft) {
		if (!is_array($draft)) {
			questionnaireItemsApiRespond(400, array('ok' => false, 'error' => 'Ungültiges Item im Batch an Position '.((int)$index + 1).'.'));
		}
		$itemId = isset($draft['id']) ? (int)$draft['id'] : 0;
		$clientId = isset($draft['client_id']) ? questionnaireItemsNormalizeString($draft['client_id']) : '';
		$validation = questionnaireItemsValidateDraft($draft);
		if (!empty($validation['errors'])) {
			questionnaireItemsApiRespond(422, array('ok' => false, 'error' => 'Validierung fehlgeschlagen für Item '.((int)$index + 1).': '.$validation['errors'][0]));
		}
		$validatedDrafts[] = array(
			'position' => (int)$index + 1,
			'id' => $itemId,
			'client_id' => $clientId,
			'item_text' => $validation['item_text'],
			'subscale_key' => $validation['subscale_key'],
			'is_reversed' => $validation['is_reversed']
		);
	}

	$existingIdSet = array();
	foreach ($validatedDrafts as $draft) {
		if ($draft['id'] > 0) {
			if (isset($existingIdSet[$draft['id']])) {
				questionnaireItemsApiRespond(400, array('ok' => false, 'error' => 'Duplikate von serverseitigen IDs im Batch sind nicht erlaubt.'));
			}
			$existingIdSet[$draft['id']] = true;
		}
	}

	if (!empty($existingIdSet)) {
		$ids = array_keys($existingIdSet);
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$checkSql = 'SELECT id FROM questionnaire_items WHERE questionnaire_id = ? AND id IN ('.$placeholders.')';
		$checkStmt = $pdo->prepare($checkSql);
		$checkStmt->execute(array_merge(array($questionnaireId), $ids));
		$foundIds = $checkStmt->fetchAll(PDO::FETCH_COLUMN, 0);
		if (count($foundIds) !== count($ids)) {
			questionnaireItemsApiRespond(400, array('ok' => false, 'error' => 'Mindestens eine serverseitige Item-ID ist ungültig oder gehört nicht zum Fragebogen.'));
		}
	}

	try {
		$pdo->beginTransaction();

		$insertStmt = $pdo->prepare('INSERT INTO questionnaire_items (questionnaire_id, item_no, item_text, scale_type, likert_min, likert_max, is_reversed, subscale_key, is_required) VALUES (:questionnaire_id, :item_no, :item_text, :scale_type, :likert_min, :likert_max, :is_reversed, :subscale_key, :is_required)');
		$updateStmt = $pdo->prepare('UPDATE questionnaire_items SET item_no = :item_no, item_text = :item_text, is_reversed = :is_reversed, subscale_key = :subscale_key WHERE id = :id AND questionnaire_id = :questionnaire_id');

		$position = 1;
		$idToClientMap = array();
		foreach ($validatedDrafts as $draft) {
			$params = array(
				':questionnaire_id' => $questionnaireId,
				':item_no' => $position,
				':item_text' => $draft['item_text'],
				':is_reversed' => $draft['is_reversed'],
				':subscale_key' => $draft['subscale_key'] === '' ? null : $draft['subscale_key']
			);

			if ($draft['id'] > 0) {
				$updateStmt->execute(array_merge($params, array(':id' => $draft['id'])));
				$idToClientMap[$draft['id']] = $draft['client_id'];
			} else {
				$insertStmt->execute(array_merge($params, array(
					':scale_type' => 'likert',
					':likert_min' => 1,
					':likert_max' => 5,
					':is_required' => 1
				)));
				$newId = (int)$pdo->lastInsertId();
				$idToClientMap[$newId] = $draft['client_id'];
			}

			$position++;
		}

		$pdo->commit();
	} catch (Exception $e) {
		if ($pdo->inTransaction()) {
			$pdo->rollBack();
		}
		questionnaireItemsApiRespond(500, array('ok' => false, 'error' => 'Batch-Update konnte nicht gespeichert werden.'));
	}

	$items = questionnaireItemsFetchItems($pdo, $questionnaireId);
	$responseItems = array();
	foreach ($items as $item) {
		$responseItems[] = array_merge($item, array(
			'client_id' => isset($idToClientMap[$item['id']]) ? $idToClientMap[$item['id']] : ''
		));
	}

	questionnaireItemsApiRespond(200, array(
		'ok' => true,
		'items' => $responseItems,
		'saved_count' => count($validatedDrafts)
	));
}

questionnaireItemsApiRespond(400, array('ok' => false, 'error' => 'Unbekannte Aktion.'));
