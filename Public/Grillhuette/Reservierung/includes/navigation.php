<?php
$current_page = basename($_SERVER['PHP_SELF']);
$basePath = isset($isInAdminArea) ? '../' : '';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $basePath; ?>index.php">Grillhütte Rechenbach</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'calendar.php' ? 'active' : ''; ?>" 
                           href="<?php echo $basePath; ?>calendar.php">
                            <i class="bi bi-calendar me-1"></i>Kalender
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" 
                           href="<?php echo $basePath; ?>dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <?php if ($isAdmin): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo strpos($current_page, 'bookings.php') !== false || strpos($current_page, 'users.php') !== false ? 'active' : ''; ?>" 
                               href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-shield-lock me-1"></i>Admin
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?php echo $basePath; ?>admin/bookings.php">
                                        <i class="bi bi-calendar-check me-2"></i>Buchungen verwalten
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $basePath; ?>admin/users.php">
                                        <i class="bi bi-people me-2"></i>Benutzer verwalten
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#newUserModal">
                                        <i class="bi bi-person-plus me-2"></i>Neuen Benutzer anlegen
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#newBookingModal">
                                        <i class="bi bi-calendar-plus me-2"></i>Neue Buchung erstellen
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $basePath; ?>backend/process_logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Abmelden
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'login.php' ? 'active' : ''; ?>" 
                           href="<?php echo $basePath; ?>login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Anmelden
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>" 
                           href="<?php echo $basePath; ?>register.php">
                            <i class="bi bi-person-plus me-1"></i>Registrieren
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav> 