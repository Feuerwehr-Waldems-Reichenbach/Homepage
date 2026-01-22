<?php
// Public/Verwaltung/Jahresplan/save_plan.php

// Immediate logging to PHP error log to verify script is being called
error_log("=== Jahresplan save_plan.php SCRIPT STARTED ===");
error_log("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'UNKNOWN'));
error_log("Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'UNKNOWN'));

require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/Security.php';

error_log("After requires - BASE_PATH: " . (defined('BASE_PATH') ? BASE_PATH : 'NOT DEFINED'));

// Logging function with multiple fallbacks
$logDir = dirname(__DIR__, 3) . '/Private/logs/';
$logFile = $logDir . 'jahresplan_upload.log';

// Ensure log directory exists
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

function logMessage($message, $logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    
    // Try to write to log file
    $written = @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // If file write fails, also log to PHP error log as fallback
    if ($written === false) {
        error_log("Jahresplan Upload: $message");
    }
}

// Log request start
logMessage("=== Save Plan Request Started ===", $logFile);
logMessage("Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'), $logFile);
logMessage("Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'UNKNOWN'), $logFile);
logMessage("User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET'), $logFile);
logMessage("Is Admin: " . (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] ? 'YES' : 'NO'), $logFile);
logMessage("Log File Path: $logFile", $logFile);
logMessage("Log Directory exists: " . (is_dir($logDir) ? 'YES' : 'NO'), $logFile);
logMessage("Log Directory writable: " . (is_writable($logDir) ? 'YES' : 'NO'), $logFile);

// Check Admin Session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    logMessage("ERROR: Unauthorized access attempt", $logFile);
    error_log("Jahresplan Upload ERROR: Unauthorized access attempt");
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized', 'debug' => [
        'user_id_set' => isset($_SESSION['user_id']),
        'is_admin_set' => isset($_SESSION['is_admin']),
        'is_admin_value' => $_SESSION['is_admin'] ?? null
    ]]);
    exit;
}

// Get JSON Input
$input = file_get_contents('php://input');
logMessage("Input length: " . strlen($input) . " bytes", $logFile);
$data = json_decode($input, true);

if (!$data) {
    $jsonError = json_last_error_msg();
    logMessage("ERROR: Invalid JSON - " . $jsonError, $logFile);
    logMessage("Input preview: " . substr($input, 0, 200), $logFile);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON: ' . $jsonError]);
    exit;
}

logMessage("JSON decoded successfully. Data keys: " . implode(', ', array_keys($data)), $logFile);

// Target File Path (Public/Mitmachen/jahresplan.json)
// BASE_PATH is defined in config.php and points to the project root
// Since the server's document root is Public/, we need to go: BASE_PATH/Public/Mitmachen/
$targetDir = BASE_PATH . DIRECTORY_SEPARATOR . 'Public' . DIRECTORY_SEPARATOR . 'Mitmachen';
$targetFile = $targetDir . DIRECTORY_SEPARATOR . 'jahresplan.json';

// Normalize path separators (important for cross-platform compatibility)
$targetDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $targetDir);
$targetFile = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $targetFile);

logMessage("BASE_PATH constant: " . BASE_PATH, $logFile);
logMessage("__DIR__ of this file: " . __DIR__, $logFile);
logMessage("dirname(__DIR__, 3): " . dirname(__DIR__, 3), $logFile);
logMessage("Target Directory: $targetDir", $logFile);
logMessage("Target File: $targetFile", $logFile);
logMessage("Directory exists check (before creation): " . (is_dir($targetDir) ? 'YES' : 'NO'), $logFile);

// Create Directory if not exists (should exist)
if (!is_dir($targetDir)) {
    logMessage("Directory does not exist, attempting to create: $targetDir", $logFile);
    if (!mkdir($targetDir, 0755, true)) {
        $error = error_get_last();
        logMessage("ERROR: Could not create directory. Error: " . ($error ? $error['message'] : 'Unknown'), $logFile);
        http_response_code(500);
        echo json_encode(['error' => 'Verzeichnis konnte nicht erstellt werden: ' . $targetDir]);
        exit;
    }
    logMessage("Directory created successfully", $logFile);
} else {
    logMessage("Directory exists", $logFile);
}

// Verify directory exists and is writable
if (!is_dir($targetDir)) {
    logMessage("ERROR: Directory does not exist after creation attempt", $logFile);
    http_response_code(500);
    echo json_encode(['error' => 'Verzeichnis existiert nicht: ' . $targetDir]);
    exit;
}

$isWritable = is_writable($targetDir);
logMessage("Directory is writable: " . ($isWritable ? 'YES' : 'NO'), $logFile);
if (!$isWritable) {
    $perms = substr(sprintf('%o', fileperms($targetDir)), -4);
    logMessage("ERROR: Directory permissions: $perms", $logFile);
    http_response_code(500);
    echo json_encode(['error' => 'Verzeichnis ist nicht beschreibbar: ' . $targetDir . ' (Permissions: ' . $perms . ')']);
    exit;
}

// Save File
logMessage("Encoding JSON data...", $logFile);
$jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if ($jsonData === false) {
    $jsonError = json_last_error_msg();
    logMessage("ERROR: JSON encoding failed - $jsonError", $logFile);
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Kodieren der JSON-Daten: ' . $jsonError]);
    exit;
}

logMessage("JSON encoded successfully. Size: " . strlen($jsonData) . " bytes", $logFile);

// Try to write the file
logMessage("Attempting to write file to: $targetFile", $logFile);
$result = @file_put_contents($targetFile, $jsonData, LOCK_EX);
if ($result === false) {
    $error = error_get_last();
    $errorMsg = 'Fehler beim Speichern der Datei.';
    if ($error) {
        $errorMsg .= ' Details: ' . $error['message'];
        logMessage("ERROR: File write failed - " . $error['message'], $logFile);
    } else {
        logMessage("ERROR: File write failed - Unknown error", $logFile);
    }
    $errorMsg .= ' Pfad: ' . $targetFile;
    logMessage("Full error: $errorMsg", $logFile);
    http_response_code(500);
    echo json_encode(['error' => $errorMsg]);
    exit;
}

logMessage("File written successfully. Bytes written: $result", $logFile);

// Verify file was written successfully
if (!file_exists($targetFile)) {
    logMessage("ERROR: File does not exist after write operation", $logFile);
    http_response_code(500);
    echo json_encode(['error' => 'Datei wurde nicht erstellt, obwohl kein Fehler gemeldet wurde. Pfad: ' . $targetFile]);
    exit;
}

$fileSize = filesize($targetFile);
logMessage("File exists and size is: $fileSize bytes", $logFile);
logMessage("=== Save Plan Request Completed Successfully ===", $logFile);

// Return success with debug info
$response = [
    'success' => true, 
    'message' => 'Plan erfolgreich veröffentlicht.', 
    'fileSize' => $fileSize,
    'filePath' => $targetFile,
    'debug' => [
        'basePath' => BASE_PATH,
        'targetDir' => $targetDir,
        'fileExists' => file_exists($targetFile),
        'fileReadable' => is_readable($targetFile)
    ]
];

logMessage("=== Save Plan Request Completed Successfully ===", $logFile);
error_log("Jahresplan Upload SUCCESS: File saved to $targetFile (Size: $fileSize bytes)");

echo json_encode($response);
