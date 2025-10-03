<?php
require_once __DIR__ . '/sendLLMRequest.php';

/**
 * Verfügbare Schreibstile für Neuigkeiten
 */
const NEWS_WRITING_STYLES = [
    'informativ' => 'Informativ und sachlich',
    'einladend' => 'Einladend und motivierend',
    'kurz' => 'Kurz und knapp',
    'ausfuehrlich' => 'Ausführlich und detailliert',
    'offiziell' => 'Offiziell und formell'
];

/**
 * Ändert den Schreibstil eines Neuigkeiten-Textes.
 *
 * @param string $text Der ursprüngliche Text
 * @param string $style Der gewünschte Stil
 * @return string Der Text im neuen Stil
 * @throws Exception Wenn ein Fehler bei der API-Anfrage auftritt oder der Stil ungültig ist
 */
function changeNewsStyle(string $text, string $style): string
{
    if (!array_key_exists($style, NEWS_WRITING_STYLES)) {
        throw new Exception("Ungültiger Stil: $style");
    }
    
    $stylePrompts = [
        'informativ' => <<<PROMPT
Formuliere den folgenden Text in einem informativen und sachlichen Stil um.

REGELN:
- Behalte ALLE Fakten und Informationen bei
- Verwende eine neutrale, objektive Sprache
- Fokussiere auf die wesentlichen Informationen (Was, Wann, Wo, Wer)
- Vermeide emotionale Formulierungen
- Klare, präzise Aussagen
PROMPT,
        
        'einladend' => <<<PROMPT
Formuliere den folgenden Text in einem einladenden und motivierenden Stil um.

REGELN:
- Behalte ALLE Fakten und Informationen bei
- Verwende eine freundliche, einladende Sprache
- Direkte Ansprache ist erwünscht ("Wir freuen uns...", "Besuchen Sie...")
- Wecke Interesse und motiviere zur Teilnahme
- Bleibe dabei authentisch und nicht zu werblich
PROMPT,
        
        'kurz' => <<<PROMPT
Formuliere den folgenden Text in einem kurzen und knappen Stil um.

REGELN:
- Behalte ALLE wichtigen Fakten bei
- Reduziere auf das Wesentliche
- Kurze, prägnante Sätze
- Keine Füllwörter
- Maximal 2-3 Absätze
PROMPT,
        
        'ausfuehrlich' => <<<PROMPT
Formuliere den folgenden Text in einem ausführlichen und detaillierten Stil um.

REGELN:
- Behalte ALLE Fakten bei und erweitere sie sinnvoll
- Füge Kontext und Hintergrundinformationen hinzu (ohne Fakten zu erfinden)
- Erkläre Zusammenhänge
- Verwende vollständige, ausformulierte Sätze
- Strukturiere klar in Absätze
PROMPT,
        
        'offiziell' => <<<PROMPT
Formuliere den folgenden Text in einem offiziellen und formellen Stil um.

REGELN:
- Behalte ALLE Fakten bei
- Verwende eine formelle, professionelle Sprache
- Offizielle Formulierungen ("Die Freiwillige Feuerwehr...", "Es wird bekannt gegeben...")
- Sachlich und respektvoll
- Klare Struktur
PROMPT
    ];
    
    $stylePrompt = $stylePrompts[$style];
    
    $fullPrompt = <<<PROMPT
{$stylePrompt}

Text:
{$text}

Umformulierter Text:
PROMPT;

    $styledText = sendLLMRequest($fullPrompt);
    
    // Entferne mögliche <think>-Tags oder ähnliche Artefakte
    $styledText = preg_replace('/^\s*(<think>.*?<\/think>\s*)+/s', '', $styledText);
    
    return trim($styledText);
}

/**
 * Gibt die verfügbaren Schreibstile für Neuigkeiten zurück
 *
 * @return array Array mit Stil-IDs als Keys und Beschreibungen als Values
 */
function getAvailableNewsStyles(): array
{
    return NEWS_WRITING_STYLES;
}

