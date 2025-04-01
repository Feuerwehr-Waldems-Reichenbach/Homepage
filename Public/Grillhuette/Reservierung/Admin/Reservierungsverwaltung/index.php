<?php
require_once '../../includes/config.php';
require_once '../../includes/User.php';
require_once '../../includes/Reservation.php';

// Nur für angemeldete Administratoren zugänglich
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['flash_message'] = 'Sie haben keine Berechtigung, auf diese Seite zuzugreifen.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . getRelativePath('home'));
    exit;
}

// Objekte initialisieren
$user = new User();
$reservation = new Reservation();

// Status-Filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Benutzer für Dropdown bei neuer Reservierung
$allUsers = $user->getAllUsers();

// Alle POST-Anfragen abfangen und PRG-Muster anwenden
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        // Status aktualisieren
        if (isset($_POST['update_status'])) {
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
            }
        }
        
        // Neue Reservierung erstellen
        else if (isset($_POST['create_reservation'])) {
            $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            $startDate = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
            $endDate = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
            $startTime = isset($_POST['start_time']) ? trim($_POST['start_time']) : '12:00';
            $endTime = isset($_POST['end_time']) ? trim($_POST['end_time']) : '12:00';
            $adminMessage = isset($_POST['admin_message']) ? trim($_POST['admin_message']) : '';
            $receiptRequested = isset($_POST['receipt_requested']) ? 1 : 0;
            $isPublic = isset($_POST['is_public']) ? 1 : 0;
            $eventName = null;
            $displayStartDate = null;
            $displayEndDate = null;
            
            // Wenn öffentliche Veranstaltung
            if ($isPublic) {
                $eventName = isset($_POST['event_name']) ? trim($_POST['event_name']) : null;
                $isDateRange = isset($_POST['show_date_range']) && $_POST['show_date_range'] === 'on';
                
                if ($isDateRange) {
                    $displayStartDate = isset($_POST['display_start_date']) ? trim($_POST['display_start_date']) : null;
                    $displayEndDate = isset($_POST['display_end_date']) ? trim($_POST['display_end_date']) : null;
                } else {
                    // Wenn kein Datumsbereich, verwende den einzelnen Tag für beide Daten
                    $eventDay = isset($_POST['event_day']) ? trim($_POST['event_day']) : null;
                    $displayStartDate = $eventDay;
                    $displayEndDate = $eventDay;
                }
            }
            
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
            
            if ($isPublic) {
                if (empty($eventName)) {
                    $errors[] = 'Bitte geben Sie einen Namen für die öffentliche Veranstaltung an.';
                }
                
                if (empty($displayStartDate) || empty($displayEndDate)) {
                    $errors[] = 'Bitte wählen Sie ein Anzeige-Start und -Enddatum für öffentliche Reservierungen.';
                }
            }
            
            if (empty($errors)) {
                // Start- und Enddatum mit Uhrzeit kombinieren
                $startDatetime = $startDate . ' ' . $startTime . ':00';
                $endDatetime = $endDate . ' ' . $endTime . ':00';
                
                // Überprüfen, ob das Enddatum nach dem Startdatum liegt
                if (strtotime($endDatetime) <= strtotime($startDatetime)) {
                    $_SESSION['flash_message'] = 'Das Enddatum muss nach dem Startdatum liegen.';
                    $_SESSION['flash_type'] = 'danger';
                } 
                // Validieren der öffentlichen Veranstaltungsdaten
                else if ($isPublic) {
                    if (empty($eventName)) {
                        $_SESSION['flash_message'] = 'Bitte geben Sie einen Namen für die Veranstaltung ein.';
                        $_SESSION['flash_type'] = 'danger';
                    }
                    else if (empty($displayStartDate) || empty($displayEndDate)) {
                        $_SESSION['flash_message'] = 'Bitte wählen Sie die Anzeigezeiträume für die Veranstaltung.';
                        $_SESSION['flash_type'] = 'danger';
                    }
                    else if (strtotime($displayStartDate) < strtotime($startDate) || 
                            strtotime($displayEndDate) > strtotime($endDate)) {
                        $_SESSION['flash_message'] = 'Die Anzeigedaten müssen innerhalb des Reservierungszeitraums liegen.';
                        $_SESSION['flash_type'] = 'danger';
                    }
                    else {
                        // Alle Validierungen bestanden - Reservierung erstellen
                        $result = $reservation->createByAdmin(
                            $userId, 
                            $startDatetime, 
                            $endDatetime, 
                            $adminMessage, 
                            $receiptRequested,
                            $isPublic,
                            $eventName,
                            $displayStartDate,
                            $displayEndDate
                        );
                        
                        $_SESSION['flash_message'] = $result['message'];
                        $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
                    }
                }
                else {
                    // Keine öffentliche Veranstaltung - normale Reservierung erstellen
                    $result = $reservation->createByAdmin($userId, $startDatetime, $endDatetime, $adminMessage, $receiptRequested);
                    
                    $_SESSION['flash_message'] = $result['message'];
                    $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
                }
            } else {
                $_SESSION['flash_message'] = implode('<br>', $errors);
                $_SESSION['flash_type'] = 'danger';
            }
        }
        
        // Nachricht hinzufügen
        else if (isset($_POST['add_admin_message'])) {
            $reservationId = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
            $message = isset($_POST['admin_message']) ? trim($_POST['admin_message']) : '';
            
            if (empty($message)) {
                $_SESSION['flash_message'] = 'Bitte geben Sie eine Nachricht ein.';
                $_SESSION['flash_type'] = 'danger';
            } else {
                $result = $reservation->addAdminMessage($reservationId, $message);
                
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            }
        }
        
        // Reservierung bearbeiten
        else if (isset($_POST['edit_reservation'])) {
            $reservationId = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
            $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            $startDate = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
            $endDate = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
            $startTime = isset($_POST['start_time']) ? trim($_POST['start_time']) : '12:00';
            $endTime = isset($_POST['end_time']) ? trim($_POST['end_time']) : '12:00';
            $adminMessage = isset($_POST['admin_message']) ? trim($_POST['admin_message']) : '';
            $status = isset($_POST['status']) ? trim($_POST['status']) : 'pending';
            $receiptRequested = isset($_POST['receipt_requested']) ? 1 : 0;
            $isPublic = isset($_POST['is_public']) ? 1 : 0;
            $eventName = null;
            $displayStartDate = null;
            $displayEndDate = null;
            
            // Wenn öffentliche Veranstaltung
            if ($isPublic) {
                $eventName = isset($_POST['event_name']) ? trim($_POST['event_name']) : null;
                $isDateRange = isset($_POST['show_date_range']) && $_POST['show_date_range'] === 'on';
                
                if ($isDateRange) {
                    $displayStartDate = isset($_POST['display_start_date']) ? trim($_POST['display_start_date']) : null;
                    $displayEndDate = isset($_POST['display_end_date']) ? trim($_POST['display_end_date']) : null;
                } else {
                    // Wenn kein Datumsbereich, verwende den einzelnen Tag für beide Daten
                    $eventDay = isset($_POST['event_day']) ? trim($_POST['event_day']) : null;
                    $displayStartDate = $eventDay;
                    $displayEndDate = $eventDay;
                }
            }
            
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
            
            if ($isPublic) {
                if (empty($eventName)) {
                    $errors[] = 'Bitte geben Sie einen Namen für die öffentliche Veranstaltung an.';
                }
                
                if (empty($displayStartDate) || empty($displayEndDate)) {
                    $errors[] = 'Bitte wählen Sie ein Anzeige-Start und -Enddatum für öffentliche Reservierungen.';
                }
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
                    // Validieren der öffentlichen Veranstaltungsdaten
                    if ($isPublic) {
                        if (empty($eventName)) {
                            $_SESSION['flash_message'] = 'Bitte geben Sie einen Namen für die Veranstaltung ein.';
                            $_SESSION['flash_type'] = 'danger';
                            // Abbrechen und zur Übersicht zurückkehren
                            $redirectUrl = getRelativePath('Admin/Reservierungsverwaltung');
                            if ($statusFilter !== 'all') {
                                $redirectUrl .= '?status=' . $statusFilter;
                            }
                            header('Location: ' . $redirectUrl);
                            exit;
                        }
                        if (empty($displayStartDate) || empty($displayEndDate)) {
                            $_SESSION['flash_message'] = 'Bitte wählen Sie die Anzeigezeiträume für die Veranstaltung.';
                            $_SESSION['flash_type'] = 'danger';
                            // Abbrechen und zur Übersicht zurückkehren
                            $redirectUrl = getRelativePath('Admin/Reservierungsverwaltung');
                            if ($statusFilter !== 'all') {
                                $redirectUrl .= '?status=' . $statusFilter;
                            }
                            header('Location: ' . $redirectUrl);
                            exit;
                        }
                        if (strtotime($displayStartDate) < strtotime($startDate) || 
                                strtotime($displayEndDate) > strtotime($endDate)) {
                            $_SESSION['flash_message'] = 'Die Anzeigedaten müssen innerhalb des Reservierungszeitraums liegen.';
                            $_SESSION['flash_type'] = 'danger';
                            // Abbrechen und zur Übersicht zurückkehren
                            $redirectUrl = getRelativePath('Admin/Reservierungsverwaltung');
                            if ($statusFilter !== 'all') {
                                $redirectUrl .= '?status=' . $statusFilter;
                            }
                            header('Location: ' . $redirectUrl);
                            exit;
                        }
                    } else {
                        // Wenn nicht öffentlich, setze die Veranstaltungsdaten auf null
                        $eventName = null;
                        $displayStartDate = null;
                        $displayEndDate = null;
                    }
                    
                    // Alle Validierungen bestanden - Reservierung aktualisieren
                    $result = $reservation->updateReservation(
                        $reservationId, 
                        $userId, 
                        $startDatetime, 
                        $endDatetime, 
                        $adminMessage, 
                        $status, 
                        $receiptRequested,
                        $isPublic,
                        $eventName,
                        $displayStartDate,
                        $displayEndDate
                    );
                    
                    $_SESSION['flash_message'] = $result['message'];
                    $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
                }
            } else {
                $_SESSION['flash_message'] = implode('<br>', $errors);
                $_SESSION['flash_type'] = 'danger';
            }
        }
        
        // Reservierung löschen
        else if (isset($_POST['delete_reservation'])) {
            $reservationId = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
            
            $result = $reservation->deleteReservation($reservationId);
            
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
        }
    }
    
    // PRG-Muster: Nach POST-Anfrage zurück zur selben Seite weiterleiten, um erneutes Absenden zu verhindern
    $redirectUrl = getRelativePath('Admin/Reservierungsverwaltung');
    if ($statusFilter !== 'all') {
        $redirectUrl .= '?status=' . $statusFilter;
    }
    header('Location: ' . $redirectUrl);
    exit;
}

