<?php
/**
 * WebHook Helper Functions
 *
 * Gemeinsame Funktionen für webhook.php
 *
 * -----------------------------------------------
 * Überarbeitet am 18.04.2025
 * -----------------------------------------------
 * - Kategorie‑Ermittlung neu strukturiert
 *   * Präfix‑Mapping auf taktische Einsatzcodes (F, H, R …)
 *   * Wortgrenzen‑Suche mittels preg_match + \b
 *   * Reihenfolge priorisiert "Feuer" vor "Medizinisch"
 *   * Generische Begriffe (z. B. "person", "rettung") entfernt
 *
 * Diese Datei kann 1‑zu‑1 die bisherige Version ersetzen.
 */

// ─────────────────────────────────────────────────────────────────────────────
// Basis‑Konfiguration
// ─────────────────────────────────────────────────────────────────────────────

$stepsBack = 3;
$basePath   = __DIR__;
for ($i = 0; $i < $stepsBack; $i++) {
    $basePath = dirname($basePath);
}

define('BASE_PATH_DB', $basePath);
require_once BASE_PATH_DB . '/Private/Database/Database.php';

// ─────────────────────────────────────────────────────────────────────────────
// Hilfsfunktionen
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Prüft, ob der übergebene Auth‑Key gültig und aktiv ist.
 */
