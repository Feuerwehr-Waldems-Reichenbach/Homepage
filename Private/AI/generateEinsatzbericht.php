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
Deine Aufgabe ist es, einen Einsatzbericht für die Webseite der Freiwilligen Feuerwehr zu schreiben. Der Bericht soll informativ, ehrlich und nachvollziehbar sein – ohne Übertreibungen, ohne falsche Aussagen und ohne ausgedachte Inhalte.

Der Text richtet sich an die interessierte Öffentlichkeit, insbesondere Bürgerinnen und Bürger aus der Umgebung. Der Stil soll menschlich und leicht lesbar sein, so als hätte ein Mitglied der Feuerwehr den Bericht selbst verfasst – mit Charakter, aber trotzdem sachlich und in einem formal passenden Ton.

Verzichte dabei auf:
- Aussagen über den Erfolg oder Misserfolg des Einsatzes
- Dankesworte oder Grußformeln
- Wiederholungen der Formulierung „die Feuerwehr Reichenbach“ oder dass „wir ausgerückt sind“ – das ergibt sich bereits aus dem Kontext
- das bloße Wiederholen der Einsatzdetails in gleicher Formulierung

Nutze stattdessen:
- eine umformulierte Darstellung des Einsatzablaufs
- kurze Bemerkungen zur Lage, zum Ort oder zu den Gegebenheiten, wenn passend
- wenn die voraushelfer im einsatz waren erwähne das explizit
- bei Fahrzeugen: verwende anstelle der Funkkennung (z. B. 4/19) immer die **Ortsbezeichnung und Fahrzeugtyp**, z. B. „Mannschaftstransportfahrzeug aus Reichenbach“

Die folgenden Einsatzdetails dienen dir als Grundlage. Verwende sie zur Einbettung der Informationen in einem gut lesbaren und interessanten Fließtext. Bitte abstrahiere und formuliere sie um – der Text darf niemals wie ein automatisch generierter Eintrag wirken:

Datum/Zeit: {$start} bis {$end}  
Einsatzstichwort: "{$stichwort}"  
Einsatzort: {$ort}  
Sachverhalt bei Alarmierung: "{$sachverhalt}"  
Kategorie: "{$kategorie}"  
Einsatzgruppe: "{$cleanedEinsatzgruppe}"  
Fahrzeuge im Einsatz: {$cleanedFahrzeuge}

Wenn du Fahrzeuge beschreibst, nutze diese Zuordnung zur besseren Lesbarkeit:
- 1-xx = Bermbach
- 2-xx = Esch
- 3-xx = Niederems
- 4-xx = Reichenbach
- 5-xx = Steinfischbach
- 6-xx = Wüstems

Beispiel:
„4-19“ ist ein Mannschaftstransportfahrzeug aus Reichenbach
„4-48“ ist ein Tragkraftspritzenfahrzeug mit Wassertank aus Reichenbach
„3-82-1“ ist ein Notarzt hier gibt es keinen Ort die zahl am anfang ist egal
„16.83“ ist ein Retungswagen hier gibt es keinen Ort die zahl am anfang ist egal

Der Bericht soll zum Schluss kommen, sobald alle relevanten Informationen vermittelt sind. Keine Floskeln, kein Fazit, kein Werturteil.
Der Komplette Bericht soll in Deutsch geschrieben werden und keine andere Sprache verwenden.

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
    Deine Aufgabe ist es, einen Einsatzbericht für die Webseite der Freiwilligen Feuerwehr zu schreiben. Der Bericht soll informativ, ehrlich und nachvollziehbar sein – ohne Übertreibungen, ohne falsche Aussagen und ohne ausgedachte Inhalte.

    WICHTIG: Es dürfen keine Annahmen getroffen werden. Wenn zu einem Aspekt keine konkrete Information vorliegt, darf er im Text nicht interpretiert, ergänzt oder ausgeschmückt werden. Erfinde niemals Situationen, Beobachtungen oder Abläufe.

    Du darfst allgemeine, nichts-aussagende Formulierungen verwenden, um einen vollständigen Satz zu bilden, aber vermeide alles, was den Anschein einer genauen Aussage erwecken könnte, wenn die Datenlage das nicht hergibt.

    Der Stil soll menschlich und leicht lesbar sein – so, als hätte ein Mitglied der Feuerwehr den Bericht selbst verfasst. Er soll natürlich wirken, aber trotzdem sachlich und formal korrekt sein.

    Verzichte dabei auf:
    - Aussagen über Erfolg oder Ergebnis des Einsatzes
    - Dankesworte, Einschätzungen oder persönliche Meinungen
    - Wiederholungen der Formulierung „die Feuerwehr Reichenbach“ oder dass „wir ausgerückt sind“
    - Formulierungen wie „es stellte sich heraus“, „es wurde festgestellt“, „die Lage war“ – wenn diese Informationen nicht vorliegen
    - jede Art von ausformuliertem Ablauf, der nicht konkret durch die Einsatzdaten gedeckt ist

    Erlaube dir:
    - kurze, zurückhaltende Formulierungen
    - neutrale Umschreibungen wie „es erfolgte eine Erkundung“, „die Lage wurde überprüft“, „weitere Maßnahmen erfolgten je nach Erfordernis“
    - wenn nur sehr wenige Informationen vorhanden sind, darf der Text entsprechend kurz und allgemein bleiben

    Wenn die Voraushelfer beteiligt waren, erwähne das explizit.

    Diese Einsatzdetails dienen dir als Grundlage. Verwende sie zur Einbettung in einen ruhigen, zurückhaltenden Fließtext. Der Bericht soll nicht wie automatisch generiert wirken, sondern wie von einem Mitglied geschrieben.

    Datum/Zeit: {$start} bis {$end}  
    Einsatzstichwort: "{$stichwort}"  
    Einsatzort: {$ort}  
    Sachverhalt bei Alarmierung: "{$sachverhalt}"  
    Kategorie: "{$kategorie}"  
    Einsatzgruppe: "{$einsatzgruppe}"

    Der Bericht soll enden, sobald alle relevanten Informationen ausgedrückt wurden. Kein Fazit, keine Floskeln, keine Wertung. Der komplette Text soll ausschließlich auf Deutsch verfasst sein.

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
