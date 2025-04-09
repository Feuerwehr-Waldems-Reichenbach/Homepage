<!DOCTYPE html>
<html>
<head>
  <!-- FFR Seite -->
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
  <link rel="shortcut icon" href="../assets/images/gravatar-logo-dunkel-1.jpg" type="image/x-icon">
  <meta name="description" content="Besuche unsere Veranstaltungen und lerne die Freiwillige Feuerwehr Reichenbach kennen. Erfahre mehr über unsere kommenden Events und Aktivitäten.">
  
  
  <title>Veranstaltungen</title>
  <link rel="stylesheet" href="../assets/web/assets/mobirise-icons2/mobirise2.css">
  <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap-grid.min.css">
  <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap-reboot.min.css">
  <link rel="stylesheet" href="../assets/parallax/jarallax.css">
  <link rel="stylesheet" href="../assets/dropdown/css/style.css">
  <link rel="stylesheet" href="../assets/socicon/css/styles.css">
  <link rel="stylesheet" href="../assets/theme/css/style.css">
  <link rel="stylesheet" href="../assets/css/custom-parallax.css">
  <link rel="preload" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap"></noscript>
  <link rel="preload" as="style" href="../assets/mobirise/css/mbr-additional.css?v=acTmw9"><link rel="stylesheet" href="../assets/mobirise/css/mbr-additional.css?v=acTmw9" type="text/css">
  
</head>
<body>
  
<?php include '../assets/includes/navbar.php'; ?>

<section data-bs-version="5.1" class="header16 cid-u8ZmTBFujS mbr-fullscreen jarallax" id="header17-3p" data-jarallax-speed="0.8">
  
  <div class="mbr-overlay" style="opacity: 0.5; background-color: rgb(0, 0, 0);"></div>
  <div class="container-fluid">
    <div class="row">
      <div class="content-wrap col-12 col-md-12">
        <h1 class="mbr-section-title mbr-fonts-style mbr-white mb-4 display-1"><strong>Veranstaltungen</strong></h1>
        
        
        <div class="mbr-section-btn"><a class="btn btn-white-outline display-7" href="#article11-4q">Erfahre mehr!</a></div>
      </div>
    </div>
  </div>
</section>

<section data-bs-version="5.1" class="article11 cid-ukzEavxMa7" id="article11-4q">
    
    <div class="container">
        <?php
        // Neuigkeiten-Modul einbinden
        require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/neuigkeiten.php';
        
        // Neuigkeiten anzeigen
        echo showNeuigkeiten();
        ?>
    </div>
</section>

<?php include '../assets/includes/socialFooter.php'; ?>
<?php include '../assets/includes/footer.php'; ?>

  <!-- Parallax Scripts -->
  <script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>  
  <script src="../assets/smoothscroll/smooth-scroll.js"></script>  
  <script src="../assets/ytplayer/index.js"></script>  
  <script src="../assets/dropdown/js/navbar-dropdown.js"></script>  
  <script src="../assets/theme/js/script.js"></script>  
  <script src="../assets/parallax/jarallax.js"></script>
  <script>
    // Initialisiere Jarallax nach dem Laden der Seite
    document.addEventListener("DOMContentLoaded", function() {
      jarallax(document.querySelectorAll('.jarallax'), {
        speed: 0.6,
        imgPosition: '50% 50%',
        imgSize: 'cover'
      });
    });
  </script>
</body>
</html>