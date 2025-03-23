<?php
require_once 'includes/config.php';
require_once 'includes/User.php';
require_once 'includes/Reservation.php';

// Nur für angemeldete Administratoren zugänglich
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['flash_message'] = 'Sie haben keine Berechtigung, auf diese Seite zuzugreifen.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

// Objekte initialisieren
$user = new User();
$reservation = new Reservation();

// Status-Filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Reservierungen abrufen
if ($statusFilter === 'all') {
    $allReservations = $reservation->getAll();
} else {
    $allReservations = $reservation->getAllByStatus($statusFilter);
}

// Benutzer für Dropdown bei neuer Reservierung
$allUsers = $user->getAllUsers();

// Status aktualisieren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $reservationId = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
        $newStatus = isset($_POST['status']) ? $_POST['status'] : '';
        $adminMessage = isset($_POST['admin_message']) ? trim($_POST['admin_message']) : '';
        
        if (empty($newStatus) || !in_array($newStatus, ['pending', 'confirmed', 'canceled'])) {
            $_SESSION['flash_message'] = 'Ungültiger Status.';
            $_SESSION['flash_type'] = 'danger';
        } else {
            $result = $reservation->updateStatus($reservationId, $newStatus, $adminMessage);
            
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            
            // Bei Erfolg die Reservierungen neu laden
            if ($result['success']) {
                if ($statusFilter === 'all') {
                    $allReservations = $reservation->getAll();
                } else {
                    $allReservations = $reservation->getAllByStatus($statusFilter);
                }
            }
        }
    }
}

// Neue Reservierung erstellen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_reservation'])) {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $startDate = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
        $endDate = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
        $startTime = isset($_POST['start_time']) ? trim($_POST['start_time']) : '12:00';
        $endTime = isset($_POST['end_time']) ? trim($_POST['end_time']) : '12:00';
        $adminMessage = isset($_POST['admin_message']) ? trim($_POST['admin_message']) : '';
        
        // Validierung
        $errors = [];
        
        if (empty($userId)) {
            $errors[] = 'Bitte wählen Sie einen Benutzer aus.';
        }
        
        if (empty($startDate) || empty($endDate)) {
            $errors[] = 'Bitte wählen Sie ein Start- und Enddatum aus.';
        }
        
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $startTime) || 
            !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $endTime)) {
            $errors[] = 'Bitte geben Sie gültige Uhrzeiten ein (Format: HH:MM).';
        }
        
        if (empty($errors)) {
            // Start- und Enddatum mit Uhrzeit kombinieren
            $startDatetime = $startDate . ' ' . $startTime . ':00';
            $endDatetime = $endDate . ' ' . $endTime . ':00';
            
            // Überprüfen, ob das Enddatum nach dem Startdatum liegt
            if (strtotime($endDatetime) <= strtotime($startDatetime)) {
                $_SESSION['flash_message'] = 'Das Enddatum muss nach dem Startdatum liegen.';
                $_SESSION['flash_type'] = 'danger';
            } else {
                $result = $reservation->createByAdmin($userId, $startDatetime, $endDatetime, $adminMessage);
                
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
                
                // Bei Erfolg die Reservierungen neu laden
                if ($result['success']) {
                    if ($statusFilter === 'all') {
                        $allReservations = $reservation->getAll();
                    } else {
                        $allReservations = $reservation->getAllByStatus($statusFilter);
                    }
                }
            }
        } else {
            $_SESSION['flash_message'] = implode('<br>', $errors);
            $_SESSION['flash_type'] = 'danger';
        }
    }
}

// Nachricht hinzufügen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin_message'])) {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $reservationId = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
        $message = isset($_POST['admin_message']) ? trim($_POST['admin_message']) : '';
        
        if (empty($message)) {
            $_SESSION['flash_message'] = 'Bitte geben Sie eine Nachricht ein.';
            $_SESSION['flash_type'] = 'danger';
        } else {
            $result = $reservation->addAdminMessage($reservationId, $message);
            
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            
            // Bei Erfolg die Reservierungen neu laden
            if ($result['success']) {
                if ($statusFilter === 'all') {
                    $allReservations = $reservation->getAll();
                } else {
                    $allReservations = $reservation->getAllByStatus($statusFilter);
                }
            }
        }
    }
}

// Titel für die Seite
$pageTitle = 'Reservierungen verwalten';

