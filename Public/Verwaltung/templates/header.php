<?php
// Set security headers
require_once dirname(__DIR__) . '/includes/Security.php';
Security::setSecurityHeaders();

// Check if user is not logged in, then redirect to login page
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $domain = $_SERVER['HTTP_HOST'];
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $verwaltungPos = strpos($scriptPath, '/Verwaltung/');
    if ($verwaltungPos !== false) {
        $verwaltungPath = substr($scriptPath, 0, $verwaltungPos) . '/Verwaltung';
        header('Location: ' . $protocol . $domain . $verwaltungPath . '/index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

// Generate CSRF token if not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Erstelle einen absoluten Basis-URL für die Verwaltungsseite
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];
$scriptPath = $_SERVER['SCRIPT_NAME'];
$verwaltungPos = strpos($scriptPath, '/Verwaltung/');
if ($verwaltungPos !== false) {
    $ADMIN_ROOT = $protocol . $domain . substr($scriptPath, 0, $verwaltungPos) . '/Verwaltung';
} else {
    $ADMIN_ROOT = $protocol . $domain . dirname($_SERVER['PHP_SELF']);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Verwaltungssystem'; ?></title>
    <link rel="stylesheet" href="<?php echo $ADMIN_ROOT; ?>/assets/css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <!-- CKEditor -->
    <script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo $ADMIN_ROOT; ?>/dashboard.php">
                    <i class="fas fa-fire me-2"></i>Verwaltungssystem
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/">
                                <i class="fas fa-globe me-1"></i> Website
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo $ADMIN_ROOT; ?>/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="einsatzDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-fire-extinguisher me-1"></i> Einsätze
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="einsatzDropdown">
                                <li><a class="dropdown-item" href="<?php echo $ADMIN_ROOT; ?>/einsatz/list.php">Alle Einsätze</a></li>
                                <li><a class="dropdown-item" href="<?php echo $ADMIN_ROOT; ?>/einsatz/create.php">Neuer Einsatz</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="newsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-newspaper me-1"></i> Neuigkeiten
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="newsDropdown">
                                <li><a class="dropdown-item" href="<?php echo $ADMIN_ROOT; ?>/neuigkeiten/list.php">Alle Neuigkeiten</a></li>
                                <li><a class="dropdown-item" href="<?php echo $ADMIN_ROOT; ?>/neuigkeiten/create.php">Neue Neuigkeit</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="authDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-key me-1"></i> Auth-Schlüssel
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="authDropdown">
                                <li><a class="dropdown-item" href="<?php echo $ADMIN_ROOT; ?>/auth-schluessel/list.php">Alle Schlüssel</a></li>
                                <li><a class="dropdown-item" href="<?php echo $ADMIN_ROOT; ?>/auth-schluessel/create.php">Neuer Schlüssel</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-users me-1"></i> Benutzer
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo $ADMIN_ROOT; ?>/users/list.php">Alle Benutzer</a></li>
                                <li><a class="dropdown-item" href="<?php echo $ADMIN_ROOT; ?>/users/create.php">Neuer Benutzer</a></li>
                            </ul>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                <li><a class="dropdown-item" href="<?php echo $ADMIN_ROOT; ?>/users/profile.php"><i class="fas fa-id-card me-1"></i> Profil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $ADMIN_ROOT; ?>/auth/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Abmelden</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container mt-4">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <h1 class="mb-4 text-white text-shadow"><?php echo $pageTitle ?? 'Verwaltungssystem'; ?></h1> 