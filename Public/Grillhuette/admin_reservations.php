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
        $newStatus = isset($_POST['update_status']) ? $_POST['update_status'] : '';
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

// Reservierung bearbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_reservation'])) {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $reservationId = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
        $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $startDate = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
        $endDate = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
        $startTime = isset($_POST['start_time']) ? trim($_POST['start_time']) : '12:00';
        $endTime = isset($_POST['end_time']) ? trim($_POST['end_time']) : '12:00';
        $adminMessage = isset($_POST['admin_message']) ? trim($_POST['admin_message']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'pending';
        
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
        
        if (!in_array($status, ['pending', 'confirmed', 'canceled'])) {
            $errors[] = 'Ungültiger Status.';
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
                $result = $reservation->updateReservation($reservationId, $userId, $startDatetime, $endDatetime, $adminMessage, $status);
                
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

// Reservierung löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reservation'])) {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $reservationId = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
        
        $result = $reservation->deleteReservation($reservationId);
        
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

// Titel für die Seite
$pageTitle = 'Reservierungen verwalten';

// Header einbinden
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Reservierungen verwalten</h1>
        
        <!-- Button zum Öffnen des Modals -->
        <div class="mb-4">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newReservationModal">
                <i class="bi bi-plus-circle"></i> Neue Reservierung erstellen
            </button>
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
                                                    <?php echo nl2br(escape(substr($res['user_message'], 0, 50) . (strlen($res['user_message']) > 50 ? '...' : ''))); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-muted">Keine Benutzernachricht</div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($res['admin_message'])): ?>
                                                <div class="message-box admin-message mb-2 p-2">
                                                    <strong>Admin-Nachricht:</strong><br>
                                                    <?php echo nl2br(escape(substr($res['admin_message'], 0, 50) . (strlen($res['admin_message']) > 50 ? '...' : ''))); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-muted">Keine Admin-Nachricht</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-primary btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editReservationModal"
                                                    data-id="<?php echo $res['id']; ?>"
                                                    data-user-id="<?php echo $res['user_id']; ?>"
                                                    data-start="<?php echo date('Y-m-d', strtotime($res['start_datetime'])); ?>"
                                                    data-start-time="<?php echo date('H:i', strtotime($res['start_datetime'])); ?>"
                                                    data-end="<?php echo date('Y-m-d', strtotime($res['end_datetime'])); ?>"
                                                    data-end-time="<?php echo date('H:i', strtotime($res['end_datetime'])); ?>"
                                                    data-message="<?php echo escape($res['admin_message'] ?? ''); ?>"
                                                    data-user-message="<?php echo escape($res['user_message'] ?? ''); ?>"
                                                    data-status="<?php echo $res['status']; ?>"
                                                    onclick="prepareEditModal(this)">
                                                <i class="bi bi-pencil"></i> Bearbeiten
                                            </button>
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

<!-- Modal für neue Reservierung -->
<div class="modal fade" id="newReservationModal" tabindex="-1" aria-labelledby="newReservationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newReservationModalLabel">Neue Reservierung erstellen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="admin_reservations.php<?php echo $statusFilter !== 'all' ? '?status=' . $statusFilter : ''; ?>" id="createReservationForm">
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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('createReservationForm').submit();">Reservierung erstellen</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal zum Bearbeiten einer Reservierung -->
<div class="modal fade" id="editReservationModal" tabindex="-1" aria-labelledby="editReservationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editReservationModalLabel">Reservierung bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="admin_reservations.php<?php echo $statusFilter !== 'all' ? '?status=' . $statusFilter : ''; ?>" id="editReservationForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="edit_reservation" value="1">
                    <input type="hidden" name="reservation_id" id="edit_reservation_id">
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_user_id" class="form-label">Benutzer</label>
                            <select class="form-select" id="edit_user_id" name="user_id" required>
                                <option value="">-- Benutzer auswählen --</option>
                                <?php foreach ($allUsers as $singleUser): ?>
                                    <option value="<?php echo $singleUser['id']; ?>">
                                        <?php echo escape($singleUser['first_name'] . ' ' . $singleUser['last_name'] . ' (' . $singleUser['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_start_date" class="form-label">Startdatum</label>
                            <input type="text" class="form-control date-picker" id="edit_start_date" name="start_date" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_end_date" class="form-label">Enddatum</label>
                            <input type="text" class="form-control date-picker" id="edit_end_date" name="end_date" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_start_time" class="form-label">Startzeit</label>
                            <input type="text" class="form-control time-picker" id="edit_start_time" name="start_time" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_end_time" class="form-label">Endzeit</label>
                            <input type="text" class="form-control time-picker" id="edit_end_time" name="end_time" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="pending">Ausstehend</option>
                                <option value="confirmed">Bestätigt</option>
                                <option value="canceled">Storniert</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_user_message_display" class="form-label">Nachricht vom Benutzer</label>
                            <textarea class="form-control" id="edit_user_message_display" rows="3" readonly></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_admin_message" class="form-label">Nachricht an den Benutzer</label>
                            <textarea class="form-control" id="edit_admin_message" name="admin_message" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <div class="d-flex justify-content-between w-100">
                    <div>
                        <button type="button" id="deleteReservationBtn" class="btn btn-danger" onclick="confirmDeleteReservation()">Reservierung löschen</button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('editReservationForm').submit();">Änderungen speichern</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formular zum Löschen einer Reservierung (wird via JavaScript abgesendet) -->
<form method="post" action="admin_reservations.php<?php echo $statusFilter !== 'all' ? '?status=' . $statusFilter : ''; ?>" id="deleteReservationForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <input type="hidden" name="delete_reservation" value="1">
    <input type="hidden" name="reservation_id" id="delete_reservation_id">
</form>

<!-- JavaScript für die Formularvorausfüllung -->
<script>
// Diese Funktion wird aufgerufen, wenn der "Bearbeiten"-Button geklickt wird
function prepareEditModal(button) {
    // Daten aus den Button-Attributen auslesen
    const reservationId = button.getAttribute('data-id');
    const userId = button.getAttribute('data-user-id');
    const startDate = button.getAttribute('data-start');
    const startTime = button.getAttribute('data-start-time');
    const endDate = button.getAttribute('data-end');
    const endTime = button.getAttribute('data-end-time');
    const adminMessage = button.getAttribute('data-message');
    const userMessage = button.getAttribute('data-user-message');
    const status = button.getAttribute('data-status');
    
    // Formularfelder ausfüllen
    document.getElementById('edit_reservation_id').value = reservationId;
    document.getElementById('delete_reservation_id').value = reservationId;
    document.getElementById('edit_user_id').value = userId;
    document.getElementById('edit_start_date').value = startDate;
    document.getElementById('edit_start_time').value = startTime;
    document.getElementById('edit_end_date').value = endDate;
    document.getElementById('edit_end_time').value = endTime;
    document.getElementById('edit_admin_message').value = adminMessage;
    document.getElementById('edit_user_message_display').value = userMessage;
    document.getElementById('edit_status').value = status;
}

function confirmDeleteReservation() {
    if (confirm('Sind Sie sicher, dass Sie diese Reservierung löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        document.getElementById('deleteReservationForm').submit();
    }
}

// Initialisierung beim Laden des Modals
document.addEventListener('DOMContentLoaded', function() {
    // Event-Listener für das Öffnen des Edit-Modals
    const editModal = document.getElementById('editReservationModal');
    if (editModal) {
        editModal.addEventListener('shown.bs.modal', function () {
            // Felder abrufen
            const startDateField = document.getElementById('edit_start_date');
            const startTimeField = document.getElementById('edit_start_time');
            const endDateField = document.getElementById('edit_end_date');
            const endTimeField = document.getElementById('edit_end_time');
            
            // Flatpickr-Instanzen zerstören, falls sie bereits existieren
            if (startDateField._flatpickr) startDateField._flatpickr.destroy();
            if (startTimeField._flatpickr) startTimeField._flatpickr.destroy();
            if (endDateField._flatpickr) endDateField._flatpickr.destroy();
            if (endTimeField._flatpickr) endTimeField._flatpickr.destroy();
            
            // Date picker für Startdatum und Enddatum
            flatpickr('#edit_start_date', {
                locale: "de",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j. F Y",
                minDate: "today",
                disableMobile: "true",
                defaultDate: startDateField.value
            });
            
            flatpickr('#edit_end_date', {
                locale: "de",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j. F Y",
                minDate: "today",
                disableMobile: "true",
                defaultDate: endDateField.value
            });
            
            // Time picker für Startzeit und Endzeit
            flatpickr('#edit_start_time', {
                locale: "de",
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                minuteIncrement: 30,
                disableMobile: "true",
                defaultDate: startTimeField.value ? `2000-01-01T${startTimeField.value}` : null
            });
            
            flatpickr('#edit_end_time', {
                locale: "de",
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                minuteIncrement: 30,
                disableMobile: "true",
                defaultDate: endTimeField.value ? `2000-01-01T${endTimeField.value}` : null
            });
        });
    }
});
</script> 