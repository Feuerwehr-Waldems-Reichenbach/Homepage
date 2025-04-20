<?php
// Include required files
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/models/AuthKey.php';
require_once dirname(__DIR__) . '/includes/Security.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Set security headers
Security::setSecurityHeaders();

// Instantiate the model
$authKeyModel = new AuthKey();

// Generate a new key
$key = $authKeyModel->generateKey();

// Return as JSON
header('Content-Type: application/json');
echo json_encode(['key' => $key]);
exit; 