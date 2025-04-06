<?php
require_once '../includes/config.php';

// Set the proper content type
header('Content-Type: application/json');

// Check if the user is an admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode([
        'success' => false,
        'message' => 'Nur Administratoren können diese Funktion nutzen.'
    ]);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if the reservations table exists
    $tables = [];
    $stmt = $db->query("SHOW TABLES LIKE 'gh_%'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    // Get schema of reservations table
    $reservationsSchema = [];
    if (in_array('gh_reservations', $tables)) {
        $stmt = $db->query("DESCRIBE gh_reservations");
        $reservationsSchema = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Check if we're missing any required columns
    $requiredColumns = [
        'id', 'user_id', 'start_datetime', 'end_datetime', 'status',
        'is_public', 'event_name', 'display_event_name_on_calendar_start_date', 
        'display_event_name_on_calendar_end_date', 'key_handover_datetime', 'key_return_datetime'
    ];
    
    $missingColumns = [];
    if (!empty($reservationsSchema)) {
        $existingColumns = array_column($reservationsSchema, 'Field');
        $missingColumns = array_diff($requiredColumns, $existingColumns);
    }
    
    // Check for sample reservations
    $sampleReservations = [];
    if (in_array('gh_reservations', $tables)) {
        $stmt = $db->query("SELECT * FROM gh_reservations LIMIT 3");
        $sampleReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Response
    echo json_encode([
        'success' => true,
        'tables' => $tables,
        'gh_reservations_exists' => in_array('gh_reservations', $tables),
        'reservations_schema' => $reservationsSchema,
        'missing_columns' => $missingColumns,
        'sample_reservations' => $sampleReservations
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Fehler: ' . $e->getMessage()
    ]);
}
?> 