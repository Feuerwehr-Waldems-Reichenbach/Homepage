<?php
// Include required files
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/includes/Security.php';

// Define title for the page
$pageTitle = "Mein Profil";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Make sure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$id = $_SESSION['user_id'];

// Instantiate the model
$userModel = new User();

// Get the user
$user = $userModel->getById($id);

if (!$user) {
    $_SESSION['error'] = 'Benutzer nicht gefunden.';
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        header('Location: ' . BASE_URL . '/users/profile.php');
        exit;
    }
    
    // Sanitize input
    $email = Security::sanitizeInput($_POST['email'] ?? '');
    $firstName = Security::sanitizeInput($_POST['first_name'] ?? '');
    $lastName = Security::sanitizeInput($_POST['last_name'] ?? '');
    $phone = Security::sanitizeInput($_POST['phone'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($firstName) || empty($lastName)) {
        $_SESSION['error'] = 'Bitte füllen Sie alle Pflichtfelder aus.';
        header('Location: ' . BASE_URL . '/users/profile.php');
        exit;
    }
    
    // Validate email format
    if (!Security::validateEmail($email)) {
        $_SESSION['error'] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
        header('Location: ' . BASE_URL . '/users/profile.php');
        exit;
    }
    
    // Check if email already exists (but not for this user)
    $existingUser = $userModel->getByEmail($email);
    if ($existingUser && $existingUser['id'] != $id) {
        $_SESSION['error'] = 'Diese E-Mail-Adresse ist bereits registriert.';
        header('Location: ' . BASE_URL . '/users/profile.php');
        exit;
    }
    
    // Prepare data
    $data = [
        'email' => $email,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'phone' => $phone
    ];
    
    // Handle password change if requested
    if (!empty($newPassword)) {
        // Validate current password
        if (empty($currentPassword)) {
            $_SESSION['error'] = 'Bitte geben Sie Ihr aktuelles Passwort ein.';
            header('Location: ' . BASE_URL . '/users/profile.php');
            exit;
        }
        
        // Verify current password
        if (!$userModel->verifyPassword($id, $currentPassword)) {
            $_SESSION['error'] = 'Das aktuelle Passwort ist nicht korrekt.';
            header('Location: ' . BASE_URL . '/users/profile.php');
            exit;
        }
        
        // Validate new password length
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            $_SESSION['error'] = 'Das neue Passwort muss mindestens ' . PASSWORD_MIN_LENGTH . ' Zeichen lang sein.';
            header('Location: ' . BASE_URL . '/users/profile.php');
            exit;
        }
        
        // Validate password confirmation
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = 'Die Passwörter stimmen nicht überein.';
            header('Location: ' . BASE_URL . '/users/profile.php');
            exit;
        }
        
        // Add new password to data
        $data['password'] = $newPassword;
    }
    
    // Update the user
    $result = $userModel->updateUser($id, $data);
    
    if ($result) {
        // Update session data
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        
        $_SESSION['success'] = 'Ihr Profil wurde erfolgreich aktualisiert.';
        header('Location: ' . BASE_URL . '/users/profile.php');
        exit;
    } else {
        $_SESSION['error'] = 'Fehler beim Aktualisieren des Profils.';
        header('Location: ' . BASE_URL . '/users/profile.php');
        exit;
    }
}

// Include header
include dirname(__DIR__) . '/templates/header.php';
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Mein Profil</h6>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">Vorname</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    <div class="invalid-feedback">
                        Bitte geben Sie einen Vornamen ein.
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Nachname</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    <div class="invalid-feedback">
                        Bitte geben Sie einen Nachnamen ein.
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">E-Mail</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    <div class="invalid-feedback">
                        Bitte geben Sie eine gültige E-Mail-Adresse ein.
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Telefon</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    <div class="form-text">Optional</div>
                </div>
            </div>
            
            <div class="mb-4">
                <h5 class="border-bottom pb-2">Passwort ändern</h5>
                <div class="form-text mb-3">Lassen Sie die Felder leer, wenn Sie Ihr Passwort nicht ändern möchten.</div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="current_password" class="form-label">Aktuelles Passwort</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                        <div class="invalid-feedback">
                            Bitte geben Sie Ihr aktuelles Passwort ein.
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="new_password" class="form-label">Neues Passwort</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                        <div class="invalid-feedback">
                            Bitte geben Sie ein Passwort mit mindestens <?php echo PASSWORD_MIN_LENGTH; ?> Zeichen ein.
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="confirm_password" class="form-label">Passwort bestätigen</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        <div class="invalid-feedback">
                            Bitte bestätigen Sie das Passwort.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <h5 class="border-bottom pb-2">Benutzerrollen</h5>
                <div class="form-text mb-3">Ihre aktuellen Rollen:</div>
                
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($user['is_admin']): ?>
                        <span class="badge bg-danger">Administrator</span>
                    <?php endif; ?>
                    
                    <?php if ($user['is_AktivesMitglied']): ?>
                        <span class="badge bg-primary">Aktives Mitglied</span>
                    <?php endif; ?>
                    
                    <?php if ($user['is_Feuerwehr']): ?>
                        <span class="badge bg-warning">Feuerwehr</span>
                    <?php endif; ?>
                    
                    <?php if (!$user['is_admin'] && !$user['is_AktivesMitglied'] && !$user['is_Feuerwehr']): ?>
                        <span class="badge bg-secondary">Standardbenutzer</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Client-side password validation
    document.addEventListener('DOMContentLoaded', function() {
        const currentPasswordInput = document.getElementById('current_password');
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        function validatePasswords() {
            // Check if we're changing password
            if (newPasswordInput.value || confirmPasswordInput.value) {
                // Current password is required
                if (!currentPasswordInput.value) {
                    currentPasswordInput.setCustomValidity('Bitte geben Sie Ihr aktuelles Passwort ein.');
                } else {
                    currentPasswordInput.setCustomValidity('');
                }
                
                // New passwords must match
                if (newPasswordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity('Die Passwörter stimmen nicht überein.');
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            } else {
                // Reset validations if we're not changing password
                currentPasswordInput.setCustomValidity('');
                newPasswordInput.setCustomValidity('');
                confirmPasswordInput.setCustomValidity('');
            }
        }
        
        currentPasswordInput.addEventListener('input', validatePasswords);
        newPasswordInput.addEventListener('input', validatePasswords);
        confirmPasswordInput.addEventListener('input', validatePasswords);
        
        // Form validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                validatePasswords();
                
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    });
</script>

<?php
// Include footer
include dirname(__DIR__) . '/templates/footer.php';
?> 