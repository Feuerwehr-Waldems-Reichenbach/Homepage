<?php

$config = parse_ini_file('/mnt/rid/08/69/543220869/htdocs/Private/Initializations/db_config.ini');

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
