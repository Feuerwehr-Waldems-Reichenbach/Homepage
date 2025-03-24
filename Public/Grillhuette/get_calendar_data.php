<?php
require_once 'includes/config.php';
require_once 'includes/Reservation.php';

// Monat und Jahr aus der Anfrage holen
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validieren
if ($month < 1 || $month > 12) {
    $month = date('n');
}

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
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $calendarData
]);
?> 