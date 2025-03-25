<?php
require_once '../../includes/config.php';
require_once '../../includes/User.php';

// Wenn der Benutzer bereits angemeldet ist, weiterleiten
if (isset($_SESSION['user_id'])) {
    header('Location: ' . getRelativePath('home'));
    exit;
}

$errors = [];
$email = '';

// POST-Anfrage verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
    } else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        // Validierung
        if (empty($email)) {
            $errors[] = 'Bitte geben Sie Ihre E-Mail-Adresse ein.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
        }
        
        if (empty($password)) {
            $errors[] = 'Bitte geben Sie Ihr Passwort ein.';
        }
        
        // Wenn keine Fehler, Login versuchen
        if (empty($errors)) {
            $user = new User();
            $result = $user->login($email, $password);
            
            if ($result['success']) {
                // Weiterleitung zur Startseite oder vorherigen Seite, falls gesetzt
                $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : getRelativePath('home');
                unset($_SESSION['redirect_after_login']);
                
                header('Location: ' . $redirect);
                exit;
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

// Titel für die Seite
$pageTitle = 'Anmelden';

// Header einbinden
require_once '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2>Anmelden</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo escape($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">E-Mail-Adresse</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($email); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Passwort</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-primary">Anmelden</button>
                        <a href="<?php echo getRelativePath('Benutzer/Passwort-vergessen'); ?>">Passwort vergessen?</a>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                Noch kein Konto? <a href="<?php echo getRelativePath('Benutzer/Registrieren'); ?>">Jetzt registrieren</a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?> 