<?php
require_once 'includes/config.php';
require_once 'includes/User.php';

// Wenn der Benutzer bereits angemeldet ist, weiterleiten
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = false;
$email = '';

// POST-Anfrage verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
    } else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        
        // Validierung
        if (empty($email)) {
            $errors[] = 'Bitte geben Sie Ihre E-Mail-Adresse ein.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
        }
        
        // Wenn keine Fehler, Passwort-Reset anfordern
        if (empty($errors)) {
            $user = new User();
            $result = $user->requestPasswordReset($email);
            
            if ($result['success']) {
                $success = true;
                $email = ''; // E-Mail-Adresse aus dem Formular löschen
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

// Titel für die Seite
$pageTitle = 'Passwort vergessen';

// Header einbinden
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2>Passwort zurücksetzen</h2>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Eine E-Mail mit Anweisungen zum Zurücksetzen Ihres Passworts wurde an die angegebene Adresse gesendet.
                    </div>
                    <p class="text-center mt-3">
                        <a href="login.php" class="btn btn-primary">Zurück zum Login</a>
                    </p>
                <?php else: ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo escape($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <p>Geben Sie Ihre E-Mail-Adresse ein, um einen Link zum Zurücksetzen Ihres Passworts zu erhalten.</p>
                    
                    <form method="post" action="forgot_password.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">E-Mail-Adresse</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($email); ?>" required>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-primary">Passwort zurücksetzen</button>
                            <a href="login.php">Zurück zum Login</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 