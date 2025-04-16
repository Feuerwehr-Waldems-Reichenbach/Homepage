<?php
require_once '../../includes/config.php';
require_once '../../includes/User.php';
require_once '../../includes/Reservation.php';
// Nur für angemeldete Benutzer zugänglich
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bitte melden Sie sich an, um Ihre Reservierungen zu sehen.';
    $_SESSION['flash_type'] = 'warning';
    $_SESSION['redirect_after_login'] = getRelativePath('Benutzer/Meine-Reservierungen');
    header('Location: ' . getRelativePath('Benutzer/Anmelden'));
    exit;
}
// Nachricht hinzufügen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        if (isset($_POST['add_message'])) {
            $reservationId = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
            $message = isset($_POST['message']) ? trim($_POST['message']) : '';
            if (empty($message)) {
                $_SESSION['flash_message'] = 'Bitte geben Sie eine Nachricht ein.';
                $_SESSION['flash_type'] = 'danger';
            } else {
                $reservation = new Reservation();
                $result = $reservation->addUserMessage($reservationId, $message);
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            }
        }
        // Bearbeiten einer öffentlichen Veranstaltung
        else if (isset($_POST['edit_public_event'])) {
            $reservationId = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
            $eventName = isset($_POST['event_name']) ? trim($_POST['event_name']) : '';
            $displayStartDate = null;
            $displayEndDate = null;
            // Datumsbereich oder einzelnen Tag verarbeiten
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
            // Validierung
            $errors = [];
            if (empty($eventName)) {
                $errors[] = 'Bitte geben Sie einen Namen für die Veranstaltung ein.';
            }
            if (empty($displayStartDate) || empty($displayEndDate)) {
                $errors[] = 'Bitte wählen Sie ein Anzeigedatum für die Veranstaltung.';
            }
            if (empty($errors)) {
                // Reservierung abrufen, um Startdatum und Enddatum zu prüfen
                $reservation = new Reservation();
                $reservationData = $reservation->getById($reservationId);
                if (!$reservationData || $reservationData['user_id'] != $_SESSION['user_id']) {
                    $_SESSION['flash_message'] = 'Diese Reservierung konnte nicht gefunden werden oder gehört nicht zu Ihrem Konto.';
                    $_SESSION['flash_type'] = 'danger';
                } else if (!isset($reservationData['is_public']) || !$reservationData['is_public']) {
                    $_SESSION['flash_message'] = 'Nur öffentliche Veranstaltungen können bearbeitet werden.';
                    $_SESSION['flash_type'] = 'danger';
                } else if (
                    strtotime($displayStartDate) < strtotime(date('Y-m-d', strtotime($reservationData['start_datetime']))) ||
                    strtotime($displayEndDate) > strtotime(date('Y-m-d', strtotime($reservationData['end_datetime'])))
                ) {
                    $_SESSION['flash_message'] = 'Die Anzeigedaten müssen innerhalb des Reservierungszeitraums liegen.';
                    $_SESSION['flash_type'] = 'danger';
                } else {
                    // Alle Validierungen bestanden - Veranstaltung aktualisieren
                    $result = $reservation->updatePublicEvent(
                        $reservationId,
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
        // Reservierung stornieren
        else if (isset($_POST['cancel_reservation'])) {
            $reservationId = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
            $reservation = new Reservation();
            $result = $reservation->cancel($reservationId);
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
        }
        // Reservierung löschen
        else if (isset($_POST['delete_reservation'])) {
            $reservationId = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
            // Überprüfen, ob die Reservierung dem aktuellen Benutzer gehört
            $reservation = new Reservation();
            $reservationData = $reservation->getById($reservationId);
            if ($reservationData && $reservationData['user_id'] == $_SESSION['user_id']) {
                // Sicherstellen, dass es eine vergangene oder stornierte Reservierung ist
                if ($reservationData['status'] === 'canceled' || strtotime($reservationData['end_datetime']) < time()) {
                    $result = $reservation->deleteReservation($reservationId);
                    $_SESSION['flash_message'] = $result['message'];
                    $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
                } else {
                    $_SESSION['flash_message'] = 'Nur vergangene oder stornierte Reservierungen können gelöscht werden.';
                    $_SESSION['flash_type'] = 'danger';
                }
            } else {
                $_SESSION['flash_message'] = 'Reservierung konnte nicht gefunden werden oder gehört nicht zu Ihrem Konto.';
                $_SESSION['flash_type'] = 'danger';
            }
        }
        // PRG-Muster: Umleiten nach POST, um erneutes Absenden bei Neuladen zu verhindern
        header('Location: ' . getRelativePath('Benutzer/Meine-Reservierungen'));
        exit;
    }
}
// Reservierungen des Benutzers abrufen
$reservation = new Reservation();
$userReservations = $reservation->getByUserId($_SESSION['user_id']);
// Titel für die Seite
$pageTitle = 'Meine Reservierungen';
// Header einbinden
require_once '../../includes/header.php';
?>
<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Meine Reservierungen</h1>
        <?php if (empty($userReservations)): ?>
            <div class="alert alert-info">
                Sie haben noch keine Reservierungen. <a href="<?php echo getRelativePath('home'); ?>">Jetzt reservieren</a>
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
                        $activeCount = 1;
                        foreach ($userReservations as $res):
                            // Nur aktuelle (nicht stornierte) und zukünftige Reservierungen anzeigen
                            if ($res['status'] !== 'canceled' && strtotime($res['end_datetime']) >= time()):
                                $hasActiveReservations = true;
                                ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <span>
                                                <h5>Reservierung <?php echo $activeCount; ?></h5>
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
                                            <!-- Kostenübersicht -->
                                            <div class="card mb-3">
                                                <div class="card-body p-3">
                                                    <?php
                                                    // Die Werte aus der Datenbank anzeigen
                                                    $daysCount = $res['days_count'] ?? 1;
                                                    $basePrice = $res['base_price'] ?? 100.00;
                                                    $totalPrice = $res['total_price'] ?? ($daysCount * $basePrice);
                                                    $depositAmount = $res['deposit_amount'] ?? 100.00;
                                                    // Formatierte Werte für die Anzeige
                                                    $formattedBasePrice = number_format($basePrice, 2, ',', '.');
                                                    $formattedTotalPrice = number_format($totalPrice, 2, ',', '.');
                                                    $formattedDepositAmount = number_format($depositAmount, 2, ',', '.');
                                                    ?>
                                                    <h6 class="mb-2">Kostenübersicht:</h6>
                                                    <ul class="list-unstyled mb-0">
                                                        <li>Grundpreis: <?php echo $formattedBasePrice; ?>€ pro Tag</li>
                                                        <li>Anzahl Tage: <?php echo $daysCount; ?></li>
                                                        <li class="border-top mt-2 pt-2"><strong>Gesamtpreis:
                                                                <?php echo $formattedTotalPrice; ?>€</strong></li>
                                                    </ul>
                                                    <div class="form-text mt-2">Kaution (<?php echo $formattedDepositAmount; ?>€)
                                                        nicht im Gesamtpreis enthalten.</div>
                                                    <?php if (isset($res['receipt_requested']) && $res['receipt_requested']): ?>
                                                        <div class="mt-2 pt-2 border-top">
                                                            <small class="text-primary">
                                                                <i class="bi bi-receipt"></i> Sie haben eine Quittung für diese
                                                                Reservierung angefordert.
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($res['key_handover_datetime']) || !empty($res['key_return_datetime'])): ?>
                                                        <div class="mt-2 pt-2 border-top">
                                                            <h6 class="text-primary mb-2">
                                                                <i class="bi bi-key"></i> Schlüsselübergabe
                                                            </h6>
                                                            <?php if (!empty($res['key_handover_datetime'])): ?>
                                                                <div>
                                                                    <strong>Übergabe:</strong>
                                                                    <?php echo date('d.m.Y H:i', strtotime($res['key_handover_datetime'])); ?>
                                                                    Uhr
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($res['key_return_datetime'])): ?>
                                                                <div>
                                                                    <strong>Rückgabe:</strong>
                                                                    <?php echo date('d.m.Y H:i', strtotime($res['key_return_datetime'])); ?>
                                                                    Uhr
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (isset($res['is_public']) && $res['is_public']): ?>
                                                        <div class="mt-2 pt-2 border-top">
                                                            <h6 class="text-success mb-2">
                                                                <i class="bi bi-calendar-event"></i> Öffentliche Veranstaltung
                                                            </h6>
                                                            <div>
                                                                <strong>Name:</strong> <?php echo escape($res['event_name'] ?? ''); ?>
                                                            </div>
                                                            <div>
                                                                <strong>Sichtbar im Kalender:</strong>
                                                                <?php
                                                                if (isset($res['display_event_name_on_calendar_start_date']) && isset($res['display_event_name_on_calendar_end_date'])) {
                                                                    $startDisplay = date('d.m.Y', strtotime($res['display_event_name_on_calendar_start_date']));
                                                                    $endDisplay = date('d.m.Y', strtotime($res['display_event_name_on_calendar_end_date']));
                                                                    if ($startDisplay === $endDisplay) {
                                                                        echo $startDisplay;
                                                                    } else {
                                                                        echo $startDisplay . ' - ' . $endDisplay;
                                                                    }
                                                                } else {
                                                                    echo 'Nicht angegeben';
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
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
                                            <form method="post" class="mb-3">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                                <div class="mb-3">
                                                    <label for="message_<?php echo $res['id']; ?>" class="form-label">Nachricht
                                                        hinzufügen/bearbeiten:</label>
                                                    <textarea class="form-control" id="message_<?php echo $res['id']; ?>"
                                                        name="message"
                                                        rows="3"><?php echo escape($res['user_message']); ?></textarea>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="submit" name="add_message"
                                                        class="btn btn-primary flex-grow-1">Nachricht speichern</button>
                                                    <?php if ($res['status'] !== 'canceled'): ?>
                                                        <button type="submit" name="cancel_reservation"
                                                            class="btn btn-danger flex-grow-1"
                                                            onclick="return confirm('Sind Sie sicher, dass Sie diese Reservierung stornieren möchten?');">Reservierung
                                                            stornieren</button>
                                                    <?php endif; ?>
                                                    <?php if (isset($res['is_public']) && $res['is_public']): ?>
                                                        <button type="button" class="btn btn-success flex-grow-1" data-bs-toggle="modal"
                                                            data-bs-target="#editPublicEventModal" data-id="<?php echo $res['id']; ?>"
                                                            data-event-name="<?php echo escape($res['event_name'] ?? ''); ?>"
                                                            data-display-start-date="<?php echo isset($res['display_event_name_on_calendar_start_date']) ? date('Y-m-d', strtotime($res['display_event_name_on_calendar_start_date'])) : ''; ?>"
                                                            data-display-end-date="<?php echo isset($res['display_event_name_on_calendar_end_date']) ? date('Y-m-d', strtotime($res['display_event_name_on_calendar_end_date'])) : ''; ?>"
                                                            data-start-date="<?php echo date('Y-m-d', strtotime($res['start_datetime'])); ?>"
                                                            data-end-date="<?php echo date('Y-m-d', strtotime($res['end_datetime'])); ?>"
                                                            onclick="prepareEditEventModal(this)">
                                                            <i class="bi bi-calendar-event"></i> Veranstaltung bearbeiten
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            endif;
                            $activeCount++;
                        endforeach;
                        if (!$hasActiveReservations):
                            ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    Sie haben keine aktiven oder zukünftigen Reservierungen. <a
                                        href="<?php echo getRelativePath('home'); ?>">Jetzt reservieren</a>
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
                        $pastCount = 1;
                        foreach ($userReservations as $res):
                            // Nur vergangene oder stornierte Reservierungen anzeigen
                            if ($res['status'] === 'canceled' || strtotime($res['end_datetime']) < time()):
                                $hasPastReservations = true;
                                ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <span>
                                                <h5>Reservierung <?php echo $pastCount; ?></h5>
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
                                            <!-- Kostenübersicht -->
                                            <div class="card mb-3">
                                                <div class="card-body p-3">
                                                    <?php
                                                    // Die Werte aus der Datenbank anzeigen
                                                    $daysCount = $res['days_count'] ?? 1;
                                                    $basePrice = $res['base_price'] ?? 100.00;
                                                    $totalPrice = $res['total_price'] ?? ($daysCount * $basePrice);
                                                    $depositAmount = $res['deposit_amount'] ?? 100.00;
                                                    // Formatierte Werte für die Anzeige
                                                    $formattedBasePrice = number_format($basePrice, 2, ',', '.');
                                                    $formattedTotalPrice = number_format($totalPrice, 2, ',', '.');
                                                    $formattedDepositAmount = number_format($depositAmount, 2, ',', '.');
                                                    ?>
                                                    <h6 class="mb-2">Kostenübersicht:</h6>
                                                    <ul class="list-unstyled mb-0">
                                                        <li>Grundpreis: <?php echo $formattedBasePrice; ?>€ pro Tag</li>
                                                        <li>Anzahl Tage: <?php echo $daysCount; ?></li>
                                                        <li class="border-top mt-2 pt-2"><strong>Gesamtpreis:
                                                                <?php echo $formattedTotalPrice; ?>€</strong></li>
                                                    </ul>
                                                    <div class="form-text mt-2">Kaution (<?php echo $formattedDepositAmount; ?>€)
                                                        nicht im Gesamtpreis enthalten.</div>
                                                    <?php if (isset($res['receipt_requested']) && $res['receipt_requested']): ?>
                                                        <div class="mt-2 pt-2 border-top">
                                                            <small class="text-primary">
                                                                <i class="bi bi-receipt"></i> Sie haben eine Quittung für diese
                                                                Reservierung angefordert.
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($res['key_handover_datetime']) || !empty($res['key_return_datetime'])): ?>
                                                        <div class="mt-2 pt-2 border-top">
                                                            <h6 class="text-primary mb-2">
                                                                <i class="bi bi-key"></i> Schlüsselübergabe
                                                            </h6>
                                                            <?php if (!empty($res['key_handover_datetime'])): ?>
                                                                <div>
                                                                    <strong>Übergabe:</strong>
                                                                    <?php echo date('d.m.Y H:i', strtotime($res['key_handover_datetime'])); ?>
                                                                    Uhr
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($res['key_return_datetime'])): ?>
                                                                <div>
                                                                    <strong>Rückgabe:</strong>
                                                                    <?php echo date('d.m.Y H:i', strtotime($res['key_return_datetime'])); ?>
                                                                    Uhr
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (isset($res['is_public']) && $res['is_public']): ?>
                                                        <div class="mt-2 pt-2 border-top">
                                                            <h6 class="text-success mb-2">
                                                                <i class="bi bi-calendar-event"></i> Öffentliche Veranstaltung
                                                            </h6>
                                                            <div>
                                                                <strong>Name:</strong> <?php echo escape($res['event_name'] ?? ''); ?>
                                                            </div>
                                                            <div>
                                                                <strong>Sichtbar im Kalender:</strong>
                                                                <?php
                                                                if (isset($res['display_event_name_on_calendar_start_date']) && isset($res['display_event_name_on_calendar_end_date'])) {
                                                                    $startDisplay = date('d.m.Y', strtotime($res['display_event_name_on_calendar_start_date']));
                                                                    $endDisplay = date('d.m.Y', strtotime($res['display_event_name_on_calendar_end_date']));
                                                                    if ($startDisplay === $endDisplay) {
                                                                        echo $startDisplay;
                                                                    } else {
                                                                        echo $startDisplay . ' - ' . $endDisplay;
                                                                    }
                                                                } else {
                                                                    echo 'Nicht angegeben';
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
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
                                            <!-- Nur Löschen ist bei vergangenen/stornierten Reservierungen möglich -->
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                                <div class="d-grid">
                                                    <button type="submit" name="delete_reservation" class="btn btn-outline-danger"
                                                        onclick="return confirm('Sind Sie sicher, dass Sie diese Reservierung endgültig löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.');">Reservierung
                                                        löschen</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            endif;
                            $pastCount++;
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
<?php require_once '../../includes/footer.php'; ?>
<!-- Modal zum Bearbeiten einer öffentlichen Veranstaltung -->
<div class="modal fade" id="editPublicEventModal" tabindex="-1" aria-labelledby="editPublicEventModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPublicEventModalLabel">Öffentliche Veranstaltung bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="<?php echo getRelativePath('Benutzer/Meine-Reservierungen'); ?>"
                    id="editPublicEventForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="edit_public_event" value="1">
                    <input type="hidden" name="reservation_id" id="edit_public_event_id">
                    <div class="mb-3">
                        <label for="event_name" class="form-label">Name der Veranstaltung</label>
                        <input type="text" class="form-control" id="event_name" name="event_name" maxlength="255"
                            placeholder="z.B. Grillfest" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="show_date_range" name="show_date_range">
                        <label class="form-check-label" for="show_date_range">Veranstaltung geht über mehrere
                            Tage</label>
                    </div>
                    <div id="single-day-field" class="mb-3">
                        <label for="event_day" class="form-label">Veranstaltungstag</label>
                        <input type="text" class="form-control date-picker" id="event_day" name="event_day">
                        <div class="form-text">An diesem Tag wird die Veranstaltung im Kalender angezeigt.</div>
                    </div>
                    <div id="date-range-fields" style="display: none;">
                        <div class="mb-3">
                            <label for="display_start_date" class="form-label">Anzeige im Kalender von</label>
                            <input type="text" class="form-control date-picker" id="display_start_date"
                                name="display_start_date">
                        </div>
                        <div class="mb-3">
                            <label for="display_end_date" class="form-label">Anzeige im Kalender bis</label>
                            <input type="text" class="form-control date-picker" id="display_end_date"
                                name="display_end_date">
                            <div class="form-text">In diesem Zeitraum wird die Veranstaltung im Kalender angezeigt.
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary"
                    onclick="document.getElementById('editPublicEventForm').submit();">Änderungen speichern</button>
            </div>
        </div>
    </div>
</div>
<!-- JavaScript für die Bearbeitung öffentlicher Veranstaltungen -->
<script nonce="<?php echo $cspNonce; ?>">
    document.addEventListener('DOMContentLoaded', function () {
        // Show/hide date range fields based on checkbox
        const showDateRangeCheckbox = document.getElementById('show_date_range');
        const singleDayField = document.getElementById('single-day-field');
        const dateRangeFields = document.getElementById('date-range-fields');
        if (showDateRangeCheckbox && dateRangeFields && singleDayField) {
            showDateRangeCheckbox.addEventListener('change', function () {
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
        // Initialize flatpickr for date pickers
        if (typeof flatpickr !== 'undefined') {
            // We'll initialize these only when the modal is opened
            // to avoid conflicts with other event handlers
        }
        // Event listener for modal opening
        const editPublicEventModal = document.getElementById('editPublicEventModal');
        if (editPublicEventModal) {
            editPublicEventModal.addEventListener('shown.bs.modal', function () {
                // Ensure dates are properly displayed (sometimes needed for browser compatibility)
                setTimeout(() => {
                    // Force refresh of values if necessary
                    if (document.getElementById('event_day')._flatpickr) {
                        document.getElementById('event_day')._flatpickr.redraw();
                    }
                    if (document.getElementById('display_start_date')._flatpickr) {
                        document.getElementById('display_start_date')._flatpickr.redraw();
                    }
                    if (document.getElementById('display_end_date')._flatpickr) {
                        document.getElementById('display_end_date')._flatpickr.redraw();
                    }
                }, 10);
            });
        }
        // Function to prepare modal for editing
        window.prepareEditEventModal = function (button) {
            const reservationId = button.getAttribute('data-id');
            const eventName = button.getAttribute('data-event-name');
            const displayStartDate = button.getAttribute('data-display-start-date');
            const displayEndDate = button.getAttribute('data-display-end-date');
            const startDate = button.getAttribute('data-start-date');
            const endDate = button.getAttribute('data-end-date');
            // Set the hidden reservation ID
            document.getElementById('edit_public_event_id').value = reservationId;
            // Set the event name
            document.getElementById('event_name').value = eventName || '';
            // Determine if single day or date range
            const isDateRange = displayStartDate && displayEndDate && displayStartDate !== displayEndDate;
            document.getElementById('show_date_range').checked = isDateRange;
            // Show/hide fields based on range type
            document.getElementById('single-day-field').style.display = isDateRange ? 'none' : 'block';
            document.getElementById('date-range-fields').style.display = isDateRange ? 'block' : 'none';
            // First, destroy any existing flatpickr instances to avoid conflicts
            if (document.getElementById('event_day')._flatpickr) {
                document.getElementById('event_day')._flatpickr.destroy();
            }
            if (document.getElementById('display_start_date')._flatpickr) {
                document.getElementById('display_start_date')._flatpickr.destroy();
            }
            if (document.getElementById('display_end_date')._flatpickr) {
                document.getElementById('display_end_date')._flatpickr.destroy();
            }
            // Set default values in the hidden inputs
            if (displayStartDate) {
                if (isDateRange) {
                    document.getElementById('display_start_date').value = displayStartDate;
                    document.getElementById('display_end_date').value = displayEndDate;
                } else {
                    document.getElementById('event_day').value = displayStartDate;
                }
            } else {
                // Wenn keine Daten vorhanden sind, Reservierungsdatum als Default
                document.getElementById('event_day').value = startDate;
                document.getElementById('display_start_date').value = startDate;
                document.getElementById('display_end_date').value = startDate;
            }
            // Re-initialize the flatpickr instances with the correct values
            const eventDayPicker = flatpickr('#event_day', {
                locale: "de",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j. F Y",
                disableMobile: "true",
                defaultDate: document.getElementById('event_day').value,
                minDate: startDate,
                maxDate: endDate,
                onChange: function (selectedDates, dateStr) {
                    if (selectedDates[0]) {
                        document.getElementById('display_start_date').value = dateStr;
                        document.getElementById('display_end_date').value = dateStr;
                        if (document.getElementById('display_end_date')._flatpickr) {
                            document.getElementById('display_end_date')._flatpickr.setDate(dateStr);
                        }
                        if (document.getElementById('display_start_date')._flatpickr) {
                            document.getElementById('display_start_date')._flatpickr.setDate(dateStr);
                        }
                    }
                }
            });
            const minDate = isDateRange && displayStartDate ? displayStartDate : startDate;
            const displayStartPicker = flatpickr('#display_start_date', {
                locale: "de",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j. F Y",
                disableMobile: "true",
                defaultDate: document.getElementById('display_start_date').value,
                minDate: startDate,
                maxDate: endDate,
                onChange: function (selectedDates, dateStr) {
                    if (selectedDates[0] && document.getElementById('display_end_date')._flatpickr) {
                        document.getElementById('display_end_date')._flatpickr.set('minDate', selectedDates[0]);
                    }
                }
            });
            const displayEndPicker = flatpickr('#display_end_date', {
                locale: "de",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j. F Y",
                disableMobile: "true",
                defaultDate: document.getElementById('display_end_date').value,
                minDate: minDate,
                maxDate: endDate
            });
        };
    });
</script>