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
if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bitte füllen Sie alle erforderlichen Felder aus.'
    ]);
    exit();
}

// Sanitize and validate input
$name = trim($data['name']);
$email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
$password = $data['password'];
$is_admin = isset($data['is_admin']) && $data['is_admin'] ? 1 : 0;

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

// Validate password
if (strlen($password) < 8) {
    echo json_encode([
        'success' => false,
        'message' => 'Das Passwort muss mindestens 8 Zeichen lang sein.'
    ]);
    exit();
}

// Create user
$user = new User();
$result = $user->createUser($name, $email, $password, $is_admin);

// Return result
echo json_encode($result); 