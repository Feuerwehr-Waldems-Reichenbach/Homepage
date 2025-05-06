<?php
require_once __DIR__ . '/sendLLMRequest.php';
require_once __DIR__ . '/../Database/Database.php';
require_once __DIR__ . '/../Email/emailSender.php';

/**
 * Generiert einen Einsatzbericht anhand gegebener Einsatzdaten und speichert ihn in der Datenbank.
 *
 * @param int $einsatz_ID     Einsatz-ID
 * @param string $start       Startdatum/-zeit
 * @param string $end         Enddatum/-zeit
 * @param string $stichwort   Einsatzstichwort
 * @param string $ort         Einsatzort
 * @param string $sachverhalt Beschreibung des gemeldeten Sachverhalts
 * @param string $kategorie   Kategorisierung (z. B. Brand, Medizinisch, etc.)
 * @param string $einsatzgruppe Gruppenbezeichnung
 * @param string $fahrzeuge   Kommagetrennte Fahrzeugliste (z. B. "4/19, 4/44")
 * @param bool $saveToDatabase Ob der generierte Bericht in der Datenbank gespeichert werden soll
 * @param string $headline Optional: Überschrift für den Einsatzbericht (falls nicht angegeben, wird das Stichwort verwendet)
 * @param string $imagePath Optional: Pfad zum Einsatzbild
 * @param bool $isPublic Optional: Ob der Bericht öffentlich angezeigt werden soll
 *
 * @return string Der generierte Einsatzbericht
 */
