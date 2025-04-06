<?php
// Session starten mit sicheren Einstellungen
session_start([
    'cookie_httponly' => true,    // Verhindert Zugriff auf Session-Cookie über JavaScript
    'cookie_secure' => isSecureConnection(), // Sicherere Überprüfung auf HTTPS-Verbindung
    'cookie_samesite' => 'Lax',   // SameSite-Schutz gegen CSRF
    'use_strict_mode' => true     // Strikte Session-ID-Validierung
]);

// Absolutes Session-Timeout implementieren
$session_max_lifetime = 14400; // 4 Stunden in Sekunden
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_max_lifetime)) {
    // Session ist älter als erlaubter Zeitraum - Session zerstören
    session_unset();
    session_destroy();
    
    // Bereinigter Neustart der Session
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isSecureConnection(),
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true
    ]);
    
    // Optional: Weiterleitung zur Login-Seite, wenn gewünscht
    if (basename($_SERVER['PHP_SELF']) !== 'index.php' && !in_array(basename($_SERVER['PHP_SELF']), ['Anmelden', 'Registrieren', 'Passwort-vergessen', 'Passwort-zuruecksetzen'])) {
        $_SESSION['flash_message'] = 'Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.';
        $_SESSION['flash_type'] = 'warning';
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . APP_ROOT . '/Benutzer/Anmelden/');
        exit;
    }
}

// Periodische Session-ID-Erneuerung für zusätzliche Sicherheit
$session_renew_period = 1800; // 30 Minuten in Sekunden
if (isset($_SESSION['last_regeneration']) && (time() - $_SESSION['last_regeneration'] > $session_renew_period)) {
    // Session-ID regelmäßig erneuern, während Benutzer aktiv ist
    regenerateSession();
    $_SESSION['last_regeneration'] = time();
}

// Aktivitätszeitstempel aktualisieren
$_SESSION['last_activity'] = time();
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
}

// Funktion zur zuverlässigen Überprüfung einer sicheren Verbindung
function isSecureConnection() {
    // Für lokale Entwicklung immer HTTP verwenden
    $localIPs = ['localhost', '127.0.0.1', '::1', '192.168.2.222'];
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Prüfen, ob wir in einer lokalen Entwicklungsumgebung sind
    if (in_array($serverName, $localIPs) || 
        in_array($serverAddr, $localIPs) || 
        in_array($remoteAddr, $localIPs) ||
        preg_match('/^192\.168\./', $serverName) ||
        preg_match('/^192\.168\./', $serverAddr) ||
        preg_match('/^192\.168\./', $remoteAddr)) {
        return false;
    }
    
    // Für Produktionsumgebungen die reguläre HTTPS-Erkennung verwenden
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && $_SERVER['HTTP_FRONT_END_HTTPS'] === 'on')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https');
}

// Gibt das korrekte Protokoll zurück (http oder https)
function getProtocol() {
    return isSecureConnection() ? 'https://' : 'http://';
}

// Baut eine vollständige URL mit dem korrekten Protokoll
function buildUrl($path) {
    return getProtocol() . $_SERVER['HTTP_HOST'] . $path;
}

// Funktion zur Session-Regeneration nach kritischen Aktionen
function regenerateSession() {
    // Aktuellen Sessiondaten sichern
    $sessionData = $_SESSION;
    
    // Session-ID regenerieren und alte Session löschen
    session_regenerate_id(true);
    
    // Sessiondaten wiederherstellen
    $_SESSION = $sessionData;
    
    // CSRF-Token nach Session-Regeneration erneuern
    generate_csrf_token(true);
    
    return true;
}

// Fehleranzeige deaktivieren (Produktionsmodus)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    // Comprehensive sanitization to prevent directory traversal
    
    // 1. Normalize input by decoding any URL-encoded values that might hide traversal attempts
    $targetPage = urldecode($targetPage);
    
    // 2. Remove all variations of directory traversal patterns
    // This includes standard, encoded, and double-encoded versions
    $patterns = [
        // Standard traversal patterns
        '../', '..\\', './', '.\\',
        // URL encoded variations
        '%2e%2e%2f', '%2e%2e/', '..%2f', '%2e%2e%5c', '%2e%2e\\', '..%5c',
        '%2e/', '.%2f', '%2e\\', '.%5c',
        // Double-encoded variations
        '%252e%252e%252f', '%252e%252e/', '..%252f', '%252e%252e%255c', '%252e%252e\\', '..%255c',
        '%252e/', '.%252f', '%252e\\', '.%255c',
        // Unicode/alternate representations
        '..%c0%af', '..%c1%9c', '..%c1%pc', '..%%32%66', '..%%35%63', '..%%35c',
        // Null byte attacks
        '../%00', '..\\%00', './%00', '.\\%00',
        // Other potential bypass attempts
        '....///', '...\\\\', '....\\',
    ];
    
    $targetPage = str_ireplace($patterns, '', $targetPage);
    
    // 3. Only allow alphanumeric characters, hyphens, and forward slashes
    $targetPage = preg_replace('/[^a-zA-Z0-9\-\/]/', '', $targetPage);
    
    // 4. Remove any sequences that could still be problematic
    $targetPage = preg_replace('/\/+/', '/', $targetPage); // Replace multiple slashes with single slash
    $targetPage = preg_replace('/^\//', '', $targetPage);  // Remove leading slash
    
    // 5. Validate final path doesn't start with suspicious patterns
    if (preg_match('/^(etc|tmp|proc|sys|var|dev|bin|sbin|usr|lib|root|home)\//', $targetPage)) {
        // If it still contains sensitive system directories, return a safe default
        $targetPage = 'home';
    }
    
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
function generate_csrf_token($force_refresh = false) {
    if (!isset($_SESSION['csrf_token']) || $force_refresh) {
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
    
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = 'Das Passwort muss mindestens ein Sonderzeichen (z.B. !@#$%^&*) enthalten.';
    }
    
    return $errors;
}

