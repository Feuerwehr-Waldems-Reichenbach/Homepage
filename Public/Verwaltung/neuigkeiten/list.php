<?php
// Include required files
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/models/News.php';

// Define title for the page
$pageTitle = "Neuigkeiten";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Instantiate the model
$newsModel = new News();

// Check for actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // CSRF protection
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        header('Location: ' . BASE_URL . '/neuigkeiten/list.php');
        exit;
    }
    
    switch ($_GET['action']) {
        case 'activate':
            if ($newsModel->activateNews($id)) {
                $_SESSION['success'] = 'Neuigkeit wurde aktiviert.';
            } else {
                $_SESSION['error'] = 'Fehler beim Aktivieren der Neuigkeit.';
            }
            break;
            
        case 'deactivate':
            if ($newsModel->deactivateNews($id)) {
                $_SESSION['success'] = 'Neuigkeit wurde deaktiviert.';
            } else {
                $_SESSION['error'] = 'Fehler beim Deaktivieren der Neuigkeit.';
            }
            break;
            
        case 'delete':
            // Get the news item to check for image
            $news = $newsModel->getById($id);
            
            if ($news) {
                // Delete the image if it exists
                if (!empty($news['path_to_image']) && file_exists(ADMIN_PATH . $news['path_to_image'])) {
                    @unlink(ADMIN_PATH . $news['path_to_image']);
                }
                
                // Delete the news item
                if ($newsModel->delete($id)) {
                    $_SESSION['success'] = 'Neuigkeit wurde gelöscht.';
                } else {
                    $_SESSION['error'] = 'Fehler beim Löschen der Neuigkeit.';
                }
            } else {
                $_SESSION['error'] = 'Neuigkeit nicht gefunden.';
            }
            break;
    }
    
    header('Location: ' . BASE_URL . '/neuigkeiten/list.php');
    exit;
}

// Get all news items
$news = $newsModel->getAll('Datum', 'DESC');

// Include header
include dirname(__DIR__) . '/templates/header.php';
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Alle Neuigkeiten</h6>
        <a href="<?php echo BASE_URL; ?>/neuigkeiten/create.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Neue Neuigkeit
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered datatable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Datum</th>
                        <th>Überschrift</th>
                        <th>Ort</th>
                        <th>Bild</th>
                        <th>Status</th>
                        <th>Popup</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($news as $item): ?>
                        <tr>
                            <td><?php echo $item['ID']; ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($item['Datum'])); ?></td>
                            <td><?php echo htmlspecialchars($item['Ueberschrift']); ?></td>
                            <td><?php echo htmlspecialchars($item['Ort']); ?></td>
                            <td>
                                <?php if (!empty($item['path_to_image'])): ?>
                                    <a href="<?php echo htmlspecialchars($item['path_to_image']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-image"></i> Anzeigen
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Kein Bild</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['aktiv']): ?>
                                    <span class="badge bg-success">Aktiv</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inaktiv</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['is_popup']): ?>
                                    <span class="badge bg-info">Ja</span>
                                    <div class="small text-muted">
                                        <?php echo date('d.m.Y', strtotime($item['popup_start'])); ?> bis 
                                        <?php echo date('d.m.Y', strtotime($item['popup_end'])); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nein</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions-column">
                                <div class="btn-group" role="group">
                                    <a href="<?php echo BASE_URL; ?>/neuigkeiten/edit.php?id=<?php echo $item['ID']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if ($item['aktiv']): ?>
                                        <a href="<?php echo BASE_URL; ?>/neuigkeiten/list.php?action=deactivate&id=<?php echo $item['ID']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo BASE_URL; ?>/neuigkeiten/list.php?action=activate&id=<?php echo $item['ID']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <form method="get" action="<?php echo BASE_URL; ?>/neuigkeiten/list.php" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $item['ID']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm delete-confirm">
                                            <i class="fas fa-trash"></i>
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

<?php
// Include footer
include dirname(__DIR__) . '/templates/footer.php';
?> 