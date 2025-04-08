<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
<!-- Socicon CSS -->
<link rel="stylesheet" href="../assets/socicon/css/styles.css">
<!-- Dropdown CSS -->
<link rel="stylesheet" href="../assets/dropdown/css/style.css">

<?php
$menu = [
    'Das sind wir' => [
        'submenu' => [
            'Einsatzabteilung' => [
                'submenu' => [
                    'Übersicht' => '/Einsatzabteilung',
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
            if ($level == 0) {
                echo '<li class="nav-item dropdown">';
                echo '<a class="nav-link dropdown-toggle text-white display-4" href="#" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">' . $label . '</a>';
            } else {
                echo '<li class="dropdown">';
                echo '<a class="dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">' . $label . '</a>';
            }
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
                echo '<a class="nav-link text-white display-4" href="' . $item . '">' . $label . '</a>';
                echo '</li>';
            }
        }
    }
}
?>

<nav class="navbar navbar-dropdown navbar-fixed-top navbar-expand-lg">
    <div class="container">
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
            <ul class="navbar-nav nav-dropdown ms-auto">
                <?php renderMenu($menu); ?>
            </ul>
            
            <div class="navbar-buttons mbr-section-btn">
                <a class="btn btn-sm" href="https://www.facebook.com/groups/163127135137/" target="_blank">
                    <span class="socicon-facebook socicon"></span>
                </a>
                <a class="btn btn-sm" href="https://www.instagram.com/feuerwehrreichenbach/" target="_blank">
                    <span class="socicon-instagram socicon"></span>
                </a>
            </div>
        </div>
    </div>
</nav>

<style>
.navbar {
    background-color: #A72920;
    padding: 0.5rem 1rem;
}

.navbar-brand {
    display: flex;
    align-items: center;
}

.navbar-caption {
    text-decoration: none;
}

.dropdown-menu {
    background-color: #A72920;
    border: none;
    border-radius: 0;
}

.dropdown-menu .dropdown-menu {
    background-color: #8f221a;
}

.dropdown-toggle::after {
    display: inline-block;
    margin-left: 0.255em;
    vertical-align: 0.255em;
    content: "";
    border-top: 0.3em solid;
    border-right: 0.3em solid transparent;
    border-left: 0.3em solid transparent;
}

.dropdown-item.dropdown-toggle::after {
    border-top: 0.3em solid transparent;
    border-right: 0;
    border-bottom: 0.3em solid transparent;
    border-left: 0.3em solid;
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
}

.navbar-buttons.mbr-section-btn .btn {
    background-color: transparent;
    border: none;
    color: white;
    padding: 0.5rem;
}

.navbar-buttons.mbr-section-btn .socicon {
    font-size: 1.5rem;
}

@media (max-width: 991px) {
    .navbar-collapse {
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .navbar-nav.nav-dropdown {
        display: block;
        width: 100%;
    }
    
    .dropdown-menu {
        position: static;
        float: none;
        margin-left: 1rem;
        border-left: 2px solid rgba(255, 255, 255, 0.2);
    }
    
    .dropdown-item.dropdown-toggle::after {
        transform: rotate(90deg);
        right: 20px;
    }
    
    .navbar-buttons.mbr-section-btn {
        margin-top: 1rem;
        justify-content: center;
        display: flex;
    }
}

@media (min-width: 992px) {
    .dropdown:hover > .dropdown-menu {
        display: block;
    }
    
    .dropdown-menu .dropdown:hover > .dropdown-menu {
        display: block;
        top: 0;
        left: 100%;
    }
}

/* Hamburger Menu */
.hamburger {
    width: 30px;
    height: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.hamburger span {
    display: block;
    height: 2px;
    width: 100%;
    background: white;
    border-radius: 2px;
}
</style>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Make mobile dropdowns work with click instead of hover
    if (window.innerWidth < 992) {
        document.querySelectorAll('.dropdown-toggle').forEach(function(element) {
            element.addEventListener('click', function(e) {
                e.preventDefault();
                let nextEl = this.nextElementSibling;
                if (nextEl && nextEl.classList.contains('dropdown-menu')) {
                    // If visible, hide it
                    if (nextEl.style.display === 'block') {
                        nextEl.style.display = 'none';
                    } else {
                        // Otherwise, show it
                        nextEl.style.display = 'block';
                    }
                }
            });
        });
    }
});
</script> 