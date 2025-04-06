<?php
// This is a test script to add a reservation to the database
// IMPORTANT: Only run this in development or testing environments!

require_once '../includes/config.php';
require_once '../includes/Reservation.php';

// Check if the user is an admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Nur Administratoren können Test-Reservierungen erstellen.'
    ]);
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Ungültige Anfrage. CSRF-Token fehlt oder ist ungültig.'
        ]);
        exit;
    }
    
    // Get form data
    $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
    $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime('+1 day'));
    $status = isset($_POST['status']) ? $_POST['status'] : 'confirmed';
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    $eventName = isset($_POST['event_name']) ? $_POST['event_name'] : 'Test Veranstaltung';
    
    // Format dates
    $startDateTime = $startDate . ' 00:00:00';
    $endDateTime = $endDate . ' 23:59:59';
    
    try {
        // Create reservation object
        $reservation = new Reservation();
        
        // Add reservation
        $result = $reservation->createByAdmin(
            $_SESSION['user_id'],  // Using the current admin user
            $startDateTime,
            $endDateTime,
            'Test Reservation', // Admin message
            0, // Receipt requested
            $isPublic,
            $eventName,
            $startDate, // Display start date (same as start date)
            $endDate    // Display end date (same as end date)
        );
        
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Fehler: ' . $e->getMessage()
        ]);
        exit;
    }
}

// HTML form for adding a test reservation
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test-Reservierung erstellen</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h2 class="card-title">Test-Reservierung erstellen</h2>
                        <p class="mb-0 text-danger">Nur für Testzwecke!</p>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Startdatum:</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="end_date" class="form-label">Enddatum:</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status:</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="confirmed">Bestätigt</option>
                                    <option value="pending">Ausstehend</option>
                                </select>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_public" name="is_public">
                                <label class="form-check-label" for="is_public">Öffentliche Veranstaltung</label>
                            </div>
                            
                            <div class="mb-3" id="event_name_container">
                                <label for="event_name" class="form-label">Veranstaltungsname:</label>
                                <input type="text" class="form-control" id="event_name" name="event_name" value="Test Veranstaltung">
                            </div>
                            
                            <button type="submit" class="btn btn-warning">Test-Reservierung erstellen</button>
                            <a href="../" class="btn btn-secondary ms-2">Zurück zur Hauptseite</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle event name field visibility based on public checkbox
        document.addEventListener('DOMContentLoaded', function() {
            const isPublicCheckbox = document.getElementById('is_public');
            const eventNameContainer = document.getElementById('event_name_container');
            
            function updateEventNameVisibility() {
                eventNameContainer.style.display = isPublicCheckbox.checked ? 'block' : 'none';
            }
            
            // Set initial state
            updateEventNameVisibility();
            
            // Add event listener
            isPublicCheckbox.addEventListener('change', updateEventNameVisibility);
        });
    </script>
</body>
</html> 