function generateEinsatzbericht(
    int $einsatz_ID,
    string $start,
    string $end,
    string $stichwort,
    string $ort,
    string $sachverhalt,
    string $kategorie,
    string $einsatzgruppe,
    string $fahrzeuge,
    bool $saveToDatabase = false,
    string $headline = '',
    string $imagePath = '',
    bool $isPublic = false
): string {
    $cleanedFahrzeuge = implode(', ', preg_match_all('/\b\d+(?:[\/-]\d+){1,2}\b/', $fahrzeuge, $m) ? $m[0] : []);
    $cleanedEinsatzgruppe = str_replace('Alle Monitore', '', $einsatzgruppe);

    $prompt = <<<PROMPT
    Deine Aufgabe ist es, einen Einsatzbericht für die Webseite der Freiwilligen Feuerwehr Reichenbach zu verfassen. Der Text soll sachlich bleiben, aber lebendig und für Bürgerinnen und Bürger nachvollziehbar geschrieben sein. Stelle dir vor, ein Feuerwehrkamerad erzählt den Einsatz in ruhiger, aber prägnanter Sprache mit Charakter, ohne jedoch zu bewerten oder zu übertreiben.
    
    ### Wichtige Grundsätze:
    - **Keine Erfindungen:** Verwende ausschließlich die gegebenen Einsatzdetails. Interpretiere keine Ergebnisse, schreibe keine Szenarien, die nicht belegt sind.
    - **Natürliche Sprache:** Nutze aktive Formulierungen, vermeide starre Satzmuster. Der Text soll wie von Mensch zu Mensch wirken.
    - **Abwechslungsreich, aber präzise:** Variiere Satzlänge und -struktur, ohne vom Faktischen abzuweichen.
    
    ### Verbotene Formulierungen:
    - Bewertungen wie „erfolgreich“, „kritisch“, „glücklich“ oder „schwerwiegend“
    - Wiederholte Nennung von „Freiwillige Feuerwehr Reichenbach“ – halte dich an die Fahrzeug-Ort-Verknüpfung
    - Direkte Übernahme der Einsatzdetails in Originalform (z.B. „Sachverhalt: {$sachverhalt}“) formuliere diese aus und erkläre gegebenfalls was der Sachverhalt aussagt und für einsatzkräfte normalerweise allgemein bedeutet.
    
    ### Erlaubte und gewünschte Formulierungen:
    - **Fahrzeugbeschreibungen:** Übersetze Funkkennungen in verständliche Begriffe:
      - **Beispiele:**  
        - „4-19“ → Mannschaftstransportfahrzeug aus Reichenbach 
        - „4-48“ → Tragkraftspritzenfahrzeug mit Wassertank aus Reichenbach 
        - „3-82-1“ → Notarzt (kein Ort nötig)  
        - „16.83“ → Rettungswagen (kein Ort nötig)
        - für alle anderen fahrzeuge nenne immer nur die Nummer 
    - **Einsatzablauf:** Nutze flüssige Abfolgen wie „Nach Eintreffen… | Vor Ort… | Anschließend…“, ohne Details zu erfinden
    - **Kontextuelle Einbettung:** Leichte Erwähnungen wie „aufgrund der Wetterbedingungen“, „bei Dunkelheit“ – **nur wenn die Gegebenheiten belegt sind oder realistisch, wie wenn es 23 uhr ist, dann ist es dunkel**
    - **Voraushelfer:** Nenne sie explizit, wenn im Einsatz: „Ein Voraushelfer unterstützte bereits vor unserem Eintreffen“
    
    ### Strukturvorschlag:
    1. **Einleitung:** Datum/Zeit + Einsatzort + Stichwort in einer flüssigen Einleitung
    2. **Hauptteil:** 
       - Einsatzgruppe & Fahrzeuge
       - Kurze Beschreibung der Tätigkeiten (ohne Ergebnisse zu nennen)
       - Erwähnung von Voraushelfern, Wetterbedingungen oder spezifischen Gegebenheiten, **wenn bekannt**
    3. **Abschluss:** Natürliche Beendigung nach letzter Faktenmeldung – kein Fazit, keine Floskel
    
    ### Beispiel für Fahrzeugintegration:
    „Mit dem Tragkraftspritzenfahrzeug aus Reichenbach und dem Rettungswagen rückten die Einsatzkräfte zum Einsatzort vor.“
    
    ### Einsatzdetails als Grundlage:
    Datum/Zeit: {$start} bis {$end}  
    Einsatzstichwort: "{$stichwort}"  
    Einsatzort: {$ort}  
    Sachverhalt bei Alarmierung: "{$sachverhalt}"  
    Kategorie: "{$kategorie}"  
    Einsatzgruppe: "{$cleanedEinsatzgruppe}"  
    Fahrzeuge im Einsatz: {$cleanedFahrzeuge}
    
    Der Bericht soll enden, sobald alle relevanten Informationen ausgedrückt wurden – ohne Schlusssatz, Bewertung oder Dank.
    PROMPT;

    $generatedText = sendLLMRequest($prompt);


    $cleanedText = preg_replace('/^\s*(<think>.*?<\/think>\s*)+/s', '', $generatedText);


    logEinsatzGeneration($einsatz_ID, $stichwort, $ort, $generatedText, $cleanedText);
    
    // Wenn der Bericht in der Datenbank gespeichert werden soll
    if ($saveToDatabase) {
        // Standardüberschrift verwenden, falls keine angegeben wurde
        if (empty($headline)) {
            $headline = $stichwort;
        }
        
        // Datenbankverbindung herstellen
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // SQL-Statement zum Einfügen des Einsatzberichts
        $sql = "INSERT INTO einsatz_Details (einsatz_id, image_path, einsatz_headline, einsatz_text, is_public) 
                VALUES (:einsatz_id, :image_path, :einsatz_headline, :einsatz_text, :is_public)";
        
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':einsatz_id', $einsatz_ID, PDO::PARAM_INT);
            $stmt->bindParam(':image_path', $imagePath, PDO::PARAM_STR);
            $stmt->bindParam(':einsatz_headline', $headline, PDO::PARAM_STR);
            $stmt->bindParam(':einsatz_text', $cleanedText, PDO::PARAM_STR);
            $stmt->bindParam(':is_public', $isPublic, PDO::PARAM_BOOL);
            $stmt->execute();
        } catch (PDOException $e) {
            // Im Fehlerfall nur den generierten Text zurückgeben
            error_log("Fehler beim Speichern des Einsatzberichts: " . $e->getMessage());
        }
    }
    
    return $cleanedText;
}

