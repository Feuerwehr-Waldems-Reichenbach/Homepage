<!DOCTYPE html>
<html>

<head>
  <!-- FFR Seite -->
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
  <link rel="shortcut icon" href="../assets/images/gravatar-logo-dunkel.webp" type="image/x-icon">
  <meta name="description"
    content="Hier findest du alle Flyer der Freiwilligen Feuerwehr Reichenbach">
  <title>Flyer</title>
  <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap-grid.min.css">
  <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap-reboot.min.css">
  <link rel="stylesheet" href="../../assets/parallax/jarallax.css">
  <link rel="stylesheet" href="../../assets/dropdown/css/style.css">
  <link rel="stylesheet" href="../../assets/socicon/css/styles.css">
  <link rel="stylesheet" href="../../assets/theme/css/style.css">
  <link rel="stylesheet" href="../../assets/css/custom-parallax.css">
  <link rel="preload"
    href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap"
    as="style" onload="this.onload=null;this.rel='stylesheet'">
  <noscript>
    <link rel="stylesheet"
      href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap">
  </noscript>
  <link rel="preload" as="style" href="../../assets/ffr/css/ffr-additional.css?v=acTmw9">
  <link rel="stylesheet" href="../../assets/ffr/css/ffr-additional.css?v=acTmw9" type="text/css">
  <style>
    .flyer-gallery {
      padding: 30px 0;
    }

    .flyer-item {
      margin-bottom: 30px;
      text-align: center;
      height: 100%;
    }

    .flyer-item img {
      width: 100%;
      height: 500px;
      /* Feste Höhe für alle Flyer */
      object-fit: contain;
      /* Behält das Seitenverhältnis bei */
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
      background-color: rgba(0, 0, 0, 0);
      /* Hintergrundfarbe für transparente Bereiche */
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

    .year-section {
      margin-top: 40px;
      margin-bottom: 20px;
      border-bottom: 2px solid #dc3545;
      padding-bottom: 10px;
    }
  </style>
</head>

<body>
  <?php
  $assetsPath = '../../assets/';
  include_once($assetsPath . 'includes/navbar.php');
  ?>
  <!-- Flyer auswahl Seite-->
  <section class="ffr-section content5 cid-Download-Cards flyer-gallery" id="content5-1">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12">
          <h2 class="ffr-section-title ffr-fonts-style mb-5 text-center display-2">Flyer Übersicht</h2>
        </div>
      </div>
      <?php
      // Ordner mit Flyern
      $dir = './';
      // Alle Dateien im Ordner auslesen
      $files = scandir($dir);
      // Array für sortierte Flyer nach Jahr
      $flyersByYear = [];
      // Anzahl gefundener Flyer
      $flyerCount = 0;
      // Alle Dateien durchgehen und nach Jahr sortieren
      foreach ($files as $file) {
        // Nur jpg und png Dateien verarbeiten
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($extension == 'jpg' || $extension == 'png') {
          $flyerCount++;
          // Titel aus dem Dateinamen extrahieren
          $filename = pathinfo($file, PATHINFO_FILENAME);
          $title = str_replace(['Flyer-', '-'], ['', ' '], $filename);
          // Jahr aus dem Titel extrahieren
          // Annahme: Das Jahr ist entweder am Anfang oder am Ende des Titels im Format YYYY
          if (preg_match('/\b(20\d{2})\b/', $title, $matches)) {
            $year = $matches[1];
          } else {
            $year = "Undatiert"; // Falls kein Jahr gefunden wurde
          }
          // Flyer zum entsprechenden Jahr hinzufügen
          $flyersByYear[$year][] = [
            'file' => $file,
            'title' => $title
          ];
        }
      }
      // Jahre absteigend sortieren (neueste zuerst)
      krsort($flyersByYear);
      // Falls keine Flyer gefunden wurden
      if ($flyerCount == 0) {
        echo '<div class="row"><div class="col-12 text-center">';
        echo '<p style="color: white;">Aktuell sind keine Flyer verfügbar.</p>';
        echo '</div></div>';
      } else {
        // Für jedes Jahr die Flyer anzeigen
        foreach ($flyersByYear as $year => $flyers) {
          echo '<div class="row">';
          echo '<div class="col-12">';
          echo '<h3 class="year-section" style="color: white;">' . $year . '</h3>';
          echo '</div>';
          echo '</div>';
          echo '<div class="row">';
          foreach ($flyers as $flyer) {
            echo '<div class="col-md-4 flyer-item">';
            echo '<div class="card h-100">';
            echo '<a href="' . $flyer['file'] . '" target="_blank">';
            echo '<img src="' . $flyer['file'] . '" alt="' . $flyer['title'] . '">';
            echo '</a>';
            echo '<h5 class="flyer-title" style="color: white;">' . $flyer['title'] . '</h5>';
            echo '<a href="' . $flyer['file'] . '" download class="download-btn">Download</a>';
            echo '</div>';
            echo '</div>';
          }
          echo '</div>';
        }
      }
      ?>
    </div>
  </section>
  <?php include '../../assets/includes/socialFooter.php'; ?>
  <?php include '../../assets/includes/footer.php'; ?>
</body>

</html>