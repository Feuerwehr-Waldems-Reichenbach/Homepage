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
    
    // DEBUG: Direkt alle Reservierungen abrufen
    $db = Database::getInstance()->getConnection();
    $debugStmt = $db->prepare("
        SELECT id, start_datetime, end_datetime, status, is_public, event_name,
               key_handover_datetime, key_return_datetime
        FROM gh_reservations 
        WHERE 
            status IN ('confirmed', 'pending') AND
            (
                (YEAR(start_datetime) = ? AND MONTH(start_datetime) = ?) OR
                (YEAR(end_datetime) = ? AND MONTH(end_datetime) = ?) OR
                (YEAR(key_handover_datetime) = ? AND MONTH(key_handover_datetime) = ?) OR
                (YEAR(key_return_datetime) = ? AND MONTH(key_return_datetime) = ?)
            )
        ORDER BY start_datetime
    ");
    $debugStmt->execute([$year, $month, $year, $month, $year, $month, $year, $month]);
    $reservationsDebug = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Daten für alle Tage im Monat sammeln
    $calendarData = [];
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
        // Format: YYYY-MM-DD
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        
        // Status des Tages abrufen (free, pending, booked oder array mit Detailinformationen)
        $dayStatus = $reservation->getReservationDayStatus($date);
        
        if (is_array($dayStatus)) {
            // Wenn das Ergebnis ein Array ist, füge alle Informationen hinzu
            $calendarData[$date] = $dayStatus;
            
            // Wenn es ein Event oder Schlüsselübergabe gibt, stelle sicher, dass die vollständigen Infos zurückgegeben werden
            if (isset($dayStatus['status'])) {
                if ($dayStatus['status'] === 'public_event' && !isset($dayStatus['event_name'])) {
                    $calendarData[$date]['event_name'] = '';
                }
                
                // Schlüsselübergabe-Informationen
                if (isset($dayStatus['key_info'])) {
                    // Stellen sicher, dass key_info erhalten bleibt
                    $calendarData[$date]['key_info'] = $dayStatus['key_info'];
                }
            }
        } else {
            // Ansonsten nur den Status-String
            $calendarData[$date] = $dayStatus;
        }
    }
    
    // JSON-Antwort senden mit Debug-Informationen
    echo json_encode([
        'success' => true,
        'data' => $calendarData,
        'debug' => [
            'reservations' => $reservationsDebug,
            'month' => $month,
            'year' => $year
        ]
    ]);
} catch (Exception $e) {
    
    // Generische Fehlermeldung zurückgeben
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ein Fehler ist bei der Verarbeitung aufgetreten. Bitte versuchen Sie es später erneut.',
        'error' => $e->getMessage()
    ]);
}
?> 