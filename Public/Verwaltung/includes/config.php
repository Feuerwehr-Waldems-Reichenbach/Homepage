<?php
// Configuration file for the administration system

// Define base paths
define('BASE_PATH', dirname(__DIR__, 3)); // Root of the project
define('ADMIN_PATH', dirname(__DIR__)); // Admin directory

// URL paths
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];

// Calculate the base path more reliably
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$scriptDir = dirname($scriptPath);

// Find the position of '/Verwaltung/' in the script path
$verwaltungPos = strpos($scriptDir, '/Verwaltung');
if ($verwaltungPos !== false) {
    // Extract the path up to and including '/Verwaltung'
    $root = substr($scriptDir, 0, $verwaltungPos) . '/Verwaltung';
} else {
    // If '/Verwaltung/' isn't found, use the current directory
    $root = $scriptDir;
}

define('BASE_URL', $protocol . $domain . $root);

// Session settings
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('INACTIVITY_TIMEOUT', 1800); // 30 minutes in seconds

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 600); // 10 minutes in seconds

// File upload settings
define('MAX_UPLOAD_SIZE', 30 * 1024 * 1024); // 30 MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_DIR', ADMIN_PATH . '/assets/images/');

// Error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/Private/logs/error.log');

// Create log directory if it doesn't exist
if (!is_dir(BASE_PATH . '/Private/logs/')) {
    mkdir(BASE_PATH . '/Private/logs/', 0755, true);
}

// Bestimme die Domain für Cookies
$cookieDomain = '';  // Leerer String bedeutet, Domain vom Host übernehmen
// Optional: Wenn Sie eine spezifische Domain nutzen wollen (z.B. bei Subdomains)
// $cookieDomain = preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $matches) ? $matches['domain'] : '';

// Set session cookie parameters
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/',  // Wichtig: Der Pfad muss für alle Bereiche der Website gleich sein
    'domain' => $cookieDomain,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'  // Geändert von 'Strict' zu 'Lax' für bessere Kompatibilität
]);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session expiration time
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}

// Check for session timeout due to inactivity
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > INACTIVITY_TIMEOUT)) {
    // Session expired, destroy it
    session_unset();
    session_destroy();
    
    // Start new session for potential error messages
    session_start();
    $_SESSION['error'] = 'Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.';
    
    // Redirect to login page if not already there
    $current_script = basename($_SERVER['SCRIPT_NAME']);
    if ($current_script !== 'index.php') {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

// Update last activity time
$_SESSION['last_activity'] = time(); 