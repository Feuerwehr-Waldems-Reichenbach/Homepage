<?php
require_once __DIR__ . '/sendLLMRequest.php';

/**
 * Generiert einen Neuigkeiten-Text basierend auf gegebenen Informationen.
 *
 * @param string $thema Das Hauptthema/Stichwort der Neuigkeit
 * @param string $ort Der Ort der Veranstaltung/Neuigkeit
 * @param string $datum Das Datum der Veranstaltung/Neuigkeit
 * @param string $zusatzInfo Optionale zusätzliche Informationen
 * @return string Der generierte Neuigkeiten-Text
 * @throws Exception Wenn ein Fehler bei der API-Anfrage auftritt
 */
function generateNewsText(
    string $thema,
    string $ort,
    string $datum,
    string $zusatzInfo = ''
): string {
    $datumFormatted = date('d.m.Y', strtotime($datum));
    
    $prompt = <<<PROMPT
Deine Aufgabe ist es, einen informativen und ansprechenden Text für die Webseite der Freiwilligen Feuerwehr Reichenbach zu verfassen.

WICHTIGE GRUNDSÄTZE:
- Schreibe sachlich, aber einladend und bürgerfreundlich
- Verwende klare, verständliche Sprache
- Informiere über das Wichtigste: Was, Wann, Wo
- Halte den Text prägnant (2-4 Absätze)
- Verwende eine positive, motivierende Tonalität
- Keine übertriebenen Werbefloskeln

STIL:
- Direkte Ansprache ist erlaubt ("Wir laden Sie ein...", "Besuchen Sie uns...")
- Aktive Formulierungen bevorzugen
- Klare Struktur: Ankündigung → Details → ggf. Aufruf zur Teilnahme
- Bei Veranstaltungen: Wichtige Infos wie Uhrzeit, Treffpunkt hervorheben

VERBOTEN:
- Erfundene Details oder Uhrzeiten
- Übertriebene Superlative ("größte", "beste", "einmalig")
- Zu formelle oder bürokratische Sprache
- Unnötige Füllwörter

Informationen zur Neuigkeit:
Thema: {$thema}
Ort: {$ort}
Datum: {$datumFormatted}
PROMPT;

    if (!empty($zusatzInfo)) {
        $prompt .= "\nZusätzliche Informationen: {$zusatzInfo}";
    }
    
    $prompt .= "\n\nVerfasse nun einen passenden Text für diese Neuigkeit:";

    $generatedText = sendLLMRequest($prompt);
    
    // Entferne mögliche <think>-Tags oder ähnliche Artefakte
    $generatedText = preg_replace('/^\s*(<think>.*?<\/think>\s*)+/s', '', $generatedText);
    
    return trim($generatedText);
}

