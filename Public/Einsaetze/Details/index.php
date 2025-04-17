<!DOCTYPE html>
<html>

<head>
  <!-- FFR Seite -->
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
  <link rel="shortcut icon" href="../../assets/images/gravatar-logo-dunkel.jpg" type="image/x-icon">
  <meta name="description"
    content="Details zu Einsätzen der Freiwilligen Feuerwehr Reichenbach. Erfahre mehr über unsere Einsätze und Aktivitäten.">
  <title>Einsatz Details</title>
  <link rel="stylesheet" href="../../assets/web/assets/mobirise-icons2/mobirise2.css">
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
  <link rel="preload" as="style" href="../../assets/mobirise/css/mbr-additional.css?v=acTmw9">
  <link rel="stylesheet" href="../../assets/mobirise/css/mbr-additional.css?v=acTmw9" type="text/css">

  <style>
   
    .mbr-section-title, 
    .mbr-section-subtitle,
    .footer-text,
    .footer-head {
      color:rgb(0, 0, 0) !important;
    }
    .einsatz-details-container {
      padding: 4rem 0;
      min-height: calc(100vh - 250px);
      background-color: #414141;
    }
    .einsatz-details-header {
      background-color:rgb(255, 255, 255);
      border-radius: 8px;
      padding: 2rem;
      margin-bottom: 2rem;
      margin-top: 4rem;
    }
    .einsatz-details-title {
      color:rgb(0, 0, 0);
      margin-bottom: 1.5rem;
      font-weight: bold;
      font-size: 2.2rem;
    }
    .einsatz-details-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 1.2rem;
      margin-bottom: 1.5rem;
      font-size: 0.95rem;
      color:rgb(0, 0, 0);
    }
    .einsatz-details-meta div {
      display: flex;
      align-items: center;
    }
    .einsatz-details-meta i {
      margin-right: 0.5rem;
      opacity: 0.8;
    }
    .einsatz-details-badges {
      display: flex;
      flex-wrap: wrap;
      gap: 0.8rem;
      margin-bottom: 1.5rem;
    }
    .einsatz-details-stichwort {
      background-color: #A72920;
      color: white;
      padding: 0.4rem 1rem;
      border-radius: 4px;
      font-size: 0.9rem;
    }
    .einsatz-details-kategorie {
      background-color: #585858;
      color: white;
      padding: 0.4rem 1rem;
      border-radius: 4px;
      font-size: 0.9rem;
    }
    .einsatz-details-content-wrapper {
      display: flex;
      flex-wrap: wrap;
      gap: 2rem;
      margin-bottom: 2rem;
    }
    .einsatz-details-text-container {
      flex: 1;
      min-width: 300px;
    }
    .einsatz-details-image {
      flex: 1;
      min-width: 300px;
      max-width: 500px;
      border-radius: 8px;
      overflow: hidden;
      height: fit-content;
    }
    .einsatz-details-image img {
      width: 100%;
      height: auto;
      display: block;
    }

    .einsatz-details-content {
      background-color:rgb(255, 255, 255);
      border-radius: 8px;
      padding: 2rem;

    }
    .einsatz-details-text {
      font-size: 1.2rem;
      line-height: 1.8;
      color:rgb(0, 0, 0);
    }
    .einsatz-details-text p:last-child {
      margin-bottom: 0;
    }
    .einsatz-details-back {
      margin-top: 2rem;
      display: inline-block;
      background-color: #A72920;
      color: white;
      padding: 0.7rem 1.5rem;
      border-radius: 4px;
      text-decoration: none;
      transition: all 0.2s ease;
      font-weight: 500;
    }
    .einsatz-details-back:hover {
      background-color: #8e2219;
      color: white;
      text-decoration: none;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .einsatz-not-found {
      text-align: center;
      padding: 4rem 2rem;
      background-color:rgb(255, 255, 255);
      border-radius: 8px;
      margin: 4rem 0;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    .einsatz-not-found h3 {
      color:rgb(0, 0, 0);
      margin-bottom: 1.5rem;
      font-size: 1.8rem;
    }
    .einsatz-sachverhalt {
      font-size: 1.2rem;
      line-height: 1.6;
      margin-bottom: 1.5rem;
      color:rgb(0, 0, 0);
    }
    @media (max-width: 992px) {
      .einsatz-details-content-wrapper {
        flex-direction: column-reverse;
      }
      .einsatz-details-image {
        max-width: 100%;
        margin-bottom: 1.5rem;
      }
    }
    @media (max-width: 768px) {
      .einsatz-details-container {
        padding: 3rem 0;
      }
      .einsatz-details-meta {
        flex-direction: column;
        gap: 0.7rem;
      }
      .einsatz-details-title {
        font-size: 1.8rem;
      }
    }
  </style>
</head>

<body>
  <?php
  $assetsPath = '../../assets/';
  include_once($assetsPath . 'includes/navbar.php');
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/Private/Database/Database.php';
  ?>

  <section class="einsatz-details-container">
    <div class="container">
      <?php
      // Check if an ID is provided
      if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $einsatzID = (int)$_GET['id'];
        
        // Get database connection
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Query to fetch einsatz data
        $einsatzSql = "SELECT * FROM einsatz WHERE ID = :einsatzID AND Anzeigen = 1";
        $einsatzStmt = $conn->prepare($einsatzSql);
        $einsatzStmt->bindParam(':einsatzID', $einsatzID, PDO::PARAM_INT);
        $einsatzStmt->execute();
        $einsatz = $einsatzStmt->fetch(PDO::FETCH_ASSOC);
        
        // Query to fetch details data
        $detailsSql = "SELECT * FROM einsatz_Details WHERE einsatz_id = :einsatzID";
        $detailsStmt = $conn->prepare($detailsSql);
        $detailsStmt->bindParam(':einsatzID', $einsatzID, PDO::PARAM_INT);
        $detailsStmt->execute();
        $details = $detailsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if both records exist
        if ($einsatz && $details) {
          // Format date and time
          $datumObj = new DateTime($einsatz['Datum']);
          $endZeitObj = new DateTime($einsatz['Endzeit']);
          $formattedDatum = $datumObj->format('d.m.Y - H:i');
          
          // Calculate duration
          $dauer = $datumObj->diff($endZeitObj);
          $dauerText = '';
          if ($dauer->d > 0) {
              $dauerText .= $dauer->d . ' Tag' . ($dauer->d > 1 ? 'e' : '') . ', ';
          }
          if ($dauer->h > 0) {
              $dauerText .= $dauer->h . ' Stunde' . ($dauer->h > 1 ? 'n' : '') . ', ';
          }
          if ($dauer->i > 0) {
              $dauerText .= $dauer->i . ' Minute' . ($dauer->i > 1 ? 'n' : '');
          }
          if (empty($dauerText)) {
              $dauerText = 'Weniger als eine Minute';
          } else if (substr($dauerText, -2) == ', ') {
              $dauerText = substr($dauerText, 0, -2);
          }
      ?>
          <!-- Einsatz Header -->
          <div class="einsatz-details-header">
            <h1 class="einsatz-details-title"><?php echo htmlspecialchars($details['einsatz_headline']); ?></h1>
            
            <div class="einsatz-details-badges">
              <span class="einsatz-details-stichwort"><?php echo htmlspecialchars($einsatz['Stichwort']); ?></span>
              <?php if (!empty($einsatz['Kategorie'])): ?>
                <span class="einsatz-details-kategorie"><?php echo htmlspecialchars($einsatz['Kategorie']); ?></span>
              <?php endif; ?>
            </div>
            
            <div class="einsatz-details-meta">
              <div><i class="bi bi-calendar-event"></i> <?php echo $formattedDatum; ?> Uhr</div>
              <div><i class="bi bi-clock"></i> Einsatzdauer: <?php echo $dauerText; ?></div>
              <div><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($einsatz['Ort']); ?></div>
              <div><i class="bi bi-people"></i> <?php echo htmlspecialchars($einsatz['Einheit']); ?></div>
            </div>
            
            <div class="einsatz-sachverhalt"><strong><?php echo htmlspecialchars($einsatz['Sachverhalt']); ?></strong></div>
          </div>
          
          <!-- Einsatz Content with Image -->
          <div class="einsatz-details-content-wrapper">
            <!-- Text Content -->
            <div class="einsatz-details-text-container">
              <div class="einsatz-details-content">
                <div class="einsatz-details-text">
                  <?php echo nl2br(htmlspecialchars($details['einsatz_text'])); ?>
                </div>
              </div>
            </div>
            
            <?php 
            // Define the default image path
            $defaultImagePath = '/assets/images/einsatzbild.webp';
            ?>
            <!-- Image if available -->
            <div class="einsatz-details-image">
              <a href="<?php echo !empty($details['image_path']) ? htmlspecialchars($details['image_path']) : '#'; ?>" target="_blank">
                <img src="<?php echo !empty($details['image_path']) ? htmlspecialchars($details['image_path']) : $defaultImagePath; ?>" alt="<?php echo htmlspecialchars($details['einsatz_headline']); ?>">
              </a>
            </div>
          </div>
          
          <!-- Back Button -->
          <a href="../" class="einsatz-details-back">
            <i class="bi bi-arrow-left"></i> Zurück zur Einsatzübersicht
          </a>
          
      <?php
        } else {
          // Display error message if einsatz not found or not visible
      ?>
          <div class="einsatz-not-found">
            <h3>Einsatz nicht gefunden</h3>
            <p>Der gesuchte Einsatz konnte nicht gefunden werden oder ist nicht zur Anzeige freigegeben.</p>
            <a href="../" class="einsatz-details-back">
              <i class="bi bi-arrow-left"></i> Zurück zur Einsatzübersicht
            </a>
          </div>
      <?php
        }
      } else {
        // Display error message if no ID is provided
      ?>
        <div class="einsatz-not-found">
          <h3>Ungültige Anfrage</h3>
          <p>Es wurde keine gültige Einsatz-ID angegeben.</p>
          <a href="../" class="einsatz-details-back">
            <i class="bi bi-arrow-left"></i> Zurück zur Einsatzübersicht
          </a>
        </div>
      <?php
      }
      ?>
    </div>
  </section>

  <?php include '../../assets/includes/socialFooter.php'; ?>
  <?php include '../../assets/includes/footer.php'; ?>
  
  <!-- Parallax Scripts -->
  <script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../../assets/smoothscroll/smooth-scroll.js"></script>
  <script src="../../assets/ytplayer/index.js"></script>
  <script src="../../assets/dropdown/js/navbar-dropdown.js"></script>
  <script src="../../assets/theme/js/script.js"></script>
  <script src="../../assets/parallax/jarallax.js"></script>
  <script>
    // Initialize Jarallax after page load
    document.addEventListener("DOMContentLoaded", function () {
      jarallax(document.querySelectorAll('.jarallax'), {
        speed: 0.6,
        imgPosition: '50% 50%',
        imgSize: 'cover'
      });
    });
  </script>
</body>

</html>