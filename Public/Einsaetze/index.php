<!DOCTYPE html>
<html>
<head>
  <!-- FFR Seite -->
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
  <link rel="shortcut icon" href="../assets/images/gravatar-logo-dunkel-1.jpg" type="image/x-icon">
  <meta name="description" content="Einsätze der Freiwilligen Feuerwehr Reichenbach. Informationen zu aktuellen und vergangenen Einsätzen.">
  
  
  <title>Einsätze</title>
  <link rel="stylesheet" href="../assets/web/assets/mobirise-icons2/mobirise2.css">
  <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap-grid.min.css">
  <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap-reboot.min.css">
  <link rel="stylesheet" href="../assets/parallax/jarallax.css">
  <link rel="stylesheet" href="../assets/dropdown/css/style.css">
  <link rel="stylesheet" href="../assets/socicon/css/styles.css">
  <link rel="stylesheet" href="../assets/theme/css/style.css">
  <link rel="stylesheet" href="../assets/gallery/style.css">
  <link rel="stylesheet" href="../assets/css/custom-parallax.css">
  <link rel="preload" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap"></noscript>
  <link rel="preload" as="style" href="../assets/mobirise/css/mbr-additional.css?v=9RFiKP"><link rel="stylesheet" href="../assets/mobirise/css/mbr-additional.css?v=9RFiKP" type="text/css">

  
  
  
</head>
<body>

<?php include '../assets/includes/navbar.php'; ?>

<section data-bs-version="5.1" class="header16 cid-u8ZqdT74F6 mbr-fullscreen jarallax" id="header17-2w" data-jarallax-speed="0.8">
  
  <div class="mbr-overlay" style="opacity: 0.5; background-color: rgb(0, 0, 0);"></div>
  <div class="container-fluid">
    <div class="row">
      <div class="content-wrap col-12 col-md-12">
        <h1 class="mbr-section-title mbr-fonts-style mbr-white mb-4 display-1"><strong>Einsätze</strong></h1>
        
        
        <div class="mbr-section-btn"><a class="btn btn-white-outline display-7" href="#einsaetze">Alle Einsätze</a></div>
      </div>
    </div>
  </div>
</section>

<section data-bs-version="5.1" class="article11 cid-ukzz9Maa6f" id="article11-4p">  
    <div class="container">
        <?php include_once "../assets/includes/einsaetze.php";
        
        $jahr = isset($_GET['statistik_jahr']) ? (int)$_GET['statistik_jahr'] : date('Y');

        showEinsaetze(4);
        showEinsatzStatistik($jahr);


        // Übersicht
        EinsatzStatistikGesamt();
        EinsatzStatistikJahresvergleich();
        
        // Zeitliche Verteilung
        EinsatzStatistikMonate();
        EinsatzStatistikTageImMonat();
        EinsatzStatistikWochentage();
        EinsatzStatistikTagesverlauf();
        EinsatzStatistikTageszeit();
        EinsatzStatistikEinsaetzeProJahreszeit();
        
        // Einsatzarten und Kategorien
        EinsatzStatistikStichworte();
        EinsatzStatistikKategorien(); 
        EinsatzStatistikStichwortKategorie();
     
        
        // Einsatzorte und Einheiten
        EinsatzStatistikOrtKategorie();
        EinsatzStatistikEinheiten();
        
        // Kombinierte Auswertungen
        EinsatzStatistikWochentagTageszeit();
        
        // Einsatzdauer
        EinsatzStatistikDauer();
        EinsatzStatistikDauerNachStichwort();
        EinsatzStatistikDauerNachOrt();
        EinsatzStatistikDauerNachKategorie();
        

        
        ?>
    </div>
</section>

