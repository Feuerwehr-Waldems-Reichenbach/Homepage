<?php
use PHPUnit\Framework\TestCase;

require_once 'rootpath.php';
require_once BASE_PATH . '/Public/WebHook/Helpers/helpers.php';

class KategorienTest extends TestCase
{
    public function testFeuerKategorie()
    {
        $result = getKategorie("Rauchentwicklung in Wohnung", "Zimmerbrand");
        $this->assertEquals("Feuer", $result, "\n❌ Fehler: Erwartete Kategorie 'Feuer', erhalten: '$result'");
        fwrite(STDOUT, "\n✅ Kategorie 'Feuer' korrekt erkannt.\n");
    }

    public function test2FeuerKategorie()
    {
        $result = getKategorie(" (F BMA) BMA Einlauf", " (F BMA) BMA Einlauf");
        $this->assertEquals("Feuer", $result, "\n❌ Fehler: Erwartete Kategorie 'Feuer', erhalten: '$result'");
        fwrite(STDOUT, "\n✅ Kategorie 'Feuer' korrekt erkannt.\n");
    }

    public function testMedizinKategorie()
    {
        $result = getKategorie("Person bewusstlos in Wohnung", "Notfall medizin");
        $this->assertEquals("Medizinisch", $result, "\n❌ Fehler: Erwartete Kategorie 'Medizinisch', erhalten: '$result'");
        fwrite(STDOUT, "\n✅ Kategorie 'Medizinisch' korrekt erkannt.\n");
    }

    public function testTHKategorie()
    {
        $result = getKategorie("Verkehrsunfall mit auslaufenden Betriebsstoffen", "Hilfeleistung");
        $this->assertEquals("Technische Hilfeleistung", $result, "\n❌ Fehler: Erwartete Kategorie 'Technische Hilfeleistung', erhalten: '$result'");
        fwrite(STDOUT, "\n✅ Kategorie 'Technische Hilfeleistung' korrekt erkannt.\n");
    }

    public function testUnwetterKategorie()
    {
        $result = getKategorie("Baum auf Fahrbahn nach Sturm", "Sturm");
        $this->assertEquals("Unwetter", $result, "\n❌ Fehler: Erwartete Kategorie 'Unwetter', erhalten: '$result'");
        fwrite(STDOUT, "\n✅ Kategorie 'Unwetter' korrekt erkannt.\n");
    }

    public function testGefahrgutKategorie()
    {
        $result = getKategorie("Unbekannter Geruch in Labor", "Chemikalie");
        $this->assertEquals("Gefahrgut", $result, "\n❌ Fehler: Erwartete Kategorie 'Gefahrgut', erhalten: '$result'");
        fwrite(STDOUT, "\n✅ Kategorie 'Gefahrgut' korrekt erkannt.\n");
    }

    public function testTierrettungKategorie()
    {
        $result = getKategorie("Katze sitzt seit Stunden auf Baum", "Tierrettung");
        $this->assertEquals("Tierrettung", $result, "\n❌ Fehler: Erwartete Kategorie 'Tierrettung', erhalten: '$result'");
        fwrite(STDOUT, "\n✅ Kategorie 'Tierrettung' korrekt erkannt.\n");
    }

    public function testAbsicherungKategorie()
    {
        $result = getKategorie("Verkehrssicherung für Umzug", "Absicherung");
        $this->assertEquals("Absicherung", $result, "\n❌ Fehler: Erwartete Kategorie 'Absicherung', erhalten: '$result'");
        fwrite(STDOUT, "\n✅ Kategorie 'Absicherung' korrekt erkannt.\n");
    }

    public function testSonstigesKategorie()
    {
        $result = getKategorie("Dies ist ein Testeinsatz ohne Schlagwort", "Systemtest");
        $this->assertEquals("Sonstiges", $result, "\n❌ Fehler: Erwartete Kategorie 'Sonstiges', erhalten: '$result'");
        fwrite(STDOUT, "\n✅ Kategorie 'Sonstiges' korrekt erkannt.\n");
    }

    public function testPriorisierung()
    {
        $result = getKategorie("Person bewusstlos nach Zimmerbrand", "Zimmerbrand");
        $this->assertEquals("Feuer", $result, "\n❌ Fehler: Erwartete Kategorie mit höherer Priorität 'Feuer', erhalten: '$result'");
        fwrite(STDOUT, "\n✅ Kategorie mit höchster Priorität ('Feuer') korrekt erkannt.\n");
    }
}
