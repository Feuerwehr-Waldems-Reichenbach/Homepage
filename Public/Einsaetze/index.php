<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';


$page = new PageBuilder(
    title: 'Einsätze der Feuerwehr Reichenbach | Aktuelle Berichte & Statistik',
    description: 'Erfahren Sie alles über die Einsätze der Freiwilligen Feuerwehr Reichenbach in Waldems. Aktuelle Einsatzberichte, detaillierte Statistiken und eine Galerie unserer Arbeit im Einsatz.',
    keywords: 'Einsätze Feuerwehr Reichenbach, Feuerwehr Waldems Einsätze, Einsatzberichte, Feuerwehr Statistik, Freiwillige Feuerwehr Reichenbach, Brand, Hilfeleistung, Technische Hilfe, Feuerwehr Einsatzbilder, Reichenbach Waldems',
    canonicalUrl: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
);

// 3. Zusätzliche Stylesheets hinzufügen (falls nicht im Standard-Bundle des Konstruktors)
$page->addStyle('../assets/gallery/style.css'); // Pfad relativ zum Webroot oder zur PHP-Datei anpassen

// 4. Inhaltsblöcke hinzufügen

// --- Hero Header (Fullscreen) ---
$page->addContent($page->renderFullscreenHero(
    'header17-2w', // ID des Abschnitts
    'Einsätze', // Titel
    '', // Subtitle (leer in diesem Fall)
    'Alle Einsätze', // Button Text
    '#Einsatzliste', // Button Link
    'Hero-Einsaetze', // CID Suffix (optional, entspricht 'cid-Hero-Einsaetze')
    0.8, // Jarallax Speed
    0.5, // Overlay Opacity
    'rgb(0, 0, 0)', // Overlay Color
    'btn-white-outline' // Button Class
));

// --- Einsatzliste & Statistik (PHP Include Block) ---
// Da dies reiner PHP-Code ist, fügen wir ihn als String ein, umgeben von den nötigen Section-Tags.

// 🧩 1. Ausgabe sammeln
ob_start();

// Dein PHP-Code, der Inhalte direkt ausgibt
require_once $_SERVER['DOCUMENT_ROOT'] . "/assets/includes/einsaetze.php";

$jahr = isset($_GET['statistik_jahr']) ? (int) $_GET['statistik_jahr'] : date('Y');
showEinsaetze(4); 
showEinsatzStatistik($jahr);

EinsatzStatistikGesamt();

EinsatzStatistikJahresvergleich();
EinsatzStatistikDauer();
EinsatzStatistikMonate();
EinsatzStatistikWochentagTageszeit();
EinsatzStatistikDauerNachStichwort();
EinsatzStatistikStichworte();
EinsatzStatistikKategorien();
EinsatzStatistikDauerNachOrt();


// 🧩 2. Ausgabe in Variable speichern
$statistikHtml = ob_get_clean();

// 🧩 3. HTML + dynamischen Inhalt zusammenbauen
$page->addContent('
<section data-bs-version="5.1" class="article11 cid-einsatz-card" id="Einsatzliste">
    <div class="container">
        ' . $statistikHtml . '
    </div>
</section>');


// --- Galerie ---
$galleryImages = [
    ['src' => '../assets/images/img20240729193049.webp', 'alt' => 'Einsatzbild 1'],
    ['src' => '../assets/images/img20240729194140.webp', 'alt' => 'Einsatzbild 2'],
    ['src' => '../assets/images/img20240729201025-1.webp', 'alt' => 'Einsatzbild 3'],
    ['src' => '../assets/images/img20240729195048-1.webp', 'alt' => 'Einsatzbild 4'],
    ['src' => '../assets/images/e69c6e0e-6622-49f5-a608-bd65a6a9dc8e.webp', 'alt' => 'Einsatzbild 5'],
    ['src' => '../assets/images/img-3898.webp', 'alt' => 'Einsatzbild 6'],
    ['src' => '../assets/images/img-3961.webp', 'alt' => 'Einsatzbild 7'],
    ['src' => '../assets/images/img-4281.webp', 'alt' => 'Einsatzbild 8'],
    ['src' => '../assets/images/img-4898.webp', 'alt' => 'Einsatzbild 9'],
    ['src' => '../assets/images/img-20231218-wa0006.webp', 'alt' => 'Einsatzbild 10'],
    ['src' => '../assets/images/img20230708143308.webp', 'alt' => 'Einsatzbild 11'],
    ['src' => '../assets/images/img20240311194221.webp', 'alt' => 'Einsatzbild 12'],
    ['src' => '../assets/images/img20240513185812.webp', 'alt' => 'Einsatzbild 13'],
    ['src' => '../assets/images/img20240513192710-1.webp', 'alt' => 'Einsatzbild 14'],
    ['src' => '../assets/images/img20240617192228-1.webp', 'alt' => 'Einsatzbild 15'],
    ['src' => '../assets/images/img-3960.webp', 'alt' => 'Einsatzbild 16'],
    ['src' => '../assets/images/img-4897.webp', 'alt' => 'Einsatzbild 17'],
    ['src' => '../assets/images/img20240617200458.webp', 'alt' => 'Einsatzbild 18'],
    ['src' => '../assets/images/img20240715191603-1.webp', 'alt' => 'Einsatzbild 19'],
    ['src' => '../assets/images/img20240715192856.webp', 'alt' => 'Einsatzbild 20'],
    ['src' => '../assets/images/img20240715200606.webp', 'alt' => 'Einsatzbild 21']
];

$page->addContent($page->renderGalleryWithLightbox(
    'gallery02-41', // ID des Abschnitts
    'Siehe uns in Action!', // Titel der Galerie
    $galleryImages, // Array mit Bilddaten
    'Image-Gallery-Grid-With-Modal-lightbox', // Eigene ID für die Lightbox (optional)
    'Image-Gallery-Grid-With-Modal' // CID Suffix (optional)
));

// 5. Gesamte Seite rendern
echo $page->renderFullPage();

?>