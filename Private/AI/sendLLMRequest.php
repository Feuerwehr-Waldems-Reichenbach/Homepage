<?php
define('BASE_PATH_API_Sender', dirname(__DIR__, 2));
$configPath = BASE_PATH_API_Sender . "/Private/Initializations/api_keys.ini";

// API-Key laden
$config = parse_ini_file($configPath);
$apiKey = $config['groq_api_key'] ?? null;

if (!$apiKey) {
    throw new Exception("API Key 'groq_api_key' nicht gefunden.");
}

/**
 * Sendet eine Anfrage an ein LLM und gibt die Antwort zurück.
 *
 * @param string $input Der Eingabetext für das LLM.
 * @return string Die Antwort des Modells.
 * @throws Exception Wenn ein Fehler bei der Anfrage auftritt.
 */
function sendLLMRequest(string $input): string
{
    global $apiKey;

    $url = "https://api.groq.com/openai/v1/chat/completions";

    $data = [
        "messages" => [
            ["role" => "user", "content" => $input]
        ],
        "model" => "deepseek-r1-distill-llama-70b",
        "temperature" => 0.85,
        "max_completion_tokens" => 2024,
        "top_p" => 1,
        "stream" => false, // Obwohl konfig angegeben ist mit true – in PHP blockierend einfacher
        "stop" => null
    ];

    $headers = [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception("cURL-Fehler: " . curl_error($ch));
    }

    curl_close($ch);

    $decoded = json_decode($response, true);

    if ($httpCode !== 200 || !isset($decoded['choices'][0]['message']['content'])) {
        throw new Exception("Fehlerhafte API-Antwort: $response");
    }

    return trim($decoded['choices'][0]['message']['content']);
}

// Optionaler Testaufruf
// echo sendLLMRequest("Wie funktioniert PHP?");