function generateEinsatzbericht2(
    int $einsatz_ID,
    string $start,
    string $end,
    string $stichwort,
    string $kategorie,
    string $einsatzgruppe,
    string $sachverhalt,
    string $ort,
) {
    $prompt = <<<PROMPT
    Deine Aufgabe ist es, einen Einsatzbericht für die Webseite der Freiwilligen Feuerwehr Reichenbach zu verfassen. Der Text soll sachlich bleiben, aber lebendig und menschlich wirken. 

    WICHTIG: 
    - Es dürfen keine Informationen hinzuerfunden werden, die nicht in den Einsatzdaten enthalten sind.
    - Vermeide spekulative Formulierungen, Bewertungen oder Interpretationen. 
    - Wenn Details fehlen, nutze allgemeine, aber flüssige Formulierungen, um einen zusammenhängenden Text zu erzeugen.

    Stilvorgaben:
    - Schreibe im aktivischen Präsens ("Die Einsatzkräfte erkunden die Lage" statt "Es erfolgte eine Erkundung")
    - Verwende natürliche Satzkonstruktionen mit variierter Länge und Struktur
    - Nutze leichte Abwechslung in der Darstellung (z.B. "Die Teammitglieder prüften..."/"Im Einsatz zeigte sich...")
    - Vermeide monotone Wiederholungen von Satzanfängen

    Erlaube dir:
    - Leichte Verben wie "prüfen", "erkennen", "stellen fest", "handeln" (ohne spezifische Ergebnisse zu nennen)
    - Natürliche Abfolgen wie "Nach Eintreffen... | Vor Ort... | Anschließend..."
    - Allgemeine Beschreibungen von Tätigkeiten wie "die Einsatzkräfte sicherten die Einsatzstelle", "es wurden weitere Kräfte nachgezogen"

    Verzichte weiterhin auf:
    - Jede Form von Bewertung, Dank oder Reflexion
    - Feste Phrasen wie "es stellte sich heraus", "die Lage war", "Erfolg wurde erzielt"
    - Wiederholte Erwähnung von "die Feuerwehr Reichenbach" oder "wir rückten aus"

    Sonstiges:
    - Erwähne Voraushelfer explizit, wenn beteiligt
    - Halte die Textlänge proportional zur Informationsdichte (kurz bei wenig Daten, länger bei komplexen Einsätzen)
    - Enden Sie mit dem letzten relevanten Fakt, kein Fazit oder Schlusssatz


    ### Strukturvorschlag:
    1. **Einleitung:** Datum/Zeit + Einsatzort + Stichwort in einer flüssigen Einleitung
    2. **Hauptteil:** 
       - Einsatzgruppe & Fahrzeuge
       - Kurze Beschreibung der Tätigkeiten (ohne Ergebnisse zu nennen)
       - Erwähnung von Voraushelfern, Wetterbedingungen oder spezifischen Gegebenheiten, **wenn bekannt**
    3. **Abschluss:** Natürliche Beendigung nach letzter Faktenmeldung – kein Fazit, keine Floskel
    

    Einsatzdetails:  
    Datum/Zeit: {$start} bis {$end}  
    Einsatzstichwort: "{$stichwort}"  
    Einsatzort: {$ort}  
    Sachverhalt bei Alarmierung: "{$sachverhalt}"  
    Kategorie: "{$kategorie}"  
    Einsatzgruppe: "{$einsatzgruppe}"

    Der Bericht soll authentisch wirken, wie von einem Feuerwehrangehörigen geschrieben, aber strikt auf den Fakten basieren.
PROMPT;

    $generatedText = sendLLMRequest($prompt);

    $cleanedText = preg_replace('/^\s*(<think>.*?<\/think>\s*)+/s', '', $generatedText);

    logEinsatzGeneration($einsatz_ID, $stichwort, $ort, $generatedText, $cleanedText);

    return $cleanedText;
}

/**
 * Schreibt einen Logeintrag in eine Datei im aktuellen Verzeichnis.
 *
 * @param int $einsatzId
 * @param string $stichwort
 * @param string $ort
 * @param string $bericht
 * @return void
 */
function logEinsatzGeneration(int $einsatzId, string $stichwort, string $ort, string $bericht, string $cleanedText): void
{
    $logPath = __DIR__ . '/einsatzbericht.log';
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[{$timestamp}] Bericht für Einsatz #{$einsatzId} ({$stichwort}, {$ort}) generiert.\n------v Original v------\n{$bericht}\n-------v Cleaned v------\n\n {$cleanedText} \n------------\n\n";

    file_put_contents($logPath, $entry, FILE_APPEND);

    $config = parse_ini_file("emailNotification.ini");
    $receiver = $config['email'];
    $subject = 'Einsatzbericht generiert';
    $body = 'Ein Einsatzbericht für Einsatz #'.$einsatzId . '(' .$stichwort . ', ' .$ort .') wurde generiert.';
    sendEmail($receiver, $subject, $body);
}
