<?php
require_once dirname(__DIR__, 2) . '/Private/Database/Database.php';
require_once __DIR__ . '/includes/config.php'; // Stellt sicher, dass Session gestartet und Benutzer geprüft wird
require_once __DIR__ . '/includes/Security.php';

// --- Konfiguration ---
// Pfad zum sicheren Upload-Verzeichnis (außerhalb des Web-Roots)
$uploadBasePath = BASE_PATH . '/Private/verwaltung_uploads/'; 
// Erlaubte MIME-Typen und ihre entsprechenden Erweiterungen
$allowedMimeTypes = [
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    'application/vnd.ms-powerpoint' => 'ppt',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    'text/plain' => 'txt', // Deckt auch .sql ab
    'text/csv' => 'csv', // CSV hinzugefügt
];
$maxFileSize = 20 * 1024 * 1024; // 20 MB
// --------------------

// Überprüfen, ob der Benutzer ein Admin ist (Session-Check in config.php sollte das bereits tun)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['error'] = 'Zugriff verweigert.';
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// CSRF-Token prüfen
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = 'Ungültiges CSRF-Token.';
    header('Location: ' . BASE_URL . '/dokumente.php'); // Zurück zum Dokumenten-Interface
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dokument'])) {
    $file = $_FILES['dokument'];
    $userId = $_SESSION['user_id'];

    // Fehler beim Upload prüfen
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => "Die hochgeladene Datei überschreitet die 'upload_max_filesize' Direktive in php.ini.",
            UPLOAD_ERR_FORM_SIZE => "Die hochgeladene Datei überschreitet die 'MAX_FILE_SIZE' Direktive, die im HTML-Formular angegeben wurde.",
            UPLOAD_ERR_PARTIAL => "Die hochgeladene Datei wurde nur teilweise hochgeladen.",
            UPLOAD_ERR_NO_FILE => "Es wurde keine Datei hochgeladen.",
            UPLOAD_ERR_NO_TMP_DIR => "Fehlender temporärer Ordner.",
            UPLOAD_ERR_CANT_WRITE => "Fehler beim Schreiben der Datei auf die Festplatte.",
            UPLOAD_ERR_EXTENSION => "Eine PHP-Erweiterung hat den Datei-Upload gestoppt.",
        ];
        $_SESSION['error'] = 'Fehler beim Upload: ' . ($uploadErrors[$file['error']] ?? 'Unbekannter Fehler.');
        header('Location: ' . BASE_URL . '/dokumente.php');
        exit;
    }

    // Dateigröße prüfen
    if ($file['size'] > $maxFileSize) {
        $_SESSION['error'] = 'Die Datei ist zu groß. Maximale Größe: ' . ($maxFileSize / 1024 / 1024) . ' MB.';
        header('Location: ' . BASE_URL . '/dokumente.php');
        exit;
    }

    // MIME-Typ prüfen (sicherer als nur die Erweiterung)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!array_key_exists($mimeType, $allowedMimeTypes)) {
        $_SESSION['error'] = 'Ungültiger Dateityp (' . htmlspecialchars($mimeType) . '). Erlaubte Typen: ' . implode(', ', array_values($allowedMimeTypes));
        header('Location: ' . BASE_URL . '/dokumente.php');
        exit;
    }

    // Originalen Dateinamen und Erweiterung holen
    $originalFilename = $file['name'];
    $fileExtension = $allowedMimeTypes[$mimeType]; // Verwende die Erweiterung basierend auf dem validierten MIME-Typ

    // Sicheren Dateinamen generieren
    $safeFilename = uniqid('doc_', true) . '.' . $fileExtension;
    $uploadPath = $uploadBasePath . $safeFilename;

    // Upload-Verzeichnis erstellen, wenn es nicht existiert
    if (!is_dir($uploadBasePath)) {
        if (!mkdir($uploadBasePath, 0750, true)) { // 0750 = rwxr-x--- 
            $_SESSION['error'] = 'Upload-Verzeichnis konnte nicht erstellt werden.';
            error_log('Konnte Upload-Verzeichnis nicht erstellen: ' . $uploadBasePath);
            header('Location: ' . BASE_URL . '/dokumente.php');
            exit;
        }
        // Sicherheits-Datei hinzufügen, um direkten Zugriff zu verhindern, falls das Verzeichnis doch im Webroot landet
        file_put_contents($uploadBasePath . '.htaccess', "Deny from all");
        file_put_contents($uploadBasePath . 'index.html', ''); // Verhindert Directory Listing
    }

    // Datei verschieben
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Eintrag in der Datenbank erstellen
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction(); // Starte Transaktion
        
        try {
            $versionGroupId = null;
            $isNewVersion = false;

            // Prüfen, ob eine Datei mit diesem Originalnamen bereits existiert (nur die neueste Version prüfen)
            $stmtCheck = $db->prepare("SELECT id, version_group_id FROM verwaltung_dokumente WHERE dateiname_original = :original AND is_latest = 1 LIMIT 1");
            $stmtCheck->bindParam(':original', $originalFilename);
            $stmtCheck->execute();
            $existingDoc = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existingDoc) {
                // Datei existiert -> neue Version erstellen
                $isNewVersion = true;
                $versionGroupId = $existingDoc['version_group_id'];
                $oldVersionId = $existingDoc['id'];

                // Alte neueste Version auf is_latest = 0 setzen
                $stmtUpdateOld = $db->prepare("UPDATE verwaltung_dokumente SET is_latest = 0 WHERE id = :id");
                $stmtUpdateOld->bindParam(':id', $oldVersionId, PDO::PARAM_INT);
                if (!$stmtUpdateOld->execute()) {
                    throw new Exception("Konnte alte Version nicht aktualisieren.");
                }
            }

            // Neuen Dokumenteneintrag einfügen (ist immer die neueste Version)
            $stmtInsert = $db->prepare("
                INSERT INTO verwaltung_dokumente 
                (dateiname_original, dateiname_sicher, pfad, mime_typ, groesse, hochgeladen_von_userid, hochgeladen_am, is_latest, version_group_id)
                VALUES (:original, :sicher, :pfad, :mime, :groesse, :userid, NOW(), 1, :vgroupid)
            ");
            $stmtInsert->bindParam(':original', $originalFilename);
            $stmtInsert->bindParam(':sicher', $safeFilename);
            $stmtInsert->bindParam(':pfad', $uploadPath); 
            $stmtInsert->bindParam(':mime', $mimeType);
            $stmtInsert->bindParam(':groesse', $file['size'], PDO::PARAM_INT);
            $stmtInsert->bindParam(':userid', $userId, PDO::PARAM_INT);
            $stmtInsert->bindParam(':vgroupid', $versionGroupId); // Kann hier noch NULL sein für erste Version
            
            if ($stmtInsert->execute()) {
                $newDocId = $db->lastInsertId();

                if (!$isNewVersion) {
                    // Dies ist die erste Version, setze version_group_id auf die eigene ID
                    $versionGroupId = $newDocId;
                    $stmtUpdateVGroupId = $db->prepare("UPDATE verwaltung_dokumente SET version_group_id = :vgroupid WHERE id = :id");
                    $stmtUpdateVGroupId->bindParam(':vgroupid', $versionGroupId, PDO::PARAM_INT);
                    $stmtUpdateVGroupId->bindParam(':id', $newDocId, PDO::PARAM_INT);
                    if (!$stmtUpdateVGroupId->execute()) {
                        throw new Exception("Konnte version_group_id für erste Version nicht setzen.");
                    }
                }
                
                $db->commit(); // Transaktion erfolgreich abschließen
                $_SESSION['success'] = 'Dokument \'' . htmlspecialchars($originalFilename) . '\' erfolgreich hochgeladen ' . ($isNewVersion ? '(Neue Version)' : '') . '.';

            } else {
                throw new Exception("Fehler beim Speichern der neuen Dokumentenversion in der Datenbank.");
            }

        } catch (Exception $e) {
            // Bei Fehlern: Transaktion zurückrollen und Datei löschen
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if (file_exists($uploadPath)) {
                 unlink($uploadPath);
            }
            $_SESSION['error'] = 'Fehler beim Upload: ' . $e->getMessage();
            error_log('Fehler beim Dokumentenupload (Versionierung): ' . $e->getMessage());
        }

    } else {
        $_SESSION['error'] = 'Fehler beim Verschieben der hochgeladenen Datei.';
         error_log('move_uploaded_file Fehler von ' . $file['tmp_name'] . ' nach ' . $uploadPath);
    }

} else {
    $_SESSION['error'] = 'Keine Datei oder ungültige Anfrage.';
}

// Zurückleiten zur Dokumentenübersicht
header('Location: ' . BASE_URL . '/dokumente.php');
exit;
?> 