<?php
require_once 'includes/auth.php';
require_once 'includes/user.php';

header('Content-Type: application/json');

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Sie müssen angemeldet sein, um Ihr Passwort zu ändern.'
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
$required_fields = ['current_password', 'new_password', 'confirm_password'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode([
            'success' => false,
            'message' => 'Bitte füllen Sie alle erforderlichen Felder aus.'
        ]);
        exit();
    }
}

// Validate password match
if ($data['new_password'] !== $data['confirm_password']) {
    echo json_encode([
        'success' => false,
        'message' => 'Die neuen Passwörter stimmen nicht überein.'
    ]);
    exit();
}

// Validate password strength
if (strlen($data['new_password']) < 8) {
    echo json_encode([
        'success' => false,
        'message' => 'Das neue Passwort muss mindestens 8 Zeichen lang sein.'
    ]);
    exit();
}

// Additional password strength checks
if (!preg_match('/[A-Z]/', $data['new_password']) || 
    !preg_match('/[a-z]/', $data['new_password']) || 
    !preg_match('/[0-9]/', $data['new_password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Das neue Passwort muss mindestens einen Großbuchstaben, einen Kleinbuchstaben und eine Zahl enthalten.'
    ]);
    exit();
}

// Check if new password is different from current
if ($data['current_password'] === $data['new_password']) {
    echo json_encode([
        'success' => false,
        'message' => 'Das neue Passwort muss sich vom aktuellen Passwort unterscheiden.'
    ]);
    exit();
}

// Change password
$user = new User();
$result = $user->changePassword($data['current_password'], $data['new_password']);

// Return result
echo json_encode($result); 