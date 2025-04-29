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
    
    // 1. Dokumenteninformationen abrufen (inkl. version_group_id und is_latest)
    $stmtSelect = $db->prepare("SELECT pfad, dateiname_original, version_group_id, is_latest FROM verwaltung_dokumente WHERE id = :id");
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
    $versionGroupId = $dokument['version_group_id'];
    $isLatest = (bool)$dokument['is_latest'];
    $newLatestId = null;

    // 2. Transaktion starten
    $db->beginTransaction(); 
    
    try {
        // 3. Wenn die zu löschende Version die Neueste ist, die nächstältere zur Neuesten machen
        if ($isLatest) {
            $stmtFindNext = $db->prepare("
                SELECT id FROM verwaltung_dokumente 
                WHERE version_group_id = :vgroupid AND id != :currentid
                ORDER BY hochgeladen_am DESC 
                LIMIT 1
            ");
            $stmtFindNext->bindParam(':vgroupid', $versionGroupId, PDO::PARAM_INT);
            $stmtFindNext->bindParam(':currentid', $dokumentId, PDO::PARAM_INT);
            $stmtFindNext->execute();
            $nextLatest = $stmtFindNext->fetch(PDO::FETCH_ASSOC);

            if ($nextLatest) {
                $newLatestId = $nextLatest['id'];
                $stmtUpdateNext = $db->prepare("UPDATE verwaltung_dokumente SET is_latest = 1 WHERE id = :id");
                $stmtUpdateNext->bindParam(':id', $newLatestId, PDO::PARAM_INT);
                if (!$stmtUpdateNext->execute()) {
                    throw new PDOException("Konnte nächste Version nicht als neueste markieren.");
                }
            }
            // Wenn es keine ältere Version gibt ($nextLatest ist false), muss nichts getan werden.
        }

        // 4. Den ZU LÖSCHENDEN Datenbankeintrag entfernen
        $stmtDelete = $db->prepare("DELETE FROM verwaltung_dokumente WHERE id = :id");
        $stmtDelete->bindParam(':id', $dokumentId, PDO::PARAM_INT);
        
        if ($stmtDelete->execute()) {
            // 5. Datei vom Dateisystem löschen
            if (file_exists($filePath)) {
                if (unlink($filePath)) {
                    // Erfolg! Transaktion abschließen
                    $db->commit();
                    $_SESSION['success'] = 'Version von \'' . htmlspecialchars($originalFilename) . '\' erfolgreich gelöscht.';
                } else {
                    // Fehler beim Löschen der Datei - Transaktion zurückrollen
                    throw new Exception('Fehler beim Löschen der Datei vom Server (' . $filePath . ').');
                }
            } else {
                // Datei existiert nicht (mehr), aber DB-Eintrag wurde gelöscht - trotzdem Erfolg?
                // Normalerweise sollte die Datei existieren. Ggf. Warning + Commit.
                $db->commit(); 
                $_SESSION['warning'] = 'Datenbankeintrag für Version von \'' . htmlspecialchars($originalFilename) . '\' gelöscht, aber die Datei wurde nicht gefunden (' . $filePath . ').';
                error_log("Datei nicht gefunden beim Löschen (Versionierung): " . $filePath);
            }
        } else {
            // Fehler beim Löschen aus der DB - Transaktion zurückrollen
            throw new PDOException('Fehler beim Löschen des Dokuments aus der Datenbank.');
        }

    } catch (Exception $e) {
        // Bei Fehlern: Transaktion zurückrollen
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['error'] = 'Fehler beim Löschen: ' . $e->getMessage();
        error_log("Fehler beim Dokumentenlöschen (Versionierung): " . $e->getMessage() . " - DocID: " . $dokumentId);
    }

} catch (PDOException $e) {
    // Fehler beim initialen DB-Zugriff
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['error'] = 'Datenbankfehler beim Löschen des Dokuments.';
    error_log("PDOException beim Dokumentenlöschen (Initial): " . $e->getMessage());
} catch (Exception $e) {
     $_SESSION['error'] = 'Ein unerwarteter Fehler ist aufgetreten.';
     error_log("Allgemeiner Fehler beim Dokumentenlöschen (Initial): " . $e->getMessage());
}

// Zurückleiten zur Dokumentenübersicht
header('Location: ' . BASE_URL . '/dokumente.php');
exit;
?> 