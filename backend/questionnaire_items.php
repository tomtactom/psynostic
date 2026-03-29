<?php
$show_only_user = 'min_manager';
$title = 'Fragebogen-Items';
$description = 'Items eines Fragebogens verwalten';
$keywords = 'fragebogen, items, backend';

include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');

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

function questionnaireItemsPageCanAccess(PDO $pdo, array $user, $questionnaireId)
{
	$stmt = $pdo->prepare('SELECT id, title, slug FROM questionnaires WHERE id = :id LIMIT 1');
	$stmt->execute(array(':id' => $questionnaireId));
	$questionnaire = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$questionnaire) {
		return null;
	}

	if ($user['role'] === 'adminstrator') {
		return $questionnaire;
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

	return $questionnaire;
}

if (!isset($_SESSION['questionnaire_items_csrf']) || !is_string($_SESSION['questionnaire_items_csrf']) || $_SESSION['questionnaire_items_csrf'] === '') {
	$_SESSION['questionnaire_items_csrf'] = bin2hex(random_bytes(32));
}

$user = check_user();
$questionnaireId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($questionnaireId <= 0) {
	die('<p>Ungültige Fragebogen-ID.</p>');
}

$questionnaire = questionnaireItemsPageCanAccess($pdo, $user, $questionnaireId);
if ($questionnaire === false) {
	die('<p>Keine Berechtigung für diesen Fragebogen.</p>');
}
if (!$questionnaire) {
	die('<p>Fragebogen nicht gefunden.</p>');
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
</main>

<template id="item-template">
	<article class="qnr-card item-row">
		<header class="item-row__header">
			<strong class="item-row__index"></strong>
			<div class="item-row__actions">
				<button type="button" class="qnr-btn qnr-btn--secondary qnr-focusable" data-action="move_up">↑</button>
				<button type="button" class="qnr-btn qnr-btn--secondary qnr-focusable" data-action="move_down">↓</button>
				<button type="button" class="qnr-btn qnr-btn--secondary qnr-focusable" data-action="clone">Klonen</button>
				<button type="button" class="qnr-btn qnr-btn--secondary qnr-focusable" data-action="delete">Löschen</button>
			</div>
		</header>
		<div class="qnr-form-row">
			<label>Itemtext</label>
			<textarea class="qnr-textarea" rows="4" data-field="item_text" required></textarea>
		</div>
		<div class="qnr-grid qnr-grid--2">
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
	var questionnaireId = <?php echo (int)$questionnaireId; ?>;
	var apiUrl = '<?php echo htmlentities($options['siteurl']); ?>/backend/api/questionnaire_items.php';
	var csrfToken = '<?php echo htmlentities((string)$_SESSION['questionnaire_items_csrf']); ?>';
	var saveStatus = document.getElementById('save-status');
	var errorBox = document.getElementById('item-builder-error');
	var listEl = document.getElementById('items-list');
	var template = document.getElementById('item-template');
	var addButton = document.getElementById('add-item');
	var items = [];
	var saveTimer = null;
	var isSaving = false;
	var pendingSave = false;

	function nextClientId() {
		return 'tmp_' + Date.now() + '_' + Math.random().toString(16).slice(2);
	}

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

	function render() {
		listEl.innerHTML = '';
		if (items.length === 0) {
			var empty = document.createElement('p');
			empty.className = 'qnr-alert qnr-alert--info';
			empty.textContent = 'Noch keine Items vorhanden. Über „Item hinzufügen“ starten.';
			listEl.appendChild(empty);
			return;
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

	function saveAllChanges() {
		if (isSaving || !pendingSave) {
			return;
		}
		pendingSave = false;
		isSaving = true;
		setError('');
		setStatus('Speichere Änderungen …', 'qnr-alert--info');

		if (items.length === 0) {
			isSaving = false;
			setStatus('Alle Änderungen gespeichert.', 'qnr-alert--success');
			return;
		}

		var upsertPayload = items.map(function (item) {
			return {
				id: item.id,
				client_id: item.client_id,
				item_text: item.item_text,
				subscale_key: item.subscale_key,
				is_reversed: item.is_reversed
			};
		});

		postApi({
			action: 'upsert_batch',
			questionnaire_id: questionnaireId,
			csrf_token: csrfToken,
			items: upsertPayload
		}).then(function (upsertData) {
			items = (upsertData.items || []).map(normalizeItem);
			var orderIds = items.map(function (item) { return item.id; }).filter(Boolean);
			return postApi({
				action: 'reorder',
				questionnaire_id: questionnaireId,
				csrf_token: csrfToken,
				item_order: orderIds
			});
		}).then(function (reorderData) {
			items = (reorderData.items || []).map(function (raw) {
				var existing = items.find(function (draft) { return draft.id === Number(raw.id); });
				return normalizeItem({
					id: raw.id,
					item_no: raw.item_no,
					item_text: raw.item_text,
					subscale_key: raw.subscale_key,
					is_reversed: raw.is_reversed,
					client_id: existing ? existing.client_id : nextClientId()
				});
			});
			render();
			isSaving = false;
			setStatus('Alle Änderungen gespeichert.', 'qnr-alert--success');
			if (pendingSave) {
				saveAllChanges();
			}
		}).catch(function (error) {
			isSaving = false;
			pendingSave = true;
			setError(error.message);
			setStatus('Speichern fehlgeschlagen', 'qnr-alert--error');
		});
	}

	function loadItems() {
		setStatus('Lade Items …', 'qnr-alert--info');
		postApi({
			action: 'list',
			questionnaire_id: questionnaireId
		}).then(function (data) {
			csrfToken = data.csrf_token || csrfToken;
			items = (data.items || []).map(normalizeItem);
			render();
			setStatus('Alle Änderungen gespeichert.', 'qnr-alert--success');
		}).catch(function (error) {
			setError(error.message);
			setStatus('Laden fehlgeschlagen', 'qnr-alert--error');
		});
	}

	addButton.addEventListener('click', function () {
		items.push(normalizeItem({
			id: null,
			item_text: '',
			subscale_key: '',
			is_reversed: 0,
			client_id: nextClientId()
		}));
		render();
		scheduleSave();
	});

	loadItems();
})();
</script>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); ?>
