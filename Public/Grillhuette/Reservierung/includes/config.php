<?php
// Session starten mit sicheren Einstellungen
session_start([
    'cookie_httponly' => true,    // Verhindert Zugriff auf Session-Cookie über JavaScript
    'cookie_secure' => isset($_SERVER['HTTPS']), // Nur über HTTPS senden
    'cookie_samesite' => 'Lax',   // SameSite-Schutz gegen CSRF
    'use_strict_mode' => true     // Strikte Session-ID-Validierung
]);

// Fehlermeldungen nur im Entwicklungsmodus anzeigen
$isDevMode = false; // Auf false setzen für Produktion
if ($isDevMode) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Zeitzone festlegen
date_default_timezone_set('Europe/Berlin');

// Stammverzeichnis definieren
$stepsBack = 4; // Anpassung an die tatsächliche Verzeichnisstruktur
$basePath = __DIR__;
for ($i = 0; $i < $stepsBack; $i++) {
    $basePath = dirname($basePath);
}
define('BASE_PATH', $basePath);

// Application/Web Root definieren für URL-Generierung
define('APP_ROOT', '/Grillhuette/Reservierung');

// URL-Basis für Links zwischen Seiten
function getRelativePath($targetPage) {
    // Sanitize input to prevent directory traversal
    $targetPage = str_replace(['../', '..\\', './', '.\\'], '', $targetPage);
    $targetPage = preg_replace('/[^a-zA-Z0-9\-\/]/', '', $targetPage);
    
    // Basis-URL ist immer gleich - bezogen auf die APP_ROOT
    if ($targetPage === 'home') {
        return APP_ROOT . '/';
    } else {
        return APP_ROOT . '/' . $targetPage . '/';
    }
}

// Autoloader für Klassen
spl_autoload_register(function ($class) {
    // Zuerst in falls-back-Verzeichnis prüfen
    $localFile = __DIR__ . '/' . $class . '.php';
    if (file_exists($localFile)) {
        require_once $localFile;
        return;
    }
    
    // Dann im Private-Verzeichnis
    $file = BASE_PATH . '/Private/Database/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Email-Sender einbinden
$emailPath = BASE_PATH . '/Private/Email/emailSender.php';
if (file_exists($emailPath)) {
    require_once $emailPath;
} else {
    // Fallback lokalen emailSender laden
    require_once __DIR__ . '/Email/emailSender.php';
}

// Hilfsfunktionen

// CSRF-Token generieren
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Zufälligen Token generieren
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// XSS-Filterung
function escape($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Passwort-Stärke validieren
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Das Passwort muss mindestens einen Großbuchstaben enthalten.';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Das Passwort muss mindestens einen Kleinbuchstaben enthalten.';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Das Passwort muss mindestens eine Zahl enthalten.';
    }
    
    return $errors;
}

// Rate-Limiting für Anmeldeversuche
function checkLoginRateLimit($email) {
    // IP-Adresse des Benutzers
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Speicherort für fehlgeschlagene Versuche
    $db = Database::getInstance()->getConnection();
    
    // Alte Einträge entfernen (älter als 15 Minuten)
    $stmt = $db->prepare("DELETE FROM gh_login_attempts WHERE attempt_time < (NOW() - INTERVAL 15 MINUTE)");
    $stmt->execute();
    
    // Anzahl der Versuche in den letzten 15 Minuten zählen
    $stmt = $db->prepare("SELECT COUNT(*) FROM gh_login_attempts WHERE (ip = ? OR email = ?) AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
    $stmt->execute([$ip, $email]);
    $attempts = $stmt->fetchColumn();
    
    // Wenn mehr als 5 Versuche, blockieren
    if ($attempts >= 5) {
        // Ermittle, wann der erste Versuch stattfand
        $stmt = $db->prepare("SELECT MIN(attempt_time) FROM gh_login_attempts WHERE (ip = ? OR email = ?) AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
        $stmt->execute([$ip, $email]);
        $firstAttempt = $stmt->fetchColumn();
        
        // Berechne verbleibende Sperrzeit in Sekunden
        $expiryTime = strtotime($firstAttempt) + (15 * 60); // 15 Minuten in Sekunden
        $remainingSeconds = $expiryTime - time();
        
        // Gib Informationen zurück für einen benutzerfreundlichen Timer
        return [
            'allowed' => false,
            'remaining_seconds' => max(0, $remainingSeconds),
            'expiry_time' => $expiryTime
        ];
    }
    
    // Zugriff erlaubt
    return [
        'allowed' => true
    ];
}

// Fehlgeschlagenen Anmeldeversuch speichern
function logFailedLogin($email) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("INSERT INTO gh_login_attempts (ip, email, attempt_time) VALUES (?, ?, NOW())");
    $stmt->execute([$ip, $email]);
}