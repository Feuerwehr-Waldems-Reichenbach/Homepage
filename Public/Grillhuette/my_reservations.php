<?php
require_once 'includes/config.php';
require_once 'includes/User.php';
require_once 'includes/Reservation.php';

// Nur für angemeldete Benutzer zugänglich
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bitte melden Sie sich an, um Ihre Reservierungen zu sehen.';
    $_SESSION['flash_type'] = 'warning';
    $_SESSION['redirect_after_login'] = 'my_reservations.php';
    header('Location: login.php');
    exit;
}

// Reservierungen des Benutzers abrufen
$reservation = new Reservation();
$userReservations = $reservation->getByUserId($_SESSION['user_id']);

// Nachricht hinzufügen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_message'])) {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $reservationId = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        
        if (empty($message)) {
            $_SESSION['flash_message'] = 'Bitte geben Sie eine Nachricht ein.';
            $_SESSION['flash_type'] = 'danger';
        } else {
            $result = $reservation->addUserMessage($reservationId, $message);
            
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            
            // Bei Erfolg die Reservierungen neu laden
            if ($result['success']) {
                $userReservations = $reservation->getByUserId($_SESSION['user_id']);
            }
        }
    }
}

// Reservierung stornieren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'])) {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $reservationId = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
        
        $result = $reservation->cancel($reservationId);
        
        $_SESSION['flash_message'] = $result['message'];
        $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
        
        // Bei Erfolg die Reservierungen neu laden
        if ($result['success']) {
            $userReservations = $reservation->getByUserId($_SESSION['user_id']);
        }
    }
}

// Titel für die Seite
$pageTitle = 'Meine Reservierungen';

// Header einbinden
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Meine Reservierungen</h1>
        
        <?php if (empty($userReservations)): ?>
            <div class="alert alert-info">
                Sie haben noch keine Reservierungen. <a href="index.php">Jetzt reservieren</a>
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Meine aktuellen und zukünftigen Reservierungen</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php 
                        $hasActiveReservations = false;
                        foreach ($userReservations as $res): 
                            // Nur aktuelle (nicht stornierte) und zukünftige Reservierungen anzeigen
                            if ($res['status'] !== 'canceled' && strtotime($res['end_datetime']) >= time()):
                                $hasActiveReservations = true;
                        ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <span>
                                            <?php echo date('d.m.Y H:i', strtotime($res['start_datetime'])); ?> - 
                                            <?php echo date('d.m.Y H:i', strtotime($res['end_datetime'])); ?>
                                        </span>
                                        <span class="badge status-badge status-<?php echo $res['status']; ?>">
                                            <?php 
                                            switch ($res['status']) {
                                                case 'pending':
                                                    echo 'Ausstehend';
                                                    break;
                                                case 'confirmed':
                                                    echo 'Bestätigt';
                                                    break;
                                                default:
                                                    echo ucfirst($res['status']);
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($res['user_message'])): ?>
                                            <div class="message-box user-message mb-3">
                                                <h5>Ihre Nachricht:</h5>
                                                <p><?php echo nl2br(escape($res['user_message'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($res['admin_message'])): ?>
                                            <div class="message-box admin-message mb-3">
                                                <h5>Nachricht vom Administrator:</h5>
                                                <p><?php echo nl2br(escape($res['admin_message'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <form method="post" action="my_reservations.php" class="mb-3">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label for="message_<?php echo $res['id']; ?>" class="form-label">Nachricht hinzufügen/bearbeiten:</label>
                                                <textarea class="form-control" id="message_<?php echo $res['id']; ?>" name="message" rows="3"><?php echo escape($res['user_message']); ?></textarea>
                                            </div>
                                            
                                            <button type="submit" name="add_message" class="btn btn-primary">Nachricht speichern</button>
                                            
                                            <?php if ($res['status'] !== 'canceled'): ?>
                                                <button type="submit" name="cancel_reservation" class="btn btn-danger ms-2" onclick="return confirm('Sind Sie sicher, dass Sie diese Reservierung stornieren möchten?');">Reservierung stornieren</button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        
                        if (!$hasActiveReservations):
                        ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    Sie haben keine aktiven oder zukünftigen Reservierungen. <a href="index.php">Jetzt reservieren</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Vergangene Reservierungen</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php 
                        $hasPastReservations = false;
                        foreach ($userReservations as $res): 
                            // Nur vergangene oder stornierte Reservierungen anzeigen
                            if ($res['status'] === 'canceled' || strtotime($res['end_datetime']) < time()):
                                $hasPastReservations = true;
                        ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <span>
                                            <?php echo date('d.m.Y H:i', strtotime($res['start_datetime'])); ?> - 
                                            <?php echo date('d.m.Y H:i', strtotime($res['end_datetime'])); ?>
                                        </span>
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
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($res['user_message'])): ?>
                                            <div class="message-box user-message mb-3">
                                                <h5>Ihre Nachricht:</h5>
                                                <p><?php echo nl2br(escape($res['user_message'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($res['admin_message'])): ?>
                                            <div class="message-box admin-message mb-3">
                                                <h5>Nachricht vom Administrator:</h5>
                                                <p><?php echo nl2br(escape($res['admin_message'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        
                        if (!$hasPastReservations):
                        ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    Sie haben keine vergangenen Reservierungen.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 