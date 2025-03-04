<?php
require_once 'includes/auth.php';
require_once 'includes/booking.php';

header('Content-Type: application/json');

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Sie müssen angemeldet sein, um eine Buchung vorzunehmen.'
    ]);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige Anfrage.'
    ]);
    exit();
}

// Get JSON data from request body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige Daten.'
    ]);
    exit();
}

// Validate required fields
$required_fields = ['start_date', 'end_date', 'start_time', 'end_time'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode([
            'success' => false,
            'message' => 'Bitte füllen Sie alle erforderlichen Felder aus.'
        ]);
        exit();
    }
}

// Validate dates and times
$start_date = new DateTime($data['start_date']);
$end_date = new DateTime($data['end_date']);
$start_time = new DateTime($data['start_time']);
$end_time = new DateTime($data['end_time']);
$now = new DateTime();

// Check if dates are in the past
if ($start_date < $now->setTime(0, 0)) {
    echo json_encode([
        'success' => false,
        'message' => 'Das Startdatum darf nicht in der Vergangenheit liegen.'
    ]);
    exit();
}

// Check if end date is before start date
if ($end_date < $start_date) {
    echo json_encode([
        'success' => false,
        'message' => 'Das Enddatum muss nach dem Startdatum liegen.'
    ]);
    exit();
}

// Check if times are within allowed range (8:00 - 20:00)
$min_time = new DateTime('08:00');
$max_time = new DateTime('20:00');

if ($start_time < $min_time || $start_time > $max_time || 
    $end_time < $min_time || $end_time > $max_time) {
    echo json_encode([
        'success' => false,
        'message' => 'Buchungen sind nur zwischen 8:00 und 20:00 Uhr möglich.'
    ]);
    exit();
}

// Check if end time is after start time
if ($end_time <= $start_time) {
    echo json_encode([
        'success' => false,
        'message' => 'Die Endzeit muss nach der Startzeit liegen.'
    ]);
    exit();
}

// Optional message
$message = isset($data['message']) ? trim($data['message']) : '';

// Create booking
$booking = new Booking();
$result = $booking->createBooking(
    $data['start_date'],
    $data['end_date'],
    $data['start_time'],
    $data['end_time'],
    $message
);

// Return result
echo json_encode($result); 