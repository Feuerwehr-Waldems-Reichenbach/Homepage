<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

require_once __DIR__ . '/config.php';

// Generate a unique nonce for this request
$cspNonce = base64_encode(random_bytes(16));

// Content Security Policy definieren
$cspHeader = "Content-Security-Policy: ".
    "default-src 'self'; ".
    "script-src 'self' 'nonce-{$cspNonce}' https://cdn.jsdelivr.net/npm/ https://cdn.jsdelivr.net/npm/flatpickr/ https://cdn.jsdelivr.net/npm/moment@2.29.4/ https://www.google.com/maps/ https://maps.googleapis.com; ".
    "style-src 'self' https://cdn.jsdelivr.net/npm/ https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/ https://cdn.jsdelivr.net/npm/flatpickr/ 'unsafe-inline'; ".
    "img-src 'self' data: https://www.google.com/maps/ https://*.googleapis.com https://*.gstatic.com; ".
    "font-src 'self' https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/; ".
    "connect-src 'self'; ".
    "frame-src 'self' https://www.google.com/maps/ https://maps.googleapis.com; ".
    "object-src 'none'; ".
    "base-uri 'self'; ".
    "form-action 'self'; ".
    "frame-ancestors 'none'; ".
    (isSecureConnection() ? "upgrade-insecure-requests;" : "");

// Content Security Policy für den Meta-Tag (ohne frame-ancestors)
$cspMeta = "default-src 'self'; ".
    "script-src 'self' 'nonce-{$cspNonce}' https://cdn.jsdelivr.net/npm/ https://cdn.jsdelivr.net/npm/flatpickr/ https://cdn.jsdelivr.net/npm/moment@2.29.4/ https://www.google.com/maps/ https://maps.googleapis.com; ".
    "style-src 'self' https://cdn.jsdelivr.net/npm/ https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/ https://cdn.jsdelivr.net/npm/flatpickr/ 'unsafe-inline'; ".
    "img-src 'self' data: https://www.google.com/maps/ https://*.googleapis.com https://*.gstatic.com; ".
    "font-src 'self' https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/; ".
    "connect-src 'self'; ".
    "frame-src 'self' https://www.google.com/maps/ https://maps.googleapis.com; ".
    "object-src 'none'; ".
    "base-uri 'self'; ".
    "form-action 'self'; ".
    (isSecureConnection() ? "upgrade-insecure-requests;" : "");

// Weitere Sicherheits-Header
header($cspHeader);
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), camera=(), microphone=()");
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#343a40">
    <meta http-equiv="Content-Security-Policy" content="<?php echo $cspMeta; ?>">
    <link rel="shortcut icon" href="/Grillhuette/Reservierung/includes/favicon.ico" type="image/x-icon">
    <title>Reservierungssystem der Grillhütte Waldems Reichenbach</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="<?php echo str_repeat('../', substr_count($_SERVER['REQUEST_URI'], '/') - 3); ?>assets/css/style.css">