// Header einbinden
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Reservierungen verwalten</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3>Neue Reservierung erstellen</h3>
            </div>
            <div class="card-body">
                <form method="post" action="admin_reservations.php<?php echo $statusFilter !== 'all' ? '?status=' . $statusFilter : ''; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="create_reservation" value="1">
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="user_id" class="form-label">Benutzer</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">-- Benutzer auswählen --</option>
                                <?php foreach ($allUsers as $singleUser): ?>
                                    <option value="<?php echo $singleUser['id']; ?>">
                                        <?php echo escape($singleUser['first_name'] . ' ' . $singleUser['last_name'] . ' (' . $singleUser['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="start_date" class="form-label">Startdatum</label>
                            <input type="text" class="form-control date-picker" id="start_date" name="start_date" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="end_date" class="form-label">Enddatum</label>
                            <input type="text" class="form-control date-picker" id="end_date" name="end_date" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="start_time" class="form-label">Startzeit</label>
                            <input type="text" class="form-control time-picker" id="start_time" name="start_time" value="12:00" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="end_time" class="form-label">Endzeit</label>
                            <input type="text" class="form-control time-picker" id="end_time" name="end_time" value="12:00" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="admin_message" class="form-label">Nachricht an den Benutzer (optional)</label>
                            <textarea class="form-control" id="admin_message" name="admin_message" rows="1"></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Reservierung erstellen</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Reservierungen</h3>
                <div class="btn-group">
                    <a href="admin_reservations.php" class="btn btn-outline-secondary <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">Alle</a>
                    <a href="admin_reservations.php?status=pending" class="btn btn-outline-warning <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Ausstehend</a>
                    <a href="admin_reservations.php?status=confirmed" class="btn btn-outline-success <?php echo $statusFilter === 'confirmed' ? 'active' : ''; ?>">Bestätigt</a>
                    <a href="admin_reservations.php?status=canceled" class="btn btn-outline-danger <?php echo $statusFilter === 'canceled' ? 'active' : ''; ?>">Storniert</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($allReservations)): ?>
                    <div class="alert alert-info">
                        Keine Reservierungen gefunden.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Benutzer</th>
                                    <th>Zeitraum</th>
                                    <th>Status</th>
                                    <th>Nachrichten</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allReservations as $res): ?>
                                    <tr>
                                        <td><?php echo $res['id']; ?></td>
                                        <td>
                                            <?php echo escape($res['first_name'] . ' ' . $res['last_name']); ?><br>
                                            <small><?php echo escape($res['email']); ?></small>
                                        </td>
                                        <td>
                                            Von: <?php echo date('d.m.Y H:i', strtotime($res['start_datetime'])); ?><br>
                                            Bis: <?php echo date('d.m.Y H:i', strtotime($res['end_datetime'])); ?>
                                        </td>
                                        <td>
                                            <span class="badge status-badge status-<?php echo $res['status']; ?>">
                                                <?php 
                                                switch ($res['status']) {
                                                    case 'pending':
                                                        echo 'Ausstehend';
                                                        break;
                                                    case 'confirmed':
                                                        echo 'Bestätigt';
                                                        break;
                                                    case 'canceled':
                                                        echo 'Storniert';
                                                        break;
                                                    default:
                                                        echo ucfirst($res['status']);
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($res['user_message'])): ?>
                                                <div class="message-box user-message mb-2 p-2">
                                                    <strong>Benutzernachricht:</strong><br>
                                                    <?php echo nl2br(escape($res['user_message'])); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($res['admin_message'])): ?>
                                                <div class="message-box admin-message mb-2 p-2">
                                                    <strong>Admin-Nachricht:</strong><br>
                                                    <?php echo nl2br(escape($res['admin_message'])); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <form method="post" action="admin_reservations.php<?php echo $statusFilter !== 'all' ? '?status=' . $statusFilter : ''; ?>" class="mt-2">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                                
                                                <div class="input-group">
                                                    <input type="text" class="form-control form-control-sm" name="admin_message" placeholder="Nachricht hinzufügen/bearbeiten" value="<?php echo escape($res['admin_message']); ?>">
                                                    <button type="submit" name="add_admin_message" class="btn btn-outline-secondary btn-sm">Speichern</button>
                                                </div>
                                            </form>
                                        </td>
                                        <td>
                                            <form method="post" action="admin_reservations.php<?php echo $statusFilter !== 'all' ? '?status=' . $statusFilter : ''; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                                <input type="hidden" name="admin_message" value="<?php echo escape($res['admin_message']); ?>">
                                                
                                                <div class="btn-group mb-2">
                                                    <?php if ($res['status'] !== 'confirmed'): ?>
                                                        <button type="submit" name="update_status" value="confirmed" class="btn btn-success btn-sm">Bestätigen</button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($res['status'] !== 'pending'): ?>
                                                        <button type="submit" name="update_status" value="pending" class="btn btn-warning btn-sm">Zurücksetzen</button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($res['status'] !== 'canceled'): ?>
                                                        <button type="submit" name="update_status" value="canceled" class="btn btn-danger btn-sm" onclick="return confirm('Sind Sie sicher, dass Sie diese Reservierung stornieren möchten?');">Stornieren</button>
                                                    <?php endif; ?>
                                                </div>
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