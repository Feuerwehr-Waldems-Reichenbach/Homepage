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
$beendet = isset($_GET['beendet']) && ($_GET['beendet'] === '1' || $_GET['beendet'] === 1) ? 1 : 0;

$datum = date("Y-m-d H:i:s"); // Aktuelles Datum und Uhrzeit
$einheit = $alarmgruppen;
$stichwort = !empty($stichwortuebersetzung) ? $stichwortuebersetzung : $stichwort;

echo "Webhook empfangen: ";

// Überprüfen, ob die EinsatzID fehlt oder 'Unbekannt' ist
if ($einsatzID === 'Unbekannt') {
    echo "Fehler: EinsatzID fehlt oder ist ungültig.";
    exit;
}

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

// Funktion zur automatischen Kategorisierung
function getKategorie($sachverhalt, $stichwort) {
    $text = strtolower($sachverhalt . ' ' . $stichwort);

    $kategorien = [
        'Medizinisch' => [
            'kollaps', 'synkope', 'notfall', 'krampfanfall', 'coronarsyndrom', 'schlaganfall', 'voraushelfer',
            'bewusstlos', 'reanimation', 'atemnot', 'verletzt', 'verletzung', 'krankentransport', 'kreislauf',
            'rettungsdienst', 'r1', 'r2', 'rtw', 'hilflose person', 'person in not'
        ],
        'Brand' => [
            'feuer', 'brand', 'rauch', 'brennt', 'rauchentwicklung', 'wohnungsbrand', 'kaminbrand', 'flammen',
            'rauchmelder', 'schornsteinbrand', 'fahrzeugbrand', 'gebäudebrand', 'bma', 'feuerschein', 'dachstuhlbrand'
        ],
        'Technische Hilfe' => [
            'unfall', 'baum', 'türöffnung', 'wasser', 'fahrzeug', 'ölspur', 'hilfeleistung',
            'eingeklemmt', 'eingeschlossen', 'verkehrsunfall', 'pkw', 'fahrzeug auf seite', 'bergung',
            'aufzug', 'fahrstuhl', 'person eingeschlossen', 'person eingeklemmt', 'Auslaufen', 'unwegsames Gelände'
        ],
        'Unwetter' => [
            'sturm', 'unwetter', 'überflutung', 'regen', 'wasserschaden', 'ast', 'baum auf straße',
            'sturmbruch', 'dach abgedeckt', 'umgestürzter baum', 'wasser im keller', 'schnee', 'eisregen', 'glätte'
        ],
        'Tierrettung' => [
            'tier', 'katze', 'hund', 'tierrettung', 'tier in not', 'tier auf baum', 'tier in fahrzeug',
            'tier in gefahr', 'vogel', 'pferd', 'rind'
        ],
        'Gefahrgut' => [
            'gefahrgut', 'gas', 'austritt', 'chemikalie', 'stoffaustritt', 'ammoniak', 'leckage',
            'unbekannter geruch', 'gift', 'tanklastzug', 'chemieunfall'
        ],
        'Absicherung' => [
            'absicherung', 'veranstaltung', 'umzug', 'sicherung', 'verkehrssicherung', 'martinszug', 'laufveranstaltung'
        ],
        'Sonstiges' => []
    ];
    
    

    foreach ($kategorien as $kategorie => $woerter) {
        foreach ($woerter as $wort) {
            if (strpos($text, strtolower($wort)) !== false) {
                return $kategorie;
            }
        }
    }

    return 'Sonstiges';
}

// Kategorie ermitteln
$kategorie = getKategorie($sachverhalt, $stichwort);




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
        $sqlInsert = "INSERT INTO `Einsatz` (`ID`, `Datum`, `Sachverhalt`, `Stichwort`, `Ort`, `Einheit`, `EinsatzID`, `Kategorie`) 
        VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)";

        $stmtInsert = $conn->prepare($sqlInsert);

        // Überprüfen, ob mehr als eine Variable den Wert 'Unbekannt' hat
        $unbekanntCount = count(array_filter([$sachverhalt, $stichwort, $ort, $einheit], fn($v) => $v === 'Unbekannt'));

        if ($unbekanntCount >= 2) {
            echo "Fehler: Zu viele unbekannte Werte.";
        } else {
            if ($stmtInsert->execute([$datum, $sachverhalt, $stichwort, $ort, $einheit, $einsatzID, $kategorie])) {
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