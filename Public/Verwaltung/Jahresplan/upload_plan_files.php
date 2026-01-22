<?php
// Public/Verwaltung/Jahresplan/upload_plan_files.php
// Endpoint to receive and save PNG/PDF files for the Jahresplan

require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/Security.php';

// Set response header
header('Content-Type: application/json');

// Logging function
$logDir = dirname(__DIR__, 3) . '/Private/logs/';
$logFile = $logDir . 'jahresplan_files_upload.log';

// Ensure log directory exists
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

function logMessage($message, $logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    error_log("Jahresplan Files Upload: $message");
}

logMessage("=== Upload Plan Files Request Started ===", $logFile);

// Check Admin Session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    logMessage("ERROR: Unauthorized access attempt", $logFile);
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logMessage("ERROR: Invalid request method: " . $_SERVER['REQUEST_METHOD'], $logFile);
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Target directory (Public/Mitmachen/)
$targetDir = BASE_PATH . DIRECTORY_SEPARATOR . 'Public' . DIRECTORY_SEPARATOR . 'Mitmachen';
$targetDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $targetDir);

logMessage("Target Directory: $targetDir", $logFile);

// Ensure directory exists
if (!is_dir($targetDir)) {
    logMessage("Directory does not exist, attempting to create: $targetDir", $logFile);
    if (!mkdir($targetDir, 0755, true)) {
        $error = error_get_last();
        logMessage("ERROR: Could not create directory. Error: " . ($error ? $error['message'] : 'Unknown'), $logFile);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Verzeichnis konnte nicht erstellt werden: ' . $targetDir]);
        exit;
    }
}

// Check if directory is writable
if (!is_writable($targetDir)) {
    $perms = substr(sprintf('%o', fileperms($targetDir)), -4);
    logMessage("ERROR: Directory not writable. Permissions: $perms", $logFile);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Verzeichnis ist nicht beschreibbar: ' . $targetDir]);
    exit;
}

$results = [];
$errors = [];

// Process PNG file if provided
if (isset($_POST['png_data']) && !empty($_POST['png_data'])) {
    $pngData = $_POST['png_data'];
    
    // Remove data URL prefix if present (data:image/png;base64,...)
    if (strpos($pngData, 'data:image/png;base64,') === 0) {
        $pngData = substr($pngData, strlen('data:image/png;base64,'));
    }
    
    // Decode base64
    $pngBinary = base64_decode($pngData, true);
    if ($pngBinary === false) {
        $errors[] = 'PNG-Daten konnten nicht dekodiert werden.';
        logMessage("ERROR: PNG base64 decode failed", $logFile);
    } else {
        $pngFile = $targetDir . DIRECTORY_SEPARATOR . 'jahresplan.png';
        $written = @file_put_contents($pngFile, $pngBinary, LOCK_EX);
        
        if ($written === false) {
            $error = error_get_last();
            $errors[] = 'PNG-Datei konnte nicht gespeichert werden: ' . ($error ? $error['message'] : 'Unknown error');
            logMessage("ERROR: PNG file write failed - " . ($error ? $error['message'] : 'Unknown'), $logFile);
        } else {
            $results['png'] = [
                'success' => true,
                'file' => 'jahresplan.png',
                'size' => $written
            ];
            logMessage("PNG file saved successfully. Size: $written bytes", $logFile);
        }
    }
}

// Process PDF file if provided
if (isset($_POST['pdf_data']) && !empty($_POST['pdf_data'])) {
    $pdfData = $_POST['pdf_data'];
    logMessage("PDF data received. Length: " . strlen($pdfData) . " bytes. First 100 chars: " . substr($pdfData, 0, 100), $logFile);
    
    // Remove data URL prefix if present (data:application/pdf;base64,...)
    $originalLength = strlen($pdfData);
    if (strpos($pdfData, 'data:application/pdf;base64,') === 0) {
        $pdfData = substr($pdfData, strlen('data:application/pdf;base64,'));
        logMessage("Removed data:application/pdf;base64, prefix. New length: " . strlen($pdfData), $logFile);
    } elseif (strpos($pdfData, 'data:application/octet-stream;base64,') === 0) {
        $pdfData = substr($pdfData, strlen('data:application/octet-stream;base64,'));
        logMessage("Removed data:application/octet-stream;base64, prefix. New length: " . strlen($pdfData), $logFile);
    } else {
        logMessage("No data URI prefix found. Assuming raw base64.", $logFile);
    }
    
    // Decode base64
    $pdfBinary = base64_decode($pdfData, true);
    if ($pdfBinary === false) {
        $errors[] = 'PDF-Daten konnten nicht dekodiert werden.';
        logMessage("ERROR: PDF base64 decode failed. Input length: " . strlen($pdfData) . ". First 50 chars: " . substr($pdfData, 0, 50), $logFile);
    } else {
        logMessage("PDF decoded successfully. Binary length: " . strlen($pdfBinary) . " bytes. First 10 bytes: " . bin2hex(substr($pdfBinary, 0, 10)), $logFile);
        
        // Verify it's actually a PDF (starts with %PDF)
        if (substr($pdfBinary, 0, 4) !== '%PDF') {
            $errors[] = 'Ungültiges PDF-Format.';
            logMessage("ERROR: Invalid PDF format (does not start with %PDF). First 20 bytes: " . bin2hex(substr($pdfBinary, 0, 20)), $logFile);
        } else {
            $pdfFile = $targetDir . DIRECTORY_SEPARATOR . 'jahresplan.pdf';
            $written = @file_put_contents($pdfFile, $pdfBinary, LOCK_EX);
            
            if ($written === false) {
                $error = error_get_last();
                $errors[] = 'PDF-Datei konnte nicht gespeichert werden: ' . ($error ? $error['message'] : 'Unknown error');
                logMessage("ERROR: PDF file write failed - " . ($error ? $error['message'] : 'Unknown'), $logFile);
            } else {
                $results['pdf'] = [
                    'success' => true,
                    'file' => 'jahresplan.pdf',
                    'size' => $written
                ];
                logMessage("PDF file saved successfully. Size: $written bytes", $logFile);
            }
        }
    }
}

// Check if at least one file was processed
if (empty($results) && empty($errors)) {
    $errors[] = 'Keine Dateien zum Hochladen erhalten.';
    logMessage("ERROR: No files received", $logFile);
}

logMessage("=== Upload Plan Files Request Completed ===", $logFile);

// Return response
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'errors' => $errors,
        'results' => $results
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Dateien erfolgreich hochgeladen.',
        'results' => $results
    ]);
}