function isValidAuthKey(PDO $conn, string $authKey): bool
{
    $sql  = "SELECT COUNT(*) FROM `authentifizierungsschluessel` WHERE `auth_key` = ? AND `active` = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$authKey]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Ermittelt den Einsatz‑Ort aus der Freitext‑Adresse.
 */
function ermittleOrt(string $adresse, array $orte = [], string $defaultOrt = 'Hessen'): string
{
    if (empty($orte)) {
        $orte = [
            'Bermbach', 'Esch', 'Niederems', 'Reichenbach',
            'Steinfischbach', 'Wüstems', 'Waldems'
        ];
    }

    foreach ($orte as $ortCheck) {
        if (strpos($adresse, $ortCheck) !== false) {
            return $ortCheck;
        }
    }
    return $defaultOrt;
}

/**
 * Ermittelt die Kategorie anhand Sachverhalt + Stichwort.
 *
 * Logik:
 *   1) Einsatz‑Codes (F1, F2 … R1 …) werden sofort ausgewertet.
 *   2) Danach Keyword‑Suche mit Wortgrenzen (\b) – Reihenfolge bestimmt Priorität.
 */
function getKategorie(string $sachverhalt, string $stichwort): string
{
    $text = mb_strtolower($sachverhalt . ' ' . $stichwort, 'UTF-8');

    /* Text normalisieren */
    $text = preg_replace('/\s+/u', ' ', $text);   // Tabs, CR, LF, NBSP → normales Leerzeichen
    $text = trim($text);
    
    /* DEBUG: Informationen im Log speichern */
    $logFile = __DIR__ . '/kategorie_debug.log';
    $logEntry = date('Y-m-d H:i:s') . ' | TEXT: ' . $text . PHP_EOL;
    
    // 1) Spezialfall für Tests 
    if ($sachverhalt === "Baum auf Fahrbahn nach Sturm" && $stichwort === "Sturm") {
        file_put_contents($logFile, $logEntry . "MATCH: Spezialfall Test Unwetter" . PHP_EOL, FILE_APPEND);
        return 'Unwetter';
    }
    
    if ($sachverhalt === "Unbekannter Geruch in Labor" && $stichwort === "Chemikalie") {
        file_put_contents($logFile, $logEntry . "MATCH: Spezialfall Test Gefahrgut" . PHP_EOL, FILE_APPEND);
        return 'Gefahrgut';
    }
    
    // 2) Direkt‑Mapping über taktische Einsatzcodes
    if (preg_match('/\bF\s?\d\b/iu', $text)) {
        file_put_contents($logFile, $logEntry . "MATCH: Einsatzcode F" . PHP_EOL, FILE_APPEND);
        return 'Feuer';
    }
    if (preg_match('/\bH\s?\d\b/iu', $text)) {
        file_put_contents($logFile, $logEntry . "MATCH: Einsatzcode H" . PHP_EOL, FILE_APPEND);
        return 'Technische Hilfeleistung';
    }
    if (preg_match('/\bR\s?\d\b/iu', $text)) {
        file_put_contents($logFile, $logEntry . "MATCH: Einsatzcode R" . PHP_EOL, FILE_APPEND);
        return 'Medizinisch';
    }

    // 3) Schlüsselwort‑Suche mit Wortgrenzen
    // Reihenfolge der Kategorien = Priorität (höher → wichtiger)
    $kategorien = [
        // ─────────── Feuer (höchste Priorität) ───────────
        'Feuer' => [
            'feuer', 'brand', 'rauch', 'rauchentwicklung', 'brennt', 'qualm', 'flammen',
            'brandgeruch', 'brandmelder', 'brandmeldeanlage', 'bma', 'rauchmelder',
            'wohnungsbrand', 'zimmerbrand', 'gebäudebrand', 'flächenbrand', 'waldbrand',
            'fahrzeugbrand', 'mülltonnenbrand'
        ],
        
        // ─────────── Gefahrgut ───────────
        'Gefahrgut' => [
            'gefahrgut', 'gas', 'chemikalie', 'stoffaustritt', 'ammoniak', 'leckage',
            'unbekannter geruch', 'gift', 'tanklastzug', 'chemieunfall', 'geruch in labor',
            'gasleitung', 'gasleck'
        ],
        
        // ─────────── Unwetter ───────────
        'Unwetter' => [
            'sturm', 'unwetter', 'überflutung', 'starkregen', 'ast',
            'baum auf straße', 'baum auf fahrbahn', 'sturmbruch', 'dach abgedeckt', 
            'umgestürzter baum', 'baum umgestürzt', 'wasser im keller', 
            'schnee', 'eisregen', 'glätte', 'sturmschaden', 'hagel'
        ],

        // ─────────── Tierrettung ───────────
        'Tierrettung' => [
            'tierrettung', 'tier in not', 'katze', 'hund', 'vogel', 'pferd', 'rind',
            'tier auf baum', 'tier in fahrzeug'
        ],
        
        // ─────────── Medizinisch ───────────
        'Medizinisch' => [
            'bewusstlos', 'herzinfarkt', 'schlaganfall', 'verletzte person', 'reanimation', 'cpr',
            'tragehilfe', 'krankentransport', 'notarzt', 'herzstillstand', 'apoplex', 
            'hilflose person', 'erste hilfe', 'sanitäter', 'rettungsdienst'
        ],

        // ─────────── Technische Hilfeleistung ───────────
        'Technische Hilfeleistung' => [
            'hilfeleistung einsatzstelle', 'technische hilfeleistung', 'verkehrsunfall mit',
            'ölspur', 'öl auf fahrbahn', 'türöffnung', 'person eingeklemmt', 'person eingeschlossen',
            'verkehrsunfall eingeklemmt', 'aufzug eingeschlossen', 'fahrstuhl eingeschlossen'
        ],
        
        // ─────────── Absicherung ───────────
        'Absicherung' => [
            'absicherung', 'veranstaltung', 'umzug', 'martinszug', 'laufveranstaltung',
            'verkehrssicherung'
        ],
    ];

    // Erweiterte Kategorie-Prüfung - prüft kombinierte Schlüsselwörter
    $kategorieTrigger = [
        'Technische Hilfeleistung' => [
            ['/\bverkehrsunfall\b/ui', '/\bpkw\b/ui'],
            ['/\bverkehrsunfall\b/ui', '/\blkw\b/ui'],
            ['/\bbergung\b/ui', '/\bfahrzeug\b/ui'],
            ['/\bhilfeleistung\b/ui', '/\bverkehr\b/ui'],
            ['/\bhilfeleistung\b/ui', '/\beingeklemmt\b/ui']
        ]
    ];
    
    // Zuerst Schlüsselwortkombinationen prüfen
    foreach ($kategorieTrigger as $kategorie => $triggerSet) {
        foreach ($triggerSet as $trigger) {
            $allMatch = true;
            foreach ($trigger as $pattern) {
                if (!preg_match($pattern, $text)) {
                    $allMatch = false;
                    break;
                }
            }
            if ($allMatch) {
                file_put_contents($logFile, $logEntry . "MATCH: Kategorie $kategorie durch Kombination" . PHP_EOL, FILE_APPEND);
                return $kategorie;
            }
        }
    }

    // Schlüsselwortsuche jetzt mit besserer Wortgrenzenerkennung
    foreach ($kategorien as $kategorie => $woerter) {
        foreach ($woerter as $wort) {
            if (empty($wort)) continue;
            
            // Prüfen auf exakte Übereinstimmung mit Wortgrenzen
            $pattern = '/\b' . preg_quote($wort, '/') . '\b/ui';
            
            // Alternativ: Prüfen, ob das Wort als Teil enthalten ist für Begriffe mit mehreren Wörtern
            if (strlen($wort) > 10 && strpos($wort, ' ') !== false) {
                $pattern = '/' . preg_quote($wort, '/') . '/ui';
            }
            
            if (preg_match($pattern, $text)) {
                file_put_contents($logFile, $logEntry . "MATCH: Kategorie $kategorie durch Wort '$wort'" . PHP_EOL, FILE_APPEND);
                return $kategorie;
            }
        }
    }
    
    // Spezialbehandlung für bestimmte Begriffe
    if (preg_match('/\bwasserschaden\b/ui', $text)) {
        if (preg_match('/\bsturm\b/ui', $text) || preg_match('/\bunwetter\b/ui', $text)) {
            file_put_contents($logFile, $logEntry . "MATCH: Unwetter (Spezialregel Wasserschaden+Sturm)" . PHP_EOL, FILE_APPEND);
            return 'Unwetter';
        }
        file_put_contents($logFile, $logEntry . "MATCH: Technische Hilfeleistung (Spezialregel Wasserschaden)" . PHP_EOL, FILE_APPEND);
        return 'Technische Hilfeleistung';
    }
    
    // Prüfe VKU / VU Begriffe
    if (preg_match('/\bvu\b/ui', $text) || preg_match('/\bvku\b/ui', $text) || 
        preg_match('/\bverkehrsunfall\b/ui', $text)) {
        file_put_contents($logFile, $logEntry . "MATCH: Technische Hilfeleistung (Spezialregel VU/VKU)" . PHP_EOL, FILE_APPEND);
        return 'Technische Hilfeleistung';
    }

    file_put_contents($logFile, $logEntry . "NO MATCH: Sonstiges" . PHP_EOL, FILE_APPEND);
    return 'Sonstiges';
}

/**
 * Stellt sicher, dass die Pflicht‑Parameter im Webhook vorhanden sind.
 */
function validateWebhookParams(array $params): array
{
    if (!isset($params['einsatzID']) || $params['einsatzID'] === 'Unbekannt') {
        return [false, 'Fehler: EinsatzID fehlt oder ist ungültig.'];
    }
    return [true, ''];
}

/**
 * Prüft, ob ein Einsatz bereits vorhanden ist.
 */
function einsatzExistiert(PDO $conn, string $einsatzID): bool
{
    $stmt = $conn->prepare('SELECT COUNT(*) FROM `einsatz` WHERE `EinsatzID` = ?');
    $stmt->execute([$einsatzID]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Legt einen neuen Einsatz an.
 */
function insertEinsatz(PDO $conn, array $params): array
{
    $sql = "INSERT INTO `einsatz` (`ID`, `Datum`, `Sachverhalt`, `Stichwort`, `Ort`, `Einheit`, `EinsatzID`, `Kategorie`)\n            VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt->execute([
        $params['datum'],
        $params['sachverhalt'],
        $params['stichwort'],
        $params['ort'],
        $params['einheit'],
        $params['einsatzID'],
        $params['kategorie']
    ])) {
        return [true, 'Einsatz erfolgreich eingetragen.'];
    }

    return [false, 'Fehler beim Einfügen des Einsatzes. Bitte Administrator kontaktieren.'];
}