// Reservierungen abrufen
if ($statusFilter === 'all') {
    $allReservations = $reservation->getAll();
} else {
    $allReservations = $reservation->getAllByStatus($statusFilter);
}

// Titel für die Seite
$pageTitle = 'Reservierungen verwalten';

// Header einbinden
require_once '../../includes/header.php';
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
            <div class="card-header">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                    <h3 class="mb-3 mb-md-0">Reservierungen</h3>
                    <div class="btn-group btn-group-sm flex-wrap filter-buttons">
                        <a href="<?php echo getRelativePath('Admin/Reservierungsverwaltung'); ?>" class="btn btn-outline-secondary <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">Alle</a>
                        <a href="<?php echo getRelativePath('Admin/Reservierungsverwaltung'); ?>?status=pending" class="btn btn-outline-warning <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Ausstehend</a>
                        <a href="<?php echo getRelativePath('Admin/Reservierungsverwaltung'); ?>?status=confirmed" class="btn btn-outline-success <?php echo $statusFilter === 'confirmed' ? 'active' : ''; ?>">Bestätigt</a>
                        <a href="<?php echo getRelativePath('Admin/Reservierungsverwaltung'); ?>?status=canceled" class="btn btn-outline-danger <?php echo $statusFilter === 'canceled' ? 'active' : ''; ?>">Storniert</a>
                    </div>
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
                                    <th>Kosten</th>
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
                                            <?php
                                            // Use prices stored in the database
                                            $daysCount = $res['days_count'] ?? 1;
                                            $priceInfo = $reservation->getPriceInformation();
                                            $defaultBasePrice = $priceInfo['base_price'];
                                            $basePrice = $res['base_price'] ?? $defaultBasePrice;
                                            $totalPrice = $res['total_price'] ?? ($daysCount * $basePrice);
                                            
                                            // Format for display
                                            $formattedBasePrice = number_format($basePrice, 2, ',', '.');
                                            $formattedTotalPrice = number_format($totalPrice, 2, ',', '.');
                                            
                                            // Determine rate type for the user
                                            $priceInfo = $reservation->getPriceInformation($res['user_id']);
                                            $rateType = $priceInfo['rate_type'] ?? 'normal';
                                            ?>
                                            <strong><?php echo $formattedTotalPrice; ?>€</strong><br>
                                            <small>(<?php echo $daysCount; ?> Tage × <?php echo $formattedBasePrice; ?>€)</small>
                                            
                                            <?php if ($rateType !== 'normal'): ?>
                                            <div class="mt-1">
                                                <small class="text-danger">
                                                    <i class="bi bi-tag-fill"></i> Spezialpreis angewendet
                                                </small>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($res['receipt_requested']) && $res['receipt_requested']): ?>
                                            <div class="mt-1">
                                                <small class="text-primary">
                                                    <i class="bi bi-receipt"></i> Quittung gewünscht
                                                </small>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($res['is_public']) && $res['is_public']): ?>
                                            <div class="mt-1">
                                                <small class="text-success">
                                                    <i class="bi bi-calendar-event"></i> Öffentliche Veranstaltung
                                                </small>
                                            </div>
                                            <?php endif; ?>
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
                                                    data-receipt-requested="<?php echo isset($res['receipt_requested']) && $res['receipt_requested'] ? '1' : '0'; ?>"
                                                    data-is-public="<?php echo isset($res['is_public']) && $res['is_public'] ? '1' : '0'; ?>"
                                                    data-event-name="<?php echo escape($res['event_name'] ?? ''); ?>"
                                                    data-display-start-date="<?php echo isset($res['display_event_name_on_calendar_start_date']) ? date('Y-m-d', strtotime($res['display_event_name_on_calendar_start_date'])) : ''; ?>"
                                                    data-display-end-date="<?php echo isset($res['display_event_name_on_calendar_end_date']) ? date('Y-m-d', strtotime($res['display_event_name_on_calendar_end_date'])) : ''; ?>"
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

