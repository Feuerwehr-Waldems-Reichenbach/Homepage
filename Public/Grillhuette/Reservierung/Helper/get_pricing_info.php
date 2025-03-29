<?php
require_once '../includes/config.php';
require_once '../includes/Reservation.php';

// Set response type to JSON
header('Content-Type: application/json');

try {
    // Initialize Reservation class to access the pricing method
    $reservation = new Reservation();
    
    // Get user ID from session if logged in
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Get pricing information for the current user
    $priceInfo = $reservation->getPriceInformation($userId);
    
    // Return the data
    echo json_encode([
        'success' => true,
        'user_rate' => $priceInfo['user_rate'],
        'base_price' => $priceInfo['base_price'],
        'deposit_amount' => $priceInfo['deposit_amount'],
        'rate_type' => $priceInfo['rate_type']
    ]);
} catch (Exception $e) {
    // Log error but return a generic error message
    error_log('Error in get_pricing_info.php: ' . $e->getMessage());
    
    // Return error with default values
    echo json_encode([
        'success' => false,
        'user_rate' => 100.00,
        'base_price' => 100.00,
        'deposit_amount' => 100.00,
        'rate_type' => 'normal',
        'message' => 'Ein Fehler ist aufgetreten. Standard-Preise werden verwendet.'
    ]);
}
?> 