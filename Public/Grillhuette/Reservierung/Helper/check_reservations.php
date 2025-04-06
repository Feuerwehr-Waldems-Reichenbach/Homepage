<?php
require_once '../includes/config.php';
require_once '../includes/Reservation.php';

// Check if the user is an admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    // For security, only show number of reservations to non-admins
    header('Content-Type: application/json');
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM gh_reservations");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'message' => 'Reservierungen in der Datenbank: ' . $count,
        'count' => $count
    ]);
    exit;
}

// For admins, show more detailed information
header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Get all reservations
    $stmt = $db->prepare("
        SELECT id, user_id, start_datetime, end_datetime, status, 
               created_at, updated_at, is_public, event_name,
               key_handover_datetime, key_return_datetime
        FROM gh_reservations 
        ORDER BY start_datetime DESC
        LIMIT 50
    ");
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get count of reservations
    $stmt = $db->prepare("SELECT COUNT(*) FROM gh_reservations");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    // Get count by status
    $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM gh_reservations GROUP BY status");
    $stmt->execute();
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get table structure
    $stmt = $db->prepare("DESCRIBE gh_reservations");
    $stmt->execute();
    $tableStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return all data
    echo json_encode([
        'success' => true,
        'message' => 'Es wurden ' . count($reservations) . ' von insgesamt ' . $count . ' Reservierungen gefunden.',
        'count' => $count,
        'status_counts' => $statusCounts,
        'reservations' => $reservations,
        'table_structure' => $tableStructure
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Fehler: ' . $e->getMessage()
    ]);
}
?> 