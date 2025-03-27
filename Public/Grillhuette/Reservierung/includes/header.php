<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#343a40">
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
                <a class="navbar-brand" href="<?php echo getRelativePath('home'); ?>">Grillhütte Waldems Reichenbach</a>
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
                    <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                            <a class="nav-link" href="/Grillhuette">Informationen</a>
                    </li>
                    <li class="nav-item">
                            <a class="nav-link" href="<?php echo getRelativePath('home'); ?>">Startseite</a>
                        </li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getRelativePath('Benutzer/Meine-Reservierungen'); ?>">Meine Reservierungen</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getRelativePath('Benutzer/Profil'); ?>">Mein Profil</a>
                            </li>
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Administration
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                        <li><a class="dropdown-item" href="<?php echo getRelativePath('Admin/Reservierungsverwaltung'); ?>">Reservierungen verwalten</a></li>
                                        <li><a class="dropdown-item" href="<?php echo getRelativePath('Admin/Benutzerverwaltung'); ?>">Benutzer verwalten</a></li>
                                    </ul>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item d-none d-lg-block">
                                <span class="nav-link user-welcome">Willkommen, <?php echo escape($_SESSION['user_name']); ?></span>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getRelativePath('Benutzer/Abmelden'); ?>">Abmelden</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>">Anmelden</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getRelativePath('Benutzer/Registrieren'); ?>">Registrieren</a>
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