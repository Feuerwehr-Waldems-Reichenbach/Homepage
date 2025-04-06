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
        $_SESSION['reg_error'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
    } else {
        // Formularfelder validieren
        $formData = [
            'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
            'first_name' => isset($_POST['first_name']) ? trim($_POST['first_name']) : '',
            'last_name' => isset($_POST['last_name']) ? trim($_POST['last_name']) : '',
            'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : ''
        ];
        
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
        
        // Validierung
        $errors = [];
        if (empty($formData['email'])) {
            $errors[] = 'Bitte geben Sie Ihre E-Mail-Adresse ein.';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
        }
        
        if (empty($formData['first_name'])) {
            $errors[] = 'Bitte geben Sie Ihren Vornamen ein.';
        }
        
        if (empty($formData['last_name'])) {
            $errors[] = 'Bitte geben Sie Ihren Nachnamen ein.';
        }
        
        if (empty($password)) {
            $errors[] = 'Bitte geben Sie ein Passwort ein.';
        } else {
            // Erweiterte Passwortvalidierung
            $passwordErrors = validatePassword($password);
            if (!empty($passwordErrors)) {
                $errors = array_merge($errors, $passwordErrors);
            }
        }
        
        if ($password !== $password_confirm) {
            $errors[] = 'Die Passwörter stimmen nicht überein.';
        }
        
        // Wenn keine Fehler, Benutzer registrieren
        if (empty($errors)) {
            $user = new User();
            $result = $user->register(
                $formData['email'],
                $password,
                $formData['first_name'],
                $formData['last_name'],
                $formData['phone']
            );
            
            if ($result['success']) {
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = isset($result['warning']) ? 'warning' : 'success';
                header('Location: ' . getRelativePath('Benutzer/Anmelden'));
                exit;
            } else {
                $_SESSION['reg_error'] = $result['message'];
                $_SESSION['reg_form_data'] = $formData;
            }
        } else {
            $_SESSION['reg_error'] = implode('<br>', $errors);
            $_SESSION['reg_form_data'] = $formData;
        }
    }
    
    // PRG-Muster: Nach POST-Anfrage zurück zur Registrierungsseite weiterleiten
    header('Location: ' . getRelativePath('Benutzer/Registrieren'));
    exit;
}

// Temporäre Fehler und Formulardaten aus der Session auslesen und entfernen
$error = '';
$formData = [
    'email' => '',
    'first_name' => '',
    'last_name' => '',
    'phone' => ''
];

if (isset($_SESSION['reg_error'])) {
    $error = $_SESSION['reg_error'];
    unset($_SESSION['reg_error']);
}

if (isset($_SESSION['reg_form_data'])) {
    $formData = $_SESSION['reg_form_data'];
    unset($_SESSION['reg_form_data']);
}

// Titel für die Seite
$pageTitle = 'Registrieren';

// Header einbinden
require_once '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h2>Registrieren</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">Vorname</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo escape($formData['first_name']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Nachname</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo escape($formData['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">E-Mail-Adresse</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($formData['email']); ?>" required>
                        <div class="form-text">Ihre E-Mail-Adresse wird für die Kommunikation und Bestätigung von Reservierungen verwendet.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Telefonnummer</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo escape($formData['phone']); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Passwort</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Mindestens 8 Zeichen mit Groß- und Kleinbuchstaben, Zahlen und mindestens einem Sonderzeichen.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password_confirm" class="form-label">Passwort bestätigen</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">Ich akzeptiere die <a href="<?php echo getRelativePath('Nutzungsbedingungen'); ?>" target="_blank">Nutzungsbedingungen</a> und <a href="/Datenschutz" target="_blank">Datenschutzrichtlinien</a>.</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Registrieren</button>
                </form>
            </div>
            <div class="card-footer text-center">
                Bereits registriert? <a href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>">Hier anmelden</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Funktion für den Toggle-Button
    function setupPasswordToggle(buttonId, passwordId) {
        const toggleButton = document.querySelector('#' + buttonId);
        const passwordField = document.querySelector('#' + passwordId);
        
        toggleButton.addEventListener('click', function() {
            // Passworttyp umschalten
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Icon umschalten
            this.querySelector('i').classList.toggle('bi-eye');
            this.querySelector('i').classList.toggle('bi-eye-slash');
        });
    }
    
    // Beide Passwortfelder einrichten
    setupPasswordToggle('togglePassword', 'password');
    setupPasswordToggle('togglePasswordConfirm', 'password_confirm');
});
</script>

<?php require_once '../../includes/footer.php'; ?> 