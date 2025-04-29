<?php
require_once dirname(__DIR__, 2) . '/Private/Database/Database.php';
require_once __DIR__ . '/includes/config.php'; // Stellt sicher, dass Session gestartet und Benutzer geprüft wird

// Überprüfen, ob der Benutzer ein Admin ist
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo "Zugriff verweigert.";
    exit;
}

// Dokumenten-ID aus dem GET-Parameter holen und validieren
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo "Ungültige Anfrage: Dokumenten-ID fehlt oder ist ungültig.";
    exit;
}

$dokumentId = (int)$_GET['id'];

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT pfad, dateiname_original, mime_typ, groesse FROM verwaltung_dokumente WHERE id = :id");
    $stmt->bindParam(':id', $dokumentId, PDO::PARAM_INT);
    $stmt->execute();
    $dokument = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dokument) {
        http_response_code(404);
        echo "Dokument nicht gefunden.";
        exit;
    }

    $filePath = $dokument['pfad'];
    $originalFilename = $dokument['dateiname_original'];
    $mimeType = $dokument['mime_typ'];
    $fileSize = $dokument['groesse'];

    // Sicherstellen, dass die Datei existiert und lesbar ist
    if (!file_exists($filePath) || !is_readable($filePath)) {
        http_response_code(500);
        error_log("Download-Fehler: Datei nicht gefunden oder nicht lesbar: " . $filePath);
        echo "Fehler beim Zugriff auf die Datei.";
        exit;
    }

    // Sicherheitsheader setzen
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    // Content-Disposition: inline (versucht Anzeige im Browser), attachment (erzwingt Download)
    // Sanitize filename for header
    $safeDisplayFilename = preg_replace('/[^\x20-\x7E]/ ', '?', $originalFilename); // Ersetzt nicht-druckbare ASCII-Zeichen
    header('Content-Disposition: inline; filename="' . $safeDisplayFilename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $fileSize);

    // Dateiinhalt lesen und ausgeben
    // Deaktiviere Output Buffering, falls aktiv
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    readfile($filePath);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Datenbankfehler beim Dokumenten-Download: " . $e->getMessage());
    echo "Ein interner Fehler ist aufgetreten.";
    exit;
} catch (Exception $e) {
    http_response_code(500);
    error_log("Allgemeiner Fehler beim Dokumenten-Download: " . $e->getMessage());
    echo "Ein unerwarteter Fehler ist aufgetreten.";
    exit;
}
?> 