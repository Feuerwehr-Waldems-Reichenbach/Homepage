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
    <link href="/assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/assets/fonts/font-awesome/css/all.min.css">
    <!-- Google Fonts -->
    <link href="/assets/fonts/inter-tight/inter-tight.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="/assets/dataTables/css/dataTables.bootstrap5.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="/assets/sweetalert2/sweetalert2.min.css">
    <!-- CKEditor -->
    <script src="/assets/ckeditor/ckeditor.js"></script>
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo $ADMIN_ROOT; ?>/dashboard.php">
                    <i class="fas fa-fire me-2"></i>Verwaltungssystem
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>"
                                href="<?php echo $ADMIN_ROOT; ?>/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/einsatz/') !== false ? 'active' : ''; ?>"
                                href="<?php echo $ADMIN_ROOT; ?>/einsatz/list.php">
                                <i class="fas fa-fire-extinguisher me-1"></i> Einsätze
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/neuigkeiten/') !== false ? 'active' : ''; ?>"
                                href="<?php echo $ADMIN_ROOT; ?>/neuigkeiten/list.php">
                                <i class="fas fa-newspaper me-1"></i> Neuigkeiten
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/Jahresplan/') !== false ? 'active' : ''; ?>"
                                href="<?php echo $ADMIN_ROOT; ?>/Jahresplan/index.php">
                                <i class="fas fa-calendar-alt me-1"></i> Jahresplanung
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $ADMIN_ROOT; ?>/dokumente.php">
                                <i class="fas fa-file-alt me-1"></i> Dokumente
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo (strpos($_SERVER['REQUEST_URI'], '/auth-schluessel/') !== false || strpos($_SERVER['REQUEST_URI'], '/users/') !== false) ? 'active' : ''; ?>"
                                href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <i class="fas fa-cog me-1"></i> Verwaltung
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                <li>
                                    <h6 class="dropdown-header"><i class="fas fa-users me-1"></i> Benutzer</h6>
                                </li>
                                <li><a class="dropdown-item" href="<?php echo $ADMIN_ROOT; ?>/users/list.php">Alle
                                        Benutzer</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <h6 class="dropdown-header"><i class="fas fa-key me-1"></i> Auth-Schlüssel</h6>
                                </li>
                                <li><a class="dropdown-item"
                                        href="<?php echo $ADMIN_ROOT; ?>/auth-schluessel/list.php">Alle Schlüssel</a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <h6 class="dropdown-header"><i class="fas fa-shield-alt me-1"></i>
                                        Benachrichtigungen</h6>
                                </li>
                                <li><a class="dropdown-item"
                                        href="<?php echo $ADMIN_ROOT; ?>/datenschutz-benachrichtigung.php">Datenschutz-Update</a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                <li><a class="dropdown-item" href="<?php echo $ADMIN_ROOT; ?>/users/profile.php"><i
                                            class="fas fa-id-card me-1"></i> Profil</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?php echo $ADMIN_ROOT; ?>/auth/logout.php"><i
                                            class="fas fa-sign-out-alt me-1"></i> Abmelden</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="<?php echo isset($useFluidContainer) && $useFluidContainer ? 'container-fluid' : 'container'; ?> mt-4">
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