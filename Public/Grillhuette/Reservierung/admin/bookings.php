<?php
require_once '../backend/includes/auth.php';
require_once '../backend/includes/booking.php';

$auth = new Auth();

// Redirect if not logged in or not admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$booking = new Booking();
$allBookings = $booking->getAllBookings();
$isLoggedIn = $auth->isLoggedIn();
$isAdmin = $auth->isAdmin();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buchungsverwaltung - Grillhütte Rechenbach</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../frontend/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php 
    $isInAdminArea = true;
    include '../includes/navigation.php'; 
    ?>

    <main class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="card-title mb-0">Buchungsverwaltung</h2>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary" id="exportBookings">
                                    <i class="bi bi-download me-2"></i>Exportieren
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Benutzer</th>
                                        <th>Datum</th>
                                        <th>Zeit</th>
                                        <th>Status</th>
                                        <th>Erstellt am</th>
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($allBookings['success']): ?>
                                        <?php foreach ($allBookings['bookings'] as $booking): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                                <td>
                                                    <?php 
                                                        $start_date = new DateTime($booking['start_date']);
                                                        $end_date = new DateTime($booking['end_date']);
                                                        
                                                        if ($booking['start_date'] === $booking['end_date']) {
                                                            // Single day booking
                                                            echo $start_date->format('d.m.Y');
                                                        } else {
                                                            // Multi-day booking
                                                            echo $start_date->format('d.m.Y') . ' - ' . $end_date->format('d.m.Y');
                                                        }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $start_time = new DateTime($booking['start_time']);
                                                        $end_time = new DateTime($booking['end_time']);
                                                        
                                                        if ($booking['start_date'] === $booking['end_date']) {
                                                            // Single day booking
                                                            echo '<span class="badge bg-info">';
                                                            echo $start_time->format('H:i') . ' - ' . $end_time->format('H:i');
                                                            echo '</span>';
                                                        } else {
                                                            // Multi-day booking
                                                            echo '<div class="small">';
                                                            echo '<span class="badge bg-primary">Tag 1: ' . $start_time->format('H:i') . ' Uhr</span><br>';
                                                            if ($start_date->diff($end_date)->days > 1) {
                                                                echo '<span class="badge bg-secondary">Zwischentage: Ganztägig</span><br>';
                                                            }
                                                            echo '<span class="badge bg-primary">Letzter Tag: ' . $end_time->format('H:i') . ' Uhr</span>';
                                                            echo '</div>';
                                                        }
                                                    ?>
                                                </td>
                                                <td>
                                                    <select class="form-select form-select-sm status-select" 
                                                            data-booking-id="<?php echo $booking['id']; ?>">
                                                        <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>
                                                            Ausstehend
                                                        </option>
                                                        <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>
                                                            Bestätigt
                                                        </option>
                                                        <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>
                                                            Storniert
                                                        </option>
                                                    </select>
                                                </td>
                                                <td><?php echo (new DateTime($booking['created_at']))->format('d.m.Y H:i'); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-danger delete-booking" 
                                                            data-booking-id="<?php echo $booking['id']; ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle status changes
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', async (e) => {
                const bookingId = e.target.dataset.bookingId;
                const status = e.target.value;
                
                try {
                    const response = await fetch('../backend/process_booking_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ booking_id: bookingId, status: status })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Fehler beim Aktualisieren des Status');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Ein Fehler ist aufgetreten');
                }
            });
        });

        // Handle booking deletion
        document.querySelectorAll('.delete-booking').forEach(button => {
            button.addEventListener('click', async (e) => {
                if (!confirm('Möchten Sie diese Buchung wirklich löschen?')) return;
                
                const bookingId = e.target.closest('button').dataset.bookingId;
                
                try {
                    const response = await fetch('../backend/process_booking_delete.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ booking_id: bookingId })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Fehler beim Löschen der Buchung');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Ein Fehler ist aufgetreten');
                }
            });
        });

        // Handle export
        document.getElementById('exportBookings').addEventListener('click', async () => {
            try {
                const response = await fetch('../backend/process_booking_export.php');
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'buchungen.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();
            } catch (error) {
                console.error('Error:', error);
                alert('Fehler beim Exportieren der Buchungen');
            }
        });
    </script>
</body>
</html> 