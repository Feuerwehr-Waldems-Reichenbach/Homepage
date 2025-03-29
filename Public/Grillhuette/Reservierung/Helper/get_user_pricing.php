<?php
require_once '../includes/config.php';
require_once '../includes/Reservation.php';

// Set response type to JSON
header('Content-Type: application/json');

// Verify the user is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized',
        'user_rate' => 100.00,
        'base_price' => 100.00,
        'deposit_amount' => 100.00,
        'rate_type' => 'normal',
    ]);
    exit;
}

// Get the user ID from the request
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

if (!$userId) {
    echo json_encode([
        'success' => false,
        'message' => 'No user ID provided',
        'user_rate' => 100.00,
        'base_price' => 100.00,
        'deposit_amount' => 100.00,
        'rate_type' => 'normal',
    ]);
    exit;
}

try {
    // Initialize Reservation class to access the pricing method
    $reservation = new Reservation();
    
    // Get pricing information for the specified user
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
    error_log('Error in get_user_pricing.php: ' . $e->getMessage());
    
    // Return error with default values
    echo json_encode([
        'success' => false,
        'message' => 'Ein Fehler ist aufgetreten. Standard-Preise werden verwendet.',
        'user_rate' => 100.00,
        'base_price' => 100.00,
        'deposit_amount' => 100.00,
        'rate_type' => 'normal'
    ]);
}
?> 