// Rate-Limiting für Anmeldeversuche
function checkLoginRateLimit($email) {
    // IP-Adresse des Benutzers
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Speicherort für fehlgeschlagene Versuche
    $db = Database::getInstance()->getConnection();
    
    // Alte Einträge entfernen (älter als 24 Stunden)
    $stmt = $db->prepare("DELETE FROM gh_login_attempts WHERE attempt_time < (NOW() - INTERVAL 24 HOUR)");
    $stmt->execute();
    
    // Separate Prüfungen für IP und Email durchführen
    
    // 1. IP-basierte Limitierung (verhindert Brute-Force von einer IP aus)
    $stmt = $db->prepare("SELECT COUNT(*) FROM gh_login_attempts WHERE ip = ? AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
    $stmt->execute([$ip]);
    $ipAttempts = $stmt->fetchColumn();
    
    // 2. Email-basierte Limitierung (schützt bestimmte Konten vor gezielten Angriffen)
    $stmt = $db->prepare("SELECT COUNT(*) FROM gh_login_attempts WHERE email = ? AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
    $stmt->execute([$email]);
    $emailAttempts = $stmt->fetchColumn();
    
    // 3. Prüfen auf wiederholte Angriffsmuster
    $stmt = $db->prepare("SELECT COUNT(DISTINCT ip) FROM gh_login_attempts WHERE email = ? AND attempt_time > (NOW() - INTERVAL 24 HOUR)");
    $stmt->execute([$email]);
    $uniqueIPs = $stmt->fetchColumn();
    
    // Schwellwerte für verschiedene Arten von Limits
    $ipThreshold = 10;      // Versuche pro IP (höher als vorher, aber immer noch begrenzt)
    $emailThreshold = 5;    // Versuche pro E-Mail (schützt Benutzerkonten)
    $distributedThreshold = 3; // Anzahl der unterschiedlichen IPs, die als verteilter Angriff gelten
    
    // Sperrzeit in Sekunden basierend auf verschiedenen Faktoren bestimmen
    $lockoutSeconds = 900;  // 15 Minuten als Basis
    
    // Progressive Sperrzeiten basierend auf Angriffsmustern
    if ($uniqueIPs >= $distributedThreshold) {
        // Bei Anzeichen eines verteilten Angriffs längere Sperrzeit
        $lockoutSeconds = 3600; // 1 Stunde
    }
    
    if ($emailAttempts > $emailThreshold * 2) {
        // Bei wiederholten Zugriffsversuchen auf eine E-Mail längere Sperrzeit
        $lockoutSeconds = 7200; // 2 Stunden
    }
    
    // Ermittle die relevante Sperrzeit basierend auf den Versuchen
    if ($ipAttempts >= $ipThreshold || $emailAttempts >= $emailThreshold) {
        // Ermittle, wann der erste Versuch stattfand
        if ($emailAttempts >= $emailThreshold) {
            // Bei E-Mail-basierter Sperrung den ersten E-Mail-Versuch verwenden
            $stmt = $db->prepare("SELECT MIN(attempt_time) FROM gh_login_attempts WHERE email = ? AND attempt_time > (NOW() - INTERVAL 24 HOUR)");
            $stmt->execute([$email]);
        } else {
            // Bei IP-basierter Sperrung den ersten IP-Versuch verwenden
            $stmt = $db->prepare("SELECT MIN(attempt_time) FROM gh_login_attempts WHERE ip = ? AND attempt_time > (NOW() - INTERVAL 24 HOUR)");
            $stmt->execute([$ip]);
        }
        $firstAttempt = $stmt->fetchColumn();
        
        // Berechne verbleibende Sperrzeit in Sekunden
        $expiryTime = strtotime($firstAttempt) + $lockoutSeconds;
        $remainingSeconds = $expiryTime - time();
        
        // Sicherheitsmaßnahme: Verzögere die Antwort leicht, um Timing-Angriffe zu erschweren
        usleep(rand(100000, 300000)); // 100-300ms zufällige Verzögerung
        
        // Gib Informationen zurück für einen benutzerfreundlichen Timer
        return [
            'allowed' => false,
            'remaining_seconds' => max(0, $remainingSeconds),
            'expiry_time' => $expiryTime
        ];
    }
    
    // Zufällige kleine Verzögerung, um Timing-Angriffe zu erschweren
    usleep(rand(50000, 150000)); // 50-150ms zufällige Verzögerung
    
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