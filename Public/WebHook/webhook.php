<?php
/**
 * Webhook für Einsatzdaten
 * 
 * Empfängt Einsatzdaten über GET-Parameter und speichert sie in der Datenbank.
 * Unterstützt das Einfügen neuer Einsätze und das Aktualisieren bestehender Einsätze.
 * 
 * Webhook bei alarmierung: https://feuerwehr-waldems-reichenbach.de/WebHook/webhook.php?auth_key=KEY&kategorie={Kategorie}&stichwort={Stichwort}&stichwortuebersetzung={Stichwortübersetzung}&standort={Standort}&sachverhalt={Sachverhalt}&adresse={Adresse}&einsatzID={Einsatz-ID}&ric={RIC}&alarmgruppen={Alarmgruppen}&infogruppen={Infogruppen}&fahrzeuge={Fahrzeuge}
 * Webhook bei Einsatzende: https://feuerwehr-waldems-reichenbach.de/WebHook/webhook.php?auth_key=KEY&beendet=1&kategorie={Kategorie}&stichwort={Stichwort}&stichwortuebersetzung={Stichwortübersetzung}&standort={Standort}&sachverhalt={Sachverhalt}&adresse={Adresse}&einsatzID={Einsatz-ID}&ric={RIC}&alarmgruppen={Alarmgruppen}&infogruppen={Infogruppen}&fahrzeuge={Fahrzeuge}
 */

// Gemeinsame Funktionen einbinden
require_once __DIR__ . '/Helpers/helpers.php';

// HTTP-Header für Webhook-Antwort setzen
header('Content-Type: text/plain; charset=utf-8');

// Prüfen, ob ein Authentifizierungsschlüssel mitgesendet wurde
if (!isset($_GET['auth_key']) || empty($_GET['auth_key'])) {
    echo "Fehler: Kein Authentifizierungsschlüssel angegeben.";
    exit;
}

// DB-Verbindung herstellen
$db = Database::getInstance();
$conn = $db->getConnection();

// Authentifizierungsschlüssel überprüfen
$authKey = $_GET['auth_key'];
if (!isValidAuthKey($conn, $authKey)) {
    echo "Fehler: Ungültiger Authentifizierungsschlüssel.";
    exit;
}

// Alle GET-Parameter sammeln und standardmäßig auf 'Unbekannt' setzen, falls nicht vorhanden
$params = [
    'stichwort' => $_GET['stichwort'] ?? 'Unbekannt',
    'stichwortuebersetzung' => $_GET['stichwortuebersetzung'] ?? 'Unbekannt',
    'standort' => $_GET['standort'] ?? 'Unbekannt',
    'sachverhalt' => $_GET['sachverhalt'] ?? 'Unbekannt',
    'adresse' => $_GET['adresse'] ?? 'Unbekannt',
    'einsatzID' => $_GET['einsatzID'] ?? 'Unbekannt',
    'alarmgruppen' => $_GET['alarmgruppen'] ?? 'Unbekannt',
    'beendet' => isset($_GET['beendet']) && ($_GET['beendet'] === '1' || $_GET['beendet'] === 1) ? 1 : 0,
    'datum' => date("Y-m-d H:i:s") // Aktuelles Datum und Uhrzeit
];

// Einige Parameter aufbereiten
$params['einheit'] = $params['alarmgruppen'];
$params['stichwort'] = !empty($params['stichwortuebersetzung']) ? $params['stichwortuebersetzung'] : $params['stichwort'];

// Ausgabe starten
echo "Webhook empfangen: ";

// Parameter validieren
list($isValid, $errorMessage) = validateWebhookParams($params);
if (!$isValid) {
    echo $errorMessage;
    exit;
}

// Ort ermitteln
$params['ort'] = ermittleOrt($params['adresse']);

// Sachverhalt setzen, falls leer
if (empty($params['sachverhalt'])) {
    $params['sachverhalt'] = $params['stichwort'];
}

// Einheit setzen, falls leer
if (empty($params['einheit'])) {
    $params['einheit'] = "Feuerwehr Reichenbach";
}

// Kategorie ermitteln
$params['kategorie'] = getKategorie($params['sachverhalt'], $params['stichwort']);

try {
    // Prüfen, ob der Einsatz bereits existiert
    $exists = einsatzExistiert($conn, $params['einsatzID']);

    if ($exists) {
        // Einsatz existiert bereits, aktualisieren
        list($success, $message) = updateEinsatz($conn, $params);
        echo $message;
    } else {
        // Neuer Einsatz, einfügen
        list($success, $message) = insertEinsatz($conn, $params);
        echo $message;
    }
} catch (PDOException $e) {
    error_log("Fehler bei der Datenbankabfrage im WebHook: " . $e->getMessage());
    echo "Ein technischer Fehler ist aufgetreten. Bitte kontaktieren Sie den Administrator.";
}
?>