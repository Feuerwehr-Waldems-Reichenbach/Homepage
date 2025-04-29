<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/Private/Database/Database.php';
require_once __DIR__ . '/includes/config.php'; // Stellt sicher, dass Session gestartet und Benutzer geprüft wird

$response = ['success' => false, 'versions' => [], 'message' => ''];

// Überprüfen, ob der Benutzer ein Admin ist
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $response['message'] = 'Zugriff verweigert.';
    http_response_code(403);
    echo json_encode($response);
    exit;
}

// Gruppen-ID aus dem GET-Parameter holen und validieren
if (!isset($_GET['group_id']) || !filter_var($_GET['group_id'], FILTER_VALIDATE_INT)) {
    $response['message'] = 'Ungültige Anfrage: Gruppen-ID fehlt oder ist ungültig.';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$groupId = (int)$_GET['group_id'];

// Funktion zum Formatieren der Dateigröße (aus dokumente.php kopiert)
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT d.id, d.dateiname_original, d.groesse, d.hochgeladen_am, d.is_latest, u.email as uploader_email
        FROM verwaltung_dokumente d
        LEFT JOIN fw_users u ON d.hochgeladen_von_userid = u.id
        WHERE d.version_group_id = :groupid
        ORDER BY d.hochgeladen_am DESC
    ");
    $stmt->bindParam(':groupid', $groupId, PDO::PARAM_INT);
    $stmt->execute();
    $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Füge formatierte Größe hinzu
    foreach ($versions as &$version) { // Wichtig: &$version, um das Array direkt zu ändern
        $version['groesse_formatiert'] = formatBytes($version['groesse']);
        $version['uploader_email'] = htmlspecialchars($version['uploader_email'] ?? 'Unbekannt');
    }
    unset($version); // Referenz aufheben nach der Schleife

    $response['success'] = true;
    $response['versions'] = $versions;

} catch (PDOException $e) {
    $response['message'] = 'Datenbankfehler beim Abrufen der Versionen.';
    error_log("PDOException beim Abrufen von Dokumentversionen: " . $e->getMessage());
    http_response_code(500);
} catch (Exception $e) {
    $response['message'] = 'Allgemeiner Fehler beim Abrufen der Versionen.';
    error_log("Fehler beim Abrufen von Dokumentversionen: " . $e->getMessage());
    http_response_code(500);
}

echo json_encode($response);
exit;
?> 