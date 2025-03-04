<?php
require_once 'backend/includes/auth.php';
require_once 'backend/includes/booking.php';
require_once 'backend/includes/user.php';

$auth = new Auth();

// Redirect if not logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$booking = new Booking();
$user = new User();

// Get user's bookings and profile
$bookingsResult = $booking->getUserBookings();
$profileResult = $user->getProfile();

$bookings = $bookingsResult['success'] ? $bookingsResult['bookings'] : [];
$profile = $profileResult['success'] ? $profileResult['profile'] : null;

$isLoggedIn = $auth->isLoggedIn();
$isAdmin = $auth->isAdmin();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Grillhütte Rechenbach</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="frontend/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <main class="container my-5">
        <div class="row">
            <!-- Profile Section -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-4">
                            <div class="flex-shrink-0">
                                <i class="bi bi-person-circle display-5 text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h2 class="h4 mb-1"><?php echo htmlspecialchars($profile['name']); ?></h2>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($profile['email']); ?></p>
                            </div>
                        </div>

                        <button class="btn btn-outline-primary w-100 mb-3" 
                                data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="bi bi-pencil-square me-2"></i>Profil bearbeiten
                        </button>
                        
                        <button class="btn btn-outline-secondary w-100" 
                                data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="bi bi-key me-2"></i>Passwort ändern
                        </button>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="h5 mb-3">Schnellzugriff</h3>
                        <div class="d-grid gap-2">
                            <a href="calendar.php" class="btn btn-primary">
                                <i class="bi bi-calendar-plus me-2"></i>Neue Buchung
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bookings Section -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Meine Buchungen</h2>
                        
                        <?php if (empty($bookings)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-calendar-x display-4 text-muted mb-3"></i>
                                <p class="text-muted mb-0">Sie haben noch keine Buchungen vorgenommen.</p>
                                <a href="calendar.php" class="btn btn-primary mt-3">Jetzt buchen</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Datum</th>
                                            <th>Zeit</th>
                                            <th>Status</th>
                                            <th>Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr data-booking-id="<?php echo $booking['id']; ?>">
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
                                                            echo $start_time->format('H:i') . ' - ' . $end_time->format('H:i');
                                                        } else {
                                                            // Multi-day booking
                                                            echo 'Tag 1: ' . $start_time->format('H:i') . ' Uhr<br>';
                                                            echo 'Letzter Tag: ' . $end_time->format('H:i') . ' Uhr<br>';
                                                            echo '<small class="text-muted">Zwischentage: Ganztägig</small>';
                                                        }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                        $status_classes = [
                                                            'pending' => 'warning',
                                                            'confirmed' => 'success',
                                                            'cancelled' => 'danger'
                                                        ];
                                                        $status_labels = [
                                                            'pending' => 'Ausstehend',
                                                            'confirmed' => 'Bestätigt',
                                                            'cancelled' => 'Storniert'
                                                        ];
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_classes[$booking['status']]; ?>">
                                                        <?php echo $status_labels[$booking['status']]; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($booking['status'] !== 'cancelled'): ?>
                                                        <button class="btn btn-sm btn-outline-danger cancel-booking-btn"
                                                                data-booking-id="<?php echo $booking['id']; ?>">
                                                            <i class="bi bi-x-circle me-1"></i>Stornieren
                                                        </button>
                                                    <?php endif; ?>
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
    </main>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Profil bearbeiten</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="profileForm">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($profile['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-Mail-Adresse</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" form="profileForm" class="btn btn-primary">Speichern</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Passwort ändern</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="passwordForm">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Aktuelles Passwort</label>
                            <input type="password" class="form-control" id="current_password" 
                                   name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Neues Passwort</label>
                            <input type="password" class="form-control" id="new_password" 
                                   name="new_password" required minlength="8">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Passwort bestätigen</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" form="passwordForm" class="btn btn-primary">Speichern</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="frontend/js/main.js"></script>
</body>
</html> 