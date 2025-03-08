<?php

use PHPUnit\Framework\TestCase;

require_once 'rootpath.php';
require_once BASE_PATH . '/Private/Database/Database.php';

class WebHookTest extends TestCase
{
    private $url = "http://localhost/WebHook/webhook.php";
    private $einsatzID = "99999";



    public function testCorrectWebhookFunctionality()
    {
        fwrite(STDOUT, "\n📡 Überprüfe Webhook-Anfrage zur Erstellung eines korrekten Einsatzes...");
        $this->InsertCorrectWebhook();
         
        // Überprüfung, ob der Eintrag wirklich in der Datenbank existiert
        $db = Database::getInstance();
        $conn = $db->getConnection();
    
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `Einsatz` WHERE `EinsatzID` = ?");
        $stmt->execute([$this->einsatzID]);
        $count = $stmt->fetchColumn();
    
        $this->assertEquals(1, $count, "\n❌ Fehler: Eintrag wurde nicht in die Datenbank eingefügt!");
        fwrite(STDOUT, "\n✅ Einsatz erfolgreich in Datenbank eingetragen.");
    

        $this->UpdateCorrectWebhook();
    
        // Überprüfung, ob das Feld `Endzeit` nun gesetzt ist (zeigt, dass der Einsatz beendet wurde)
        $stmt = $conn->prepare("SELECT Endzeit FROM `Einsatz` WHERE `EinsatzID` = ?");
        $stmt->execute([$this->einsatzID]);
        $endzeit = $stmt->fetchColumn();
    
        $this->assertNotNull($endzeit, "\n❌ Fehler: Der Einsatz wurde nicht als beendet markiert!");
        fwrite(STDOUT, "\n✅ Einsatz erfolgreich als beendet markiert.");
    
        // Cleanup nach erfolgreichem Test
        $this->cleanupDatabase();
    }
    

    private function InsertCorrectWebhook()
    {
        $testData = [
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
        fwrite(STDOUT, "\n✅ Webhook-Antwort: $response");
    }

    private function UpdateCorrectWebhook()
    {
        $testData = [
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
        
        fwrite(STDOUT, "\n✅ Aktualisiere Webhook-Anfrage zur Beendung des Einsatzes");
        $response = $this->sendRequest($testData);
        fwrite(STDOUT, "\n✅ Webhook-Antwort: $response");
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

        fwrite(STDOUT, "\n✅ Test-Datenbankeintrag entfernt.");
    }
}
?>