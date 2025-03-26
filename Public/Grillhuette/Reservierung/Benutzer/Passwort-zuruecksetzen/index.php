<?php
require_once '../../includes/config.php';
require_once '../../includes/User.php';

// Wenn der Benutzer bereits angemeldet ist, weiterleiten
if (isset($_SESSION['user_id'])) {
    header('Location: ' . getRelativePath('home'));
    exit;
}

// Token aus der URL holen
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// Validierung
if (empty($token)) {
    $_SESSION['flash_message'] = 'Ungültiger Reset-Link.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . getRelativePath('Benutzer/Anmelden'));
    exit;
}

// POST-Anfrage verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['reset_error'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
    } else {
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
        
        // Validierung
        $errors = [];
        if (empty($password)) {
            $errors[] = 'Bitte geben Sie ein Passwort ein.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
        }
        
        if ($password !== $password_confirm) {
            $errors[] = 'Die Passwörter stimmen nicht überein.';
        }
        
        // Wenn keine Fehler, Passwort zurücksetzen
        if (empty($errors)) {
            $user = new User();
            $result = $user->resetPassword($token, $password);
            
            if ($result['success']) {
                $_SESSION['reset_success'] = true;
            } else {
                $_SESSION['reset_error'] = $result['message'];
            }
        } else {
            $_SESSION['reset_error'] = implode('<br>', $errors);
        }
    }
    
    // PRG-Muster: Nach POST-Anfrage zurück zur selben Seite weiterleiten mit dem Token
    header('Location: ' . getRelativePath('Benutzer/Passwort-zuruecksetzen') . '?token=' . urlencode($token));
    exit;
}

// Temporäre Daten aus der Session auslesen und entfernen
$error = '';
$success = false;

if (isset($_SESSION['reset_error'])) {
    $error = $_SESSION['reset_error'];
    unset($_SESSION['reset_error']);
}

if (isset($_SESSION['reset_success'])) {
    $success = true;
    unset($_SESSION['reset_success']);
}

// Titel für die Seite
$pageTitle = 'Passwort zurücksetzen';

// Header einbinden
require_once '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2>Neues Passwort festlegen</h2>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Ihr Passwort wurde erfolgreich zurückgesetzt.
                    </div>
                    <p class="text-center mt-3">
                        <a href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>" class="btn btn-primary">Zum Login</a>
                    </p>
                <?php else: ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Neues Passwort</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Mindestens 8 Zeichen.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Passwort bestätigen</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Passwort zurücksetzen</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?> 