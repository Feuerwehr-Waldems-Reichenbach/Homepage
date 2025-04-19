<?php
session_start();
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/Security.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__, 3) . '/Private/Email/emailSender.php';

// Set security headers
Security::setSecurityHeaders();

$pageTitle = "Passwort zurücksetzen";
$validToken = false;
$success = false;
$error = '';
$token = '';

// Check if we have a token
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = Security::sanitizeInput($_GET['token']);
    
    // Check if the token is valid
    try {
        $userModel = new User();
        $tokenData = $userModel->checkPasswordResetToken($token);
        
        if ($tokenData) {
            $validToken = true;
        } else {
            $error = 'Der Link zum Zurücksetzen des Passworts ist ungültig oder abgelaufen.';
        }
    } catch (Exception $e) {
        error_log('Token check error: ' . $e->getMessage());
        $error = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
    }
} else {
    $error = 'Kein Token angegeben.';
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
    } else {
        // Validate passwords
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($password)) {
            $error = 'Bitte geben Sie ein Passwort ein.';
        } elseif (strlen($password) < 8) {
            $error = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Die Passwörter stimmen nicht überein.';
        } else {
            try {
                $userModel = new User();
                $result = $userModel->resetPassword($token, $password);
                
                if ($result) {
                    $success = true;
                    // Forward success message to login page
                    $_SESSION['success'] = 'Ihr Passwort wurde erfolgreich zurückgesetzt. Sie können sich jetzt anmelden.';
                } else {
                    $error = 'Das Passwort konnte nicht zurückgesetzt werden. Bitte versuchen Sie es später erneut.';
                }
            } catch (Exception $e) {
                error_log('Password reset error: ' . $e->getMessage());
                $error = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
            }
        }
    }
}

// Generate a new CSRF token
$csrfToken = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;
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
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>Passwort zurücksetzen</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                Ihr Passwort wurde erfolgreich zurückgesetzt.
                            </div>
                            <div class="d-grid gap-2 mt-3">
                                <a href="<?php echo dirname($_SERVER['PHP_SELF'], 2); ?>/index.php" class="btn btn-primary">Zum Login</a>
                            </div>
                        <?php elseif ($validToken): ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>" method="post" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Neues Passwort</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               minlength="8" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        Das Passwort muss mindestens 8 Zeichen lang sein.
                                    </div>
                                    <div class="invalid-feedback">
                                        Bitte geben Sie ein Passwort mit mindestens 8 Zeichen ein.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Passwort bestätigen</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                               minlength="8" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">
                                        Bitte bestätigen Sie Ihr Passwort.
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Passwort zurücksetzen</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                            <div class="d-grid gap-2 mt-3">
                                <a href="<?php echo dirname($_SERVER['PHP_SELF'], 2); ?>/index.php" class="btn btn-primary">Zurück zum Login</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap and other scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Toggle confirm password visibility
        document.getElementById('toggleConfirmPassword')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
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
                    
                    // Custom validation for password match
                    const password = document.getElementById('password');
                    const confirmPassword = document.getElementById('confirm_password');
                    
                    if (password && confirmPassword && password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Passwörter stimmen nicht überein');
                        event.preventDefault();
                        event.stopPropagation();
                    } else if (confirmPassword) {
                        confirmPassword.setCustomValidity('');
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html> 