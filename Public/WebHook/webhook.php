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
// Wenn Validierung fehlschlägt, versuche vollständige URL manuell zu parsen (für Fälle mit # im URL)
if (!$isValid && strpos($errorMessage, "EinsatzID fehlt") !== false) {
    // Vollständige URL aus Server-Variablen rekonstruieren
    $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
        "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    echo "Full URL: " . $fullUrl . "\n";
    // Direktes Parsen des URL-Query-Strings
    $rawQuery = $_SERVER['QUERY_STRING'] ?? '';
    echo "Raw Query: " . $rawQuery . "\n";
    // Nach einsatzID in der URL suchen
    if (preg_match('/einsatzID=([^&]+)/', $fullUrl, $matches)) {
        $params['einsatzID'] = urldecode($matches[1]);
        echo "EinsatzID gefunden: " . $params['einsatzID'] . "\n";
        // Neuer Validierungsversuch mit der gefundenen einsatzID
        list($isValid, $errorMessage) = validateWebhookParams($params);
    } else {
        echo "EinsatzID nicht gefunden - generiere Ersatz-ID\n";
        // Generiere eine eindeutige ID beginnend mit "999"
        // Verwende einen Teil der Adresse + Zeitstempel für Eindeutigkeit
        $adresseHash = '';
        if (isset($_GET['adresse']) && !empty($_GET['adresse'])) {
            // Nehme nur die Zahlen aus der Adresse für den Hash
            preg_match_all('/\d+/', $_GET['adresse'], $matches);
            $adresseHash = implode('', $matches[0]);
            // Beschränke auf 6 Zeichen, wenn vorhanden
            $adresseHash = substr($adresseHash, 0, min(6, strlen($adresseHash)));
        }
        // Aktuelle Uhrzeit in Sekunden seit Mitternacht
        $sekundenSeitMitternacht = (time() - strtotime('today')) % 100000;
        // Erstelle die EinsatzID mit 999 + AdresseHash + Zeit
        $params['einsatzID'] = '999' . $adresseHash . $sekundenSeitMitternacht;
        echo "Generierte EinsatzID: " . $params['einsatzID'] . "\n";
        // Neuer Validierungsversuch mit der generierten einsatzID
        list($isValid, $errorMessage) = validateWebhookParams($params);
    }
}
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

    // Wenn der Einsatz beendet wurde (beendet=1), einen Einsatzbericht automatisch generieren
    if ($params['beendet'] == 1 && $success) {
        echo "\nEinsatz beendet. Generiere Einsatzbericht...\n";
        
        // Hole alle notwendigen Daten für den Einsatzbericht
        try {
            // Die interne ID des Einsatzes abrufen (nicht die EinsatzID)
            $sql = "SELECT ID, Datum, Endzeit, Stichwort, Ort, Sachverhalt, Kategorie, Einheit FROM einsatz WHERE EinsatzID = :einsatzID";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':einsatzID', $params['einsatzID'], PDO::PARAM_STR);
            $stmt->execute();
            $einsatzData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($einsatzData) {
                // Fahrzeuge aus den Parametern extrahieren (falls vorhanden)
                $fahrzeuge = isset($_GET['fahrzeuge']) ? $_GET['fahrzeuge'] : "";
                
                // Einsatzbericht generieren
                require_once __DIR__ . '/../../Private/AI/generateEinsatzbericht.php';
                
                $bericht = generateEinsatzbericht(
                    $einsatzData['ID'],
                    $einsatzData['Datum'],
                    $einsatzData['Endzeit'],
                    $einsatzData['Stichwort'],
                    $einsatzData['Ort'],
                    $einsatzData['Sachverhalt'],
                    $einsatzData['Kategorie'],
                    $einsatzData['Einheit'],
                    $fahrzeuge,
                    true,  // In Datenbank speichern
                    '',    // Standardüberschrift verwenden
                    '',    // Kein Bild
                    false  // Nicht öffentlich, muss zuerst überprüft werden
                );
                
                echo "Einsatzbericht erfolgreich generiert.\n";
            } else {
                echo "Fehler: Einsatzdaten konnten nicht abgerufen werden.\n";
            }
        } catch (PDOException $e) {
            echo "Fehler bei der Einsatzberichtsgenerierung: " . $e->getMessage() . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Ein technischer Fehler ist aufgetreten. Bitte kontaktieren Sie den Administrator." . $e->getMessage();
}
?>