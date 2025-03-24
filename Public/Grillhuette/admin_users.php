<?php
require_once 'includes/config.php';
require_once 'includes/User.php';

// Nur für angemeldete Administratoren zugänglich
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['flash_message'] = 'Sie haben keine Berechtigung, auf diese Seite zuzugreifen.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

// User-Objekt initialisieren
$user = new User();

// Benutzer abrufen
$allUsers = $user->getAllUsers();

// Admin-Status ändern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_admin'])) {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        // Verhindern, dass ein Admin sich selbst die Rechte entzieht
        if ($userId === $_SESSION['user_id']) {
            $_SESSION['flash_message'] = 'Sie können Ihren eigenen Administrator-Status nicht ändern.';
            $_SESSION['flash_type'] = 'danger';
        } else {
            $result = $user->toggleAdmin($userId);
            
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            
            // Bei Erfolg die Benutzer neu laden
            if ($result['success']) {
                $allUsers = $user->getAllUsers();
            }
        }
    }
}

// Neuen Benutzer erstellen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
        $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
        
        // Validierung
        $errors = [];
        
        if (empty($email)) {
            $errors[] = 'Bitte geben Sie eine E-Mail-Adresse ein.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
        }
        
        if (empty($firstName)) {
            $errors[] = 'Bitte geben Sie einen Vornamen ein.';
        }
        
        if (empty($lastName)) {
            $errors[] = 'Bitte geben Sie einen Nachnamen ein.';
        }
        
        if (empty($password)) {
            $errors[] = 'Bitte geben Sie ein Passwort ein.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
        }
        
        if (empty($errors)) {
            $result = $user->createUserByAdmin($email, $password, $firstName, $lastName, $phone, $isAdmin);
            
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            
            // Bei Erfolg die Benutzer neu laden
            if ($result['success']) {
                $allUsers = $user->getAllUsers();
            }
        } else {
            $_SESSION['flash_message'] = implode('<br>', $errors);
            $_SESSION['flash_type'] = 'danger';
        }
    }
}

// Titel für die Seite
$pageTitle = 'Benutzer verwalten';

// Header einbinden
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Benutzer verwalten</h1>
        
        <!-- Button zum Öffnen des Modals -->
        <div class="mb-4">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newUserModal">
                <i class="bi bi-plus-circle"></i> Neuen Benutzer erstellen
            </button>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Benutzer</h3>
            </div>
            <div class="card-body">
                <?php if (empty($allUsers)): ?>
                    <div class="alert alert-info">
                        Keine Benutzer gefunden.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>E-Mail</th>
                                    <th>Telefon</th>
                                    <th>Status</th>
                                    <th>Registriert am</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allUsers as $userItem): ?>
                                    <tr>
                                        <td><?php echo $userItem['id']; ?></td>
                                        <td><?php echo escape($userItem['first_name'] . ' ' . $userItem['last_name']); ?></td>
                                        <td><?php echo escape($userItem['email']); ?></td>
                                        <td><?php echo escape($userItem['phone'] ?: '-'); ?></td>
                                        <td>
                                            <?php if ($userItem['is_verified']): ?>
                                                <span class="badge bg-success">Verifiziert</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Nicht verifiziert</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($userItem['is_admin']): ?>
                                                <span class="badge bg-primary">Administrator</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($userItem['created_at'])); ?></td>
                                        <td>
                                            <form method="post" action="admin_users.php">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $userItem['id']; ?>">
                                                
                                                <?php if ($userItem['id'] !== $_SESSION['user_id']): ?>
                                                    <?php if ($userItem['is_admin']): ?>
                                                        <button type="submit" name="toggle_admin" class="btn btn-warning btn-sm" onclick="return confirm('Sind Sie sicher, dass Sie die Administrator-Rechte entziehen möchten?');">
                                                            Admin-Rechte entziehen
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" name="toggle_admin" class="btn btn-primary btn-sm">
                                                            Zum Admin machen
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Ihr Konto</span>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<!-- Modal für neuen Benutzer -->
<div class="modal fade" id="newUserModal" tabindex="-1" aria-labelledby="newUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newUserModalLabel">Neuen Benutzer erstellen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="admin_users.php" id="createUserForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="create_user" value="1">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">Vorname</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Nachname</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">E-Mail-Adresse</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Telefonnummer (optional)</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Passwort</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Mindestens 8 Zeichen.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check mt-4">
                                <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin">
                                <label class="form-check-label" for="is_admin">Administrator-Berechtigungen</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('createUserForm').submit();">Benutzer erstellen</button>
            </div>
        </div>
    </div>
</div> 