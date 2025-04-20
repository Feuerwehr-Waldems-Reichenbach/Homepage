<?php
// Include required files
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/Security.php';

// Set security headers
Security::setSecurityHeaders();

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        header('Location: ' . dirname($_SERVER['PHP_SELF'], 2) . '/index.php');
        exit;
    }
    
    // Sanitize input
    $email = Security::sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Bitte füllen Sie alle Felder aus.';
        header('Location: ' . dirname($_SERVER['PHP_SELF'], 2) . '/index.php');
        exit;
    }
    
    // Validate email format
    if (!Security::validateEmail($email)) {
        $_SESSION['error'] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
        header('Location: ' . dirname($_SERVER['PHP_SELF'], 2) . '/index.php');
        exit;
    }
    
    // Check if the user is locked out due to too many login attempts
    if (Security::isLockedOut($email)) {
        // Log the security event
        Security::logSecurityEvent($email, 'account_lockout', 'warning', 'Zu viele fehlgeschlagene Anmeldeversuche');
        
        $_SESSION['error'] = 'Zu viele fehlgeschlagene Anmeldeversuche. Bitte versuchen Sie es später erneut.';
        header('Location: ' . dirname($_SERVER['PHP_SELF'], 2) . '/index.php');
        exit;
    }
    
    try {
        // Get database connection
        $db = Database::getInstance()->getConnection();
        
        // Prepare SQL statement to get user by email
        $stmt = $db->prepare("SELECT id, email, password, first_name, last_name, is_admin, is_verified FROM fw_users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Record the login attempt
        Security::recordLoginAttempt($email);
        
        // Check if user exists and verify password
        if (!$user || !Security::verifyPassword($password, $user['password'])) {
            // Log the security event
            Security::logSecurityEvent($email, 'login', 'failure', 'Ungültige Anmeldedaten');
            
            $_SESSION['error'] = 'Ungültige E-Mail oder Passwort.';
            header('Location: ' . dirname($_SERVER['PHP_SELF'], 2) . '/index.php');
            exit;
        }
        
        // Check if user is verified
        if (!$user['is_verified']) {
            Security::logSecurityEvent($email, 'login', 'failure', 'Nicht verifizierter Account');
            
            $_SESSION['error'] = 'Ihr Konto wurde noch nicht verifiziert. Bitte überprüfen Sie Ihre E-Mails.';
            header('Location: ' . dirname($_SERVER['PHP_SELF'], 2) . '/index.php');
            exit;
        }
        
        // Check if user is an admin
        if (!$user['is_admin']) {
            Security::logSecurityEvent($email, 'login', 'failure', 'Kein Administratorzugriff');
            
            $_SESSION['error'] = 'Sie haben keinen Zugriff auf den Verwaltungsbereich.';
            header('Location: ' . dirname($_SERVER['PHP_SELF'], 2) . '/index.php');
            exit;
        }
        
        // Password is correct, check if it needs rehashing
        if (Security::passwordNeedsRehash($user['password'])) {
            // Rehash the password
            $newHash = Security::hashPassword($password);
            
            // Update the password in the database
            $updateStmt = $db->prepare("UPDATE fw_users SET password = ? WHERE id = ?");
            $updateStmt->execute([$newHash, $user['id']]);
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['last_activity'] = time();
        
        // Generate a new session ID to prevent session fixation
        session_regenerate_id(true);
        
        // Log the successful login
        Security::logSecurityEvent($email, 'login', 'success', 'Erfolgreiche Anmeldung');
        
        // Redirect to dashboard
        header('Location: ' . dirname($_SERVER['PHP_SELF'], 2) . '/dashboard.php');
        exit;
    } catch (PDOException $e) {
        // Log the error
        error_log('Login error: ' . $e->getMessage());
        
        $_SESSION['error'] = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
        header('Location: ' . dirname($_SERVER['PHP_SELF'], 2) . '/index.php');
        exit;
    }
} else {
    // If not a POST request, redirect to login page
    header('Location: ' . dirname($_SERVER['PHP_SELF'], 2) . '/index.php');
    exit;
} 