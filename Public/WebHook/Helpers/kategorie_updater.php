<?php

// Gemeinsame Funktionen laden
require_once __DIR__ . '/helpers.php';

try {
    // Datenbankverbindung abrufen
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Alle Kategorien aktualisieren (auch bestehende überschreiben: false = alles)
    [$updatedCount, $message] = updateAllKategorien($conn, false);

    echo $message;

} catch (PDOException $e) {
    error_log("Fehler bei der Datenbankverbindung im Kategorie-Updater: " . $e->getMessage());
    echo "Ein technischer Fehler ist aufgetreten. Bitte kontaktieren Sie den Administrator.";
}
