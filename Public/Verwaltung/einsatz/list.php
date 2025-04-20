<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Include required files
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/models/Einsatz.php';
require_once dirname(__DIR__) . '/includes/Security.php';

// Define title for the page
$pageTitle = "Einsätze";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Instantiate the model
$einsatzModel = new Einsatz();

// Check for actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // CSRF protection
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        header('Location: ' . BASE_URL . '/einsatz/list.php');
        exit;
    }
    
    switch ($_GET['action']) {
        case 'toggle':
            // Get the current status
            $einsatz = $einsatzModel->getById($id);
            
            if ($einsatz) {
                // Toggle the Anzeigen flag
                $status = !$einsatz['Anzeigen'];
                
                if ($einsatzModel->update($id, ['Anzeigen' => $status])) {
                    $_SESSION['success'] = 'Status wurde aktualisiert.';
                } else {
                    $_SESSION['error'] = 'Fehler beim Aktualisieren des Status.';
                }
            } else {
                $_SESSION['error'] = 'Einsatz nicht gefunden.';
            }
            break;
            
        case 'delete':
            if ($einsatzModel->deleteWithDetails($id)) {
                $_SESSION['success'] = 'Einsatz wurde gelöscht.';
            } else {
                $_SESSION['error'] = 'Fehler beim Löschen des Einsatzes.';
            }
            break;
    }
    
    header('Location: ' . BASE_URL . '/einsatz/list.php');
    exit;
}

// Get filter parameters
$year = isset($_GET['year']) ? intval($_GET['year']) : null;
$showPublic = isset($_GET['public_only']) && $_GET['public_only'] === '1';
$kategorie = isset($_GET['kategorie']) ? Security::sanitizeInput($_GET['kategorie']) : null;

// Get available years and categories for filters
$availableYears = $einsatzModel->getAvailableYears();
$categories = []; // Will be populated from the results

// Get all operations or filtered ones
if ($year) {
    $einsaetze = $einsatzModel->getByYear($year);
} else {
    $einsaetze = $einsatzModel->getAllWithDetails('Datum', 'DESC');
}



// Apply public filter if needed
if ($showPublic) {
    $einsaetze = array_filter($einsaetze, function($einsatz) {
        return $einsatz['Anzeigen'] == 1;
    });
}

// Apply category filter and collect all categories
$uniqueCategories = [];
$filteredEinsaetze = [];

foreach ($einsaetze as $einsatz) {
    // Collect unique categories
    if (!empty($einsatz['Kategorie']) && !in_array($einsatz['Kategorie'], $uniqueCategories)) {
        $uniqueCategories[] = $einsatz['Kategorie'];
    }
    
    // Apply category filter
    if ($kategorie && $einsatz['Kategorie'] !== $kategorie) {
        continue;
    }
    
    $filteredEinsaetze[] = $einsatz;
}

$einsaetze = $filteredEinsaetze;
sort($uniqueCategories);

// Include header
include dirname(__DIR__) . '/templates/header.php';
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Alle Einsätze</h6>
        <a href="<?php echo BASE_URL; ?>/einsatz/create.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Neuer Einsatz
        </a>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <div class="mb-4">
            <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="year" class="form-label">Jahr</label>
                    <select class="form-select" id="year" name="year">
                        <option value="">Alle Jahre</option>
                        <?php foreach ($availableYears as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="kategorie" class="form-label">Kategorie</label>
                    <select class="form-select" id="kategorie" name="kategorie">
                        <option value="">Alle Kategorien</option>
                        <?php foreach ($uniqueCategories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $kategorie == $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="public_only" name="public_only" value="1" <?php echo $showPublic ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="public_only">
                            Nur öffentliche Einsätze
                        </label>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Filtern</button>
                    <a href="<?php echo BASE_URL; ?>/einsatz/list.php" class="btn btn-secondary">Zurücksetzen</a>
                </div>
            </form>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered datatable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Datum</th>
                        <th>Stichwort</th>
                        <th>Sachverhalt</th>
                        <th>Kategorie</th>
                        <th>Ort</th>
                        <th>Öffentlich</th>
                        <th>Details</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($einsaetze as $einsatz): ?>
                        <tr>
                            <td><?php echo $einsatz['ID']; ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($einsatz['Datum'])); ?></td>
                            <td><?php echo htmlspecialchars($einsatz['Stichwort']); ?></td>
                            <td><?php echo htmlspecialchars($einsatz['Sachverhalt']); ?></td>
                            <td><?php echo htmlspecialchars($einsatz['Kategorie'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($einsatz['Ort']); ?></td>
                            <td>
                                <?php if ($einsatz['Anzeigen']): ?>
                                    <span class="badge bg-success">Ja</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Nein</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($einsatz['einsatz_headline'])): ?>
                                    <span class="badge bg-info">Vorhanden</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Keine</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions-column">
                                <div class="btn-group" role="group">
                                    <a href="<?php echo $ADMIN_ROOT; ?>/einsatz/edit.php?id=<?php echo $einsatz['ID']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> <span>Bearbeiten</span>
                                    </a>
                                    
                                    <a href="<?php echo $ADMIN_ROOT; ?>/einsatz/list.php?action=toggle&id=<?php echo $einsatz['ID']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn <?php echo $einsatz['Anzeigen'] ? 'btn-warning' : 'btn-success'; ?> btn-sm">
                                        <i class="fas <?php echo $einsatz['Anzeigen'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i> 
                                        <span><?php echo $einsatz['Anzeigen'] ? 'Verstecken' : 'Anzeigen'; ?></span>
                                    </a>
                                    
                                    <form method="get" action="<?php echo $ADMIN_ROOT; ?>/einsatz/list.php" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $einsatz['ID']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm delete-confirm">
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

<?php
// Include footer
include dirname(__DIR__) . '/templates/footer.php';
?> 