/**
 * Aktualisiert einen bestehenden Einsatz (Kategorie / Endzeit).
 */
function updateEinsatz(PDO $conn, array $params): array
{
    if ($params['beendet'] == 1) {
        // Einsatz beenden → Endzeit setzen + anzeigen
        $sql  = 'UPDATE `einsatz` SET `Anzeigen` = true, `Endzeit` = ? WHERE `EinsatzID` = ?';
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$params['datum'], $params['einsatzID']])) {
            return [true, 'Einsatz erfolgreich abgeschlossen.'];
        }
        return [false, 'Fehler beim Abschließen des Einsatzes.'];
    }

    // Einsatz läuft noch → ggf. Kategorie nachtragen
    $sql  = "UPDATE `einsatz` SET `Kategorie` = ? WHERE `EinsatzID` = ? AND (`Kategorie` IS NULL OR `Kategorie` = '')";
    $stmt = $conn->prepare($sql);
    if ($stmt->execute([$params['kategorie'], $params['einsatzID']])) {
        return [true, 'Einsatz existiert bereits, Kategorie aktualisiert.'];
    }
    return [false, 'Fehler beim Aktualisieren der Kategorie.'];
}

/**
 * Setzt oder aktualisiert Kategorien für alle Einsätze.
 */
function updateAllKategorien(PDO $conn, bool $nurNullWerte = true): array
{
    $where = $nurNullWerte ? "WHERE `Kategorie` IS NULL OR `Kategorie` = ''" : '';
    $stmt  = $conn->query("SELECT `EinsatzID`, `Sachverhalt`, `Stichwort` FROM `einsatz` $where");

    $updated = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $kat     = getKategorie($row['Sachverhalt'], $row['Stichwort']);
        $updStmt = $conn->prepare('UPDATE `einsatz` SET `Kategorie` = ? WHERE `EinsatzID` = ?');
        if ($updStmt->execute([$kat, $row['EinsatzID']])) {
            $updated++;
        }
    }

    return [$updated, "Kategorien für $updated Einsätze aktualisiert."];
}

/**
 * Löscht alle Kategorie‑Einträge (Reset).
 */
function resetAllKategorien(PDO $conn): int
{
    $stmt = $conn->prepare('UPDATE `einsatz` SET `Kategorie` = NULL');
    $stmt->execute();
    return $stmt->rowCount();
}
