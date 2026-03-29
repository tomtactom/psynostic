<?php
// Lädt optionale Plugins robust, ohne die Anwendung bei fehlenden Dateien zu beenden.
$pluginInits = [
    $options['pluginpath'] . '/statistic/init.php',
    $options['pluginpath'] . '/questionnaire/init.php',
];

foreach ($pluginInits as $pluginInit) {
    if (is_readable($pluginInit)) {
        require_once $pluginInit;
        continue;
    }

    error_log('Plugin konnte nicht geladen werden: ' . $pluginInit);
}
?>
