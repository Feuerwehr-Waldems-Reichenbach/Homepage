<?php
require_once '../includes/config.php';

// Set the proper content type for form
header('Content-Type: text/html; charset=UTF-8');

// Check if the user is an admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode([
        'success' => false,
        'message' => 'Nur Administratoren können diese Funktion nutzen.'
    ]);
    exit;
}

$message = '';
$messageType = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Ungültige Anfrage. CSRF-Token fehlt oder ist ungültig.';
        $messageType = 'danger';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            if ($_POST['action'] === 'create_tables') {
                // Create the reservations table if it doesn't exist
                $db->exec("
                    CREATE TABLE IF NOT EXISTS `gh_reservations` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `user_id` int(11) NOT NULL,
                      `start_datetime` datetime NOT NULL,
                      `end_datetime` datetime NOT NULL,
                      `days_count` int(11) DEFAULT '0',
                      `status` enum('pending','confirmed','canceled','completed') NOT NULL DEFAULT 'pending',
                      `base_price` decimal(10,2) DEFAULT '0.00',
                      `total_price` decimal(10,2) DEFAULT '0.00',
                      `deposit_amount` decimal(10,2) DEFAULT '0.00',
                      `deposit_paid` tinyint(1) DEFAULT '0',
                      `full_amount_paid` tinyint(1) DEFAULT '0',
                      `receipt_requested` tinyint(1) DEFAULT '0',
                      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      `is_public` tinyint(1) DEFAULT '0',
                      `event_name` varchar(255) DEFAULT NULL,
                      `display_event_name_on_calendar_start_date` date DEFAULT NULL,
                      `display_event_name_on_calendar_end_date` date DEFAULT NULL,
                      `key_handover_datetime` datetime DEFAULT NULL,
                      `key_return_datetime` datetime DEFAULT NULL,
                      PRIMARY KEY (`id`),
                      KEY `user_id` (`user_id`),
                      KEY `start_datetime` (`start_datetime`),
                      KEY `end_datetime` (`end_datetime`),
                      KEY `status` (`status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                
                $message = 'Die Datenbanktabellen wurden erfolgreich erstellt.';
                $messageType = 'success';
            } 
            else if ($_POST['action'] === 'create_test_reservation') {
                // Add a test reservation
                $today = date('Y-m-d');
                $tomorrow = date('Y-m-d', strtotime('+1 day'));
                
                $stmt = $db->prepare("
                    INSERT INTO gh_reservations 
                    (user_id, start_datetime, end_datetime, status, days_count, base_price, total_price, deposit_amount, is_public, event_name) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $_SESSION['user_id'],
                    $today . ' 00:00:00',
                    $tomorrow . ' 23:59:59',
                    'confirmed',
                    2,
                    100.00,
                    200.00,
                    50.00,
                    1,
                    'Test Veranstaltung'
                ]);
                
                $message = 'Eine Test-Reservierung wurde erfolgreich erstellt.';
                $messageType = 'success';
            }
        } catch (Exception $e) {
            $message = 'Fehler: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Check current database status
try {
    $db = Database::getInstance()->getConnection();
    
    // Check if the reservations table exists
    $stmt = $db->query("SHOW TABLES LIKE 'gh_reservations'");
    $reservationsTableExists = $stmt->rowCount() > 0;
    
    // Count reservations if table exists
    $reservationsCount = 0;
    if ($reservationsTableExists) {
        $stmt = $db->query("SELECT COUNT(*) FROM gh_reservations");
        $reservationsCount = $stmt->fetchColumn();
    }
} catch (Exception $e) {
    $message = 'Fehler bei der Datenbankprüfung: ' . $e->getMessage();
    $messageType = 'danger';
    $reservationsTableExists = false;
    $reservationsCount = 0;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datenbank-Reparatur</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h2 class="card-title mb-0">Datenbank-Reparatur</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $messageType; ?> mb-4">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <h4>Datenbank-Status:</h4>
                            <ul class="list-group mb-3">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Reservierungstabelle existiert
                                    <?php if ($reservationsTableExists): ?>
                                        <span class="badge bg-success rounded-pill">Ja</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-pill">Nein</span>
                                    <?php endif; ?>
                                </li>
                                <?php if ($reservationsTableExists): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Anzahl der Reservierungen
                                        <span class="badge bg-primary rounded-pill"><?php echo $reservationsCount; ?></span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <form method="post" class="mb-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="action" value="create_tables">
                                    <button type="submit" class="btn btn-danger" <?php echo $reservationsTableExists ? 'disabled' : ''; ?>>
                                        Datenbanktabellen erstellen
                                    </button>
                                </form>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="action" value="create_test_reservation">
                                    <button type="submit" class="btn btn-warning" <?php echo !$reservationsTableExists ? 'disabled' : ''; ?>>
                                        Test-Reservierung erstellen
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="check_reservations.php" class="btn btn-primary me-2" target="_blank">Reservierungen prüfen</a>
                            <a href="../" class="btn btn-secondary">Zurück zur Hauptseite</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 