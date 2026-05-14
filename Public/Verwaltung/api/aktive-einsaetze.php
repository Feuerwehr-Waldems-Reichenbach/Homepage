<?php
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/models/AuthKey.php';
require_once dirname(__DIR__) . '/models/Einsatz.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: X-API-Key, Content-Type');
header('Access-Control-Max-Age: 600');
header('Content-Type: application/json; charset=utf-8');

/**
 * Send a JSON response and stop script execution.
 *
 * @param int $statusCode
 * @param array $data
 * @return void
 */
function sendJsonResponse(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Check whether a query parameter is set to a truthy value.
 *
 * @param string $paramName
 * @return bool
 */
function isTruthyQueryParam(string $paramName): bool
{
    if (!isset($_GET[$paramName])) {
        return false;
    }

    $value = trim((string) $_GET[$paramName]);
    if ($value === '') {
        return true;
    }

    $normalized = strtolower($value);
    return in_array($normalized, ['1', 'true', 'yes', 'on', 'ja'], true);
}

/**
 * Read API key from X-API-Key header with query fallback.
 *
 * @return string
 */
function getApiKeyFromRequest(): string
{
    $apiKey = '';

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $name => $value) {
                if (strtolower((string) $name) === 'x-api-key') {
                    $apiKey = trim((string) $value);
                    break;
                }
            }
        }
    }

    if ($apiKey === '' && isset($_SERVER['HTTP_X_API_KEY'])) {
        $apiKey = trim((string) $_SERVER['HTTP_X_API_KEY']);
    }

    if ($apiKey === '' && isset($_SERVER['REDIRECT_HTTP_X_API_KEY'])) {
        $apiKey = trim((string) $_SERVER['REDIRECT_HTTP_X_API_KEY']);
    }

    if ($apiKey === '' && isset($_GET['auth_key'])) {
        $apiKey = trim((string) $_GET['auth_key']);
    }

    return $apiKey;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET, OPTIONS');
    sendJsonResponse(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

if (isTruthyQueryParam('docs') || isTruthyQueryParam('doku')) {
    sendJsonResponse(200, [
        'success' => true,
        'documentation' => [
            'name' => 'Aktive Einsaetze Polling API',
            'endpoint' => '/Verwaltung/api/aktive-einsaetze.php',
            'method' => 'GET',
            'authentication' => [
                'required_for_incident_data' => true,
                'header' => 'X-API-Key',
                'query_fallback' => 'auth_key'
            ],
            'query_parameters' => [
                [
                    'name' => 'docs',
                    'description' => 'Liefert diese API-Dokumentation als JSON',
                    'example' => '?docs=1',
                    'requires_api_key' => false
                ],
                [
                    'name' => 'doku',
                    'description' => 'Alias fuer docs',
                    'example' => '?doku=1',
                    'requires_api_key' => false
                ],
                [
                    'name' => 'test_alarm',
                    'description' => 'Liefert einen synthetischen Testeinsatz im normalen Datenformat',
                    'example' => '?test_alarm=1',
                    'requires_api_key' => true
                ]
            ],
            'response_fields' => [
                'success',
                'active_incidents',
                'meta.count',
                'meta.generated_at'
            ],
            'incident_fields' => [
                'EinsatzID',
                'Datum',
                'Endzeit',
                'Stichwort',
                'Sachverhalt',
                'Kategorie',
                'Ort',
                'Einheit'
            ],
            'status_codes' => [
                '200' => 'Erfolgreiche Antwort (auch leeres active_incidents)',
                '204' => 'OPTIONS / CORS preflight',
                '401' => 'API-Key fehlt, ungueltig oder inaktiv',
                '405' => 'Methode nicht erlaubt'
            ]
        ]
    ]);
}

$apiKey = getApiKeyFromRequest();

if ($apiKey === '') {
    sendJsonResponse(401, [
        'success' => false,
        'message' => 'Missing API key'
    ]);
}

try {
    $authKeyModel = new AuthKey();
    if (!$authKeyModel->isValidKey($apiKey)) {
        sendJsonResponse(401, [
            'success' => false,
            'message' => 'Invalid API key'
        ]);
    }

    if (isTruthyQueryParam('test_alarm')) {
        $testAlarm = [
            'EinsatzID' => 'TEST-ALARM-001',
            'Datum' => date('Y-m-d H:i:s'),
            'Endzeit' => null,
            'Stichwort' => 'Testalarm',
            'Sachverhalt' => 'Dies ist ein synthetischer Testeinsatz fuer API-Integrationen.',
            'Kategorie' => 'Sonstiges',
            'Ort' => 'Test-Ort',
            'Einheit' => 'Test-Einheit'
        ];

        sendJsonResponse(200, [
            'success' => true,
            'active_incidents' => [$testAlarm],
            'meta' => [
                'count' => 1,
                'generated_at' => date('c'),
                'test_alarm' => true
            ]
        ]);
    }

    $einsatzModel = new Einsatz();
    $activeIncidents = $einsatzModel->getActiveEinsaetze();

    sendJsonResponse(200, [
        'success' => true,
        'active_incidents' => $activeIncidents,
        'meta' => [
            'count' => count($activeIncidents),
            'generated_at' => date('c')
        ]
    ]);
} catch (Throwable $e) {
    error_log('Polling API error: ' . $e->getMessage());

    sendJsonResponse(500, [
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
