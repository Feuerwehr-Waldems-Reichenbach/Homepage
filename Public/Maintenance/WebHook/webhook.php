<?php
// Anzahl der Verzeichnisse, um zum Stammverzeichnis zurückzugehen
$stepsBack = 3;
// Dynamisch den Pfad zum Stammverzeichnis berechnen
$basePath = __DIR__;
for ($i = 0; $i < $stepsBack; $i++) {
    $basePath = dirname($basePath);
}
define('BASE_PATH_DB', $basePath);

include BASE_PATH_DB . '/Public/WebHook/webhook.php';

?>
