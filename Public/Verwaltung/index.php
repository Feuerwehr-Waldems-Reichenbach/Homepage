<?php
session_start();
// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id']) && $_SESSION['is_admin']) {
    header('Location: dashboard.php');
    exit;
}

// Generate a CSRF token and store it in the session
$csrfToken = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

// Define title for the page
$pageTitle = "Verwaltung - Login";
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                        <h4 class="mb-0 fw-bold">Verwaltungssystem</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php 
                                    echo $_SESSION['error']; 
                                    unset($_SESSION['error']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php 
                                    echo $_SESSION['success']; 
                                    unset($_SESSION['success']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form action="auth/login.php" method="post" class="needs-validation" novalidate>
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
                            
                            <div class="mb-4">
                                <label for="password" class="form-label fw-bold">Passwort</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required placeholder="Ihr Passwort">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Passwort anzeigen/verbergen">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Bitte geben Sie Ihr Passwort ein.
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mb-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Anmelden
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <a href="auth/forgot-password.php" class="text-decoration-none">
                                    <i class="fas fa-key me-1"></i>Passwort vergessen?
                                </a>
                            </div>
                        </form>
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
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
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
