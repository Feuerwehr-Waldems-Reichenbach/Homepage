<?php
require_once __DIR__ . '/sendLLMRequest.php';

/**
 * Korrigiert Schreibfehler im gegebenen Text ohne den Inhalt zu verändern.
 *
 * @param string $text Der zu korrigierende Text
 * @return string Der korrigierte Text
 * @throws Exception Wenn ein Fehler bei der API-Anfrage auftritt
 */
function correctSpelling(string $text): string
{
    $prompt = <<<PROMPT
Deine Aufgabe ist es, den folgenden Text auf Rechtschreibfehler, Grammatikfehler und Tippfehler zu überprüfen und zu korrigieren.

WICHTIGE REGELN:
- Korrigiere NUR Rechtschreibfehler, Grammatikfehler und Tippfehler
- Ändere NICHT den Inhalt, die Formulierung oder den Stil des Textes
- Ändere NICHT die Satzstruktur oder die Wortwahl (außer bei offensichtlichen Tippfehlern)
- Entferne KEINE Informationen
- Füge KEINE neuen Informationen hinzu
- Behalte die ursprüngliche Formatierung bei (Absätze, Zeilenumbrüche, etc.)
- Gib NUR den korrigierten Text zurück, KEINE Erklärungen oder Kommentare

Text:
{$text}

Korrigierter Text:
PROMPT;

    $correctedText = sendLLMRequest($prompt);
    
    // Entferne mögliche <think>-Tags oder ähnliche Artefakte
    $correctedText = preg_replace('/^\s*(<think>.*?<\/think>\s*)+/s', '', $correctedText);
    
    return trim($correctedText);
}

