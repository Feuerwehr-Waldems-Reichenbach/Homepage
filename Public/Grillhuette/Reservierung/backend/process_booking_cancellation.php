<?php
require_once 'includes/auth.php';
require_once 'includes/booking.php';

header('Content-Type: application/json');

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Sie müssen angemeldet sein, um eine Buchung zu stornieren.'
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

if (!$data || !isset($data['booking_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige Daten.'
    ]);
    exit();
}

$booking_id = (int)$data['booking_id'];

// Get booking details to check if it can be cancelled
$booking = new Booking();
$bookingsResult = $booking->getUserBookings();

if (!$bookingsResult['success']) {
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Abrufen der Buchungsdaten.'
    ]);
    exit();
}

$bookingFound = false;
foreach ($bookingsResult['bookings'] as $b) {
    if ($b['id'] === $booking_id) {
        $bookingFound = true;
        
        // Check if booking is already cancelled
        if ($b['status'] === 'cancelled') {
            echo json_encode([
                'success' => false,
                'message' => 'Diese Buchung wurde bereits storniert.'
            ]);
            exit();
        }

        // Check if booking is in the past
        $start_date = new DateTime($b['start_date'] . ' ' . $b['start_time']);
        $now = new DateTime();
        
        if ($start_date < $now) {
            echo json_encode([
                'success' => false,
                'message' => 'Vergangene Buchungen können nicht storniert werden.'
            ]);
            exit();
        }

        // Check if booking starts within 24 hours
        $diff = $start_date->diff($now);
        if ($diff->days === 0 && $diff->invert === 1) {
            echo json_encode([
                'success' => false,
                'message' => 'Buchungen können nur bis 24 Stunden vor Beginn storniert werden.'
            ]);
            exit();
        }
        
        break;
    }
}

if (!$bookingFound) {
    echo json_encode([
        'success' => false,
        'message' => 'Buchung nicht gefunden oder Sie sind nicht berechtigt, diese Buchung zu stornieren.'
    ]);
    exit();
}

// Cancel booking
$result = $booking->cancelBooking($booking_id);

// Return result
echo json_encode($result); 