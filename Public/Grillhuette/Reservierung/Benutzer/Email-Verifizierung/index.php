<?php
require_once '../../includes/config.php';
require_once '../../includes/User.php';

// Wenn der Benutzer bereits angemeldet ist, weiterleiten
if (isset($_SESSION['user_id'])) {
    header('Location: ' . getRelativePath('home'));
    exit;
}

// POST-Anfrage verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['verification_error'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
    } else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        
        // Validierung
        if (empty($email)) {
            $_SESSION['verification_error'] = 'Bitte geben Sie Ihre E-Mail-Adresse ein.';
            $_SESSION['verification_email'] = $email;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['verification_error'] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
            $_SESSION['verification_email'] = $email;
        } else {
            // Wenn keine Fehler, Bestätigungslink anfordern
            $user = new User();
            $result = $user->resendVerificationEmail($email);
            
            if ($result['success']) {
                $_SESSION['verification_success'] = true;
            } else {
                $_SESSION['verification_error'] = $result['message'];
                $_SESSION['verification_email'] = $email;
            }
        }
    }
    
    // PRG-Muster: Nach POST-Anfrage zurück zur selben Seite weiterleiten
    header('Location: ' . getRelativePath('Benutzer/Email-Verifizierung'));
    exit;
}

// Temporäre Daten aus der Session auslesen und entfernen
$error = '';
$email = '';
$success = false;

if (isset($_SESSION['verification_error'])) {
    $error = $_SESSION['verification_error'];
    unset($_SESSION['verification_error']);
}

if (isset($_SESSION['verification_email'])) {
    $email = $_SESSION['verification_email'];
    unset($_SESSION['verification_email']);
}

if (isset($_SESSION['verification_success'])) {
    $success = true;
    unset($_SESSION['verification_success']);
}

// Titel für die Seite
$pageTitle = 'Bestätigungslink anfordern';

// Header einbinden
require_once '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2>Bestätigungslink anfordern</h2>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Eine E-Mail mit einem neuen Bestätigungslink wurde an die angegebene Adresse gesendet.
                    </div>
                    <p class="text-center mt-3">
                        <a href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>" class="btn btn-primary">Zurück zum Login</a>
                    </p>
                <?php else: ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <p>Wenn Sie Ihren Bestätigungslink nicht erhalten haben oder dieser abgelaufen ist, können Sie hier einen neuen anfordern.</p>
                    
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">E-Mail-Adresse</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($email); ?>" required>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-primary">Bestätigungslink anfordern</button>
                            <a href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>">Zurück zum Login</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?> 