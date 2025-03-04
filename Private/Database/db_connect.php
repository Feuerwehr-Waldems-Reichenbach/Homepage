<?php

// Anzahl der Verzeichnisse, um zum Stammverzeichnis zurückzugehen
$stepsBack = 2;
// Dynamisch den Pfad zum Stammverzeichnis berechnen
$basePath = __DIR__;
for ($i = 0; $i < $stepsBack; $i++) {
    $basePath = dirname($basePath);
}
define('BASE_PATH', $basePath);

$configPath = BASE_PATH . '/Private/Initializations/db_config.ini';

// Datei einlesen
$config = parse_ini_file($configPath);

if ($config === false) {
    throw new Exception("Fehler beim Laden der Konfigurationsdatei.");
}

// Erstelle Verbindung
$conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['db']);

// Überprüfe Verbindung
if ($conn->connect_error) {
  die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
?>
