<?php
require_once __DIR__ . '/sendLLMRequest.php';
require_once __DIR__ . '/../Database/Database.php';

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
    string $imagePath = ''
): string {
    $prompt = <<<PROMPT
Deine Aufgabe ist es einen Einsatzbericht zu schreiben. Das Ziel ist einen detaillierten Bericht zu schreiben, der Bericht soll menschlich klingen, keine falschen Aussagen machen, keine Dinge dazuerfinden.

Die Texte sollen dazu dienen die Bevölkerung darüber zu informieren was wir die Freiwillige Feuerwehr Reichenbach in dem Feuerwehr Einsatz gemacht haben. Er soll informativ und natürlich klingen.

Hier sind die Einsatzdetails: Wir waren heute am {$start} bis {$end} im Einsatz mit dem Einsatzstichwort "{$stichwort}" sind wir nach {$ort} gefahren, der bei der Alarmierung angegebene Sachverhalt lautete "{$sachverhalt}" was in die Kategorie "{$kategorie}" fällt. Ausgerückt sind die "{$einsatzgruppe}" mit den Fahrzeugen "{$fahrzeuge}".

Die Fahrzeuge werden durch Nummern zu den Ortsteilen identifiziert. Dazu haben die Autos Nummern: vor dem "/" ist die Ortnummer – die 1 gehören zu Bermbach, 2 zu Esch, die 3 zu Niederems, die 4 zu Reichenbach, die 5 zu Steinfischbach und die 6 zu Wüstems. Die Zahl danach gibt den Fahrzeugtyp an. Das bedeutet der "4/19" wäre das Mannschaftstransportfahrzeug aus Reichenbach oder der "1/48" wäre ein Tragkraftspritzenfahrzeug mit Wasser aus Bermbach. Es haben nur die Fahrzeuge am Einsatz teilgenommen, die mit nummer aufgeführt sind.

Der Einsatzbericht soll keine personenbezogenen Daten enthalten. Der Bericht soll menschlich klingen und Bemerkungen zu verschiedenen Informationen machen. Der Charakter und Charme einer kleinen Freiwilligen Feuerwehr kann im Text erkennbar sein, dabei soll er trotzdem formal und rechtlich korrekt bleiben.
PROMPT;

    $generatedText = sendLLMRequest($prompt);
    
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
        $sql = "INSERT INTO einsatz_Details (einsatz_id, image_path, einsatz_headline, einsatz_text) 
                VALUES (:einsatz_id, :image_path, :einsatz_headline, :einsatz_text)";
        
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':einsatz_id', $einsatz_ID, PDO::PARAM_INT);
            $stmt->bindParam(':image_path', $imagePath, PDO::PARAM_STR);
            $stmt->bindParam(':einsatz_headline', $headline, PDO::PARAM_STR);
            $stmt->bindParam(':einsatz_text', $generatedText, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            // Im Fehlerfall nur den generierten Text zurückgeben
            error_log("Fehler beim Speichern des Einsatzberichts: " . $e->getMessage());
        }
    }
    
    return $generatedText;
}
