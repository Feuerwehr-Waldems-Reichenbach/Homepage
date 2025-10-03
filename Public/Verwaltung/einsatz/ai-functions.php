<?php
// Include required files
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/Security.php';
require_once dirname(__DIR__, 3) . '/Private/AI/correctSpelling.php';
require_once dirname(__DIR__, 3) . '/Private/AI/changeStyle.php';
require_once dirname(__DIR__, 3) . '/Private/AI/customPrompt.php';
require_once dirname(__DIR__, 3) . '/Private/AI/generateHeadline.php';

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

// Function to return success response
function returnSuccess($data) {
    echo json_encode(['success' => true, 'data' => $data]);
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

// Check if function type is specified
if (!isset($data['function'])) {
    returnError('Function type not specified');
}

$function = $data['function'];

try {
    switch ($function) {
        case 'correct_spelling':
            // Schreibfehlerkorrektur
            if (empty($data['text'])) {
                returnError('Text is required');
            }
            
            $correctedText = correctSpelling($data['text']);
            returnSuccess(['text' => $correctedText]);
            break;
            
        case 'change_style':
            // Stil ändern
            if (empty($data['text'])) {
                returnError('Text is required');
            }
            if (empty($data['style'])) {
                returnError('Style is required');
            }
            
            $styledText = changeStyle($data['text'], $data['style']);
            returnSuccess(['text' => $styledText]);
            break;
            
        case 'custom_prompt':
            // Eigene Anweisung
            if (empty($data['instruction'])) {
                returnError('Instruction is required');
            }
            
            $text = $data['text'] ?? '';
            $result = processWithCustomPrompt($text, $data['instruction']);
            returnSuccess(['text' => $result]);
            break;
            
        case 'generate_headline':
            // Überschrift generieren
            if (empty($data['stichwort']) || empty($data['sachverhalt']) || empty($data['ort'])) {
                returnError('Stichwort, Sachverhalt and Ort are required');
            }
            
            $headline = generateHeadline(
                $data['stichwort'],
                $data['sachverhalt'],
                $data['ort'],
                $data['kategorie'] ?? '',
                $data['text'] ?? ''
            );
            returnSuccess(['headline' => $headline]);
            break;
            
        case 'get_styles':
            // Liste der verfügbaren Stile abrufen
            $styles = getAvailableStyles();
            returnSuccess(['styles' => $styles]);
            break;
            
        default:
            returnError('Unknown function: ' . $function);
    }
} catch (Exception $e) {
    returnError('Error: ' . $e->getMessage());
}

