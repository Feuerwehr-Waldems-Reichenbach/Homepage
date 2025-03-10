<?php

$stepsBack = 2;
$basePath = __DIR__;
for ($i = 0; $i < $stepsBack; $i++) {
    $basePath = dirname($basePath);
}
define('BASE_PATH_DB', $basePath);
require_once BASE_PATH_DB . '/Private/Database/Database.php';

// DB-Verbindung
$db = Database::getInstance();
$conn = $db->getConnection();

// Kategorien-Definition
function getKategorie($sachverhalt, $stichwort)
{
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

try {
    $stmt = $conn->query("SELECT ID, Sachverhalt, Stichwort FROM Einsatz");
    $einsaetze = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updateStmt = $conn->prepare("UPDATE Einsatz SET Kategorie = ? WHERE ID = ?");

    $updated = 0;

    foreach ($einsaetze as $einsatz) {
        $kategorie = getKategorie($einsatz['Sachverhalt'], $einsatz['Stichwort']);
        if ($updateStmt->execute([$kategorie, $einsatz['ID']])) {
            $updated++;
        }
    }

    echo "Kategorisierung abgeschlossen. $updated Einsätze aktualisiert.";

} catch (PDOException $e) {
    echo "Fehler: " . $e->getMessage();
}
