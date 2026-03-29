<?php
	$show_only_user = 'min_manager';
	$title = 'Normtabellen';
	$description = 'Verwalte Normtabellen im Backend und importiere CSV-Dateien mit Validierung.';
	$keywords = 'normtabellen, backend, verwaltung, csv, import';

	if (isset($_GET['download_template']) && $_GET['download_template'] === '1') {
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="normtables-template-v1.csv"');

		$output = fopen('php://output', 'w');
		fputcsv($output, array(
			'csv_schema_version',
			'group_key',
			'score_key',
			'raw_min',
			'raw_max',
			'norm_value',
			'percentile',
			'norm_label'
		));
		fputcsv($output, array('1', 'gruppe_a', 'score_gesamt', '0', '9', '15', '10', 'Niedrig'));
		fputcsv($output, array('1', 'gruppe_a', 'score_gesamt', '10', '19', '35', '25', 'Unterer Durchschnitt'));
		fputcsv($output, array('1', 'gruppe_a', 'score_gesamt', '20', '30', '55', '50', 'Durchschnitt'));
		fclose($output);
		exit;
	}

	require_once($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');

	const NORMTABLE_SUPPORTED_SCHEMA_VERSIONS = array('1');
	const NORMTABLE_REQUIRED_COLUMNS = array(
		'csv_schema_version',
		'group_key',
		'score_key',
		'raw_min',
		'raw_max',
		'norm_value',
		'percentile',
		'norm_label'
	);

	function addRowError(&$errors, $row, $type, $message) {
		if (!isset($errors[$row])) {
			$errors[$row] = array();
		}
		$errors[$row][] = array(
			'type' => $type,
			'message' => $message
		);
	}

	function normalizeHeader($headerRow) {
		$normalized = array();
		foreach ($headerRow as $value) {
			$normalized[] = trim((string)$value);
		}
		return $normalized;
	}

	function mapRowByHeader($header, $row) {
		$mapped = array();
		foreach ($header as $index => $column) {
			$mapped[$column] = isset($row[$index]) ? trim((string)$row[$index]) : '';
		}
		return $mapped;
	}

	function validateCsvFileType($fileInfo) {
		$allowedMimeTypes = array(
			'text/csv',
			'text/plain',
			'application/csv',
			'application/vnd.ms-excel',
			'text/comma-separated-values'
		);

		if (!isset($fileInfo['name']) || !isset($fileInfo['tmp_name'])) {
			return 'Es wurde keine Datei übergeben.';
		}

		$extension = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
		if ($extension !== 'csv') {
			return 'Nur Dateien mit der Endung .csv sind erlaubt.';
		}

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$detectedMimeType = $finfo ? finfo_file($finfo, $fileInfo['tmp_name']) : '';
		if ($finfo) {
			finfo_close($finfo);
		}

		if (!in_array($detectedMimeType, $allowedMimeTypes, true)) {
			return 'Ungültiger MIME-Typ: '.htmlentities((string)$detectedMimeType).'. Nur CSV-Dateien sind erlaubt.';
		}

		return '';
	}

	$errorTypeLabels = array(
		'schema' => 'Schema',
		'required' => 'Pflichtfeld',
		'numeric' => 'Numerik',
		'interval' => 'Intervall'
	);
	$importErrors = array();
	$importSummary = array();
	$uploadedRows = array();
	$uploadAttempted = false;
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_normtable_csv'])) {
		$uploadAttempted = true;
		if (!isset($_FILES['normtable_csv']) || (int)$_FILES['normtable_csv']['error'] !== UPLOAD_ERR_OK) {
			$importSummary[] = 'Beim Upload ist ein Fehler aufgetreten. Bitte Datei erneut auswählen.';
		} else {
			$fileValidationError = validateCsvFileType($_FILES['normtable_csv']);
			if ($fileValidationError !== '') {
				$importSummary[] = $fileValidationError;
			} else {
				$handle = fopen($_FILES['normtable_csv']['tmp_name'], 'r');

				if ($handle === false) {
					$importSummary[] = 'CSV-Datei konnte nicht gelesen werden.';
				} else {
					$header = fgetcsv($handle);

					if ($header === false) {
						$importSummary[] = 'CSV-Datei ist leer.';
					} else {
						$header = normalizeHeader($header);
						$missingColumns = array_diff(NORMTABLE_REQUIRED_COLUMNS, $header);

						if (!empty($missingColumns)) {
							$importSummary[] = 'Pflichtspalten fehlen: '.implode(', ', $missingColumns);
						} else {
							$rowNumber = 1;
							$intervalsByGroupScore = array();

							while (($row = fgetcsv($handle)) !== false) {
								$rowNumber++;

								if ($row === array(null) || (count($row) === 1 && trim((string)$row[0]) === '')) {
									continue;
								}

								$mappedRow = mapRowByHeader($header, $row);

								if ($mappedRow['csv_schema_version'] === '') {
									addRowError($importErrors, $rowNumber, 'required', 'csv_schema_version ist leer.');
								} elseif (!in_array($mappedRow['csv_schema_version'], NORMTABLE_SUPPORTED_SCHEMA_VERSIONS, true)) {
									addRowError($importErrors, $rowNumber, 'schema', 'Nicht unterstützte csv_schema_version: '.$mappedRow['csv_schema_version']);
								}

								if ($mappedRow['group_key'] === '') {
									addRowError($importErrors, $rowNumber, 'required', 'group_key darf nicht leer sein.');
								}

								if ($mappedRow['score_key'] === '') {
									addRowError($importErrors, $rowNumber, 'required', 'score_key darf nicht leer sein.');
								}

								$numericFields = array('raw_min', 'raw_max', 'norm_value', 'percentile');
								foreach ($numericFields as $fieldName) {
									if ($mappedRow[$fieldName] === '' || !is_numeric($mappedRow[$fieldName])) {
										addRowError($importErrors, $rowNumber, 'numeric', $fieldName.' muss numerisch sein.');
									}
								}

								$hasRawBoundaries = is_numeric($mappedRow['raw_min']) && is_numeric($mappedRow['raw_max']);
								if ($hasRawBoundaries) {
									$rawMin = (float)$mappedRow['raw_min'];
									$rawMax = (float)$mappedRow['raw_max'];

									if ($rawMin > $rawMax) {
										addRowError($importErrors, $rowNumber, 'interval', 'Intervallfehler: raw_min ist größer als raw_max.');
									}
								}

								if ($mappedRow['norm_label'] === '') {
									addRowError($importErrors, $rowNumber, 'required', 'norm_label darf nicht leer sein.');
								}

								if (!isset($importErrors[$rowNumber]) && $hasRawBoundaries) {
									$bucketKey = $mappedRow['group_key'].'||'.$mappedRow['score_key'];

									if (!isset($intervalsByGroupScore[$bucketKey])) {
										$intervalsByGroupScore[$bucketKey] = array();
									}

									$intervalsByGroupScore[$bucketKey][] = array(
										'row' => $rowNumber,
										'raw_min' => (float)$mappedRow['raw_min'],
										'raw_max' => (float)$mappedRow['raw_max']
									);

									$uploadedRows[] = $mappedRow;
								}
							}

							foreach ($intervalsByGroupScore as $bucketKey => $intervals) {
								usort($intervals, function($a, $b) {
									if ($a['raw_min'] === $b['raw_min']) {
										return 0;
									}
									return ($a['raw_min'] < $b['raw_min']) ? -1 : 1;
								});

								$previous = null;
								foreach ($intervals as $current) {
									if ($previous !== null && $current['raw_min'] <= $previous['raw_max']) {
										addRowError(
											$importErrors,
											$current['row'],
											'interval',
											'Überlappendes Intervall für '.$bucketKey.' mit Zeile '.$previous['row'].'.'
										);
									}
									$previous = $current;
								}
							}

							if (empty($importErrors)) {
								$importSummary[] = 'CSV wurde erfolgreich validiert. Zeilen: '.count($uploadedRows).'.';
							} else {
								$importSummary[] = 'CSV enthält '.count($importErrors).' fehlerhafte Zeile(n).';
							}
						}
					}

					fclose($handle);
				}
			}
		}
	}

	$errorTypeCounts = array();
	foreach ($importErrors as $rowErrors) {
		foreach ($rowErrors as $rowError) {
			$errorType = isset($rowError['type']) ? (string)$rowError['type'] : 'schema';
			if (!isset($errorTypeCounts[$errorType])) {
				$errorTypeCounts[$errorType] = 0;
			}
			$errorTypeCounts[$errorType]++;
		}
	}
	arsort($errorTypeCounts);

	$isSuccessfulValidation = $uploadAttempted && empty($importErrors) && !empty($importSummary);
