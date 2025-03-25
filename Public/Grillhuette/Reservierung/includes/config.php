<?php
// Session starten
session_start();

// Fehlermeldungen anzeigen (für Entwicklung)
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