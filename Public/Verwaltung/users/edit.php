<?php
// Include required files
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/includes/Security.php';

// Define title for the page
$pageTitle = "Benutzer bearbeiten";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = Security::getCSRFToken();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'Keine Benutzer-ID angegeben.';
    header('Location: ' . BASE_URL . '/users/list.php');
    exit;
}

$id = intval($_GET['id']);

// Instantiate the model
$userModel = new User();

// Get the user
$user = $userModel->getById($id);

if (!$user) {
    $_SESSION['error'] = 'Benutzer nicht gefunden.';
    header('Location: ' . BASE_URL . '/users/list.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF protection
        if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
            $_SESSION['error'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
            header('Location: ' . BASE_URL . '/users/edit.php?id=' . $id);
            exit;
        }
        
        // Sanitize input
        $email = Security::sanitizeInput($_POST['email'] ?? '');
        $firstName = Security::sanitizeInput($_POST['first_name'] ?? '');
        $lastName = Security::sanitizeInput($_POST['last_name'] ?? '');
        $phone = Security::sanitizeInput($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
        $isVerified = isset($_POST['is_verified']) ? 1 : 0;
        
        // Validate input
        if (empty($email) || empty($firstName) || empty($lastName)) {
            $_SESSION['error'] = 'Bitte füllen Sie alle Pflichtfelder aus.';
            header('Location: ' . BASE_URL . '/users/edit.php?id=' . $id);
            exit;
        }
        
        // Validate email format
        if (!Security::validateEmail($email)) {
            $_SESSION['error'] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
            header('Location: ' . BASE_URL . '/users/edit.php?id=' . $id);
            exit;
        }
        
        // Check if email already exists (but not for this user)
        $existingUser = $userModel->getByEmail($email);
        if ($existingUser && $existingUser['id'] != $id) {
            $_SESSION['error'] = 'Diese E-Mail-Adresse ist bereits registriert.';
            header('Location: ' . BASE_URL . '/users/edit.php?id=' . $id);
            exit;
        }
        
        // Validate password if provided
        if (!empty($password)) {
            // Validate password length
            if (strlen($password) < PASSWORD_MIN_LENGTH) {
                $_SESSION['error'] = 'Das Passwort muss mindestens ' . PASSWORD_MIN_LENGTH . ' Zeichen lang sein.';
                header('Location: ' . BASE_URL . '/users/edit.php?id=' . $id);
                exit;
            }
            
            // Validate password confirmation
            if ($password !== $confirmPassword) {
                $_SESSION['error'] = 'Die Passwörter stimmen nicht überein.';
                header('Location: ' . BASE_URL . '/users/edit.php?id=' . $id);
                exit;
            }
        }
        
        // Prepare data
        $data = [
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'is_verified' => $isVerified
        ];
        
        // Special case for admin status when editing yourself
        if ($id != $_SESSION['user_id']) {
            $data['is_admin'] = $isAdmin;
        } elseif (!$isAdmin && $user['is_admin']) {
            // Don't allow admins to remove their own admin status
            $_SESSION['error'] = 'Sie können nicht Ihre eigenen Administratorrechte entfernen.';
            header('Location: ' . BASE_URL . '/users/edit.php?id=' . $id);
            exit;
        }
        
        // Add password if provided
        if (!empty($password)) {
            $data['password'] = $password;
        }
        
        // Update the user
        $result = $userModel->updateUser($id, $data);
        
        if ($result) {
            // Update session data if editing own profile
            if ($id == $_SESSION['user_id']) {
                $_SESSION['email'] = $email;
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
            }
            
            $_SESSION['success'] = 'Benutzer wurde erfolgreich aktualisiert.';
            header('Location: ' . BASE_URL . '/users/list.php');
            exit;
        } else {
            $_SESSION['error'] = 'Fehler beim Aktualisieren des Benutzers.';
            header('Location: ' . BASE_URL . '/users/edit.php?id=' . $id);
            exit;
        }
    } catch (Exception $e) {
        // Log the error
        error_log('Error updating user: ' . $e->getMessage());
        $_SESSION['error'] = 'Ein Fehler ist aufgetreten: ' . $e->getMessage();
        header('Location: ' . BASE_URL . '/users/edit.php?id=' . $id);
        exit;
    }
}

// Include header
include dirname(__DIR__) . '/templates/header.php';
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Benutzer bearbeiten</h6>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $id); ?>" class="needs-validation" novalidate>
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
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Neues Passwort</label>
                    <input type="password" class="form-control" id="password" name="password" minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                    <div class="form-text">Lassen Sie das Feld leer, um das Passwort nicht zu ändern.</div>
                    <div class="invalid-feedback">
                        Bitte geben Sie ein Passwort mit mindestens <?php echo PASSWORD_MIN_LENGTH; ?> Zeichen ein.
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="confirm_password" class="form-label">Passwort bestätigen</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    <div class="invalid-feedback">
                        Bitte bestätigen Sie das Passwort.
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin" <?php echo $user['is_admin'] ? 'checked' : ''; ?> <?php echo ($id == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                    <label class="form-check-label" for="is_admin">Administrator</label>
                    <?php if ($id == $_SESSION['user_id']): ?>
                        <input type="hidden" name="is_admin" value="1">
                    <?php endif; ?>
                </div>
                
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="is_verified" name="is_verified" <?php echo $user['is_verified'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_verified">Verifiziert</label>
                </div>
            </div>
            
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="<?php echo BASE_URL; ?>/users/list.php" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Client-side password validation
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        function validatePasswords() {
            if (passwordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.setCustomValidity('Die Passwörter stimmen nicht überein.');
            } else {
                confirmPasswordInput.setCustomValidity('');
            }
        }
        
        passwordInput.addEventListener('change', validatePasswords);
        confirmPasswordInput.addEventListener('keyup', validatePasswords);
        
        // Form validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                // Special check for password fields
                if (passwordInput.value || confirmPasswordInput.value) {
                    if (passwordInput.value !== confirmPasswordInput.value) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
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