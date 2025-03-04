<?php
// Anzahl der Verzeichnisse, um zum Stammverzeichnis zurückzugehen
$stepsBack = 2;
// Dynamisch den Pfad zum Stammverzeichnis berechnen
$basePath = __DIR__;
for ($i = 0; $i < $stepsBack; $i++) {
    $basePath = dirname($basePath);
}
define('BASE_PATH', $basePath);

include BASE_PATH . '/Private/Database/db_connect.php';

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
$einheit = $alarmgruppen; // 'Einheit' wird als Alarmgruppen gesetzt
$stichwort = !empty($stichwortuebersetzung) ? $stichwortuebersetzung : $stichwort;

echo "Webhook empfangen\n\n";

// Ort basierend auf der Adresse festlegen
function checkOrt($adresse, $ort, $text) {
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

// Überprüfen, ob der Eintrag bereits existiert
$sqlCheck = "SELECT * FROM `Einsatz` WHERE `EinsatzID` = ?";
$stmtCheck = $conn->prepare($sqlCheck);
if ($stmtCheck === false) {
    die("MySQL prepare error: " . $conn->error);
}
$stmtCheck->bind_param("s", $einsatzID);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows > 0) {
    // Einsatz existiert bereits, aktualisieren
    if ($beendet == 1) {
        $sqlUpdate = "UPDATE `Einsatz` SET `Anzeigen` = true, `Endzeit` = ? WHERE `EinsatzID` = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        if ($stmtUpdate === false) {
            die("MySQL prepare error: " . $conn->error);
        }
        $stmtUpdate->bind_param("ss", $datum, $einsatzID);
        if ($stmtUpdate->execute()) {
            echo "Einsatz erfolgreich aktualisiert.";
        } else {
            echo "Fehler beim Aktualisieren: " . $stmtUpdate->error;
        }
        $stmtUpdate->close();
    } else {
        echo "Einsatz existiert bereits und ist noch nicht beendet.";
    }
} else {
    // Neuer Einsatz, einfügen
    $sqlInsert = "INSERT INTO `Einsatz` (`ID`, `Datum`, `Sachverhalt`, `Stichwort`, `Ort`, `Einheit`, `EinsatzID`) VALUES (NULL, ?, ?, ?, ?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    if ($stmtInsert === false) {
        die("MySQL prepare error: " . $conn->error);
    }
    $stmtInsert->bind_param("ssssss", $datum, $sachverhalt, $stichwort, $ort, $einheit, $einsatzID);

    // Überprüfen, ob mehr als eine Variable den Wert 'Unbekannt' hat
    $unbekanntCount = 0;
    foreach ([$datum, $sachverhalt, $stichwort, $ort, $einheit] as $value) {
        if ($value === 'Unbekannt') {
            $unbekanntCount++;
        }
    }

    if ($unbekanntCount >= 2) {
        echo "Fehler: Zu viele unbekannte Werte.";
    } else {
        // Ausführen des Statements
        if ($stmtInsert->execute()) {
            echo "Einsatz erfolgreich eingetragen.";
        } else {
            echo "Fehler beim Einfügen: " . $stmtInsert->error;
        }
    }
    $stmtInsert->close();
}

$stmtCheck->close();
$conn->close();

?>
