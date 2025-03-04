<?php
require_once 'includes/auth.php';
require_once 'includes/user.php';

header('Content-Type: application/json');

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Sie müssen angemeldet sein, um Ihr Profil zu bearbeiten.'
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
if (!isset($data['name']) || !isset($data['email'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bitte füllen Sie alle erforderlichen Felder aus.'
    ]);
    exit();
}

// Sanitize and validate input
$name = trim($data['name']);
$email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);

// Validate name
if (empty($name) || strlen($name) > 255) {
    echo json_encode([
        'success' => false,
        'message' => 'Der Name muss zwischen 1 und 255 Zeichen lang sein.'
    ]);
    exit();
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.'
    ]);
    exit();
}

// Update profile
$user = new User();
$result = $user->updateProfile($name, $email);

// Return result
echo json_encode($result); 