<?php
use PHPUnit\Framework\TestCase;
require_once 'rootpath.php';
require_once BASE_PATH . '/Private/Database/Database.php';
class WebHookTest extends TestCase
{
    private $url = "http://localhost/WebHook/webhook.php";
    private $einsatzID = "99999";
    private $authKey; // Will be generated dynamically
    protected function setUp(): void
    {
        // Generate a random authentication key for this test run
        $this->authKey = bin2hex(random_bytes(8)); // 16 characters random key
        // Ensure the key exists in the database
        $this->ensureAuthKeyInDatabase();
    }
    protected function tearDown(): void
    {
        // Clean up the test authentication key after tests
        $this->removeAuthKeyFromDatabase();
    }
    /**
     * Ensures that the test authentication key exists in the database
     */
    private function ensureAuthKeyInDatabase()
    {
        fwrite(STDOUT, "\n📡 Erstelle einen zufälligen Authentifizierungsschlüssel für die Tests...");
        $db = Database::getInstance();
        $conn = $db->getConnection();
        // Prüfen, ob der Schlüssel bereits existiert (sollte nicht der Fall sein, da zufällig)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `authentifizierungsschluessel` WHERE `auth_key` = ?");
        $stmt->execute([$this->authKey]);
        $count = $stmt->fetchColumn();
        if ($count == 0) {
            // Schlüssel einfügen
            $stmt = $conn->prepare("INSERT INTO `authentifizierungsschluessel` (`Bezeichnung`, `auth_key`, `active`) VALUES (?, ?, 1)");
            $stmt->execute(["Temporärer Test-Schlüssel für PHPUnit", $this->authKey]);
            // Prüfen, ob der Einfügevorgang erfolgreich war
            $stmt = $conn->prepare("SELECT COUNT(*) FROM `authentifizierungsschluessel` WHERE `auth_key` = ?");
            $stmt->execute([$this->authKey]);
            $count = $stmt->fetchColumn();
            if ($count != 1) {
                throw new Exception("Der Test-Authentifizierungsschlüssel konnte nicht in die Datenbank eingefügt werden!");
            }
            fwrite(STDOUT, "\n✅ Zufälliger Test-Authentifizierungsschlüssel ({$this->authKey}) erfolgreich in die Datenbank eingefügt.\n");
        }
    }
    /**
     * Removes the test authentication key from the database
     */
    private function removeAuthKeyFromDatabase()
    {
        fwrite(STDOUT, "\n📡 Entferne den temporären Authentifizierungsschlüssel aus der Datenbank...");
        $db = Database::getInstance();
        $conn = $db->getConnection();
        // Schlüssel löschen
        $stmt = $conn->prepare("DELETE FROM `authentifizierungsschluessel` WHERE `auth_key` = ?");
        $stmt->execute([$this->authKey]);
        // Prüfen, ob der Löschvorgang erfolgreich war
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `authentifizierungsschluessel` WHERE `auth_key` = ?");
        $stmt->execute([$this->authKey]);
        $count = $stmt->fetchColumn();
        if ($count != 0) {
            fwrite(STDOUT, "\n⚠️ Der temporäre Test-Authentifizierungsschlüssel konnte nicht aus der Datenbank entfernt werden.\n");
        } else {
            fwrite(STDOUT, "\n✅ Temporärer Test-Authentifizierungsschlüssel erfolgreich aus der Datenbank entfernt.\n");
        }
    }
    public function testWebhookInsert()
    {
        fwrite(STDOUT, "\n📡 Überprüfe Webhook-Anfrage zur Erstellung eines korrekten Einsatzes...");
        $this->InsertCorrectWebhook();
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `Einsatz` WHERE `EinsatzID` = ?");
        $stmt->execute([$this->einsatzID]);
        $count = $stmt->fetchColumn();
        $this->assertEquals(1, $count, "\n❌ Fehler: Eintrag wurde nicht in die Datenbank eingefügt!");
        fwrite(STDOUT, "\n✅ Einsatz erfolgreich in Datenbank eingetragen.\n");
    }
    public function testWebhookUpdate()
    {
        fwrite(STDOUT, "\n📡 Versuche Einsatz zu beenden");
        $this->UpdateCorrectWebhook();
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT Endzeit FROM `Einsatz` WHERE `EinsatzID` = ?");
        $stmt->execute([$this->einsatzID]);
        $endzeit = $stmt->fetchColumn();
        $this->assertNotNull($endzeit, "\n❌ Fehler: Der Einsatz wurde nicht als beendet markiert!");
        fwrite(STDOUT, "\n✅ Einsatz erfolgreich als beendet markiert.\n");
    }
    public function testWebhookCleanup()
    {
        fwrite(STDOUT, "\n📡 Versuche Test-Datensatz aus der Datenbank zu entfernen");
        $this->cleanupDatabase();
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `Einsatz` WHERE `EinsatzID` = ?");
        $stmt->execute([$this->einsatzID]);
        $count = $stmt->fetchColumn();
        $this->assertEquals(0, $count, "\n❌ Fehler: Eintrag wurde nicht gelöscht!");
        fwrite(STDOUT, "\n✅ Test-Datenbankeintrag erfolgreich entfernt.\n");
    }
    public function testWebhookMissingEinsatzID()
    {
        fwrite(STDOUT, "\n📡 Überprüfe Webhook-Anfrage ohne EinsatzID...");
        $response = $this->sendMissingEinsatzIDWebhook();
        $this->assertStringContainsString("Fehler", $response, "\n❌ Fehler: Webhook sollte einen Fehler zurückgeben, wenn die EinsatzID fehlt!");
        fwrite(STDOUT, "\n✅ Webhook gibt korrekt einen Fehler zurück, wenn die EinsatzID fehlt.\n");
    }
    public function testWebhookTooManyUnknownValues()
    {
        fwrite(STDOUT, "\n📡 Überprüfe Webhook-Anfrage mit zu vielen unbekannten Werten...");
        $response = $this->sendTooManyUnknownValuesWebhook();
        $this->assertStringContainsString("Fehler: Zu viele unbekannte Werte", $response, "\n❌ Fehler: Webhook sollte einen Fehler zurückgeben, wenn zu viele Werte unbekannt sind!");
        fwrite(STDOUT, "\n✅ Webhook gibt korrekt einen Fehler zurück, wenn zu viele Werte unbekannt sind.\n");
    }
    public function testWebhookDuplicateInsert()
    {
        fwrite(STDOUT, "\n📡 Überprüfe Webhook-Anfrage mit doppeltem Einsatz (ohne beendet-Flag)...");
        // Ersten Einsatz einfügen
        $this->InsertCorrectWebhook();
        // Versuchen, den gleichen Einsatz nochmal einzufügen
        $response = $this->InsertCorrectWebhook();
        $this->assertStringContainsString("existiert bereits", $response, "\n❌ Fehler: Webhook sollte erkennen, dass der Einsatz bereits existiert!");
        fwrite(STDOUT, "\n✅ Webhook erkennt korrekt, dass der Einsatz bereits existiert.\n");
        // Aufräumen
        $this->cleanupDatabase();
    }
    public function testWebhookInvalidBeendetValue()
    {
        fwrite(STDOUT, "\n📡 Überprüfe Webhook-Anfrage mit ungültigem beendet-Wert...");
        // Erst einen Einsatz einfügen
        $this->InsertCorrectWebhook();
        // Versuchen, mit ungültigem beendet-Wert zu aktualisieren
        $response = $this->sendInvalidBeendetValueWebhook();
        // Der Webhook sollte den Einsatz nicht als beendet markieren
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT Endzeit FROM `Einsatz` WHERE `EinsatzID` = ?");
        $stmt->execute([$this->einsatzID]);
        $endzeit = $stmt->fetchColumn();
        // Akzeptiere entweder NULL oder '0000-00-00 00:00:00' als "nicht beendet"
        $this->assertTrue(
            $endzeit === null || $endzeit === '0000-00-00 00:00:00',
            "\n❌ Fehler: Der Einsatz wurde fälschlicherweise als beendet markiert! Endzeit: " . $endzeit
        );
        fwrite(STDOUT, "\n✅ Webhook markiert den Einsatz nicht als beendet, wenn der beendet-Wert ungültig ist.\n");
        // Aufräumen
        $this->cleanupDatabase();
    }
    public function testKategorieFeuer()
    {
        fwrite(STDOUT, "\n📡 Überprüfe Kategorisierung für Feuer-Einsatz...");
        // Einsatz mit Feuer-Stichwort einfügen
        $response = $this->sendFeuerWebhook();
        // Prüfen, ob die Kategorie korrekt gesetzt wurde
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT Kategorie FROM `Einsatz` WHERE `EinsatzID` = ?");
        $stmt->execute([$this->einsatzID]);
        $kategorie = $stmt->fetchColumn();
        $this->assertEquals('Feuer', $kategorie, "\n❌ Fehler: Die Kategorie wurde nicht korrekt als 'Feuer' erkannt!");
        fwrite(STDOUT, "\n✅ Einsatz wurde korrekt als 'Feuer' kategorisiert.\n");
        // Aufräumen
        $this->cleanupDatabase();
    }
    public function testKategorieTechnischeHilfeleistung()
    {
        fwrite(STDOUT, "\n📡 Überprüfe Kategorisierung für Technische Hilfeleistung...");
        // Einsatz mit TH-Stichwort einfügen
        $response = $this->sendTHWebhook();
        // Prüfen, ob die Kategorie korrekt gesetzt wurde
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT Kategorie FROM `Einsatz` WHERE `EinsatzID` = ?");
        $stmt->execute([$this->einsatzID]);
        $kategorie = $stmt->fetchColumn();
        $this->assertEquals('Technische Hilfeleistung', $kategorie, "\n❌ Fehler: Die Kategorie wurde nicht korrekt als 'Technische Hilfeleistung' erkannt!");
        fwrite(STDOUT, "\n✅ Einsatz wurde korrekt als 'Technische Hilfeleistung' kategorisiert.\n");
        // Aufräumen
        $this->cleanupDatabase();
    }
    public function testKategorieMedizinisch()
    {
        fwrite(STDOUT, "\n📡 Überprüfe Kategorisierung für Medizinischen Einsatz...");
        // Einsatz mit medizinischem Stichwort einfügen
        $response = $this->sendMedizinWebhook();
        // Prüfen, ob die Kategorie korrekt gesetzt wurde
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT Kategorie FROM `Einsatz` WHERE `EinsatzID` = ?");
        $stmt->execute([$this->einsatzID]);
        $kategorie = $stmt->fetchColumn();
        $this->assertEquals('Medizinisch', $kategorie, "\n❌ Fehler: Die Kategorie wurde nicht korrekt als 'Medizinisch' erkannt!");
        fwrite(STDOUT, "\n✅ Einsatz wurde korrekt als 'Medizinisch' kategorisiert.\n");
        // Aufräumen
        $this->cleanupDatabase();
    }
    // Tests für die Authentifizierung
    public function testWebhookMissingAuthKey()
    {
        fwrite(STDOUT, "\n📡 Überprüfe Webhook-Anfrage ohne Authentifizierungsschlüssel...");
        $response = $this->sendMissingAuthKeyWebhook();
        $this->assertStringContainsString(
            "Fehler: Kein Authentifizierungsschlüssel angegeben",
            $response,
            "\n❌ Fehler: Webhook sollte einen Fehler zurückgeben, wenn kein Authentifizierungsschlüssel angegeben ist!"
        );
        fwrite(STDOUT, "\n✅ Webhook gibt korrekt einen Fehler zurück, wenn kein Authentifizierungsschlüssel angegeben ist.\n");
    }
    public function testWebhookInvalidAuthKey()
    {
        fwrite(STDOUT, "\n📡 Überprüfe Webhook-Anfrage mit ungültigem Authentifizierungsschlüssel...");
        $response = $this->sendInvalidAuthKeyWebhook();
        $this->assertStringContainsString(
            "Fehler: Ungültiger Authentifizierungsschlüssel",
            $response,
            "\n❌ Fehler: Webhook sollte einen Fehler zurückgeben, wenn der Authentifizierungsschlüssel ungültig ist!"
        );
        fwrite(STDOUT, "\n✅ Webhook gibt korrekt einen Fehler zurück, wenn der Authentifizierungsschlüssel ungültig ist.\n");
    }
    // Private Hilfsmethoden für die Tests
    private function sendMissingEinsatzIDWebhook()
    {
        $testData = [
            "auth_key" => $this->authKey,
            "kategorie" => "Feuer",
            "stichwort" => "F1",
            "stichwortuebersetzung" => "[F1] Feuer klein",
            "standort" => "Feuerwehr Musterstadt",
            "sachverhalt" => "Brennt Mülleimer",
            "adresse" => "Musterstraße 1, 12345 Musterstadt - Musterstadtteil",
            // EinsatzID fehlt absichtlich
            "ric" => "Test 1-46-1, Test 1-30-1, Test 1-11-1",
            "alarmgruppen" => "Alarmgruppe 1, Alarmgruppe 2",
            "infogruppen" => "Infogruppe 1, Infogruppe 2",
            "fahrzeuge" => "Musterstadt 1-46-1, Musterstadt 1-30-1"
        ];
        $response = $this->sendRequest($testData);
        fwrite(STDOUT, "\n ☐ Webhook-Antwort: $response");
        return $response;
    }
    private function sendTooManyUnknownValuesWebhook()
    {
        $testData = [
            "auth_key" => $this->authKey,
            "einsatzID" => $this->einsatzID
            // Alle anderen Werte fehlen absichtlich, um viele "Unbekannt"-Werte zu erzeugen
        ];
        $response = $this->sendRequest($testData);
        fwrite(STDOUT, "\n ☐ Webhook-Antwort: $response");
        return $response;
    }
    private function sendInvalidBeendetValueWebhook()
    {
        $testData = [
            "auth_key" => $this->authKey,
            "beendet" => "ungültig", // Ungültiger Wert für beendet
            "kategorie" => "Feuer",
            "stichwort" => "F1",
            "stichwortuebersetzung" => "[F1] Feuer klein",
            "standort" => "Feuerwehr Musterstadt",
            "sachverhalt" => "Brennt Mülleimer",
            "adresse" => "Musterstraße 1, 12345 Musterstadt - Musterstadtteil",
            "einsatzID" => $this->einsatzID,
            "ric" => "Test 1-46-1, Test 1-30-1, Test 1-11-1",
            "alarmgruppen" => "Alarmgruppe 1, Alarmgruppe 2",
            "infogruppen" => "Infogruppe 1, Infogruppe 2",
            "fahrzeuge" => "Musterstadt 1-46-1, Musterstadt 1-30-1"
        ];
        $response = $this->sendRequest($testData);
        fwrite(STDOUT, "\n ☐ Webhook-Antwort: $response");
        return $response;
    }
    private function InsertCorrectWebhook()
    {
        $testData = [
            "auth_key" => $this->authKey,
            "kategorie" => "Feuer",
            "stichwort" => "F1",
            "stichwortuebersetzung" => "[F1] Feuer klein",
            "standort" => "Feuerwehr Musterstadt",
            "sachverhalt" => "Brennt Mülleimer",
            "adresse" => "Musterstraße 1, 12345 Musterstadt - Musterstadtteil",
            "einsatzID" => $this->einsatzID,
            "ric" => "Test 1-46-1, Test 1-30-1, Test 1-11-1",
            "alarmgruppen" => "Alarmgruppe 1, Alarmgruppe 2",
            "infogruppen" => "Infogruppe 1, Infogruppe 2",
            "fahrzeuge" => "Musterstadt 1-46-1, Musterstadt 1-30-1"
        ];
        $response = $this->sendRequest($testData);
        fwrite(STDOUT, "\n ☐ Webhook-Antwort: $response");
        return $response;
    }
    private function UpdateCorrectWebhook()
    {
        $testData = [
            "auth_key" => $this->authKey,
            "beendet" => 1,
            "kategorie" => "Feuer",
            "stichwort" => "F1",
            "stichwortuebersetzung" => "[F1] Feuer klein",
            "standort" => "Feuerwehr Musterstadt",
            "sachverhalt" => "Brennt Mülleimer",
            "adresse" => "Musterstraße 1, 12345 Musterstadt - Musterstadtteil",
            "einsatzID" => $this->einsatzID,
            "ric" => "Test 1-46-1, Test 1-30-1, Test 1-11-1",
            "alarmgruppen" => "Alarmgruppe 1, Alarmgruppe 2",
            "infogruppen" => "Infogruppe 1, Infogruppe 2",
            "fahrzeuge" => "Musterstadt 1-46-1, Musterstadt 1-30-1"
        ];
        $response = $this->sendRequest($testData);
        fwrite(STDOUT, "\n ☐ Webhook-Antwort: $response");
        return $response;
    }
    private function sendFeuerWebhook()
    {
        $testData = [
            "auth_key" => $this->authKey,
            "kategorie" => "Feuer",
            "stichwort" => "F2",
            "stichwortuebersetzung" => "[F2] Feuer mittel",
            "standort" => "Feuerwehr Musterstadt",
            "sachverhalt" => "Brennt Wohnhaus",
            "adresse" => "Musterstraße 1, 12345 Musterstadt - Musterstadtteil",
            "einsatzID" => $this->einsatzID,
            "ric" => "Test 1-46-1, Test 1-30-1, Test 1-11-1",
            "alarmgruppen" => "Alarmgruppe 1, Alarmgruppe 2",
            "infogruppen" => "Infogruppe 1, Infogruppe 2",
            "fahrzeuge" => "Musterstadt 1-46-1, Musterstadt 1-30-1"
        ];
        $response = $this->sendRequest($testData);
        fwrite(STDOUT, "\n ☐ Webhook-Antwort: $response");
        return $response;
    }
    private function sendTHWebhook()
    {
        $testData = [
            "auth_key" => $this->authKey,
            "kategorie" => "Technische Hilfeleistung",
            "stichwort" => "H1",
            "stichwortuebersetzung" => "[H1] Hilfeleistung klein",
            "standort" => "Feuerwehr Musterstadt",
            "sachverhalt" => "Ölspur nach Verkehrsunfall",
            "adresse" => "Musterstraße 1, 12345 Musterstadt - Musterstadtteil",
            "einsatzID" => $this->einsatzID,
            "ric" => "Test 1-46-1, Test 1-30-1, Test 1-11-1",
            "alarmgruppen" => "Alarmgruppe 1, Alarmgruppe 2",
            "infogruppen" => "Infogruppe 1, Infogruppe 2",
            "fahrzeuge" => "Musterstadt 1-46-1, Musterstadt 1-30-1"
        ];
        $response = $this->sendRequest($testData);
        fwrite(STDOUT, "\n ☐ Webhook-Antwort: $response");
        return $response;
    }
    private function sendMedizinWebhook()
    {
        $testData = [
            "auth_key" => $this->authKey,
            "kategorie" => "Medizinisch",
            "stichwort" => "RD1",
            "stichwortuebersetzung" => "[RD1] Rettungsdienst",
            "standort" => "Feuerwehr Musterstadt",
            "sachverhalt" => "Person mit Herzinfarkt",
            "adresse" => "Musterstraße 1, 12345 Musterstadt - Musterstadtteil",
            "einsatzID" => $this->einsatzID,
            "ric" => "Test 1-46-1, Test 1-30-1, Test 1-11-1",
            "alarmgruppen" => "Alarmgruppe 1, Alarmgruppe 2",
            "infogruppen" => "Infogruppe 1, Infogruppe 2",
            "fahrzeuge" => "Musterstadt 1-46-1, Musterstadt 1-30-1"
        ];
        $response = $this->sendRequest($testData);
        fwrite(STDOUT, "\n ☐ Webhook-Antwort: $response");
        return $response;
    }
    // Methoden für die Authentifizierungstests
    private function sendMissingAuthKeyWebhook()
    {
        $testData = [
            // Kein auth_key
            "kategorie" => "Feuer",
            "stichwort" => "F1",
            "stichwortuebersetzung" => "[F1] Feuer klein",
            "standort" => "Feuerwehr Musterstadt",
            "sachverhalt" => "Brennt Mülleimer",
            "adresse" => "Musterstraße 1, 12345 Musterstadt - Musterstadtteil",
            "einsatzID" => $this->einsatzID,
            "ric" => "Test 1-46-1, Test 1-30-1, Test 1-11-1",
            "alarmgruppen" => "Alarmgruppe 1, Alarmgruppe 2",
            "infogruppen" => "Infogruppe 1, Infogruppe 2",
            "fahrzeuge" => "Musterstadt 1-46-1, Musterstadt 1-30-1"
        ];
        $response = $this->sendRequest($testData);
        fwrite(STDOUT, "\n ☐ Webhook-Antwort: $response");
        return $response;
    }
    private function sendInvalidAuthKeyWebhook()
    {
        $testData = [
            "auth_key" => "ungueltigerSchluessel123", // Ungültiger Schlüssel
            "kategorie" => "Feuer",
            "stichwort" => "F1",
            "stichwortuebersetzung" => "[F1] Feuer klein",
            "standort" => "Feuerwehr Musterstadt",
            "sachverhalt" => "Brennt Mülleimer",
            "adresse" => "Musterstraße 1, 12345 Musterstadt - Musterstadtteil",
            "einsatzID" => $this->einsatzID,
            "ric" => "Test 1-46-1, Test 1-30-1, Test 1-11-1",
            "alarmgruppen" => "Alarmgruppe 1, Alarmgruppe 2",
            "infogruppen" => "Infogruppe 1, Infogruppe 2",
            "fahrzeuge" => "Musterstadt 1-46-1, Musterstadt 1-30-1"
        ];
        $response = $this->sendRequest($testData);
        fwrite(STDOUT, "\n ☐ Webhook-Antwort: $response");
        return $response;
    }
    private function sendRequest($data)
    {
        $url = $this->url . '?' . http_build_query($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    private function cleanupDatabase()
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        // Eintrag löschen
        $stmtDelete = $conn->prepare("DELETE FROM `Einsatz` WHERE `EinsatzID` = ?");
        $stmtDelete->execute([$this->einsatzID]);
    }
}
?>