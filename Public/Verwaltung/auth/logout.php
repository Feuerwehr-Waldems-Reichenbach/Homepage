<?php
// Include required files
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/Security.php';
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (isset($_SESSION['email'])) {
    // Log the logout event
    Security::logSecurityEvent($_SESSION['email'], 'login', 'success', 'Erfolgreich abgemeldet');
}

// Clear all session variables
$_SESSION = [];

// Delete the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Start a new session to potentially set a success message
session_start();
$_SESSION['success'] = 'Sie wurden erfolgreich abgemeldet.';

// Use a more explicit, absolute URL for redirection
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['REQUEST_URI'], 2); // Go up two levels from auth/logout.php to get to Verwaltung

// Redirect to login page
header('Location: ' . $protocol . $domain . $path . '/index.php');
exit; 