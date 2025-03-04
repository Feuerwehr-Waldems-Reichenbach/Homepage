<?php
require_once 'includes/auth.php';
require_once 'includes/user.php';

header('Content-Type: application/json');

$auth = new Auth();

// Check if user is logged in and is admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Nicht autorisiert.'
    ]);
    exit();
}

// Get all users
$user = new User();
$result = $user->getAllUsers();

// Return result
echo json_encode($result); 