<?php
require_once '../../includes/config.php';
require_once '../../includes/User.php';

// Wenn der Benutzer bereits angemeldet ist, weiterleiten
if (isset($_SESSION['user_id'])) {
    header('Location: ' . getRelativePath('home'));
    exit;
}

$errors = [];
$formData = [
    'email' => '',
    'first_name' => '',
    'last_name' => '',
    'phone' => ''
];

// POST-Anfrage verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
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
        } elseif (strlen($password) < 8) {
            $errors[] = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
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
                $errors[] = $result['message'];
            }
        }
    }
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
                        <label for="phone" class="form-label">Telefonnummer (optional)</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo escape($formData['phone']); ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Passwort</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Mindestens 8 Zeichen.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password_confirm" class="form-label">Passwort bestätigen</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">Ich akzeptiere die Nutzungsbedingungen und Datenschutzrichtlinien.</label>
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

<?php require_once '../../includes/footer.php'; ?> 