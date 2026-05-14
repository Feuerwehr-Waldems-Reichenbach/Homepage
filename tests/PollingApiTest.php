<?php
use PHPUnit\Framework\TestCase;

require_once 'rootpath.php';
require_once BASE_PATH . '/Private/Database/Database.php';

class PollingApiTest extends TestCase
{
    private string $apiUrl = 'http://localhost/Verwaltung/api/aktive-einsaetze.php';
    private PDO $conn;
    private string $validAuthKey;
    private array $createdAuthKeys = [];
    private array $createdEinsatzIds = [];

    protected function setUp(): void
    {
        $this->conn = Database::getInstance()->getConnection();
        $this->validAuthKey = $this->createAuthKey(true);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdEinsatzIds as $einsatzId) {
            $stmt = $this->conn->prepare("DELETE FROM `einsatz` WHERE `EinsatzID` = ?");
            $stmt->execute([$einsatzId]);
        }

        foreach ($this->createdAuthKeys as $authKey) {
            $stmt = $this->conn->prepare("DELETE FROM `authentifizierungsschluessel` WHERE `auth_key` = ?");
            $stmt->execute([$authKey]);
        }
    }

    public function testValidKeyAndNoActiveIncidentsReturnsEmptyArray(): void
    {
        if ($this->countActiveIncidents() > 0) {
            $this->markTestSkipped('Precondition not met: database already contains active incidents.');
        }

        $response = $this->sendRequest('GET', [], [
            'X-API-Key: ' . $this->validAuthKey
        ]);

        $payload = json_decode($response['body'], true);

        $this->assertSame(200, $response['status']);
        $this->assertIsArray($payload);
        $this->assertTrue($payload['success']);
        $this->assertSame([], $payload['active_incidents']);
        $this->assertSame(0, $payload['meta']['count']);
        $this->assertArrayHasKey('generated_at', $payload['meta']);
    }

    public function testValidKeyAndActiveIncidentReturnsExpectedFields(): void
    {
        $einsatzId = 'poll-active-' . uniqid('', true);
        $this->insertIncident($einsatzId, null);

        $response = $this->sendRequest('GET', [], [
            'X-API-Key: ' . $this->validAuthKey
        ]);

        $payload = json_decode($response['body'], true);

        $this->assertSame(200, $response['status']);
        $this->assertIsArray($payload);
        $this->assertTrue($payload['success']);
        $this->assertIsArray($payload['active_incidents']);
        $this->assertArrayHasKey('meta', $payload);
        $this->assertArrayHasKey('count', $payload['meta']);
        $this->assertArrayHasKey('generated_at', $payload['meta']);

        $matchingIncident = null;
        foreach ($payload['active_incidents'] as $incident) {
            if (($incident['EinsatzID'] ?? '') === $einsatzId) {
                $matchingIncident = $incident;
                break;
            }
        }

        $this->assertNotNull($matchingIncident, 'Inserted active incident was not returned by polling endpoint.');

        $expectedKeys = ['EinsatzID', 'Datum', 'Endzeit', 'Stichwort', 'Sachverhalt', 'Kategorie', 'Ort', 'Einheit'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $matchingIncident);
        }
    }

    public function testEndedIncidentIsNotReturnedAsActive(): void
    {
        $einsatzId = 'poll-ended-' . uniqid('', true);
        $endzeit = date('Y-m-d H:i:s');
        $this->insertIncident($einsatzId, $endzeit);

        $response = $this->sendRequest('GET', [
            'auth_key' => $this->validAuthKey
        ]);

        $payload = json_decode($response['body'], true);

        $this->assertSame(200, $response['status']);
        $this->assertIsArray($payload);
        $this->assertTrue($payload['success']);

        foreach ($payload['active_incidents'] as $incident) {
            $this->assertNotSame(
                $einsatzId,
                $incident['EinsatzID'] ?? null,
                'Ended incident must not be returned as active.'
            );
        }
    }

    public function testMissingApiKeyReturns401(): void
    {
        $response = $this->sendRequest('GET');
        $payload = json_decode($response['body'], true);

        $this->assertSame(401, $response['status']);
        $this->assertIsArray($payload);
        $this->assertFalse($payload['success']);
    }

    public function testInvalidOrInactiveApiKeyReturns401(): void
    {
        $invalidKey = 'invalid-' . bin2hex(random_bytes(8));

        $invalidResponse = $this->sendRequest('GET', [], [
            'X-API-Key: ' . $invalidKey
        ]);

        $invalidPayload = json_decode($invalidResponse['body'], true);

        $this->assertSame(401, $invalidResponse['status']);
        $this->assertIsArray($invalidPayload);
        $this->assertFalse($invalidPayload['success']);

        $inactiveKey = $this->createAuthKey(false);

        $inactiveResponse = $this->sendRequest('GET', [], [
            'X-API-Key: ' . $inactiveKey
        ]);

        $inactivePayload = json_decode($inactiveResponse['body'], true);

        $this->assertSame(401, $inactiveResponse['status']);
        $this->assertIsArray($inactivePayload);
        $this->assertFalse($inactivePayload['success']);
    }

    public function testPostMethodReturns405(): void
    {
        $response = $this->sendRequest('POST', [], [
            'X-API-Key: ' . $this->validAuthKey
        ]);

        $payload = json_decode($response['body'], true);

        $this->assertSame(405, $response['status']);
        $this->assertIsArray($payload);
        $this->assertFalse($payload['success']);
    }

    public function testOptionsReturns204WithCorsHeaders(): void
    {
        $response = $this->sendRequest('OPTIONS');

        $this->assertSame(204, $response['status']);
        $this->assertArrayHasKey('access-control-allow-origin', $response['headers']);
        $this->assertSame('*', $response['headers']['access-control-allow-origin']);
        $this->assertArrayHasKey('access-control-allow-headers', $response['headers']);
        $this->assertStringContainsStringIgnoringCase(
            'X-API-Key',
            $response['headers']['access-control-allow-headers']
        );
    }

    public function testDocumentationCanBeRequestedWithoutApiKey(): void
    {
        $response = $this->sendRequest('GET', [
            'docs' => 1
        ]);

        $payload = json_decode($response['body'], true);

        $this->assertSame(200, $response['status']);
        $this->assertIsArray($payload);
        $this->assertTrue($payload['success']);
        $this->assertArrayHasKey('documentation', $payload);
        $this->assertSame('/Verwaltung/api/aktive-einsaetze.php', $payload['documentation']['endpoint']);
    }

    public function testTestAlarmReturnsSyntheticIncidentWithValidApiKey(): void
    {
        $response = $this->sendRequest('GET', [
            'test_alarm' => 1
        ], [
            'X-API-Key: ' . $this->validAuthKey
        ]);

        $payload = json_decode($response['body'], true);

        $this->assertSame(200, $response['status']);
        $this->assertIsArray($payload);
        $this->assertTrue($payload['success']);
        $this->assertIsArray($payload['active_incidents']);
        $this->assertCount(1, $payload['active_incidents']);
        $this->assertSame('TEST-ALARM-001', $payload['active_incidents'][0]['EinsatzID']);
        $this->assertArrayHasKey('meta', $payload);
        $this->assertTrue($payload['meta']['test_alarm']);
    }

    private function createAuthKey(bool $active): string
    {
        $key = bin2hex(random_bytes(16));
        $stmt = $this->conn->prepare(
            "INSERT INTO `authentifizierungsschluessel` (`Bezeichnung`, `auth_key`, `active`) VALUES (?, ?, ?)"
        );
        $stmt->execute(['Polling API Test Key', $key, $active ? 1 : 0]);

        $this->createdAuthKeys[] = $key;
        return $key;
    }

    private function insertIncident(string $einsatzId, ?string $endzeit): void
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO `einsatz` (`EinsatzID`, `Anzeigen`, `Datum`, `Endzeit`, `Sachverhalt`, `Stichwort`, `Ort`, `Einheit`, `Kategorie`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bindValue(1, $einsatzId, PDO::PARAM_STR);
        $stmt->bindValue(2, $endzeit === null ? 0 : 1, PDO::PARAM_INT);
        $stmt->bindValue(3, date('Y-m-d H:i:s'), PDO::PARAM_STR);
        if ($endzeit === null) {
            $stmt->bindValue(4, null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(4, $endzeit, PDO::PARAM_STR);
        }
        $stmt->bindValue(5, 'Polling API Test Sachverhalt', PDO::PARAM_STR);
        $stmt->bindValue(6, 'Polling API Test Stichwort', PDO::PARAM_STR);
        $stmt->bindValue(7, 'Polling-Test-Ort', PDO::PARAM_STR);
        $stmt->bindValue(8, 'Polling-Test-Einheit', PDO::PARAM_STR);
        $stmt->bindValue(9, 'Sonstiges', PDO::PARAM_STR);
        $stmt->execute();

        $this->createdEinsatzIds[] = $einsatzId;
    }

    private function countActiveIncidents(): int
    {
        $stmt = $this->conn->query(
            "SELECT COUNT(*) FROM `einsatz`
             WHERE `Endzeit` IS NULL OR `Endzeit` = '' OR `Endzeit` = '0000-00-00 00:00:00'"
        );

        return (int) $stmt->fetchColumn();
    }

    private function sendRequest(string $method, array $query = [], array $headers = []): array
    {
        $url = $this->apiUrl;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $rawResponse = curl_exec($ch);
        if ($rawResponse === false) {
            $error = curl_error($ch);
            curl_close($ch);
            $this->fail('cURL request failed: ' . $error);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaders = substr($rawResponse, 0, $headerSize);
        $body = substr($rawResponse, $headerSize);

        return [
            'status' => $status,
            'headers' => $this->parseHeaders($rawHeaders),
            'body' => $body
        ];
    }

    private function parseHeaders(string $rawHeaders): array
    {
        $parsed = [];
        $lines = preg_split("/\r\n|\n|\r/", trim($rawHeaders));

        foreach ($lines as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $parsed[strtolower(trim($name))] = trim($value);
        }

        return $parsed;
    }
}
