<?php
// Anzahl der Verzeichnisse, um zum Stammverzeichnis zurückzugehen
$stepsBack = 1;
// Dynamisch den Pfad zum Stammverzeichnis berechnen
$basePath = __DIR__;
for ($i = 0; $i < $stepsBack; $i++) {
    $basePath = dirname($basePath);
}
define('BASE_PATH', $basePath);
?>