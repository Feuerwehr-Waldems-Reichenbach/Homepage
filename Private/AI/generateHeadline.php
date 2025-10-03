<?php
require_once __DIR__ . '/sendLLMRequest.php';

/**
 * Generiert eine passende Überschrift für einen Einsatzbericht.
 *
 * @param string $stichwort Das Einsatzstichwort
 * @param string $sachverhalt Der gemeldete Sachverhalt
 * @param string $ort Der Einsatzort
 * @param string $kategorie Die Einsatzkategorie (optional)
 * @param string $reportText Der Berichtstext (optional, falls vorhanden)
 * @return string Die generierte Überschrift
 * @throws Exception Wenn ein Fehler bei der API-Anfrage auftritt
 */
function generateHeadline(
    string $stichwort,
    string $sachverhalt,
    string $ort,
    string $kategorie = '',
    string $reportText = ''
): string {
    $prompt = <<<PROMPT
Deine Aufgabe ist es, eine prägnante und informative Überschrift für einen Einsatzbericht der Feuerwehr zu generieren.

WICHTIGE REGELN:
- Die Überschrift soll kurz und prägnant sein (maximal 10 Wörter)
- Sie soll das Wesentliche des Einsatzes erfassen
- Verwende eine sachliche Sprache
- Keine Bewertungen oder Emotionen
- Keine kompletten Sätze, sondern eine aussagekräftige Überschrift
- Gib NUR die Überschrift zurück, KEINE Erklärungen

Einsatzdaten:
Stichwort: {$stichwort}
Sachverhalt: {$sachverhalt}
Ort: {$ort}
PROMPT;

    if (!empty($kategorie)) {
        $prompt .= "\nKategorie: {$kategorie}";
    }
    
    if (!empty($reportText)) {
        $prompt .= "\n\nBerichtstext:\n{$reportText}";
    }
    
    $prompt .= "\n\nÜberschrift:";

    $headline = sendLLMRequest($prompt);
    
    // Entferne mögliche <think>-Tags oder ähnliche Artefakte
    $headline = preg_replace('/^\s*(<think>.*?<\/think>\s*)+/s', '', $headline);
    
    // Entferne Anführungszeichen am Anfang und Ende, falls vorhanden
    $headline = trim($headline, " \t\n\r\0\x0B\"'");
    
    return trim($headline);
}