?>
<main class="qnr-layout qnr-layout--backend">
	<section class="qnr-card">
		<?php questionnaire_show_backend_overview('Normtabellen', 'Übersicht über verfügbare Normtabellen, deren Pflege und CSV-Import.'); ?>
	</section>

	<section class="qnr-card">
		<h1>Normtabellen per CSV importieren</h1>
		<p>
			Dieses Werkzeug validiert CSV-Dateien strikt anhand der Pflichtspalten, Datentypen,
			Intervallgrenzen und Überlappungsregeln je Kombination aus <code>group_key + score_key</code>.
		</p>
		<form action="" method="post" enctype="multipart/form-data">
			<div class="qnr-form-row">
				<label for="normtable_csv">CSV-Datei</label>
				<input class="qnr-input" type="file" name="normtable_csv" id="normtable_csv" accept=".csv,text/csv" required>
			</div>
			<div class="qnr-form-row">
				<button class="qnr-btn qnr-focusable" type="submit" name="upload_normtable_csv" value="1">CSV prüfen</button>
				<a class="qnr-btn qnr-btn--secondary qnr-focusable" href="?download_template=1">CSV-Beispieldatei herunterladen</a>
			</div>
		</form>

		<?php if ($uploadAttempted) { ?>
			<div id="upload-result" tabindex="-1">
				<h2>Ergebnis</h2>
				<h3>1) Gesamtstatus</h3>
				<div role="<?php echo $isSuccessfulValidation ? 'status' : 'alert'; ?>" class="<?php echo $isSuccessfulValidation ? 'qnr-alert qnr-alert--success' : 'qnr-alert qnr-alert--danger'; ?>">
					<strong><?php echo $isSuccessfulValidation ? 'Erfolg' : 'Fehler'; ?>:</strong>
					<?php echo $isSuccessfulValidation ? 'Datei validiert.' : 'Es wurden Validierungsfehler gefunden.'; ?>
				</div>
			<?php if (!empty($importSummary)) { ?>
				<ul>
				<?php foreach ($importSummary as $message) { ?>
					<li><?php echo htmlentities($message); ?></li>
				<?php } ?>
				</ul>
			<?php } ?>

			<?php if ($isSuccessfulValidation) { ?>
				<div role="status" class="qnr-alert qnr-alert--success">
					<strong>Nächster Schritt:</strong> Datei ist valide – bereit für Persistierung. Daten jetzt importieren.
				</div>
			<?php } ?>

			<?php if (!empty($importErrors)) { ?>
				<h3>2) Fehlerstatistik</h3>
				<ul>
					<li>Fehlerhafte CSV-Zeilen: <?php echo count($importErrors); ?></li>
					<li>Häufigste Fehlertypen:
						<?php
							$topErrorTypes = array_slice($errorTypeCounts, 0, 3, true);
							$topTypeLabels = array();
							foreach ($topErrorTypes as $errorType => $count) {
								$topTypeLabels[] = (isset($errorTypeLabels[$errorType]) ? $errorTypeLabels[$errorType] : ucfirst($errorType)).' ('.$count.')';
							}
							echo htmlentities(!empty($topTypeLabels) ? implode(', ', $topTypeLabels) : 'keine');
						?>
					</li>
				</ul>
				<p>
					Filter:
					<?php foreach ($errorTypeCounts as $errorType => $count) { ?>
						<a href="#error-type-<?php echo htmlentities($errorType); ?>"><?php echo htmlentities((isset($errorTypeLabels[$errorType]) ? $errorTypeLabels[$errorType] : ucfirst($errorType)).' ('.$count.')'); ?></a>
					<?php } ?>
				</p>
			<?php } ?>
			</div>
		<?php } ?>

		<?php if (!empty($importErrors)) { ?>
			<h3>3) Detailliste je Zeile</h3>
			<?php foreach ($errorTypeCounts as $errorType => $count) { ?>
				<h4 id="error-type-<?php echo htmlentities($errorType); ?>"><?php echo htmlentities(isset($errorTypeLabels[$errorType]) ? $errorTypeLabels[$errorType] : ucfirst($errorType)); ?> (<?php echo (int)$count; ?>)</h4>
			<?php } ?>
			<div class="qnr-table-wrap">
				<table class="tablesorter qnr-table">
					<thead>
						<tr>
							<th>CSV-Zeile</th>
							<th>Fehlertyp</th>
							<th>Fehler</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($importErrors as $rowNumber => $rowErrors) { ?>
							<?php foreach ($rowErrors as $rowError) { ?>
								<tr>
									<td><?php echo (int)$rowNumber; ?></td>
									<td><?php echo htmlentities(isset($errorTypeLabels[$rowError['type']]) ? $errorTypeLabels[$rowError['type']] : ucfirst((string)$rowError['type'])); ?></td>
									<td><?php echo htmlentities((string)$rowError['message']); ?></td>
								</tr>
							<?php } ?>
						<?php } ?>
					</tbody>
				</table>
			</div>
		<?php } ?>
	</section>
</main>
<?php if ($uploadAttempted) { ?>
<script>
	(function() {
		var resultContainer = document.getElementById('upload-result');
		if (resultContainer && typeof resultContainer.focus === 'function') {
			resultContainer.focus();
		}
	})();
</script>
<?php } ?>
<?php
	include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php');
?>
