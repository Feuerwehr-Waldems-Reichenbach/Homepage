<?php
// Ensure any errors don't affect JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);
// Catch errors and log them instead of displaying them
function jsonErrorHandler($errno, $errstr, $errfile, $errline)
{
    error_log("Error in get_calendar_data.php: [$errno] $errstr in $errfile on line $errline");
    return true; // Don't execute PHP's default error handler
}
// Set custom error handler
set_error_handler("jsonErrorHandler");
require_once '../includes/config.php';
require_once '../includes/Reservation.php';
// Set response header
header('Content-Type: application/json');
try {
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
    // Anzahl der Tage im Monat ermitteln
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    // Reservierungsobjekt erstellen
    $reservation = new Reservation();
    // Additional debug: Check if there are any reservations in the database at all
    $db = Database::getInstance()->getConnection();
    $allReservationsStmt = $db->prepare("SELECT COUNT(*) FROM gh_reservations");
    $allReservationsStmt->execute();
    $totalReservations = $allReservationsStmt->fetchColumn();
    // DEBUG: Direkt alle Reservierungen abrufen
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
    // Log the results to the error log for debugging
    error_log("Calendar data request for Month: " . $month . ", Year: " . $year);
    error_log("Total reservations in database: " . $totalReservations);
    error_log("Reservations for this month: " . count($reservationsDebug));
    // Daten für alle Tage im Monat sammeln
    $calendarData = [];
    $dates = [];
    for ($day = 1; $day <= $daysInMonth; $day++) {
        // Format: YYYY-MM-DD
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $dates[] = $date;
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
            'year' => $year,
            'total_reservations' => $totalReservations,
            'reservations_this_month' => count($reservationsDebug),
            'days_in_month' => $daysInMonth,
            'first_day' => isset($dates[0]) ? $dates[0] : null,
            'all_dates' => $dates
        ]
    ]);
} catch (Exception $e) {
    error_log("Exception in get_calendar_data.php: " . $e->getMessage());
    // Generische Fehlermeldung zurückgeben
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ein Fehler ist bei der Verarbeitung aufgetreten. Bitte versuchen Sie es später erneut.',
        'error' => $e->getMessage()
    ]);
} finally {
    // Restore default error handler
    restore_error_handler();
}
?>