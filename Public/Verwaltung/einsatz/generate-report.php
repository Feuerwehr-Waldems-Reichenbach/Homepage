<?php
// Include required files
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/Security.php';
require_once dirname(__DIR__, 3) . '/Private/AI/generateEinsatzbericht.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set the response content type to JSON
header('Content-Type: application/json');

// Function to return error response
function returnError($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnError('Invalid request method');
}

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Check if data is valid
if (!$data) {
    returnError('Invalid data format');
}

// CSRF protection
if (!isset($data['csrf_token']) || !Security::validateCSRFToken($data['csrf_token'])) {
    returnError('Invalid CSRF token');
}

// Check required fields
$requiredFields = ['start', 'end', 'stichwort', 'kategorie', 'einsatzgruppe', 'sachverhalt', 'ort'];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        returnError("Field '$field' is required");
    }
}

try {
    // Generate the report
    $einsatz_id = $data['einsatz_id'] ?? 0; // Use 0 for new einsatz
    $start = $data['start'];
    $end = $data['end'];
    $stichwort = $data['stichwort'];
    $kategorie = $data['kategorie'];
    $einsatzgruppe = $data['einsatzgruppe'];
    $sachverhalt = $data['sachverhalt'];
    $ort = $data['ort'];

    // Call the AI function to generate the report
    $report = generateEinsatzbericht2(
        $einsatz_id,
        $start,
        $end,
        $stichwort,
        $kategorie,
        $einsatzgruppe,
        $sachverhalt,
        $ort
    );

    // Return success response with the generated text
    echo json_encode([
        'success' => true,
        'text' => $report
    ]);
} catch (Exception $e) {
    returnError('Error generating report: ' . $e->getMessage());
} 