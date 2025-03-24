<?php
require_once 'includes/header.php';

// Reservation-Objekt für Kalenderdaten
require_once 'includes/Reservation.php';
$reservation = new Reservation();

// Aktueller Monat und Jahr
$currentMonth = date('n');
$currentYear = date('Y');

// Überprüfen, ob ein bestimmter Monat und Jahr in der URL angegeben wurden
if (isset($_GET['month']) && isset($_GET['year'])) {
    $month = intval($_GET['month']);
    $year = intval($_GET['year']);
    
    // Validieren
    if ($month >= 1 && $month <= 12 && $year >= date('Y')) {
        $currentMonth = $month;
        $currentYear = $year;
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1 class="mb-4">Grillhütte Reservierungssystem</h1>
        
        <!-- Neues Layout: Kalender (70%) und Eingabefelder (30%) nebeneinander -->
        <div class="row">
            <!-- Kalender Container (70%) -->
            <div class="col-md-8">
                <div class="calendar-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <button id="prevMonth" class="btn btn-outline-secondary">
                            <i class="bi bi-chevron-left"></i> Vorheriger Monat
                        </button>
                        <h3 id="monthYear" class="mb-0">
                            <?php 
                            $monthNames = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
                            echo $monthNames[$currentMonth - 1] . ' ' . $currentYear; 
                            ?>
                        </h3>
                        <button id="nextMonth" class="btn btn-outline-secondary">
                            Nächster Monat <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                    
                    <div id="calendar">
                        <!-- Kalender wird durch JavaScript gerendert -->
                    </div>
                    
                    <form id="calendarForm" method="get" class="d-none">
                        <input type="hidden" id="month" name="month" value="<?php echo $currentMonth; ?>">
                        <input type="hidden" id="year" name="year" value="<?php echo $currentYear; ?>">
                    </form>
                </div>
                
                <!-- Willkommenskarte unter dem Kalender -->
                <div class="card mb-4 mt-4">
                    <div class="card-body">
                        <p>Willkommen im Reservierungssystem der Grillhütte Reichenbach. Hier können Sie freie Termine einsehen und eine Reservierung vornehmen.</p>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="me-2" style="width: 20px; height: 20px; background-color: #d4edda; border-radius: 3px;"></div>
                                    <span>Frei</span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="me-2" style="width: 20px; height: 20px; background-color: #fff3cd; border-radius: 3px;"></div>
                                    <span>Anfrage in Bearbeitung</span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="me-2" style="width: 20px; height: 20px; background-color: #f8d7da; border-radius: 3px;"></div>
                                    <span>Belegt</span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <div class="alert alert-info">
                                Um eine Reservierung vorzunehmen, müssen Sie sich <a href="login.php">anmelden</a> oder <a href="register.php">registrieren</a>.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Manuelle Eingabefelder (30%) -->
            <div class="col-md-4">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['is_verified']): ?>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Neue Reservierung</h4>
                        </div>
                        <div class="card-body">
                            <form id="reservationForm" method="post" action="create_reservation.php">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Startdatum</label>
                                    <input type="text" class="form-control" id="start_date" name="start_date" readonly required>
                                </div>
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">Enddatum</label>
                                    <input type="text" class="form-control" id="end_date" name="end_date" readonly required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">Startzeit</label>
                                    <input type="text" class="form-control time-picker" id="start_time" name="start_time" value="12:00" required>
                                </div>
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">Endzeit</label>
                                    <input type="text" class="form-control time-picker" id="end_time" name="end_time" value="12:00" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="message" class="form-label">Nachricht / Anmerkungen (optional)</label>
                                    <textarea class="form-control" id="message" name="message" rows="3"></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Reservierung anfragen</button>
                            </form>
                        </div>
                    </div>
                <?php elseif (isset($_SESSION['user_id']) && !$_SESSION['is_verified']): ?>
                    <div class="alert alert-warning">
                        Bitte bestätigen Sie Ihre E-Mail-Adresse, um eine Reservierung vornehmen zu können.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 