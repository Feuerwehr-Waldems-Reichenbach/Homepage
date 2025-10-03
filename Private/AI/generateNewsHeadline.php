<?php
require_once __DIR__ . '/sendLLMRequest.php';

/**
 * Generiert eine passende Überschrift für eine Neuigkeit.
 *
 * @param string $thema Das Hauptthema der Neuigkeit
 * @param string $ort Der Ort der Veranstaltung/Neuigkeit
 * @param string $datum Das Datum der Veranstaltung/Neuigkeit
 * @param string $text Der Neuigkeiten-Text (optional)
 * @return string Die generierte Überschrift
 * @throws Exception Wenn ein Fehler bei der API-Anfrage auftritt
 */
function generateNewsHeadline(
    string $thema,
    string $ort,
    string $datum,
    string $text = ''
): string {
    $datumFormatted = date('d.m.Y', strtotime($datum));
    
    $prompt = <<<PROMPT
Deine Aufgabe ist es, eine einprägsame und informative Überschrift für eine Neuigkeit der Feuerwehr Reichenbach zu erstellen.

WICHTIGE REGELN:
- Die Überschrift soll kurz und prägnant sein (maximal 8-10 Wörter)
- Sie soll das Wesentliche erfassen und Interesse wecken
- Verwende eine aktive, einladende Sprache
- Keine Clickbait-Formulierungen
- Keine Fragen als Überschrift
- Keine Sonderzeichen außer Bindestrich
- Gib NUR die Überschrift zurück, KEINE Erklärungen

STIL-BEISPIELE:
- "Tag der offenen Tür am 15. Mai"
- "Jugendfeuerwehr sammelt für guten Zweck"
- "Neue Fahrzeuge für die Einsatzabteilung"
- "Erfolgreiche Übung mit Nachbarwehren"

Informationen:
Thema: {$thema}
Ort: {$ort}
Datum: {$datumFormatted}
PROMPT;

    if (!empty($text)) {
        $prompt .= "\n\nText der Neuigkeit:\n{$text}";
    }
    
    $prompt .= "\n\nÜberschrift:";

    $headline = sendLLMRequest($prompt);
    
    // Entferne mögliche <think>-Tags oder ähnliche Artefakte
    $headline = preg_replace('/^\s*(<think>.*?<\/think>\s*)+/s', '', $headline);
    
    // Entferne Anführungszeichen am Anfang und Ende
    $headline = trim($headline, " \t\n\r\0\x0B\"'");
    
    return trim($headline);
}

