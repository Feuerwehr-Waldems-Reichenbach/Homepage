<?php
// Demo-Datei zum Testen des Einsätze-Moduls
$pageTitle = "Einsätze Demo";

// Jahr aus dem GET-Parameter holen oder aktuelles Jahr verwenden
$jahr = isset($_GET['statistik_jahr']) ? (int)$_GET['statistik_jahr'] : date('Y');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Feuerwehr Waldems Reichenbach</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            padding-top: 70px; /* Abstand für die Navbar */
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .demo-info {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 0 4px 4px 0;
        }
        
        .demo-code {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 2rem;
            font-family: monospace;
            white-space: pre-wrap;
        }
        
        .section-divider {
            margin: 3rem 0;
            border-top: 2px solid #e9ecef;
            position: relative;
        }
        
        .section-divider-text {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #fff;
            padding: 0 1rem;
            font-weight: bold;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include_once "assets/includes/navbar.php"; ?>
    
    <div class="container mt-5">
        <h1><?php echo $pageTitle; ?></h1>
        
        <div class="demo-info">
            <h4>Einsätze-Modul Demo</h4>
            <p>Diese Seite demonstriert die Verwendung des Einsätze-Moduls. Das Modul lädt Einsatzdaten aus der Datenbank und zeigt sie mit Paginierung an. Außerdem können mit der Statistik-Funktion interessante Auswertungen der Einsätze angezeigt werden.</p>
        </div>
        
        <h2>1. Einsätze anzeigen</h2>
        
        <h3>Standardverwendung</h3>
        <div class="demo-code">
// In Ihrer PHP-Datei:
include_once "assets/includes/einsaetze.php";

// Einsätze anzeigen mit Standardeinstellungen (5 Einträge pro Seite)
showEinsaetze();
        </div>
        
        <?php 
        // Einsätze-Modul einbinden
        include_once "assets/includes/einsaetze.php";
        
        // Standardanzeige
        showEinsaetze(); 
        ?>
        
        <h3>Anpassung: 3 Einträge pro Seite</h3>
        <div class="demo-code">
// In Ihrer PHP-Datei:
include_once "assets/includes/einsaetze.php";

// Einsätze anzeigen mit 3 Einträgen pro Seite
showEinsaetze(3);
        </div>
        
        <?php 
        // 3 Einträge pro Seite
        showEinsaetze(3, 'custom-einsaetze'); 
        ?>
        
        <div class="section-divider">
            <span class="section-divider-text">Einsatzstatistiken</span>
        </div>
        
        <h2>2. Einsatzstatistiken anzeigen</h2>
        
        <h3>Standardverwendung (aktuelles Jahr)</h3>
        <div class="demo-code">
// In Ihrer PHP-Datei:
include_once "assets/includes/einsaetze.php";

// Statistiken für das aktuelle Jahr anzeigen
showEinsatzStatistik();
        </div>
        
        <h3>Statistik für ein bestimmtes Jahr</h3>
        <div class="demo-code">
// In Ihrer PHP-Datei:
include_once "assets/includes/einsaetze.php";

// Statistiken für das Jahr 2023 anzeigen
showEinsatzStatistik(2023);
        </div>
        
        <h3>Statistik mit benutzerdefinierter CSS-Klasse</h3>
        <div class="demo-code">
// In Ihrer PHP-Datei:
include_once "assets/includes/einsaetze.php";

// Statistiken mit benutzerdefinierter Klasse anzeigen
showEinsatzStatistik(2024, 'my-custom-stats-class');

// In Ihrer CSS-Datei:
.my-custom-stats-class {
    background-color: #f0f0f0;
    border: 2px solid #ddd;
}
        </div>
        
        <style>
            .my-custom-stats-class {
                background-color: #f0f0f0;
                border: 2px solid #ddd;
            }
        </style>
        
        <?php 
        // Statistik für das ausgewählte Jahr anzeigen
        showEinsatzStatistik($jahr, 'my-custom-stats-class'); 
        ?>
        
        <h3>Einsatzliste und Statistik kombinieren</h3>
        <div class="demo-code">
// In Ihrer PHP-Datei:
include_once "assets/includes/einsaetze.php";

// Statistiken anzeigen
showEinsatzStatistik(2024);

// Einsätze darunter anzeigen
showEinsaetze(5, 'with-stats');
        </div>
        
        <p class="mt-5 text-muted text-center">
            <small>Hinweis: Verwenden Sie auf Ihren eigenen Seiten entweder die Einsatzliste oder die Statistik oder kombinieren Sie beides nach Bedarf.</small>
        </p>
    </div>
    
    <?php include_once "assets/includes/footer.php"; ?>
    
    <!-- Bootstrap JS Bundle mit Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 