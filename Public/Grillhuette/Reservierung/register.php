<?php
require_once 'backend/includes/auth.php';
$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($name) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = 'Bitte füllen Sie alle Felder aus.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
    } elseif (strlen($password) < 8) {
        $error = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
    } elseif ($password !== $password_confirm) {
        $error = 'Die Passwörter stimmen nicht überein.';
    } else {
        $result = $auth->register($name, $email, $password);
        if ($result['success']) {
            $success = 'Registrierung erfolgreich! Sie können sich jetzt anmelden.';
        } else {
            $error = $result['message'];
        }
    }
}

$isLoggedIn = $auth->isLoggedIn();
$isAdmin = $auth->isAdmin();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrieren - Grillhütte Rechenbach</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="frontend/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h1 class="h3 mb-4 text-center">Registrieren</h1>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo htmlspecialchars($success); ?>
                                <br>
                                <a href="login.php" class="alert-link">Jetzt anmelden</a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="register.php" id="registerForm" novalidate>
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" 
                                           required>
                                    <div class="invalid-feedback">
                                        Bitte geben Sie Ihren Namen ein.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">E-Mail-Adresse</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" 
                                           required>
                                    <div class="invalid-feedback">
                                        Bitte geben Sie eine gültige E-Mail-Adresse ein.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Passwort</label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           required minlength="8">
                                    <div class="invalid-feedback">
                                        Das Passwort muss mindestens 8 Zeichen lang sein.
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="password_confirm" class="form-label">Passwort bestätigen</label>
                                    <input type="password" class="form-control" id="password_confirm" 
                                           name="password_confirm" required>
                                    <div class="invalid-feedback">
                                        Bitte bestätigen Sie Ihr Passwort.
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Registrieren</button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <div class="text-center mt-4">
                            <p class="mb-0">Bereits registriert? <a href="login.php" class="text-decoration-none">Jetzt anmelden</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Client-side form validation
        (function() {
            'use strict';
            const form = document.getElementById('registerForm');
            
            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    const password = document.getElementById('password');
                    const passwordConfirm = document.getElementById('password_confirm');
                    
                    if (password.value !== passwordConfirm.value) {
                        event.preventDefault();
                        passwordConfirm.setCustomValidity('Die Passwörter stimmen nicht überein.');
                    } else {
                        passwordConfirm.setCustomValidity('');
                    }
                    
                    form.classList.add('was-validated');
                });

                // Real-time password confirmation validation
                document.getElementById('password_confirm').addEventListener('input', function(e) {
                    if (this.value !== document.getElementById('password').value) {
                        this.setCustomValidity('Die Passwörter stimmen nicht überein.');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
        })();
    </script>
</body>
</html> 