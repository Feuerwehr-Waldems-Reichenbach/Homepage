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
$stepsBack = 3; // Anpassung an die tatsächliche Verzeichnisstruktur
$basePath = __DIR__;
for ($i = 0; $i < $stepsBack; $i++) {
    $basePath = dirname($basePath);
}
define('BASE_PATH', $basePath);

// Autoloader für Klassen
spl_autoload_register(function ($class) {
    $file = BASE_PATH . '/Private/Database/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Email-Sender einbinden
require_once BASE_PATH . '/Private/Email/emailSender.php';

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