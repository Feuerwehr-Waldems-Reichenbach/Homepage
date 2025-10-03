<?php
require_once __DIR__ . '/sendLLMRequest.php';

/**
 * Verarbeitet einen Text mit einer benutzerdefinierten Anweisung.
 *
 * @param string $text Der zu verarbeitende Text
 * @param string $customInstruction Die benutzerdefinierte Anweisung
 * @return string Der verarbeitete Text
 * @throws Exception Wenn ein Fehler bei der API-Anfrage auftritt
 */
function processWithCustomPrompt(string $text, string $customInstruction): string
{
    // Bei leerem Text nur die Anweisung senden
    if (empty($text)) {
        $prompt = $customInstruction;
    } else {
        // Sonst Text mit Anweisung kombinieren
        $prompt = <<<PROMPT
{$customInstruction}

Text:
{$text}
PROMPT;
    }

    $result = sendLLMRequest($prompt);
    
    // Entferne mögliche <think>-Tags oder ähnliche Artefakte
    $result = preg_replace('/^\s*(<think>.*?<\/think>\s*)+/s', '', $result);
    
    return trim($result);
}

