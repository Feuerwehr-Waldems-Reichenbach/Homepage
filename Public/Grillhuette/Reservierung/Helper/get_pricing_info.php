<?php
require_once '../includes/config.php';
require_once '../includes/Reservation.php';

// Set response type to JSON
header('Content-Type: application/json');

try {
    // Initialize Reservation class to access the pricing method
    $reservation = new Reservation();
    
    // Get user ID from session if logged in
    $userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    
    // Sicherheitsprüfung: Nur validierte user_id verwenden, keine Parameter von außen akzeptieren
    if ($userId !== null && $userId <= 0) {
        throw new Exception('Ungültige Benutzer-ID.');
    }
    
    // Get pricing information for the current user
    $priceInfo = $reservation->getPriceInformation($userId);
    
    // Preise werden jetzt komplett serverseitig berechnet, keine manuelle Anpassung mehr
    
    // Return the data
    echo json_encode([
        'success' => true,
        'user_rate' => $priceInfo['user_rate'],
        'base_price' => $priceInfo['base_price'],
        'deposit_amount' => $priceInfo['deposit_amount'],
        'rate_type' => $priceInfo['rate_type']
    ]);
} catch (Exception $e) {
    // Log error but don't expose detailed error information
    error_log('Error in get_pricing_info.php: ' . $e->getMessage());
    
    // Return error with default values
    http_response_code(400); // Bad request
    echo json_encode([
        'success' => false,
        'message' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.'
    ]);
}
?> 