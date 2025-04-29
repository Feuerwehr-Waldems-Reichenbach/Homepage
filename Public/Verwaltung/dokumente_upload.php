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
    'text/plain' => 'txt',
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
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO verwaltung_dokumente 
                (dateiname_original, dateiname_sicher, pfad, mime_typ, groesse, hochgeladen_von_userid, hochgeladen_am)
                VALUES (:original, :sicher, :pfad, :mime, :groesse, :userid, NOW())
            ");
            $stmt->bindParam(':original', $originalFilename);
            $stmt->bindParam(':sicher', $safeFilename);
            $stmt->bindParam(':pfad', $uploadPath); // Speichere den vollen Pfad für einfachen Zugriff
            $stmt->bindParam(':mime', $mimeType);
            $stmt->bindParam(':groesse', $file['size'], PDO::PARAM_INT);
            $stmt->bindParam(':userid', $userId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Dokument \'' . htmlspecialchars($originalFilename) . '\' erfolgreich hochgeladen.';
            } else {
                $_SESSION['error'] = 'Fehler beim Speichern der Dateiinformationen in der Datenbank.';
                // Optional: Hochgeladene Datei wieder löschen, wenn DB-Eintrag fehlschlägt
                unlink($uploadPath);
                 error_log('DB Fehler beim Dokumentenupload: ' . implode('; ', $stmt->errorInfo()));
            }

        } catch (PDOException $e) {
            $_SESSION['error'] = 'Datenbankfehler beim Speichern des Dokuments.';
            // Optional: Hochgeladene Datei wieder löschen
            unlink($uploadPath);
            error_log('PDOException beim Dokumentenupload: ' . $e->getMessage());
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