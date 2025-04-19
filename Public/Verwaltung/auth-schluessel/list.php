<?php
// Include required files
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/models/AuthKey.php';

// Define title for the page
$pageTitle = "Authentifizierungsschlüssel";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Instantiate the model
$authKeyModel = new AuthKey();

// Check for actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // CSRF protection
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        header('Location: ' . BASE_URL . '/auth-schluessel/list.php');
        exit;
    }
    
    switch ($_GET['action']) {
        case 'activate':
            if ($authKeyModel->activateKey($id)) {
                $_SESSION['success'] = 'Schlüssel wurde aktiviert.';
            } else {
                $_SESSION['error'] = 'Fehler beim Aktivieren des Schlüssels.';
            }
            break;
            
        case 'deactivate':
            if ($authKeyModel->deactivateKey($id)) {
                $_SESSION['success'] = 'Schlüssel wurde deaktiviert.';
            } else {
                $_SESSION['error'] = 'Fehler beim Deaktivieren des Schlüssels.';
            }
            break;
            
        case 'delete':
            if ($authKeyModel->delete($id)) {
                $_SESSION['success'] = 'Schlüssel wurde gelöscht.';
            } else {
                $_SESSION['error'] = 'Fehler beim Löschen des Schlüssels.';
            }
            break;
    }
    
    header('Location: ' . BASE_URL . '/auth-schluessel/list.php');
    exit;
}

// Get all keys
$keys = $authKeyModel->getAll('id', 'DESC');

// Include header
include dirname(__DIR__) . '/templates/header.php';
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Alle Authentifizierungsschlüssel</h6>
        <a href="<?php echo BASE_URL; ?>/auth-schluessel/create.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Neuer Schlüssel
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered datatable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Bezeichnung</th>
                        <th>Schlüssel</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($keys as $key): ?>
                        <tr>
                            <td><?php echo $key['id']; ?></td>
                            <td><?php echo htmlspecialchars($key['Bezeichnung']); ?></td>
                            <td>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($key['auth_key']); ?>" readonly>
                                    <button class="btn btn-outline-secondary btn-sm copy-btn" type="button" data-clipboard-text="<?php echo htmlspecialchars($key['auth_key']); ?>">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <?php if ($key['active']): ?>
                                    <span class="badge bg-success">Aktiv</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inaktiv</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions-column">
                                <div class="btn-group" role="group">
                                    <a href="<?php echo $ADMIN_ROOT; ?>/auth-schluessel/edit.php?id=<?php echo $key['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> <span>Bearbeiten</span>
                                    </a>
                                    
                                    <?php if ($key['active']): ?>
                                        <a href="<?php echo $ADMIN_ROOT; ?>/auth-schluessel/list.php?action=deactivate&id=<?php echo $key['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-ban"></i> <span>Deaktivieren</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo $ADMIN_ROOT; ?>/auth-schluessel/list.php?action=activate&id=<?php echo $key['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> <span>Aktivieren</span>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <form method="get" action="<?php echo $ADMIN_ROOT; ?>/auth-schluessel/list.php" class="d-inline" onsubmit="return confirm('Sind Sie sicher, dass Sie diesen Schlüssel löschen möchten?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $key['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> <span>Löschen</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Clipboard.js for copy functionality -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
<script>
    // Initialize clipboard.js
    document.addEventListener('DOMContentLoaded', function() {
        var clipboard = new ClipboardJS('.copy-btn');
        
        clipboard.on('success', function(e) {
            // Show tooltip or feedback
            const btn = e.trigger;
            const icon = btn.querySelector('i');
            
            // Change icon temporarily
            icon.classList.remove('fa-copy');
            icon.classList.add('fa-check');
            
            setTimeout(function() {
                icon.classList.remove('fa-check');
                icon.classList.add('fa-copy');
            }, 1500);
            
            e.clearSelection();
        });
    });
</script>

<?php
// Include footer
include dirname(__DIR__) . '/templates/footer.php';
?> 