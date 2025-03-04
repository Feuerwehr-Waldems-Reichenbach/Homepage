<?php
require_once 'backend/includes/auth.php';
require_once 'backend/includes/booking.php';

$auth = new Auth();

// Redirect if not logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$booking = new Booking();

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
            $isStartDate = $date->format('Y-m-d') === $booking['start_date'];
            $isEndDate = $date->format('Y-m-d') === $booking['end_date'];
            
            $bookedDates[] = [
                'date' => $date->format('Y-m-d'),
                'start_time' => $isStartDate ? $booking['start_time'] : '00:00',
                'end_time' => $isEndDate ? $booking['end_time'] : '23:59',
                'isFullDay' => !$isStartDate && !$isEndDate,
                'status' => $booking['status']
            ];
        }
    }
}

$isLoggedIn = $auth->isLoggedIn();
$isAdmin = $auth->isAdmin();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalender - Grillhütte Rechenbach</title>
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
                        <h2 class="card-title mb-4">Kalender</h2>
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-4">Neue Buchung</h3>
                        <form id="bookingForm" method="POST" action="backend/process_booking.php">
                            <input type="hidden" id="start_date" name="start_date" required>
                            <input type="hidden" id="end_date" name="end_date" required>

                            <div class="mb-3">
                                <label for="start_date_input" class="form-label">Startdatum</label>
                                <input type="date" class="form-control" id="start_date_input" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="end_date_input" class="form-label">Enddatum</label>
                                <input type="date" class="form-control" id="end_date_input" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="start_time" class="form-label">Startzeit</label>
                                <input type="text" class="form-control" id="start_time" name="start_time" 
                                       required readonly placeholder="Startzeit auswählen">
                            </div>

                            <div class="mb-3">
                                <label for="end_time" class="form-label">Endzeit</label>
                                <input type="text" class="form-control" id="end_time" name="end_time" 
                                       required readonly placeholder="Endzeit auswählen">
                            </div>

                            <div class="mb-4">
                                <label for="message" class="form-label">Nachricht (optional)</label>
                                <textarea class="form-control" id="message" name="message" rows="3" 
                                          placeholder="Zusätzliche Informationen zur Buchung"></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" disabled>
                                    <i class="bi bi-calendar-check me-2"></i>Buchung erstellen
                                </button>
                            </div>
                        </form>
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
                                <span class="d-inline-block bg-warning rounded me-2" style="width: 20px; height: 20px;"></span>
                                Teilweise gebucht
                            </li>
                            <li class="mb-2">
                                <span class="d-inline-block bg-danger rounded me-2" style="width: 20px; height: 20px;"></span>
                                Komplett gebucht
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
                                <i class="bi bi-info-circle text-primary me-2"></i>
                                Wählen Sie zuerst ein Start- und Enddatum im Kalender
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-clock text-primary me-2"></i>
                                Buchungen sind zwischen 8:00 und 20:00 Uhr möglich
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-calendar-check text-primary me-2"></i>
                                Bereits gebuchte Termine sind im Kalender markiert
                            </li>
                            <li>
                                <i class="bi bi-exclamation-triangle text-primary me-2"></i>
                                Buchungen können bis 24 Stunden vorher storniert werden
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Booking Success Modal -->
    <div class="modal fade" id="bookingSuccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buchung erfolgreich</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-check-circle text-success display-1 mb-3"></i>
                    <p class="lead mb-0">Ihre Buchung wurde erfolgreich erstellt!</p>
                    <p class="text-muted">Sie werden in Kürze zu Ihrem Dashboard weitergeleitet.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="frontend/js/main.js"></script>
    <script src="frontend/js/calendar.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const calendarContainer = document.getElementById('calendar');
            if (calendarContainer) {
                const bookedDates = <?php echo json_encode($bookedDates); ?>;
                new Calendar('calendar', {
                    bookedDates: bookedDates,
                    minDate: new Date(),
                    showBookingTimes: true
                });
            }
        });
    </script>
</body>
</html> 