<!DOCTYPE html>
<html>
<head>
  <!-- FFR Seite -->
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
  <link rel="shortcut icon" href="../assets/images/gravatar-logo-dunkel.jpg" type="image/x-icon">
  <meta name="description" content="Unterstütze die Freiwillige Feuerwehr Reichenbach durch Spenden oder ehrenamtliches Engagement. Erfahre, wie du uns helfen kannst, noch besser zu werden.">
  
  
  <title>Flyer</title>
  <link rel="stylesheet" href="../../assets/web/assets/mobirise-icons2/mobirise2.css">
  <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap-grid.min.css">
  <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap-reboot.min.css">
  <link rel="stylesheet" href="../../assets/parallax/jarallax.css">
  <link rel="stylesheet" href="../../assets/dropdown/css/style.css">
  <link rel="stylesheet" href="../../assets/socicon/css/styles.css">
  <link rel="stylesheet" href="../../assets/theme/css/style.css">
  <link rel="stylesheet" href="../../assets/css/custom-parallax.css">
  <link rel="preload" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap"></noscript>
  <link rel="preload" as="style" href="../../assets/mobirise/css/mbr-additional.css?v=acTmw9"><link rel="stylesheet" href="../../assets/mobirise/css/mbr-additional.css?v=acTmw9" type="text/css">

  <style>
    .flyer-gallery {
      padding: 30px 0;
    }
    .flyer-item {
      margin-bottom: 30px;
      text-align: center;
    }
    .flyer-item img {
      max-width: 100%;
      height: auto;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
    }
    .flyer-item img:hover {
      transform: scale(1.03);
    }
    .flyer-title {
      margin-top: 15px;
      margin-bottom: 10px;
      font-weight: 600;
    }
    .download-btn {
      display: inline-block;
      padding: 6px 15px;
      background-color: #dc3545;
      color: white;
      border-radius: 4px;
      text-decoration: none;
      transition: background-color 0.3s ease;
    }
    .download-btn:hover {
      background-color: #c82333;
      color: white;
      text-decoration: none;
    }
  </style>
  
</head>
<body>
  

<?php
$assetsPath = '../../assets/';

include_once( $assetsPath . 'includes/navbar.php');
?>

<!-- Flyer auswahl Seite-->
<section class="mbr-section content5 cid-u8NdAjmIcn flyer-gallery" id="content5-1">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <h2 class="mbr-section-title mbr-fonts-style mb-5 text-center display-2">Flyer Übersicht</h2>
            </div>
        </div>
        
        <div class="row">
            <?php
            // Ordner mit Flyern
            $dir = './';
            
            // Alle Dateien im Ordner auslesen
            $files = scandir($dir);
            
            // Anzahl gefundener Flyer
            $flyerCount = 0;
            
            // Alle Dateien durchgehen
            foreach($files as $file) {
                // Nur jpg und png Dateien verarbeiten
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if($extension == 'jpg' || $extension == 'png') {
                    $flyerCount++;
                    
                    // Titel aus dem Dateinamen extrahieren
                    $filename = pathinfo($file, PATHINFO_FILENAME);
                    $title = str_replace(['Flyer-', '-'], ['', ' '], $filename);
                    
                    // HTML für den Flyer ausgeben
                    echo '<div class="col-md-4 flyer-item">';
                    echo '<img src="' . $file . '" alt="' . $title . '">';
                    echo '<h5 class="flyer-title">' . $title . '</h5>';
                    echo '<a href="' . $file . '" download class="download-btn">Download</a>';
                    echo '</div>';
                }
            }
            
            // Falls keine Flyer gefunden wurden
            if($flyerCount == 0) {
                echo '<div class="col-12 text-center">';
                echo '<p>Aktuell sind keine Flyer verfügbar.</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</section>

<?php include '../../assets/includes/socialFooter.php'; ?>
<?php include '../../assets/includes/footer.php'; ?>
 
</body>
</html>