<?php
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/Private/Database/Database.php';

// Prüfen, ob eine ID angegeben wurde
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Keine ID angegeben';
    exit;
}

$id = (int)$_GET['id'];

try {
    $db = Database::getInstance()->getConnection();
    
    // Neuigkeit aus der Datenbank abrufen
    $stmt = $db->prepare("SELECT * FROM neuigkeiten WHERE ID = :id AND aktiv = 1");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $neuigkeit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$neuigkeit) {
        header('HTTP/1.1 404 Not Found');
        echo 'Neuigkeit nicht gefunden';
        exit;
    }
    
    // Datum verarbeiten - wir nehmen an, dass nur ein Datum in der Datenbank gespeichert ist
    // Falls Start- und Endzeit benötigt werden, müsste die Datenbankstruktur angepasst werden
    $datum = new DateTime($neuigkeit['Datum']);
    $datumStart = $datum->format('Ymd\THis\Z'); // iCalendar-Format
    
    // Enddatum 1 Stunde später (kann nach Bedarf angepasst werden)
    $datum->modify('+1 hour');
    $datumEnde = $datum->format('Ymd\THis\Z');
    
    // Zufällige ID für den Kalendereintrag generieren
    $uid = md5(uniqid(mt_rand(), true)) . '@feuerwehr-website.de';
    
    // Aktuelle Zeit für DTSTAMP
    $jetzt = new DateTime();
    $jetzt = $jetzt->format('Ymd\THis\Z');
    
    // iCalendar-Datei erstellen
    $ical = "BEGIN:VCALENDAR\r\n";
    $ical .= "VERSION:2.0\r\n";
    $ical .= "PRODID:-//Feuerwehr//NONSGML Neuigkeiten//DE\r\n";
    $ical .= "METHOD:PUBLISH\r\n";
    $ical .= "BEGIN:VEVENT\r\n";
    $ical .= "UID:" . $uid . "\r\n";
    $ical .= "DTSTAMP:" . $jetzt . "\r\n";
    $ical .= "DTSTART:" . $datumStart . "\r\n";
    $ical .= "DTEND:" . $datumEnde . "\r\n";
    $ical .= "SUMMARY:" . $neuigkeit['Ueberschrift'] . "\r\n";
    
    // Beschreibung: Information aus der Datenbank
    // iCalendar erfordert spezielle Formatierung für mehrzeiligen Text
    $beschreibung = str_replace("\r\n", "\\n", $neuigkeit['Information']);
    $beschreibung = str_replace("\n", "\\n", $beschreibung);
    $ical .= "DESCRIPTION:" . $beschreibung . "\r\n";
    
    // Ort hinzufügen, falls vorhanden
    if (!empty($neuigkeit['Ort'])) {
        $ical .= "LOCATION:" . $neuigkeit['Ort'] . "\r\n";
    }
    
    $ical .= "END:VEVENT\r\n";
    $ical .= "END:VCALENDAR\r\n";
    
    // Header für den Download setzen
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="Neuigkeit_' . $id . '.ics"');
    
    // iCalendar-Datei ausgeben
    echo $ical;
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Fehler: ' . $e->getMessage();
}
?> 