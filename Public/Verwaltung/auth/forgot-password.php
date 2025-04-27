<?php
session_start();
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/Security.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__, 3) . '/Private/Email/emailSender.php';

// Set security headers
Security::setSecurityHeaders();

$success = false;
$error = '';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
    } else {
        // Sanitize input
        $email = Security::sanitizeInput($_POST['email'] ?? '');
        
        // Validate input
        if (empty($email)) {
            $error = 'Bitte geben Sie Ihre E-Mail-Adresse ein.';
        } elseif (!Security::validateEmail($email)) {
            $error = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
        } else {
            try {
                // Create User model
                $userModel = new User();
                
                // Create password reset token
                $token = $userModel->createPasswordResetToken($email);
                
                if ($token) {
                    // Get user info
                    $user = $userModel->getByEmail($email);
                    
                    // Create reset URL
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                    $domain = $_SERVER['HTTP_HOST'];
                    $resetPath = dirname($_SERVER['PHP_SELF']) . '/reset-password.php?token=' . $token;
                    $resetUrl = $protocol . $domain . $resetPath;
                    
                    // Prepare email content
                    $subject = 'Passwort zurücksetzen - Feuerwehr Reichenbach Verwaltung';
                    $htmlMessage = '<p>Hallo ' . htmlspecialchars($user['first_name']) . ',</p>';
                    $htmlMessage .= '<p>Sie haben eine Anfrage zum Zurücksetzen Ihres Passworts für die Feuerwehr Reichenbach Verwaltung gestellt.</p>';
                    $htmlMessage .= '<p>Klicken Sie auf den folgenden Link, um Ihr Passwort zurückzusetzen:</p>';
                    $htmlMessage .= '<p><a href="' . $resetUrl . '">' . $resetUrl . '</a></p>';
                    $htmlMessage .= '<p>Dieser Link ist für 1 Stunde gültig.</p>';
                    $htmlMessage .= '<p>Falls Sie keine Anfrage zum Zurücksetzen Ihres Passworts gestellt haben, ignorieren Sie bitte diese E-Mail.</p>';
                    
                    // Send email using emailSender
                    $result = sendEmail($email, $subject, $htmlMessage);
                    
                    if ($result['success']) {
                        $success = true;
                    } else {
                        error_log('Failed to send password reset email: ' . $result['message']);
                        $error = 'Die E-Mail konnte nicht gesendet werden. Bitte versuchen Sie es später erneut.';
                    }
                } else {
                    // Don't reveal that the email doesn't exist for security reasons
                    $success = true;
                }
            } catch (Exception $e) {
                // Log the error
                error_log('Password reset error: ' . $e->getMessage());
                $error = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
            }
        }
    }
}

// Generate a new CSRF token
$csrfToken = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

// Define title for the page
$pageTitle = "Passwort vergessen";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
    </style>
</head>
<body class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card glass-card shadow">
                    <div class="card-header text-white text-center">
                        <h4 class="mb-0 fw-bold">Passwort zurücksetzen</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                Falls Ihre E-Mail-Adresse in unserer Datenbank existiert, haben wir Ihnen einen Link zum Zurücksetzen Ihres Passworts gesendet. Bitte überprüfen Sie auch Ihren Spam-Ordner.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <div class="d-grid gap-2 mt-3">
                                <a href="<?php echo dirname($_SERVER['PHP_SELF'], 2); ?>/index.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Zurück zum Login
                                </a>
                            </div>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo $error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            <p class="mb-3">Geben Sie Ihre E-Mail-Adresse ein, und wir senden Ihnen einen Link zum Zurücksetzen Ihres Passworts.</p>
                            <form action="forgot-password.php" method="post" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                
                                <div class="mb-4">
                                    <label for="email" class="form-label fw-bold">E-Mail</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" required placeholder="ihreemail@beispiel.de">
                                    </div>
                                    <div class="invalid-feedback">
                                        Bitte geben Sie eine gültige E-Mail-Adresse ein.
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 mb-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Passwort zurücksetzen
                                    </button>
                                </div>
                                
                                <div class="text-center">
                                    <a href="<?php echo dirname($_SERVER['PHP_SELF'], 2); ?>/index.php" class="text-decoration-none">
                                        <i class="fas fa-arrow-left me-1"></i>Zurück zum Login
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-center mt-3 text-white">
                    <small>&copy; <?php echo date('Y'); ?> Feuerwehr Verwaltungssystem</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap and other scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html> 