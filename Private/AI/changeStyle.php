<?php
require_once __DIR__ . '/sendLLMRequest.php';

/**
 * Verfügbare Schreibstile für Einsatzberichte
 */
const WRITING_STYLES = [
    'sachlich' => 'Sachlich und neutral',
    'detailliert' => 'Detailliert und ausführlich',
    'knapp' => 'Knapp und prägnant',
    'buergerfreundlich' => 'Bürgerfreundlich und verständlich',
    'technisch' => 'Technisch und fachspezifisch'
];

/**
 * Ändert den Schreibstil eines Textes, ohne den Inhalt zu verändern.
 *
 * @param string $text Der ursprüngliche Text
 * @param string $style Der gewünschte Stil (siehe WRITING_STYLES)
 * @return string Der Text im neuen Stil
 * @throws Exception Wenn ein Fehler bei der API-Anfrage auftritt oder der Stil ungültig ist
 */
function changeStyle(string $text, string $style): string
{
    if (!array_key_exists($style, WRITING_STYLES)) {
        throw new Exception("Ungültiger Stil: $style");
    }
    
    $stylePrompts = [
        'sachlich' => <<<PROMPT
Formuliere den folgenden Einsatzbericht in einem sachlichen und neutralen Stil um.

WICHTIGE REGELN:
- Behalte ALLE Fakten und Informationen bei
- Verwende eine neutrale, objektive Sprache
- Vermeide emotionale oder wertende Formulierungen
- Verwende klare, präzise Formulierungen
- Behalte die chronologische Reihenfolge bei
- Keine Spekulationen oder Interpretationen
PROMPT,
        
        'detailliert' => <<<PROMPT
Formuliere den folgenden Einsatzbericht in einem detaillierten und ausführlichen Stil um.

WICHTIGE REGELN:
- Behalte ALLE Fakten und Informationen bei
- Erweitere Formulierungen, um mehr Kontext zu geben (ohne neue Fakten zu erfinden)
- Verwende vollständige Sätze und ausführliche Beschreibungen
- Erkläre Abläufe und Zusammenhänge genauer
- Behalte die chronologische Reihenfolge bei
- Erfinde KEINE neuen Details, die nicht im Originaltext enthalten sind
PROMPT,
        
        'knapp' => <<<PROMPT
Formuliere den folgenden Einsatzbericht in einem knappen und prägnanten Stil um.

WICHTIGE REGELN:
- Behalte ALLE wichtigen Fakten und Informationen bei
- Entferne unnötige Füllwörter und Wiederholungen
- Verwende kurze, präzise Sätze
- Konzentriere dich auf das Wesentliche
- Behalte die chronologische Reihenfolge bei
- Verliere KEINE relevanten Informationen
PROMPT,
        
        'buergerfreundlich' => <<<PROMPT
Formuliere den folgenden Einsatzbericht in einem bürgerfreundlichen und verständlichen Stil um.

WICHTIGE REGELN:
- Behalte ALLE Fakten und Informationen bei
- Verwende einfache, verständliche Sprache
- Erkläre Fachbegriffe oder vermeide sie
- Schreibe so, dass es auch für Laien gut verständlich ist
- Verwende aktive Formulierungen
- Behalte die chronologische Reihenfolge bei
- Bleibe dabei sachlich und professionell
PROMPT,
        
        'technisch' => <<<PROMPT
Formuliere den folgenden Einsatzbericht in einem technischen und fachspezifischen Stil um.

WICHTIGE REGELN:
- Behalte ALLE Fakten und Informationen bei
- Verwende präzise Fachterminologie
- Ergänze technische Details wo sinnvoll (ohne neue Fakten zu erfinden)
- Verwende eine präzise, fachgerechte Sprache
- Behalte die chronologische Reihenfolge bei
- Orientiere dich an offiziellen Einsatzberichten der Feuerwehr
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
 * Gibt die verfügbaren Schreibstile zurück
 *
 * @return array Array mit Stil-IDs als Keys und Beschreibungen als Values
 */
function getAvailableStyles(): array
{
    return WRITING_STYLES;
}

