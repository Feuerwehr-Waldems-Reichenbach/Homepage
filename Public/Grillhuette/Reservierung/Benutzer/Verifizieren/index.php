<?php
require_once '../../includes/config.php';
require_once '../../includes/User.php';

// Token aus der URL holen
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// Validierung
if (empty($token)) {
    $_SESSION['flash_message'] = 'Ungültiger Verifikationslink.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . getRelativePath('Benutzer/Anmelden'));
    exit;
}

// Verifikation durchführen
$user = new User();
$result = $user->verify($token);

$_SESSION['flash_message'] = $result['message'];
$_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';

// Weiterleitung zur Login-Seite
header('Location: ' . getRelativePath('Benutzer/Anmelden'));
exit;
?> 