<?php require_once '../../includes/footer.php'; ?>

<!-- Modal für neue Reservierung -->
<div class="modal fade" id="newReservationModal" tabindex="-1" aria-labelledby="newReservationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newReservationModalLabel">Neue Reservierung erstellen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="<?php echo getRelativePath('Admin/Reservierungsverwaltung'); ?><?php echo $statusFilter !== 'all' ? '?status=' . $statusFilter : ''; ?>" id="createReservationForm">
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
                            <input type="time" class="form-control" id="start_time" name="start_time" value="12:00" step="1800" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="end_time" class="form-label">Endzeit</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" value="12:00" step="1800" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="admin_message" class="form-label">Nachricht an den Benutzer (optional)</label>
                            <textarea class="form-control" id="admin_message" name="admin_message" rows="1"></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="receipt_requested" name="receipt_requested" value="1">
                        <label class="form-check-label" for="receipt_requested">Quittung für die Reservierung gewünscht</label>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_public" name="is_public" value="1">
                        <label class="form-check-label" for="is_public">Öffentliche Reservierung (im Kalender sichtbar)</label>
                    </div>
                    
                    <div id="public-event-details" style="display: none;">
                        <div class="mb-3">
                            <label for="event_name" class="form-label">Name der Veranstaltung</label>
                            <input type="text" class="form-control" id="event_name" name="event_name" maxlength="255" placeholder="z.B. Grillfest">
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="show_date_range" name="show_date_range">
                            <label class="form-check-label" for="show_date_range">Veranstaltung geht über mehrere Tage</label>
                        </div>
                        
                        <div id="single-day-field" class="mb-3">
                            <label for="event_day" class="form-label">Veranstaltungstag</label>
                            <input type="text" class="form-control date-picker" id="event_day" name="event_day">
                            <div class="form-text">An diesem Tag wird die Veranstaltung im Kalender angezeigt.</div>
                        </div>
                        
                        <div id="date-range-fields" style="display: none;">
                            <div class="mb-3">
                                <label for="display_start_date" class="form-label">Anzeige im Kalender von</label>
                                <input type="text" class="form-control date-picker" id="display_start_date" name="display_start_date">
                            </div>
                            
                            <div class="mb-3">
                                <label for="display_end_date" class="form-label">Anzeige im Kalender bis</label>
                                <input type="text" class="form-control date-picker" id="display_end_date" name="display_end_date">
                                <div class="form-text">In diesem Zeitraum wird die Veranstaltung im Kalender angezeigt.</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kostenübersicht -->
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Kostenübersicht</label>
                            <div class="card">
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul class="list-unstyled mb-0" id="new-cost-overview" data-base-price="<?php echo $priceInfo['base_price']; ?>">
                                                <?php 
                                                // Holen Sie den Standardpreis aus der Datenbank
                                                $priceInfo = $reservation->getPriceInformation();
                                                $basePrice = number_format($priceInfo['base_price'], 2, ',', '.');
                                                $basePriceRaw = $priceInfo['base_price'];
                                                ?>
                                                <li>Grundpreis: <span class="daily-rate" data-base-price="<?php echo $basePriceRaw; ?>"><?php echo $basePrice; ?>€</span> pro Tag</li>
                                                <li>Anzahl Tage: <span id="new-day-count">1</span></li>
                                                <li class="border-top mt-2 pt-2"><strong>Gesamtpreis: <span id="new-total-cost"><?php echo $basePrice; ?>€</span></strong></li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-text">
                                                Der Mindestbuchungszeitraum beträgt 1 Tag.<br>
                                                <?php 
                                                // Get pricing info from database
                                                $priceInfo = $reservation->getPriceInformation();
                                                $depositAmount = number_format($priceInfo['deposit_amount'], 2, ',', '.');
                                                ?>
                                                Kaution (<?php echo $depositAmount; ?>€) nicht im Gesamtpreis enthalten.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                <form method="post" action="<?php echo getRelativePath('Admin/Reservierungsverwaltung'); ?><?php echo $statusFilter !== 'all' ? '?status=' . $statusFilter : ''; ?>" id="editReservationForm">
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
                            <input type="time" class="form-control" id="edit_start_time" name="start_time" step="1800" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_end_time" class="form-label">Endzeit</label>
                            <input type="time" class="form-control" id="edit_end_time" name="end_time" step="1800" required>
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
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_receipt_requested" name="receipt_requested" value="1">
                        <label class="form-check-label" for="edit_receipt_requested">Quittung für die Reservierung gewünscht</label>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_is_public" name="is_public" value="1">
                        <label class="form-label" for="edit_is_public">Öffentliche Reservierung (im Kalender sichtbar)</label>
                    </div>
                    
                    <div id="edit_public-event-details" style="display: none;">
                        <div class="mb-3">
                            <label for="edit_event_name" class="form-label">Name der Veranstaltung</label>
                            <input type="text" class="form-control" id="edit_event_name" name="event_name" maxlength="255" placeholder="z.B. Grillfest">
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_show_date_range" name="show_date_range">
                            <label class="form-check-label" for="edit_show_date_range">Veranstaltung geht über mehrere Tage</label>
                        </div>
                        
                        <div id="edit_single-day-field" class="mb-3">
                            <label for="edit_event_day" class="form-label">Veranstaltungstag</label>
                            <input type="text" class="form-control date-picker" id="edit_event_day" name="event_day">
                            <div class="form-text">An diesem Tag wird die Veranstaltung im Kalender angezeigt.</div>
                        </div>
                        
                        <div id="edit_date-range-fields" style="display: none;">
                            <div class="mb-3">
                                <label for="edit_display_start_date" class="form-label">Anzeige im Kalender von</label>
                                <input type="text" class="form-control date-picker" id="edit_display_start_date" name="display_start_date">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_display_end_date" class="form-label">Anzeige im Kalender bis</label>
                                <input type="text" class="form-control date-picker" id="edit_display_end_date" name="display_end_date">
                                <div class="form-text">In diesem Zeitraum wird die Veranstaltung im Kalender angezeigt.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <!-- Kostenübersicht -->
                            <label class="form-label">Kostenübersicht</label>
                            <div class="card">
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul class="list-unstyled mb-0" id="edit-cost-overview" data-base-price="<?php echo $priceInfo['base_price']; ?>">
                                                <?php 
                                                // Holen Sie den Standardpreis aus der Datenbank
                                                $priceInfo = $reservation->getPriceInformation();
                                                $basePrice = number_format($priceInfo['base_price'], 2, ',', '.');
                                                $basePriceRaw = $priceInfo['base_price'];
                                                ?>
                                                <li>Grundpreis: <span class="daily-rate" data-base-price="<?php echo $basePriceRaw; ?>"><?php echo $basePrice; ?>€</span> pro Tag</li>
                                                <li>Anzahl Tage: <span id="edit-day-count">1</span></li>
                                                <li class="border-top mt-2 pt-2"><strong>Gesamtpreis: <span id="edit-total-cost"><?php echo $basePrice; ?>€</span></strong></li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-text">
                                                Der Mindestbuchungszeitraum beträgt 1 Tag.<br>
                                                <?php 
                                                // Get pricing info from database
                                                $priceInfo = $reservation->getPriceInformation();
                                                $depositAmount = number_format($priceInfo['deposit_amount'], 2, ',', '.');
                                                ?>
                                                Kaution (<?php echo $depositAmount; ?>€) nicht im Gesamtpreis enthalten.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
