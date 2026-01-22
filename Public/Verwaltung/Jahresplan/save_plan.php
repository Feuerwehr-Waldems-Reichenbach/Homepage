<?php
// Public/Verwaltung/Jahresplan/save_plan.php
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/Security.php';

// Check Admin Session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get JSON Input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Target File Path (Public/Mitmachen/jahresplan.json)
$targetDir = dirname(__DIR__, 2) . '/Mitmachen';
$targetFile = $targetDir . '/jahresplan.json';

// Create Directory if not exists (should exist)
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

// Save File
if (file_put_contents($targetFile, json_encode($data, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true, 'message' => 'Plan erfolgreich veröffentlicht.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Speichern der Datei.']);
}
