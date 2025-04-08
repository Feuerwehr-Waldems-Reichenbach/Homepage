
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
<!-- Socicon CSS -->
<link rel="stylesheet" href="../assets/socicon/css/styles.css">

<?php
$menu = [
    'Das sind wir' => [
        'submenu' => [
            'Einsatzabteilung' => [
                'submenu' => [
                    'Einsatzabteilung' => '/Einsatzabteilung',
                    'Fahrzeuge' => '/Fahrzeuge',
                    'Feuerwehrhaus' => '/Feuerwehrhaus'
                ]
            ],
            'Voraus-Helfer' => '/Voraus-Helfer',
            'Realistische Unfalldarstellung' => '/Realistische-Unfalldarstellung',
            'Jugendfeuerwehr' => '/Jugendfeuerwehr',
            'Kinderfeuerwehr' => '/Kinderfeuerwehr',
            'Förderverein' => '/Foerderverein'
        ]
    ],
    'Unterstützen' => [
        'submenu' => [
            'Mitmachen' => '/Mitmachen',
            'Unterstützen' => '/Unterstuetzen'
        ]
    ],
    'Neuigkeiten' => [
        'submenu' => [
            'Veranstaltungen' => '/Veranstaltungen',
            'Einsätze' => '/Einsaetze'
        ]
    ],
    'Grillhütte' => '/Grillhuette'
];

function renderMenu($items, $level = 0) {
    foreach ($items as $label => $item) {
        if (is_array($item) && isset($item['submenu'])) {
            echo '<li class="nav-item dropdown">';
            echo '<a class="nav-link link ' . ($level == 0 ? 'dropdown-toggle' : '') . ' text-white display-4" href="#" 
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">' . $label . '</a>';
            echo '<ul class="dropdown-menu">';
            renderMenu($item['submenu'], $level + 1);
            echo '</ul>';
            echo '</li>';
        } else {
            if ($level > 0) {
                echo '<li>';
                echo '<a class="dropdown-item text-white" href="' . $item . '">' . $label . '</a>';
                echo '</li>';
            } else {
                echo '<li class="nav-item">';
                echo '<a class="nav-link link text-white display-4" href="' . $item . '">' . $label . '</a>';
                echo '</li>';
            }
        }
    }
}
?>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid">
        <div class="navbar-brand">
            <span class="navbar-logo">
                <a href="/">
                    <img src="../assets/images/gravatar-logo-dunkel-1.webp" alt="Feuerwehr Reichenbach" style="height: 3rem;">
                </a>
            </span>
            <span class="navbar-caption-wrap">
                <a class="navbar-caption text-white display-5" href="/">Feuerwehr Reichenbach</a>
            </span>
        </div>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php renderMenu($menu); ?>
            </ul>
            <div class="icons-menu">
                <a class="iconfont-wrapper" href="https://www.facebook.com/groups/163127135137/" target="_blank">
                    <span class="p-2 mbr-iconfont socicon-facebook socicon"></span>
                </a>
                <a class="iconfont-wrapper" href="https://www.instagram.com/feuerwehrreichenbach/" target="_blank">
                    <span class="p-2 mbr-iconfont socicon-instagram socicon"></span>
                </a>
            </div>
        </div>
    </div>
</nav>

<style>
.navbar {
    background-color: #A72920;
    padding: 0.5rem 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.navbar-brand {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.navbar-logo img {
    transition: transform 0.3s ease;
}

.navbar-logo img:hover {
    transform: scale(1.05);
}

.navbar-caption {
    text-decoration: none;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.nav-link, .dropdown-item {
    color: white !important;
    padding: 0.5rem 1rem !important;
    border-radius: 4px;
    transition: all 0.2s ease;
    position: relative;
}

.nav-link:hover, .dropdown-item:hover {
    color: white !important;
    background-color: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
}

.dropdown-menu {
    background-color: #A72920;
    border: none;
    margin-top: 0;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    padding: 0.5rem;
}

.dropdown-menu .dropdown-menu {
    margin-left: 0;
}

@media (min-width: 992px) {
    .dropdown:hover > .dropdown-menu {
        display: block;
        animation: fadeIn 0.2s ease-in-out;
    }
    
    .dropdown-menu .dropdown:hover > .dropdown-menu {
        display: block;
        position: absolute;
        left: 100%;
        top: 0;
        animation: slideIn 0.2s ease-in-out;
    }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(-10px); }
    to { opacity: 1; transform: translateX(0); }
}

/* Hamburger Menu */
.navbar-toggler {
    border: none;
    padding: 0.5rem;
    transition: all 0.3s ease;
}

.hamburger {
    width: 30px;
    height: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.3s ease;
}

.hamburger span {
    display: block;
    height: 2px;
    width: 100%;
    background: white;
    border-radius: 2px;
    transition: all 0.3s ease;
}

.navbar-toggler:hover .hamburger span {
    background: rgba(255, 255, 255, 0.8);
}

/* Mobile Styles */
@media (max-width: 991px) {
    .navbar-collapse {
        background-color: #A72920;
        padding: 1rem;
        border-radius: 8px;
        margin-top: 0.5rem;
    }
    
    .dropdown-menu {
        background-color: rgba(167, 41, 32, 0.9);
        margin-left: 1rem;
        border-left: 2px solid rgba(255, 255, 255, 0.2);
    }
    
    .icons-menu {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        margin-top: 1.5rem;
        padding: 1rem 0;
    }
    
    .navbar-nav {
        margin-top: 1rem;
    }

    .nav-link, .dropdown-item {
        padding: 0.75rem 1rem !important;
    }
}

.icons-menu .iconfont-wrapper {
    text-decoration: none;
    transition: all 0.3s ease;
}

.icons-menu .iconfont-wrapper:hover {
    transform: translateY(-2px);
}

.mbr-iconfont {
    color: white;
    font-size: 1.5rem;
    transition: all 0.3s ease;
}

.mbr-iconfont:hover {
    color: rgba(255, 255, 255, 0.8);
}
</style>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script> 