<form method="post" action="<?php echo getRelativePath('Admin/Reservierungsverwaltung'); ?><?php echo $statusFilter !== 'all' ? '?status=' . $statusFilter : ''; ?>" id="deleteReservationForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <input type="hidden" name="delete_reservation" value="1">
    <input type="hidden" name="reservation_id" id="delete_reservation_id">
</form>

<!-- JavaScript für die Formularvorausfüllung -->
<script>
// Diese Funktion wird aufgerufen, wenn der "Bearbeiten"-Button geklickt wird
function prepareEditModal(button) {
    // Reservation ID abrufen
    const reservationId = button.getAttribute('data-id');
    document.getElementById('edit_reservation_id').value = reservationId;
    
    // Benutzer ID 
    const userId = button.getAttribute('data-user-id');
    document.getElementById('edit_user_id').value = userId;
    
    // Startdatum 
    const startDate = button.getAttribute('data-start');
    document.getElementById('edit_start_date').value = startDate;
    
    // Enddatum 
    const endDate = button.getAttribute('data-end');
    document.getElementById('edit_end_date').value = endDate;
    
    // Startzeit 
    const startTime = button.getAttribute('data-start-time');
    document.getElementById('edit_start_time').value = startTime;
    
    // Endzeit 
    const endTime = button.getAttribute('data-end-time');
    document.getElementById('edit_end_time').value = endTime;
    
    // Admin-Nachricht 
    const message = button.getAttribute('data-message');
    document.getElementById('edit_admin_message').value = message || '';
    
    // Benutzer-Nachricht
    const userMessage = button.getAttribute('data-user-message');
    document.getElementById('edit_user_message_display').value = userMessage || '';
    
    // Status (Dropdown)
    const status = button.getAttribute('data-status');
    document.getElementById('edit_status').value = status;
    
    // Receipt requested
    const receiptRequested = button.getAttribute('data-receipt-requested');
    document.getElementById('edit_receipt_requested').checked = (receiptRequested === '1');
    
    // Öffentliche Veranstaltung
    const isPublic = button.getAttribute('data-is-public');
    document.getElementById('edit_is_public').checked = (isPublic === '1');
    
    // Veranstaltungsname
    const eventName = button.getAttribute('data-event-name');
    document.getElementById('edit_event_name').value = eventName || '';
    
    // Anzeigebereich
    const displayStartDate = button.getAttribute('data-display-start-date');
    const displayEndDate = button.getAttribute('data-display-end-date');
    
    // Überprüfen, ob es ein Datumsbereich ist
    const isDateRange = displayStartDate && displayEndDate && displayStartDate !== displayEndDate;
    document.getElementById('edit_show_date_range').checked = isDateRange;
    
    // Sichtbarkeit der Bereiche aktualisieren
    document.getElementById('edit_public-event-details').style.display = isPublic === '1' ? 'block' : 'none';
    document.getElementById('edit_single-day-field').style.display = isDateRange ? 'none' : 'block';
    document.getElementById('edit_date-range-fields').style.display = isDateRange ? 'block' : 'none';
    
    // Daten für entweder den einzelnen Tag oder den Bereich setzen
    if (displayStartDate) {
        if (isDateRange) {
            document.getElementById('edit_display_start_date').value = displayStartDate;
            document.getElementById('edit_display_end_date').value = displayEndDate;
        } else {
            document.getElementById('edit_event_day').value = displayStartDate;
        }
    }
    
    // Löschformular vorbereiten
    document.getElementById('delete_reservation_id').value = reservationId;
    
    // Kostenberechnung aktualisieren
    updateEditCosts();
    
    // Öffentliche Veranstaltungsdetails anzeigen/ausblenden
    togglePublicEventDetails('edit');
}

