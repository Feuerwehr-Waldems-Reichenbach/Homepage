<?php
/**
 * WebHook Helper Functions
 * 
 * Gemeinsame Funktionen für webhook.php und kategorie_updater.php
 */

// Pfad zur Datenbank-Klasse
$stepsBack = 3;
$basePath = __DIR__;
for ($i = 0; $i < $stepsBack; $i++) {
    $basePath = dirname($basePath);
}
define('BASE_PATH_DB', $basePath);
require_once BASE_PATH_DB . '/Private/Database/Database.php';

/**
 * Überprüft, ob ein Authentifizierungsschlüssel gültig ist
 * 
 * @param PDO $conn Die Datenbankverbindung
 * @param string $authKey Der zu überprüfende Authentifizierungsschlüssel
 * @return bool True, wenn der Schlüssel gültig ist, sonst False
 */
function isValidAuthKey($conn, $authKey)
{
    $sql = "SELECT COUNT(*) FROM `authentifizierungsschluessel` WHERE `auth_key` = ? AND `active` = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$authKey]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Ermittelt den Ort basierend auf der Adresse
 * 
 * @param string $adresse Die Adresse aus dem Webhook
 * @param array $orte Liste der möglichen Orte
 * @param string $defaultOrt Standard-Ort, falls kein Ort erkannt wird
 * @return string Der erkannte Ort oder der Standard-Ort
 */
function ermittleOrt($adresse, $orte = [], $defaultOrt = "Hessen")
{
    if (empty($orte)) {
        $orte = ["Bermbach", "Esch", "Niederems", "Reichenbach", "Steinfischbach", "Wüstems", "Waldems"];
    }
    
    $ort = $defaultOrt;
    foreach ($orte as $ortCheck) {
        if (strpos($adresse, $ortCheck) !== false) {
            $ort = $ortCheck;
            break;
        }
    }
    
    return $ort;
}

/**
 * Ermittelt die Kategorie basierend auf Sachverhalt und Stichwort
 * 
 * @param string $sachverhalt Der Sachverhalt aus dem Webhook
 * @param string $stichwort Das Stichwort aus dem Webhook
 * @return string Die ermittelte Kategorie
 */
function getKategorie($sachverhalt, $stichwort)
{
    $text = strtolower($sachverhalt . ' ' . $stichwort);

    $kategorien = [
        'Medizinisch' => [
            'notfall', 'person', 'rettung', 'erste hilfe', 'bewusstlos', 'herzinfarkt', 'schlaganfall',
            'verletzt', 'sturz', 'tragehilfe', 'krankentransport', 'rettungsdienst', 'sanitäter', 'notarzt'
        ],
        'Feuer' => [
            'brand', 'feuer', 'rauch', 'rauchentwicklung', 'brennt', 'qualm', 'flammen', 'brandgeruch',
            'brandmelder', 'brandmeldeanlage', 'bma', 'rauchmelder', 'wohnungsbrand', 'zimmerbrand',
            'gebäudebrand', 'flächenbrand', 'waldbrand', 'fahrzeugbrand', 'mülltonnenbrand'
        ],
        'Technische Hilfeleistung' => [
            'hilfeleistung', 'th', 'verkehrsunfall', 'vku', 'vu', 'öl', 'ölspur', 'wasser', 'tür öffnen',
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

/**
 * Validiert die Webhook-Parameter
 * 
 * @param array $params Die Parameter aus dem Webhook
 * @return array [bool $isValid, string $errorMessage]
 */
function validateWebhookParams($params)
{
    // Prüfen, ob die EinsatzID fehlt oder 'Unbekannt' ist
    if (!isset($params['einsatzID']) || $params['einsatzID'] === 'Unbekannt') {
        return [false, "Fehler: EinsatzID fehlt oder ist ungültig."];
    }
    
    // Prüfen, ob zu viele Parameter unbekannt sind
    $unbekanntCount = 0;
    $criticalParams = ['sachverhalt', 'stichwort', 'adresse', 'alarmgruppen'];
    
    foreach ($criticalParams as $param) {
        if (!isset($params[$param]) || $params[$param] === 'Unbekannt') {
            $unbekanntCount++;
        }
    }
    
    if ($unbekanntCount >= 2) {
        return [false, "Fehler: Zu viele unbekannte Werte."];
    }
    
    return [true, ""];
}

/**
 * Prüft, ob ein Einsatz bereits in der Datenbank existiert
 * 
 * @param PDO $conn Die Datenbankverbindung
 * @param string $einsatzID Die EinsatzID
 * @return bool True, wenn der Einsatz existiert, sonst False
 */
function einsatzExistiert($conn, $einsatzID)
{
    $sqlCheck = "SELECT COUNT(*) FROM `Einsatz` WHERE `EinsatzID` = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->execute([$einsatzID]);
    return $stmtCheck->fetchColumn() > 0;
}

/**
 * Fügt einen neuen Einsatz in die Datenbank ein
 * 
 * @param PDO $conn Die Datenbankverbindung
 * @param array $params Die Parameter für den Einsatz
 * @return array [bool $success, string $message]
 */
function insertEinsatz($conn, $params)
{
    $sqlInsert = "INSERT INTO `Einsatz` (`ID`, `Datum`, `Sachverhalt`, `Stichwort`, `Ort`, `Einheit`, `EinsatzID`, `Kategorie`) 
                  VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    
    if ($stmtInsert->execute([
        $params['datum'],
        $params['sachverhalt'],
        $params['stichwort'],
        $params['ort'],
        $params['einheit'],
        $params['einsatzID'],
        $params['kategorie']
    ])) {
        return [true, "Einsatz erfolgreich eingetragen."];
    } else {
        error_log("Fehler beim Einfügen des Einsatzes: " . implode(", ", $stmtInsert->errorInfo()));
        return [false, "Fehler beim Einfügen des Einsatzes. Bitte kontaktieren Sie den Administrator."];
    }
}

/**
 * Aktualisiert einen bestehenden Einsatz in der Datenbank
 * 
 * @param PDO $conn Die Datenbankverbindung
 * @param array $params Die Parameter für den Einsatz
 * @return array [bool $success, string $message]
 */
function updateEinsatz($conn, $params)
{
    // Wenn beendet=1, setze Endzeit
    if ($params['beendet'] == 1) {
        $sqlUpdate = "UPDATE `Einsatz` SET `Anzeigen` = true, `Endzeit` = ? WHERE `EinsatzID` = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        
        if ($stmtUpdate->execute([$params['datum'], $params['einsatzID']])) {
            return [true, "Einsatz erfolgreich aktualisiert."];
        } else {
            error_log("Fehler beim Aktualisieren des Einsatzes: " . implode(", ", $stmtUpdate->errorInfo()));
            return [false, "Fehler beim Aktualisieren des Einsatzes. Bitte kontaktieren Sie den Administrator."];
        }
    } else {
        // Wenn nicht beendet, aktualisiere nur die Kategorie, falls nötig
        $sqlUpdate = "UPDATE `Einsatz` SET `Kategorie` = ? WHERE `EinsatzID` = ? AND (`Kategorie` IS NULL OR `Kategorie` = '')";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        
        if ($stmtUpdate->execute([$params['kategorie'], $params['einsatzID']])) {
            return [true, "Einsatz existiert bereits und ist noch nicht beendet."];
        } else {
            error_log("Fehler beim Aktualisieren der Kategorie: " . implode(", ", $stmtUpdate->errorInfo()));
            return [false, "Fehler beim Aktualisieren der Kategorie. Bitte kontaktieren Sie den Administrator."];
        }
    }
}

/**
 * Aktualisiert die Kategorien aller Einsätze in der Datenbank
 * 
 * @param PDO $conn Die Datenbankverbindung
 * @param bool $nurNullWerte Nur NULL-Werte aktualisieren oder alle
 * @return array [int $updatedCount, string $message]
 */
function updateAllKategorien($conn, $nurNullWerte = true)
{
    // SQL-Abfrage, um alle Einsätze zu holen
    $whereClause = $nurNullWerte ? "WHERE `Kategorie` IS NULL OR `Kategorie` = ''" : "";
    $sqlSelect = "SELECT `EinsatzID`, `Sachverhalt`, `Stichwort` FROM `Einsatz` $whereClause";
    $stmt = $conn->query($sqlSelect);
    
    $updatedCount = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $kategorie = getKategorie($row['Sachverhalt'], $row['Stichwort']);
        
        $sqlUpdate = "UPDATE `Einsatz` SET `Kategorie` = ? WHERE `EinsatzID` = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        
        if ($stmtUpdate->execute([$kategorie, $row['EinsatzID']])) {
            $updatedCount++;
        }
    }
    
    return [$updatedCount, "Kategorien für $updatedCount Einsätze aktualisiert."];
} 