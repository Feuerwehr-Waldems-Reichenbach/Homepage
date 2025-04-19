<?php
// Include required files
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/models/User.php';

// Define title for the page
$pageTitle = "Benutzer";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Instantiate the model
$userModel = new User();

// Check for actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // CSRF protection
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        header('Location: ' . BASE_URL . '/users/list.php');
        exit;
    }
    
    // Prevent editing or deleting yourself
    if ($id == $_SESSION['user_id'] && ($_GET['action'] === 'toggleAdmin' || $_GET['action'] === 'delete')) {
        $_SESSION['error'] = 'Sie können Ihre eigenen Administratorrechte nicht ändern oder Ihr eigenes Konto löschen.';
        header('Location: ' . BASE_URL . '/users/list.php');
        exit;
    }
    
    switch ($_GET['action']) {
        case 'toggleAdmin':
            $user = $userModel->getById($id);
            
            if ($user) {
                // Toggle admin status
                $isAdmin = !$user['is_admin'];
                
                // Check if this is the last admin
                if (!$isAdmin) {
                    $admins = $userModel->getAdmins();
                    if (count($admins) <= 1) {
                        $_SESSION['error'] = 'Es muss mindestens ein Administrator vorhanden sein.';
                        header('Location: ' . BASE_URL . '/users/list.php');
                        exit;
                    }
                }
                
                if ($userModel->updateUser($id, ['is_admin' => $isAdmin])) {
                    $_SESSION['success'] = 'Administratorstatus wurde aktualisiert.';
                } else {
                    $_SESSION['error'] = 'Fehler beim Aktualisieren des Administratorstatus.';
                }
            } else {
                $_SESSION['error'] = 'Benutzer nicht gefunden.';
            }
            break;
            
        case 'toggleVerified':
            $user = $userModel->getById($id);
            
            if ($user) {
                // Toggle verified status
                $isVerified = !$user['is_verified'];
                
                if ($userModel->updateUser($id, ['is_verified' => $isVerified])) {
                    $_SESSION['success'] = 'Verifizierungsstatus wurde aktualisiert.';
                } else {
                    $_SESSION['error'] = 'Fehler beim Aktualisieren des Verifizierungsstatus.';
                }
            } else {
                $_SESSION['error'] = 'Benutzer nicht gefunden.';
            }
            break;
            
        case 'delete':
            $user = $userModel->getById($id);
            
            if ($user) {
                // Check if this is the last admin
                if ($user['is_admin']) {
                    $admins = $userModel->getAdmins();
                    if (count($admins) <= 1) {
                        $_SESSION['error'] = 'Der letzte Administrator kann nicht gelöscht werden.';
                        header('Location: ' . BASE_URL . '/users/list.php');
                        exit;
                    }
                }
                
                if ($userModel->delete($id)) {
                    $_SESSION['success'] = 'Benutzer wurde gelöscht.';
                } else {
                    $_SESSION['error'] = 'Fehler beim Löschen des Benutzers.';
                }
            } else {
                $_SESSION['error'] = 'Benutzer nicht gefunden.';
            }
            break;
    }
    
    header('Location: ' . BASE_URL . '/users/list.php');
    exit;
}

// Get all users
$users = $userModel->getAll('first_name', 'ASC');

// Include header
include dirname(__DIR__) . '/templates/header.php';
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Alle Benutzer</h6>
        <a href="<?php echo BASE_URL; ?>/users/create.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Neuer Benutzer
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered datatable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Telefon</th>
                        <th>Rolle</th>
                        <th>Status</th>
                        <th>Erstellt am</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                    <span class="badge bg-danger">Administrator</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Benutzer</span>
                                <?php endif; ?>
                                
                                <?php if ($user['is_AktivesMitglied']): ?>
                                    <span class="badge bg-primary">Aktives Mitglied</span>
                                <?php endif; ?>
                                
                                <?php if ($user['is_Feuerwehr']): ?>
                                    <span class="badge bg-warning">Feuerwehr</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['is_verified']): ?>
                                    <span class="badge bg-success">Verifiziert</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Nicht verifiziert</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                            <td class="actions-column">
                                <div class="btn-group" role="group">
                                    <a href="<?php echo $ADMIN_ROOT; ?>/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> <span>Bearbeiten</span>
                                    </a>
                                    
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <?php if ($user['is_admin']): ?>
                                            <a href="<?php echo $ADMIN_ROOT; ?>/users/list.php?action=toggleAdmin&id=<?php echo $user['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-warning btn-sm" title="Admin-Rechte entziehen">
                                                <i class="fas fa-user-shield"></i> <span>Admin entfernen</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo $ADMIN_ROOT; ?>/users/list.php?action=toggleAdmin&id=<?php echo $user['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-success btn-sm" title="Zum Admin machen">
                                                <i class="fas fa-user-shield"></i> <span>Zum Admin</span>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['is_verified']): ?>
                                            <a href="<?php echo $ADMIN_ROOT; ?>/users/list.php?action=toggleVerified&id=<?php echo $user['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-warning btn-sm" title="Verifizierung aufheben">
                                                <i class="fas fa-user-check"></i> <span>Verifizierung entfernen</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo $ADMIN_ROOT; ?>/users/list.php?action=toggleVerified&id=<?php echo $user['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-success btn-sm" title="Benutzer verifizieren">
                                                <i class="fas fa-user-check"></i> <span>Verifizieren</span>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <form method="get" action="<?php echo $ADMIN_ROOT; ?>/users/list.php" class="d-inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm delete-confirm">
                                                <i class="fas fa-trash"></i> <span>Löschen</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Include footer
include dirname(__DIR__) . '/templates/footer.php';
?> 