function confirmDeleteReservation() {
    if (confirm('Sind Sie sicher, dass Sie diese Reservierung löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        document.getElementById('deleteReservationForm').submit();
    }
}

// Calculate costs for edit reservation
function updateEditCosts() {
    calculateCosts('edit_start_date', 'edit_end_date', 'edit-day-count', 'edit-total-cost');
}

// Calculate costs for new reservation
function updateNewCosts() {
    calculateCosts('start_date', 'end_date', 'new-day-count', 'new-total-cost');
}

// Generic cost calculation function
function calculateCosts(startDateId, endDateId, dayCountId, totalCostId) {
    
    const startDateInput = document.getElementById(startDateId);
    const endDateInput = document.getElementById(endDateId);
    const dayCountElement = document.getElementById(dayCountId);
    const totalCostElement = document.getElementById(totalCostId);
    
    // Finde die passenden Zeitfelder basierend auf den Datums-IDs
    let startTimeId = startDateId.replace('date', 'time');
    let endTimeId = endDateId.replace('date', 'time');
    const startTimeInput = document.getElementById(startTimeId);
    const endTimeInput = document.getElementById(endTimeId);
    
    if (!startDateInput || !endDateInput || !dayCountElement || !totalCostElement) {
        return;
    }
    
    // Get the standard base price from the cost overview element
    const costOverview = totalCostElement.closest('.card-body').querySelector('[data-base-price]');
    const defaultBasePrice = costOverview && costOverview.dataset.basePrice ? 
        parseFloat(costOverview.dataset.basePrice) : 100;
    
    // Only calculate if both dates are selected
    if (startDateInput.value && endDateInput.value) {     
        
        // Erstelle vollständige Datums-Zeit-Objekte
        let startDateTime = new Date(startDateInput.value);
        let endDateTime = new Date(endDateInput.value);
        
        // Füge die Uhrzeiten hinzu, falls verfügbar
        if (startTimeInput && startTimeInput.value) {
            const [startHours, startMinutes] = startTimeInput.value.split(':').map(Number);
            startDateTime.setHours(startHours, startMinutes, 0);
        }
        
        if (endTimeInput && endTimeInput.value) {
            const [endHours, endMinutes] = endTimeInput.value.split(':').map(Number);
            endDateTime.setHours(endHours, endMinutes, 0);
        }     
        
        // Get the user ID if we're on the edit form
        let userId = null;
        if (startDateId === 'edit_start_date') {
            const userSelect = document.getElementById('edit_user_id');
            userId = userSelect ? userSelect.value : null;
        } else if (startDateId === 'start_date') {
            const userSelect = document.getElementById('user_id');
            userId = userSelect ? userSelect.value : null;
        }
        
        // Fetch pricing information if we have a user ID
        if (userId) {
            // Make an AJAX request to get pricing for this specific user
            fetch('../../Helper/get_user_pricing.php?user_id=' + userId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Netzwerkfehler beim Abrufen der Preisdaten');
                    }
                    return response.json();
                })
                .then(priceInfo => {
                    if (priceInfo.success) {
                        // Berechne die Differenz in Millisekunden
                        const diffTime = Math.abs(endDateTime - startDateTime);
                        
                        // Berechne die Anzahl der Tage als Dezimalzahl (z.B. 1,5 Tage)
                        const diffDays = diffTime / (24 * 60 * 60 * 1000);
                        
                        // Runde auf ganze Tage auf (mindestens 1 Tag)
                        const days = Math.max(1, Math.ceil(diffDays));
                        
                        // Calculate total cost based on user rate
                        const dailyRate = priceInfo.user_rate;
                        const totalCost = days * dailyRate;
                        
                        
                        // Update the UI
                        dayCountElement.textContent = days;
                        
                        // Format with German locale
                        const formattedTotal = totalCost.toLocaleString('de-DE', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        totalCostElement.textContent = formattedTotal + '€';
                        
                        // Update the displayed rate and add note for special pricing
                        const rateElement = totalCostElement.closest('.card-body').querySelector('.daily-rate');
                        if (rateElement) {
                            const formattedRate = dailyRate.toLocaleString('de-DE', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                            rateElement.textContent = formattedRate + '€';
                            
                            // Clear any existing special pricing notes
                            const existingNote = totalCostElement.closest('.card-body').querySelector('.pricing-note');
                            if (existingNote) {
                                existingNote.remove();
                            }
                            
                            // Add note for special pricing
                            if (priceInfo.rate_type !== 'normal') {
                                const noteText = priceInfo.rate_type === 'feuerwehr' ? 
                                    'Spezialpreis für Feuerwehr' : 
                                    'Spezialpreis für aktives Mitglied';
                                
                                const noteDiv = document.createElement('div');
                                noteDiv.className = 'pricing-note text-danger mt-2';
                                noteDiv.innerHTML = `<strong>Hinweis:</strong> ${noteText}`;
                                
                                const costInfo = totalCostElement.closest('ul');
                                if (costInfo) {
                                    costInfo.insertAdjacentElement('afterend', noteDiv);
                                }
                            }
                        }
                        
                    } else {
                        // Fallback to standard calculation with generic error handling
                        console.log('Preisermittlung nicht erfolgreich. Standardberechnung wird verwendet.');
                        calculateStandardCost(startDateTime, endDateTime, dayCountElement, totalCostElement, defaultBasePrice);
                    }
                })
                .catch(error => {
                    // Generic error handling without revealing implementation details
                    console.log('Fehler bei der Preisermittlung. Standardberechnung wird verwendet.');
                    calculateStandardCost(startDateTime, endDateTime, dayCountElement, totalCostElement, defaultBasePrice);
                });
        } else {
            // If no user ID, use standard calculation
            calculateStandardCost(startDateTime, endDateTime, dayCountElement, totalCostElement, defaultBasePrice);
        }
    } else {
        // Default values if dates not selected
        dayCountElement.textContent = '1';
        
        // Format with German locale
        const formattedRate = defaultBasePrice.toLocaleString('de-DE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        totalCostElement.textContent = formattedRate + '€';
    }
}

// Fallback to standard pricing calculation
function calculateStandardCost(startDateTime, endDateTime, dayCountElement, totalCostElement, defaultBasePrice) {
    // Berechne die Differenz in Millisekunden
    const diffTime = Math.abs(endDateTime - startDateTime);
    
    // Berechne die Anzahl der Tage als Dezimalzahl (z.B. 1,5 Tage)
    const diffDays = diffTime / (24 * 60 * 60 * 1000);
    
    // Runde auf ganze Tage auf (mindestens 1 Tag)
    const days = Math.max(1, Math.ceil(diffDays));
    
    // Get the standard rate from the DOM or use provided defaultBasePrice
    const dailyRate = defaultBasePrice || 100;
    
    const totalCost = days * dailyRate;
    
    // Update the UI
    dayCountElement.textContent = days;
    totalCostElement.textContent = totalCost.toLocaleString('de-DE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + '€';
}

// Initialisierung beim Laden des Modals
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to the time inputs in the new reservation form directly
    const newStartTimeField = document.getElementById('start_time');
    const newEndTimeField = document.getElementById('end_time');
    
    if (newStartTimeField) {
        newStartTimeField.addEventListener('change', updateNewCosts);
    }
    
    if (newEndTimeField) {
        newEndTimeField.addEventListener('change', updateNewCosts);
    }
    
    // Handle public event checkbox for new form
    const isPublicCheckbox = document.getElementById('is_public');
    const showDateRangeCheckbox = document.getElementById('show_date_range');
    const publicEventDetails = document.getElementById('public-event-details');
    const singleDayField = document.getElementById('single-day-field');
    const dateRangeFields = document.getElementById('date-range-fields');
    
    if (isPublicCheckbox && publicEventDetails) {
        isPublicCheckbox.addEventListener('change', function() {
            publicEventDetails.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    if (showDateRangeCheckbox && dateRangeFields && singleDayField) {
        showDateRangeCheckbox.addEventListener('change', function() {
            dateRangeFields.style.display = this.checked ? 'block' : 'none';
            singleDayField.style.display = this.checked ? 'none' : 'block';
            
            // Synchronize the dates if needed
            const eventDayField = document.getElementById('event_day');
            const displayStartField = document.getElementById('display_start_date');
            const displayEndField = document.getElementById('display_end_date');
            
            if (this.checked && eventDayField && eventDayField.value) {
                // If switching to range mode and we have an event day, use it for both start and end
                displayStartField.value = eventDayField.value;
                displayEndField.value = eventDayField.value;
            } else if (!this.checked && displayStartField && displayStartField.value) {
                // If switching to single day mode, use the start date
                eventDayField.value = displayStartField.value;
            }
        });
    }
    
    // Handle public event checkbox for edit form
    const editIsPublicCheckbox = document.getElementById('edit_is_public');
    const editShowDateRangeCheckbox = document.getElementById('edit_show_date_range');
    const editPublicEventDetails = document.getElementById('edit-public-event-details');
    const editSingleDayField = document.getElementById('edit-single-day-field');
    const editDateRangeFields = document.getElementById('edit-date-range-fields');
    
    if (editIsPublicCheckbox && editPublicEventDetails) {
        editIsPublicCheckbox.addEventListener('change', function() {
            editPublicEventDetails.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    if (editShowDateRangeCheckbox && editDateRangeFields && editSingleDayField) {
        editShowDateRangeCheckbox.addEventListener('change', function() {
            editDateRangeFields.style.display = this.checked ? 'block' : 'none';
            editSingleDayField.style.display = this.checked ? 'none' : 'block';
            
            // Synchronize the dates if needed
            const eventDayField = document.getElementById('edit_event_day');
            const displayStartField = document.getElementById('edit_display_start_date');
            const displayEndField = document.getElementById('edit_display_end_date');
            
            if (this.checked && eventDayField && eventDayField.value) {
                // If switching to range mode and we have an event day, use it for both start and end
                displayStartField.value = eventDayField.value;
                displayEndField.value = eventDayField.value;
            } else if (!this.checked && displayStartField && displayStartField.value) {
                // If switching to single day mode, use the start date
                eventDayField.value = displayStartField.value;
            }
        });
    }
    
    // Initialize flatpickr for the new reservation form date fields
    flatpickr('#start_date', {
        locale: "de",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j. F Y",
        minDate: "today",
        disableMobile: "true",
        onChange: function() {
            updateNewCosts();
            // Update constraints for event dates
            updateNewEventDateConstraints();
        }
    });
    
    flatpickr('#end_date', {
        locale: "de",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j. F Y",
        minDate: "today",
        disableMobile: "true",
        onChange: function() {
            updateNewCosts();
            // Update constraints for event dates
            updateNewEventDateConstraints();
        }
    });
    
    // Initialize flatpickr for the event day fields
    const eventDayPicker = flatpickr('#event_day', {
        locale: "de",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j. F Y",
        minDate: "today",
        disableMobile: "true",
        onChange: function(selectedDates, dateStr) {
            // When single day is selected, update both display date fields
            if (selectedDates[0]) {
                document.getElementById('display_start_date').value = dateStr;
                document.getElementById('display_end_date').value = dateStr;
            }
        }
    });
    
    const displayStartPicker = flatpickr('#display_start_date', {
        locale: "de",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j. F Y",
        minDate: "today",
        disableMobile: "true",
        onChange: function(selectedDates, dateStr) {
            // Update min date of display end picker
            if (selectedDates[0]) {
                displayEndPicker.set('minDate', selectedDates[0]);
            }
        }
    });
    
    const displayEndPicker = flatpickr('#display_end_date', {
        locale: "de",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j. F Y",
        minDate: "today",
        disableMobile: "true"
    });
    
    // Function to update event date constraints
    function updateNewEventDateConstraints() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        if (startDate && endDate) {
            // Update constraints for event dates
            eventDayPicker.set('minDate', startDate);
            eventDayPicker.set('maxDate', endDate);
            displayStartPicker.set('minDate', startDate);
            displayStartPicker.set('maxDate', endDate);
            displayEndPicker.set('minDate', startDate);
            displayEndPicker.set('maxDate', endDate);
        }
    }
    
    // Run the initial cost calculation for the new reservation form
    updateNewCosts();
    
    // Event-Listener für das Öffnen des Edit-Modals
    const editModal = document.getElementById('editReservationModal');
    if (editModal) {
        editModal.addEventListener('shown.bs.modal', function () {
            // Felder abrufen
            const startDateField = document.getElementById('edit_start_date');
            const startTimeField = document.getElementById('edit_start_time');
            const endDateField = document.getElementById('edit_end_date');
            const endTimeField = document.getElementById('edit_end_time');
            const eventDayField = document.getElementById('edit_event_day');
            const displayStartDateField = document.getElementById('edit_display_start_date');
            const displayEndDateField = document.getElementById('edit_display_end_date');
            
            // Flatpickr-Instanzen zerstören, falls sie bereits existieren
            if (startDateField._flatpickr) startDateField._flatpickr.destroy();
            if (endDateField._flatpickr) endDateField._flatpickr.destroy();
            if (eventDayField._flatpickr) eventDayField._flatpickr.destroy();
            if (displayStartDateField._flatpickr) displayStartDateField._flatpickr.destroy();
            if (displayEndDateField._flatpickr) displayEndDateField._flatpickr.destroy();
            
            // Date picker für Startdatum und Enddatum
            flatpickr('#edit_start_date', {
                locale: "de",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j. F Y",
                minDate: "today",
                disableMobile: "true",
                defaultDate: startDateField.value,
                onChange: function() {
                    updateEditCosts();
                    updateEditEventDateConstraints();
                }
            });
            
            flatpickr('#edit_end_date', {
                locale: "de",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j. F Y",
                minDate: "today",
                disableMobile: "true",
                defaultDate: endDateField.value,
                onChange: function() {
                    updateEditCosts();
                    updateEditEventDateConstraints();
                }
            });
            
            // Datepicker für Veranstaltungsdaten
            const editEventDayPicker = flatpickr('#edit_event_day', {
                locale: "de",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j. F Y",
                minDate: "today",
                disableMobile: "true",
                defaultDate: eventDayField.value,
                onChange: function(selectedDates, dateStr) {
                    // When single day is selected, update both display date fields
                    if (selectedDates[0]) {
                        document.getElementById('edit_display_start_date').value = dateStr;
                        document.getElementById('edit_display_end_date').value = dateStr;
                    }
                }
            });
            
            const editDisplayStartPicker = flatpickr('#edit_display_start_date', {
                locale: "de",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j. F Y",
                minDate: "today",
                disableMobile: "true",
                defaultDate: displayStartDateField.value,
                onChange: function(selectedDates, dateStr) {
                    // Update min date of display end picker
                    if (selectedDates[0]) {
                        editDisplayEndPicker.set('minDate', selectedDates[0]);
                    }
                }
            });
            
            const editDisplayEndPicker = flatpickr('#edit_display_end_date', {
                locale: "de",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j. F Y",
                minDate: "today",
                disableMobile: "true",
                defaultDate: displayEndDateField.value
            });
            
            // Function to update event date constraints
            function updateEditEventDateConstraints() {
                const startDate = document.getElementById('edit_start_date').value;
                const endDate = document.getElementById('edit_end_date').value;
                
                if (startDate && endDate) {
                    // Update constraints for event dates
                    editEventDayPicker.set('minDate', startDate);
                    editEventDayPicker.set('maxDate', endDate);
                    editDisplayStartPicker.set('minDate', startDate);
                    editDisplayStartPicker.set('maxDate', endDate);
                    editDisplayEndPicker.set('minDate', startDate);
                    editDisplayEndPicker.set('maxDate', endDate);
                }
            }
            
            // Update event date constraints initially
            updateEditEventDateConstraints();
            
            // Event-Listener hinzufügen, um die Kostenberechnung zu aktualisieren, wenn sich die Zeit ändert
            startTimeField.addEventListener('change', updateEditCosts);
            endTimeField.addEventListener('change', updateEditCosts);
            
            // Initial cost calculation
            updateEditCosts();
        });
    }
    
    // Setup for new reservation modal
    const newModal = document.getElementById('newReservationModal');
    if (newModal) {
        newModal.addEventListener('shown.bs.modal', function () {
            // Just run the cost calculation again when the modal is shown
            updateNewCosts();
            // And update event date constraints
            updateNewEventDateConstraints();
        });
    }
});
</script> 