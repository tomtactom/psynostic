<?php
	require_once($options['pluginpath']."/questionnaire/functions.inc.php");

	if (PHP_SAPI !== 'cli' && isset($pdo) && $pdo instanceof PDO && isset($_SERVER['REQUEST_URI'])) {
		$requestPath = (string)parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH);
		if (strpos($requestPath, '/backend/') === 0) {
			$schemaStatus = questionnaireEnsureSchema($pdo);
			if (!isset($_SESSION)) {
				@session_start();
			}
			if ($schemaStatus['applied'] && !empty($schemaStatus['created_tables'])) {
				$_SESSION['questionnaire_schema_autoinstall_notice'] = 'Fragebogen-Plugin: Fehlende Datenbanktabellen wurden automatisch installiert ('.implode(', ', $schemaStatus['created_tables']).').';
			}
		}
	}
?>