</head>
<body>
    <header class="bg-dark text-white">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand d-lg-welcome-separator" href="<?php echo getRelativePath('home'); ?>">Grillhütte Waldems Reichenbach</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Navigation umschalten">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Welcome message at the top of mobile menu -->
                    <div class="d-lg-none py-2 mb-2 border-bottom border-secondary">
                        <span class="text-white">Willkommen, <?php echo escape($_SESSION['user_name']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Main Navigation -->
                    <ul class="navbar-nav me-auto">
                        <!-- Allgemein Dropdown für Desktop -->
                        <li class="nav-item dropdown d-none d-lg-block">
                            <a class="nav-link dropdown-toggle" href="#" id="allgemeinDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Allgemein
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="allgemeinDropdown">
                                <li><a class="dropdown-item" href="/Grillhuette">
                                    <i class="bi bi-info-square me-2"></i>Informationen</a>
                                </li>
                                <li><a class="dropdown-item" href="<?php echo getRelativePath('home'); ?>">
                                    <i class="bi bi-house-door me-2"></i>Startseite</a>
                                </li>
                                <li><a class="dropdown-item" href="<?php echo getRelativePath('Anleitung'); ?>">
                                    <i class="bi bi-question-circle me-2"></i>Anleitung</a>
                                </li>
                            </ul>
                        </li>
                        
                        <!-- Mobile: Direkte Links statt Dropdown -->
                        <li class="nav-item d-lg-none">
                            <a class="nav-link" href="/Grillhuette">Informationen</a>
                        </li>
                        <li class="nav-item d-lg-none">
                            <a class="nav-link" href="<?php echo getRelativePath('home'); ?>">Startseite</a>
                        </li>
                        <li class="nav-item d-lg-none">
                            <a class="nav-link" href="<?php echo getRelativePath('Anleitung'); ?>">Anleitung</a>
                        </li>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <!-- Benutzer Dropdown für Desktop -->
                            <li class="nav-item dropdown d-none d-lg-block">
                                <a class="nav-link dropdown-toggle" href="#" id="benutzerDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Mein Bereich
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="benutzerDropdown">
                                    <li><a class="dropdown-item" href="<?php echo getRelativePath('Benutzer/Meine-Reservierungen'); ?>">
                                        <i class="bi bi-calendar-check me-2"></i>Meine Reservierungen</a>
                                    </li>
                                    <li><a class="dropdown-item" href="<?php echo getRelativePath('Benutzer/Profil'); ?>">
                                        <i class="bi bi-person me-2"></i>Mein Profil</a>
                                    </li>
                                </ul>
                            </li>
                            
                            <!-- Mobile: Direkte Links statt Dropdown -->
                            <li class="nav-item d-lg-none">
                                <span class="nav-link text-muted ps-2">Mein Bereich</span>
                            </li>
                            <li class="nav-item d-lg-none">
                                <a class="nav-link ps-4" href="<?php echo getRelativePath('Benutzer/Meine-Reservierungen'); ?>">
                                    <i class="bi bi-calendar-check me-2"></i>Meine Reservierungen
                                </a>
                            </li>
                            <li class="nav-item d-lg-none">
                                <a class="nav-link ps-4" href="<?php echo getRelativePath('Benutzer/Profil'); ?>">
                                    <i class="bi bi-person me-2"></i>Mein Profil
                                </a>
                            </li>
                            
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                <!-- Admin Dropdown für Desktop -->
                                <li class="nav-item dropdown d-none d-lg-block">
                                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Administration
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                        <li><a class="dropdown-item" href="<?php echo getRelativePath('Admin/Reservierungsverwaltung'); ?>">
                                            <i class="bi bi-calendar-check me-2"></i>Reservierungen verwalten</a>
                                        </li>
                                        <li><a class="dropdown-item" href="<?php echo getRelativePath('Admin/Benutzerverwaltung'); ?>">
                                            <i class="bi bi-people me-2"></i>Benutzer verwalten</a>
                                        </li>
                                        <li><a class="dropdown-item" href="<?php echo getRelativePath('Admin/Informationsverwaltung'); ?>">
                                            <i class="bi bi-info-circle me-2"></i>Informationen verwalten</a>
                                        </li>
                                    </ul>
                                </li>
                                
                                <!-- Mobile: Direkte Links statt Dropdown -->
                                <li class="nav-item d-lg-none">
                                    <span class="nav-link text-muted ps-2">Administration</span>
                                </li>
                                <li class="nav-item d-lg-none">
                                    <a class="nav-link ps-4" href="<?php echo getRelativePath('Admin/Reservierungsverwaltung'); ?>">
                                        <i class="bi bi-calendar-check me-2"></i>Reservierungen verwalten
                                    </a>
                                </li>
                                <li class="nav-item d-lg-none">
                                    <a class="nav-link ps-4" href="<?php echo getRelativePath('Admin/Benutzerverwaltung'); ?>">
                                        <i class="bi bi-people me-2"></i>Benutzer verwalten
                                    </a>
                                </li>
                                <li class="nav-item d-lg-none">
                                    <a class="nav-link ps-4" href="<?php echo getRelativePath('Admin/Informationsverwaltung'); ?>">
                                        <i class="bi bi-info-circle me-2"></i>Informationen verwalten
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                    
                    <!-- User Authentication -->
                    <ul class="navbar-nav align-items-center">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item d-none d-lg-flex welcome-item me-3">
                                <span class="nav-link user-welcome"><i class="bi bi-person-circle me-1"></i>Willkommen, <?php echo escape($_SESSION['user_name']); ?></span>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link btn btn-outline-light btn-sm px-3" href="<?php echo getRelativePath('Benutzer/Abmelden'); ?>">
                                    <i class="bi bi-box-arrow-right me-1"></i>Abmelden
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item me-2">
                                <a class="nav-link btn btn-outline-light btn-sm px-3" href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Anmelden
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link btn btn-primary btn-sm px-3" href="<?php echo getRelativePath('Benutzer/Registrieren'); ?>">
                                    <i class="bi bi-person-plus me-1"></i>Registrieren
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container py-4">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['flash_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
            </div>
            <?php 
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
            ?>
        <?php endif; ?> 