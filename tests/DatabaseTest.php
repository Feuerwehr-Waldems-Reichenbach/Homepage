<?php
use PHPUnit\Framework\TestCase;
require_once 'rootpath.php';
require_once BASE_PATH . '/Private/Database/Database.php';
class DatabaseTest extends TestCase
{
    public function testDatabaseConnection()
    {
        try {
            fwrite(STDOUT, "\n📡 Teste Verbindung zur Datenbank...\n");
            // Datenbankinstanz abrufen
            $db = Database::getInstance();
            fwrite(STDOUT, "✅ Datenbank-Instanz erfolgreich geladen.\n");
            // Verbindung abrufen
            $conn = $db->getConnection();
            fwrite(STDOUT, "✅ Verbindung zur Datenbank erfolgreich hergestellt.\n");
            // Test: Ist die Verbindung ein PDO-Objekt?
            $this->assertInstanceOf(PDO::class, $conn);
            fwrite(STDOUT, "✅ Verbindung ist eine gültige PDO-Instanz.\n");
        } catch (Exception $e) {
            fwrite(STDOUT, "❌ Fehler bei der Verbindung: " . $e->getMessage() . "\n");
            $this->fail("Die Datenbankverbindung konnte nicht erfolgreich getestet werden.");
        }
    }
    public function testDatabaseTablesExist()
    {
        fwrite(STDOUT, "\n📡 Überprüfe, ob alle Tabellen in der Datenbank existieren...\n");
        $expectedTables = ['bookings', 'einsatz', 'neuigkeiten', 'popup', 'users', 'authentifizierungsschluessel'];
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($expectedTables as $table) {
            $this->assertContains($table, $existingTables, "❌ Tabelle '$table' existiert nicht!");
            fwrite(STDOUT, "✅ Tabelle '$table' ist vorhanden.\n");
        }
    }
    /**
     * @dataProvider tableColumnsProvider
     */
    public function testTableColumnsExist($tableName, $expectedColumns)
    {
        fwrite(STDOUT, "\n📡 Überprüfe, ob alle Spalten in der Tabelle '$tableName' existieren...\n");
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->query("SHOW COLUMNS FROM $tableName");
        $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($expectedColumns as $column) {
            $this->assertContains($column, $existingColumns, "❌ Spalte '$column' existiert nicht in '$tableName'!");
            fwrite(STDOUT, "✅ Spalte '$column' ist vorhanden.\n");
        }
    }
    public static function tableColumnsProvider()
    {
        return [
            'bookings' => [
                'bookings',
                ['id', 'user_id', 'start_date', 'end_date', 'start_time', 'end_time', 'message', 'status', 'created_at']
            ],
            'einsatz' => [
                'einsatz',
                ['ID', 'EinsatzID', 'Anzeigen', 'Datum', 'Endzeit', 'Sachverhalt', 'Stichwort', 'Ort', 'Einheit', 'Kategorie']
            ],
            'neuigkeiten' => [
                'neuigkeiten',
                ['ID', 'Ueberschrift', 'Datum', 'Ort', 'Information']
            ],
            'popup' => [
                'popup',
                ['id', 'ueberschrift', 'kurzinfo', 'datum', 'ort', 'aktiv']
            ],
            'users' => [
                'users',
                ['id', 'name', 'email', 'password', 'is_admin', 'created_at']
            ],
            'authentifizierungsschluessel' => [
                'authentifizierungsschluessel',
                ['id', 'Bezeichnung', 'auth_key', 'active']
            ]
        ];
    }
    public function testAuthKeyTableStructure()
    {
        fwrite(STDOUT, "\n📡 Überprüfe die Struktur der Authentifizierungsschlüssel-Tabelle...\n");
        $db = Database::getInstance();
        $conn = $db->getConnection();
        // Prüfen, ob die Spalte 'active' vom Typ TINYINT(1) ist (für Boolean-Werte)
        $stmt = $conn->query("SHOW COLUMNS FROM authentifizierungsschluessel WHERE Field = 'active'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotFalse($column, "❌ Spalte 'active' existiert nicht in der Tabelle 'authentifizierungsschluessel'!");
        $this->assertStringContainsString('tinyint', strtolower($column['Type']), "❌ Spalte 'active' ist nicht vom Typ TINYINT!");
        fwrite(STDOUT, "✅ Spalte 'active' hat den korrekten Datentyp.\n");
        // Prüfen, ob die Spalte 'auth_key' einen ausreichenden Datentyp für Schlüssel hat
        $stmt = $conn->query("SHOW COLUMNS FROM authentifizierungsschluessel WHERE Field = 'auth_key'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotFalse($column, "❌ Spalte 'auth_key' existiert nicht in der Tabelle 'authentifizierungsschluessel'!");
        $this->assertTrue(
            strpos(strtolower($column['Type']), 'varchar') !== false ||
            strpos(strtolower($column['Type']), 'char') !== false ||
            strpos(strtolower($column['Type']), 'text') !== false,
            "❌ Spalte 'auth_key' hat keinen geeigneten Datentyp für Zeichenketten!"
        );
        fwrite(STDOUT, "✅ Spalte 'auth_key' hat einen geeigneten Datentyp.\n");
    }
}
?>