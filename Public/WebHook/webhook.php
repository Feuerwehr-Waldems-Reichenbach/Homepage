<?php

// Anzahl der Verzeichnisse, um zum Stammverzeichnis zurückzugehen
$stepsBack = 2;
$basePath = __DIR__;
for ($i = 0; $i < $stepsBack; $i++) {
    $basePath = dirname($basePath);
}
define('BASE_PATH_DB', $basePath);

require_once BASE_PATH_DB . '/Private/Database/Database.php';

// Datenbankverbindung abrufen
$db = Database::getInstance();
$conn = $db->getConnection();

// Alle GET-Parameter sammeln und standardmäßig auf 'Unbekannt' setzen, falls nicht vorhanden
$stichwort = $_GET['stichwort'] ?? 'Unbekannt';
$stichwortuebersetzung = $_GET['stichwortuebersetzung'] ?? 'Unbekannt';
$standort = $_GET['standort'] ?? 'Unbekannt';
$sachverhalt = $_GET['sachverhalt'] ?? 'Unbekannt';
$adresse = $_GET['adresse'] ?? 'Unbekannt';
$einsatzID = $_GET['einsatzID'] ?? 'Unbekannt';
$alarmgruppen = $_GET['alarmgruppen'] ?? 'Unbekannt';
$beendet = isset($_GET['beendet']) ? $_GET['beendet'] : 0;

$datum = date("Y-m-d H:i:s"); // Aktuelles Datum und Uhrzeit
$einheit = $alarmgruppen;
$stichwort = !empty($stichwortuebersetzung) ? $stichwortuebersetzung : $stichwort;

echo "Webhook empfangen: ";

// Ort basierend auf der Adresse festlegen
function checkOrt($adresse, $ort, $text)
{
    return strpos($adresse, $text) !== false ? $ort : null;
}

$orte = ["Bermbach", "Esch", "Niederems", "Reichenbach", "Steinfischbach", "Wüstems", "Waldems"];
$ort = "Hessen"; // Standardort
foreach ($orte as $ortCheck) {
    $result = checkOrt($adresse, $ortCheck, $ortCheck);
    if ($result !== null) {
        $ort = $result;
        break;
    }
}

if (empty($sachverhalt)) {
    $sachverhalt = $stichwort;
}

if (empty($einheit)) {
    $einheit = "Feuerwehr Reichenbach";
}

try {
    // Überprüfen, ob der Eintrag bereits existiert
    $sqlCheck = "SELECT COUNT(*) FROM `Einsatz` WHERE `EinsatzID` = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->execute([$einsatzID]);
    $exists = $stmtCheck->fetchColumn();

    if ($exists) {
        // Einsatz existiert bereits, aktualisieren
        if ($beendet == 1) {
            $sqlUpdate = "UPDATE `Einsatz` SET `Anzeigen` = true, `Endzeit` = ? WHERE `EinsatzID` = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            if ($stmtUpdate->execute([$datum, $einsatzID])) {
                echo "Einsatz erfolgreich aktualisiert.";
            } else {
                echo "Fehler beim Aktualisieren: " . implode(", ", $stmtUpdate->errorInfo());
            }
        } else {
            echo "Einsatz existiert bereits und ist noch nicht beendet.";
        }
    } else {
        // Neuer Einsatz, einfügen
        $sqlInsert = "INSERT INTO `Einsatz` (`ID`, `Datum`, `Sachverhalt`, `Stichwort`, `Ort`, `Einheit`, `EinsatzID`) 
                      VALUES (NULL, ?, ?, ?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);

        // Überprüfen, ob mehr als eine Variable den Wert 'Unbekannt' hat
        $unbekanntCount = count(array_filter([$datum, $sachverhalt, $stichwort, $ort, $einheit], fn($v) => $v === 'Unbekannt'));

        if ($unbekanntCount >= 2) {
            echo "Fehler: Zu viele unbekannte Werte.";
        } else {
            if ($stmtInsert->execute([$datum, $sachverhalt, $stichwort, $ort, $einheit, $einsatzID])) {
                echo "Einsatz erfolgreich eingetragen.";
            } else {
                echo "Fehler beim Einfügen: " . implode(", ", $stmtInsert->errorInfo());
            }
        }
    }
} catch (PDOException $e) {
    echo "Fehler bei der Datenbankabfrage: " . $e->getMessage();
}
?>