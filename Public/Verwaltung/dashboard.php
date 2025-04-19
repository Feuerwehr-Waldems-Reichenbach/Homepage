<?php
// Include required files
require_once dirname(__DIR__, 2) . '/Private/Database/Database.php';
require_once __DIR__ . '/includes/config.php';

// Define title for the page
$pageTitle = "Dashboard";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get statistics from database
try {
    $db = Database::getInstance()->getConnection();
    
    // Get counts for each table
    $einsatzCount = $db->query("SELECT COUNT(*) as count FROM einsatz")->fetch(PDO::FETCH_ASSOC)['count'];
    $publicEinsatzCount = $db->query("SELECT COUNT(*) as count FROM einsatz WHERE Anzeigen = 1")->fetch(PDO::FETCH_ASSOC)['count'];
    
    $neuigkeitenCount = $db->query("SELECT COUNT(*) as count FROM neuigkeiten")->fetch(PDO::FETCH_ASSOC)['count'];
    $activeNeuigkeitenCount = $db->query("SELECT COUNT(*) as count FROM neuigkeiten WHERE aktiv = 1")->fetch(PDO::FETCH_ASSOC)['count'];
    
    $authSchluesselCount = $db->query("SELECT COUNT(*) as count FROM authentifizierungsschluessel")->fetch(PDO::FETCH_ASSOC)['count'];
    $activeAuthSchluesselCount = $db->query("SELECT COUNT(*) as count FROM authentifizierungsschluessel WHERE active = 1")->fetch(PDO::FETCH_ASSOC)['count'];
    
    $userCount = $db->query("SELECT COUNT(*) as count FROM fw_users")->fetch(PDO::FETCH_ASSOC)['count'];
    $adminCount = $db->query("SELECT COUNT(*) as count FROM fw_users WHERE is_admin = 1")->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get recent entries
    $stmt = $db->query("SELECT ID, Datum, Sachverhalt, Stichwort FROM einsatz ORDER BY Datum DESC LIMIT 5");
    $recentEinsaetze = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT ID, Ueberschrift, Datum FROM neuigkeiten ORDER BY Datum DESC LIMIT 5");
    $recentNeuigkeiten = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
} catch (PDOException $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    $_SESSION['error'] = 'Ein Fehler ist aufgetreten beim Laden des Dashboards.';
    
    // Initialize variables to prevent errors
    $einsatzCount = $publicEinsatzCount = $neuigkeitenCount = $activeNeuigkeitenCount = 0;
    $authSchluesselCount = $activeAuthSchluesselCount = $userCount = $adminCount = 0;
    $recentEinsaetze = $recentNeuigkeiten = [];
}

// Include header
include __DIR__ . '/templates/header.php';
?>

<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Einsätze</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $einsatzCount; ?></div>
                        <div class="small text-muted"><?php echo $publicEinsatzCount; ?> öffentlich</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-fire-extinguisher fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Neuigkeiten</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $neuigkeitenCount; ?></div>
                        <div class="small text-muted"><?php echo $activeNeuigkeitenCount; ?> aktiv</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-newspaper fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Auth-Schlüssel</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $authSchluesselCount; ?></div>
                        <div class="small text-muted"><?php echo $activeAuthSchluesselCount; ?> aktiv</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-key fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Benutzer</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $userCount; ?></div>
                        <div class="small text-muted"><?php echo $adminCount; ?> Administratoren</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Neueste Einsätze</h6>
                <a href="<?php echo $ADMIN_ROOT; ?>/einsatz/list.php" class="btn btn-sm btn-primary">Alle anzeigen</a>
            </div>
            <div class="card-body">
                <?php if (!empty($recentEinsaetze)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Datum</th>
                                    <th>Sachverhalt</th>
                                    <th>Stichwort</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentEinsaetze as $einsatz): ?>
                                    <tr>
                                        <td><?php echo $einsatz['ID']; ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($einsatz['Datum'])); ?></td>
                                        <td><?php echo $einsatz['Sachverhalt']; ?></td>
                                        <td><?php echo $einsatz['Stichwort']; ?></td>
                                        <td class="actions-column">
                                            <a href="<?php echo $ADMIN_ROOT; ?>/einsatz/edit.php?id=<?php echo $einsatz['ID']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i> <span>Bearbeiten</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">Keine Einsätze vorhanden.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Neueste Neuigkeiten</h6>
                <a href="<?php echo $ADMIN_ROOT; ?>/neuigkeiten/list.php" class="btn btn-sm btn-primary">Alle anzeigen</a>
            </div>
            <div class="card-body">
                <?php if (!empty($recentNeuigkeiten)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Datum</th>
                                    <th>Überschrift</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentNeuigkeiten as $neuigkeit): ?>
                                    <tr>
                                        <td><?php echo $neuigkeit['ID']; ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($neuigkeit['Datum'])); ?></td>
                                        <td><?php echo $neuigkeit['Ueberschrift']; ?></td>
                                        <td class="actions-column">
                                            <a href="<?php echo $ADMIN_ROOT; ?>/neuigkeiten/edit.php?id=<?php echo $neuigkeit['ID']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i> <span>Bearbeiten</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">Keine Neuigkeiten vorhanden.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/templates/footer.php';
?> 