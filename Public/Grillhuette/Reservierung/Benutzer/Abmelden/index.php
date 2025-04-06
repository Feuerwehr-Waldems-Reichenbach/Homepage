<?php
require_once '../../includes/config.php';

// Session-Daten löschen
$_SESSION = array();

// Löschen des Session-Cookies, wenn dieses gesetzt ist
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session-ID neu generieren und danach zerstören
session_regenerate_id(true);
session_destroy();

// Zurück zur Startseite
header('Location: ' . getRelativePath('home'));
exit;
?> 