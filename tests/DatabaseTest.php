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
}
?>