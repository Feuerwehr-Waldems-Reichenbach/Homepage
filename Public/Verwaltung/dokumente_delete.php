<?php
require_once dirname(__DIR__, 2) . '/Private/Database/Database.php';
require_once __DIR__ . '/includes/config.php'; // Stellt sicher, dass Session gestartet und Benutzer geprüft wird
require_once __DIR__ . '/includes/Security.php';

// Überprüfen, ob der Benutzer ein Admin ist
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['error'] = 'Zugriff verweigert.';
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// CSRF-Token prüfen (wichtig bei Löschaktionen!)
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = 'Ungültiges CSRF-Token.';
    header('Location: ' . BASE_URL . '/dokumente.php');
    exit;
}

// Dokumenten-ID aus POST holen und validieren
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error'] = 'Ungültige Anfrage.';
    header('Location: ' . BASE_URL . '/dokumente.php');
    exit;
}

$dokumentId = (int)$_POST['id'];

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Dokumenteninformationen abrufen (insbesondere den Pfad)
    $stmtSelect = $db->prepare("SELECT pfad, dateiname_original FROM verwaltung_dokumente WHERE id = :id");
    $stmtSelect->bindParam(':id', $dokumentId, PDO::PARAM_INT);
    $stmtSelect->execute();
    $dokument = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    if (!$dokument) {
        $_SESSION['error'] = 'Dokument nicht gefunden.';
        header('Location: ' . BASE_URL . '/dokumente.php');
        exit;
    }

    $filePath = $dokument['pfad'];
    $originalFilename = $dokument['dateiname_original'];

    // 2. Datenbankeintrag löschen
    $db->beginTransaction(); // Starte Transaktion
    
    $stmtDelete = $db->prepare("DELETE FROM verwaltung_dokumente WHERE id = :id");
    $stmtDelete->bindParam(':id', $dokumentId, PDO::PARAM_INT);
    
    if ($stmtDelete->execute()) {
        // 3. Datei vom Dateisystem löschen
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                // Erfolg! Transaktion abschließen
                $db->commit();
                $_SESSION['success'] = 'Dokument \'' . htmlspecialchars($originalFilename) . '\' erfolgreich gelöscht.';
            } else {
                // Fehler beim Löschen der Datei - Transaktion zurückrollen
                $db->rollBack();
                $_SESSION['error'] = 'Fehler beim Löschen der Datei vom Server.';
                error_log("Fehler beim Löschen der Datei: " . $filePath);
            }
        } else {
            // Datei existiert nicht (mehr), aber DB-Eintrag wurde gelöscht - trotzdem Erfolg
            $db->commit(); 
            $_SESSION['warning'] = 'Datenbankeintrag für \'' . htmlspecialchars($originalFilename) . '\' gelöscht, aber die Datei wurde nicht gefunden (' . $filePath . ').';
            error_log("Datei nicht gefunden beim Löschen: " . $filePath);
        }
    } else {
        // Fehler beim Löschen aus der DB - Transaktion zurückrollen
        $db->rollBack();
        $_SESSION['error'] = 'Fehler beim Löschen des Dokuments aus der Datenbank.';
        error_log('DB Fehler beim Dokumentenlöschen: ' . implode('; ', $stmtDelete->errorInfo()));
    }

} catch (PDOException $e) {
    // Bei DB-Fehler Transaktion zurückrollen, falls sie noch aktiv ist
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['error'] = 'Datenbankfehler beim Löschen des Dokuments.';
    error_log("PDOException beim Dokumentenlöschen: " . $e->getMessage());
} catch (Exception $e) {
     $_SESSION['error'] = 'Ein unerwarteter Fehler ist aufgetreten.';
     error_log("Allgemeiner Fehler beim Dokumentenlöschen: " . $e->getMessage());
}

// Zurückleiten zur Dokumentenübersicht
header('Location: ' . BASE_URL . '/dokumente.php');
exit;
?> 