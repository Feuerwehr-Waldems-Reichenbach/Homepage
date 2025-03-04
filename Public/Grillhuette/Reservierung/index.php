<?php
require_once 'backend/includes/auth.php';
require_once 'backend/includes/booking.php';

$auth = new Auth();
$booking = new Booking();
$isLoggedIn = $auth->isLoggedIn();
$isAdmin = $auth->isAdmin();

// Get all bookings for calendar display
$allBookings = $booking->getAllBookings();
$bookedDates = [];
if ($allBookings['success']) {
    foreach ($allBookings['bookings'] as $booking) {
        $start = new DateTime($booking['start_date']);
        $end = new DateTime($booking['end_date']);
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($start, $interval, $end->modify('+1 day'));
        
        foreach ($dateRange as $date) {
            $bookedDates[] = [
                'date' => $date->format('Y-m-d'),
                'start_time' => $booking['start_time'],
                'end_time' => $booking['end_time']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grillhütte Rechenbach - Buchungssystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="frontend/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <main class="container my-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Verfügbarkeit der Grillhütte</h2>
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title h4 mb-4">Informationen</h3>
                        <?php if (!$isLoggedIn): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Bitte melden Sie sich an, um eine Buchung vorzunehmen.
                            </div>
                            <div class="d-grid gap-2">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Anmelden
                                </a>
                                <a href="register.php" class="btn btn-outline-primary">
                                    <i class="bi bi-person-plus me-2"></i>Registrieren
                                </a>
                            </div>
                        <?php else: ?>
                            <a href="calendar.php" class="btn btn-primary d-block">
                                <i class="bi bi-calendar-plus me-2"></i>Neue Buchung
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h4 class="card-title h5 mb-3">Legende</h4>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <span class="d-inline-block bg-white border rounded me-2" style="width: 20px; height: 20px;"></span>
                                Verfügbar
                            </li>
                            <li class="mb-2">
                                <span class="d-inline-block bg-secondary rounded me-2" style="width: 20px; height: 20px;"></span>
                                Gebucht
                            </li>
                            <li>
                                <span class="d-inline-block border border-primary rounded me-2" style="width: 20px; height: 20px;"></span>
                                Heute
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h4 class="card-title h5 mb-3">Hinweise</h4>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-clock text-primary me-2"></i>
                                Buchungen sind zwischen 8:00 und 20:00 Uhr möglich
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-calendar-check text-primary me-2"></i>
                                Buchungen müssen innerhalb von 7 Tagen bestätigt werden
                            </li>
                            <li>
                                <i class="bi bi-exclamation-triangle text-primary me-2"></i>
                                Stornierungen sind bis 24 Stunden vorher möglich
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="frontend/js/main.js"></script>
    <script src="frontend/js/calendar.js"></script>
    <script>
        // Initialize calendar with booked dates
        document.addEventListener('DOMContentLoaded', () => {
            const calendarContainer = document.getElementById('calendar');
            if (calendarContainer) {
                const bookedDates = <?php echo json_encode($bookedDates); ?>;
                new Calendar('calendar', {
                    bookedDates: bookedDates,
                    isReadOnly: <?php echo $isLoggedIn ? 'false' : 'true'; ?>,
                    minDate: new Date(),
                    showBookingTimes: true
                });
            }
        });
    </script>

    <?php if ($isLoggedIn && $auth->isAdmin()): ?>
    <!-- New User Modal -->
    <div class="modal fade" id="newUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Neuen Benutzer anlegen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="newUserForm">
                        <div class="mb-3">
                            <label for="new_user_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="new_user_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_user_email" class="form-label">E-Mail-Adresse</label>
                            <input type="email" class="form-control" id="new_user_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_user_password" class="form-label">Passwort</label>
                            <input type="password" class="form-control" id="new_user_password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="new_user_is_admin" name="is_admin">
                                <label class="form-check-label" for="new_user_is_admin">Als Administrator anlegen</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" form="newUserForm" class="btn btn-primary">Benutzer anlegen</button>
                </div>
            </div>
        </div>
    </div>

    <!-- New Booking Modal -->
    <div class="modal fade" id="newBookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Neue Buchung erstellen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="adminBookingForm">
                        <div class="mb-3">
                            <label for="booking_user" class="form-label">Benutzer</label>
                            <select class="form-select" id="booking_user" name="user_id" required>
                                <option value="">Benutzer auswählen...</option>
                                <!-- Will be populated via AJAX -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="booking_start_date" class="form-label">Startdatum</label>
                            <input type="date" class="form-control" id="booking_start_date" name="start_date" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="booking_end_date" class="form-label">Enddatum</label>
                            <input type="date" class="form-control" id="booking_end_date" name="end_date" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="booking_start_time" class="form-label">Startzeit</label>
                            <input type="time" class="form-control" id="booking_start_time" name="start_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="booking_end_time" class="form-label">Endzeit</label>
                            <input type="time" class="form-control" id="booking_end_time" name="end_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="booking_message" class="form-label">Nachricht (optional)</label>
                            <textarea class="form-control" id="booking_message" name="message" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" form="adminBookingForm" class="btn btn-primary">Buchung erstellen</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html> 