<section data-bs-version="5.1" class="gallery1 mbr-gallery cid-u8Zqvxr6si" id="gallery02-41">   
    
    

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 content-head">
                <div class="mbr-section-head mb-5">
                    <h3 class="mbr-section-title mbr-fonts-style align-center m-0 display-2"><strong>Siehe uns in Action!</strong></h3>
                    
                </div>
            </div>
        </div>
        <div class="row mbr-gallery mbr-masonry" data-masonry="{&quot;percentPosition&quot;: true }">
            
            
            
            
            
            
            <div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img20240729193049.webp" alt="Mobirise Website Builder" data-slide-to="0" data-bs-slide-to="0" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img20240729194140.webp" alt="Mobirise Website Builder" data-slide-to="1" data-bs-slide-to="1" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img20240729201025-1.webp" alt="Mobirise Website Builder" data-slide-to="2" data-bs-slide-to="2" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img20240729195048-1.webp" alt="Mobirise Website Builder" data-slide-to="3" data-bs-slide-to="3" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/e69c6e0e-6622-49f5-a608-bd65a6a9dc8e.webp" alt="Mobirise Website Builder" data-slide-to="4" data-bs-slide-to="4" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img-3898.webp" alt="Mobirise Website Builder" data-slide-to="5" data-bs-slide-to="5" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img-3961.webp" alt="Mobirise Website Builder" data-slide-to="6" data-bs-slide-to="6" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img-4281.webp" alt="Mobirise Website Builder" data-slide-to="7" data-bs-slide-to="7" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img-4898.webp" alt="Mobirise Website Builder" data-slide-to="8" data-bs-slide-to="8" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img-20231218-wa0006.webp" alt="Mobirise Website Builder" data-slide-to="9" data-bs-slide-to="9" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img20230708143308.webp" alt="Mobirise Website Builder" data-slide-to="10" data-bs-slide-to="10" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img20240311194221.webp" alt="Mobirise Website Builder" data-slide-to="11" data-bs-slide-to="11" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img20240513185812.webp" alt="Mobirise Website Builder" data-slide-to="12" data-bs-slide-to="12" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img20240513192710-1.webp" alt="Mobirise Website Builder" data-slide-to="13" data-bs-slide-to="13" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img20240617192228-1.webp" alt="Mobirise Website Builder" data-slide-to="14" data-bs-slide-to="14" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img-3960.webp" alt="Mobirise Website Builder" data-slide-to="15" data-bs-slide-to="15" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img-4897.webp" alt="Mobirise Website Builder" data-slide-to="16" data-bs-slide-to="16" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img20240617200458.webp" alt="Mobirise Website Builder" data-slide-to="17" data-bs-slide-to="17" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img20240715191603-1.webp" alt="Mobirise Website Builder" data-slide-to="18" data-bs-slide-to="18" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img20240715192856.webp" alt="Mobirise Website Builder" data-slide-to="19" data-bs-slide-to="19" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div><div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-toggle="modal" data-bs-toggle="modal" data-target="#uH1uF6kWJK-modal" data-bs-target="#uH1uF6kWJK-modal">
                    <img class="w-100" src="../assets/images/img20240715200606.webp" alt="Mobirise Website Builder" data-slide-to="20" data-bs-slide-to="20" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK">
                    <div class="icon-wrapper">
                        <span class="mobi-mbri mobi-mbri-search mbr-iconfont mbr-iconfont-btn"></span>
                    </div>
                </div>
                
            </div>
        </div>

        <div class="modal mbr-slider" tabindex="-1" role="dialog" aria-hidden="true" id="uH1uF6kWJK-modal">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="carousel slide" id="lb-uH1uF6kWJK" data-interval="5000" data-bs-interval="5000">
                            <div class="carousel-inner">
                                
                                
                                
                                
                                
                                
                                <div class="carousel-item active">
                                    <img class="d-block w-100" src="../assets/images/img20240729193049.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img20240729194140.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img20240729201025-1.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img20240729195048-1.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/e69c6e0e-6622-49f5-a608-bd65a6a9dc8e.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img-3898.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img-3961.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img-4281.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img-4898.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img-20231218-wa0006.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img20230708143308.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img20240311194221.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img20240513185812.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img20240513192710-1.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img20240617192228-1.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img-3960.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img-4897.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img20240617200458.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img20240715191603-1.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img20240715192856.webp" alt="Mobirise Website Builder">
                                </div><div class="carousel-item">
                                    <img class="d-block w-100" src="../assets/images/img20240715200606.webp" alt="Mobirise Website Builder">
                                </div>
                            </div>
                            <ol class="carousel-indicators">
                                <li data-slide-to="0" data-bs-slide-to="0" class="active" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="1" data-bs-slide-to="1" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="2" data-bs-slide-to="2" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="3" data-bs-slide-to="3" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="4" data-bs-slide-to="4" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="5" data-bs-slide-to="5" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="6" data-bs-slide-to="6" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="7" data-bs-slide-to="7" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="8" data-bs-slide-to="8" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="9" data-bs-slide-to="9" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="10" data-bs-slide-to="10" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="11" data-bs-slide-to="11" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="12" data-bs-slide-to="12" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="13" data-bs-slide-to="13" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="14" data-bs-slide-to="14" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="15" data-bs-slide-to="15" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="16" data-bs-slide-to="16" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="17" data-bs-slide-to="17" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="18" data-bs-slide-to="18" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="19" data-bs-slide-to="19" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li><li data-slide-to="20" data-bs-slide-to="20" class="" data-target="#lb-uH1uF6kWJK" data-bs-target="#lb-uH1uF6kWJK"></li>
                                
                                
                                
                                
                                
                                
                            </ol>
                            <a role="button" href="" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                            </a>
                            <a class="carousel-control-prev carousel-control" role="button" data-slide="prev" data-bs-slide="prev" href="#lb-uH1uF6kWJK">
                                <span class="mobi-mbri mobi-mbri-arrow-prev" aria-hidden="true"></span>
                                <span class="sr-only visually-hidden">Previous</span>
                            </a>
                            <a class="carousel-control-next carousel-control" role="button" data-slide="next" data-bs-slide="next" href="#lb-uH1uF6kWJK">
                                <span class="mobi-mbri mobi-mbri-arrow-next" aria-hidden="true"></span>
                                <span class="sr-only visually-hidden">Next</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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