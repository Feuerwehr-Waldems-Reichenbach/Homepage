<?php
require_once '../includes/config.php';
require_once '../includes/Reservation.php';

// Set response header
header('Content-Type: application/json');

// Monat und Jahr aus der Anfrage holen
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validieren
if ($month === false || $month < 1 || $month > 12) {
    $month = date('n');
}

if ($year === false || $year < date('Y') || $year > date('Y') + 2) {
    $year = date('Y');
}

// Sitzungsprüfung - für eine öffentliche Ansicht kann dies optional sein
$isAuth = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;

try {
    // Anzahl der Tage im Monat ermitteln
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    // Reservierungsobjekt erstellen
    $reservation = new Reservation();
    
    // Daten für alle Tage im Monat sammeln
    $calendarData = [];
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
        // Format: YYYY-MM-DD
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        
        // Status des Tages abrufen (free, pending, booked)
        $status = $reservation->getReservationDayStatus($date);
        
        // In das Array einfügen
        $calendarData[$date] = $status;
    }
    
    // JSON-Antwort senden
    echo json_encode([
        'success' => true,
        'data' => $calendarData
    ]);
} catch (Exception $e) {
    
    // Generische Fehlermeldung zurückgeben
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ein Fehler ist bei der Kalenderdatenverarbeitung aufgetreten.'
    ]);
}
?> 