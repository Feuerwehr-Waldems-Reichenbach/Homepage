<?php
// 1. PageBuilder-Klasse mit absolutem Pfad einbinden
require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

// 2. PageBuilder Instanz erstellen
$page = new PageBuilder(
    'Einsatzabteilung', // Seitentitel
    'Die Einsatzabteilung der Freiwilligen Feuerwehr Reichenbach. Erfahre mehr über unsere Arbeit, Ausbildung und Einsätze.' // Meta-Beschreibung
);

// 3. Zusätzliche Stylesheets hinzufügen (falls nicht im Standard-Bundle)
$page->addStyle('../assets/gallery/style.css'); // Pfad ggf. anpassen

// 4. Inhaltsblöcke hinzufügen

// --- Hero Header (Fullscreen) ---
$page->addContent($page->renderFullscreenHero(
    'header17-2w', // ID
    'Einsatzabteilung', // Titel
    '', // Subtitle (leer)
    'Erfahre mehr!', // Button Text
    '#image08-2k', // Button Link (Anker zur nächsten Sektion)
    'Hero-Einsatzabteilung', // CID Suffix
    0.8, // Jarallax Speed
    0.5, // Overlay Opacity
    'rgb(0, 0, 0)', // Overlay Color
    'btn-white-outline' // Button Class
));

// --- Text/Bild Block 1: Unsere Einsatzabteilung ---
$page->addContent($page->renderImageInfoBlock(
    'image08-2k', // ID
    'Unsere Einsatztabteilung', // Titel (Tippfehler aus HTML übernommen, ggf. zu "Einsatzabteilung" korrigieren)
    'Die Einsatzabteilung besteht aus engagierten, ehrenamtlichen Mitgliedern, die sich freiwillig für den Schutz und die Sicherheit unserer Gemeinde einsetzen.<br><br> Unsere Feuerwehrleute stehen rund um die Uhr bereit, um in Notfällen schnell und effektiv zu helfen.<br><br><br>', // Text (inkl. HTML <br>)
    '../assets/images/img20240513192710-1.webp', // Bildquelle
    'Einsatzkräfte im Einsatz', // Alternativtext für Bild (Beispiel)
    'Image-Info-Image-Right' // CID Suffix
));

// --- Text/Bild Block 2: Regelmäßige Übungen ---
$page->addContent($page->renderImageInfoBlock(
    'image08-2l', // ID
    'Regelmäßige Übungen', // Titel
    'Um unsere Einsatzkräfte optimal vorzubereiten, führen wir regelmäßige Übungsabende durch. <br><br>Diese Trainingseinheiten ermöglichen es uns, verschiedene Einsatzszenarien zu üben und die Abläufe zu festigen, um im Notfall reibungslos und sicher zu arbeiten.', // Text (inkl. HTML <br>)
    '../assets/images/e69c6e0e-6622-49f5-a608-bd65a6a9dc8e.webp', // Bildquelle
    'Feuerwehrübung', // Alternativtext für Bild (Beispiel)
    'Image-Info-Image-Left' // CID Suffix
));

// --- Call to Action Banner ---
$page->addContent($page->renderCallToActionBanner(
    'header14-51', // ID
    'Interesse geweckt?', // Titel
    '/Mitmachen', // Button Link
    'Jetzt mitmachen', // Button Text
    'CTA-Einsatzabteilung', // CID Suffix
    'btn-primary' // Button Class
));

// --- Text/Bild Block 3: Spezialisierte Fortbildung ---
$page->addContent($page->renderImageInfoBlock(
    'image08-52', // ID
    'Spezialisierte Fortbildung', // Titel
    'Gut ausgebildet zu sein, ist für uns das A und O. Bei speziellen Trainings, wie hier im Gasbrand-Übungszentrum, bereiten wir uns auf die unterschiedlichsten Einsätze vor.<br>', // Text (inkl. HTML <br>)
    '../assets/images/img20240311194221-2.webp', // Bildquelle
    'Fortbildung Gasbrand', // Alternativtext für Bild (Beispiel)
    'Image-Info-Image-Right' // CID Suffix
));

// --- Text/Bild Block 4: Realistisch und abwechslungsreich ---
$page->addContent($page->renderImageInfoBlock(
    'image08-50', // ID
    'Realistisch und abwechslungsreich', // Titel
    'In stark verrauchten Räumen, bei simulierten Bränden und bei der Personensuche, unsere Übungen sind vielseitig und orientieren sich an echten Einsatzbedingungen. <br><br>Durch abwechslungsreiche Trainings unter nahezu realen Bedingungen bleiben wir flexibel und sind auf alle Herausforderungen vorbereitet.', // Text (inkl. HTML <br>)
    '../assets/images/img20240729194549-genswap-1-neu.webp', // Bildquelle
    'Realistische Übung', // Alternativtext für Bild (Beispiel)
    'Image-Info-Image-Left' // CID Suffix
));

// --- Text/Bild Block 5: Zusammenarbeit ---
$page->addContent($page->renderImageInfoBlock(
    'image08-53', // ID
    'Zusammenarbeit für gemeinsame Einsätze', // Titel
    'Regelmäßige Übungen mit benachbarten Feuerwehren sind ein wichtiger Bestandteil unserer Arbeit. <br><br>Dabei trainieren wir gemeinsam verschiedene Einsatzszenarien, wie zum Beispiel technische Hilfeleistungen bei eingeklemmten Personen. <br><br>Diese Zusammenarbeit stärkt nicht nur den Teamgeist, sondern sorgt auch dafür, dass wir bei gemeinsamen Einsätzen effektiv und reibungslos agieren können.', // Text (inkl. HTML <br>)
    '../assets/images/img20240617193441.webp', // Bildquelle
    'Gemeinsame Übung mit anderer Feuerwehr', // Alternativtext für Bild (Beispiel)
    'Image-Info-Image-Right' // CID Suffix
));

// --- Galerie ---
$galleryImagesEa = [
    ['src' => '../assets/images/e69c6e0e-6622-49f5-a608-bd65a6a9dc8e.webp', 'alt' => 'Galeriebild 1'],
    ['src' => '../assets/images/img-3959.webp', 'alt' => 'Galeriebild 2'],
    ['src' => '../assets/images/img-20231218-wa0008.webp', 'alt' => 'Galeriebild 3'],
    ['src' => '../assets/images/img-4280.webp', 'alt' => 'Galeriebild 4'],
    ['src' => '../assets/images/8f510736-e9b6-448f-a4a2-3ece5b30602d.webp', 'alt' => 'Galeriebild 5'],
    ['src' => '../assets/images/1024d6e5-9a7b-4004-9dc4-8b416865dfe1.webp', 'alt' => 'Galeriebild 6'],
    ['src' => '../assets/images/93903ff0-9517-484a-bb52-d18879a1f168.webp', 'alt' => 'Galeriebild 7'],
    ['src' => '../assets/images/ac95b9a1-230d-438b-bea7-d28b9ce2fdeb-1.webp', 'alt' => 'Galeriebild 8'],
    ['src' => '../assets/images/dsc-133.webp', 'alt' => 'Galeriebild 9'],
    ['src' => '../assets/images/dsc-601.webp', 'alt' => 'Galeriebild 10'],
    ['src' => '../assets/images/img-1014.webp', 'alt' => 'Galeriebild 11'],
    ['src' => '../assets/images/img-1230.webp', 'alt' => 'Galeriebild 12'],
    ['src' => '../assets/images/img-3392.webp', 'alt' => 'Galeriebild 13'],
    ['src' => '../assets/images/img-4038.webp', 'alt' => 'Galeriebild 14'],
    ['src' => '../assets/images/img-4150.webp', 'alt' => 'Galeriebild 15'],
    ['src' => '../assets/images/img-4370.webp', 'alt' => 'Galeriebild 16'],
    ['src' => '../assets/images/img-4763.webp', 'alt' => 'Galeriebild 17'],
    ['src' => '../assets/images/img-20231218-wa0008-1.webp', 'alt' => 'Galeriebild 18'],
    ['src' => '../assets/images/img20230708143308-1.webp', 'alt' => 'Galeriebild 19'],
    ['src' => '../assets/images/img20240311194221-1.webp', 'alt' => 'Galeriebild 20'],
    ['src' => '../assets/images/img20240513192047.webp', 'alt' => 'Galeriebild 21'],
    ['src' => '../assets/images/img20240617192228-1.webp', 'alt' => 'Galeriebild 22'],
    ['src' => '../assets/images/img20240729193049.webp', 'alt' => 'Galeriebild 23']
];

$page->addContent($page->renderGalleryWithLightbox(
    'gallery02-2o', // ID des Abschnitts
    'Sieh uns in Action', // Titel der Galerie
    $galleryImagesEa, // Array mit Bilddaten
    'einsatzabteilung-gallery-lightbox', // Eigene ID für die Lightbox (optional)
    'Image-Gallery-Grid-With-Modal' // CID Suffix (optional)
));

// 5. Gesamte Seite rendern
echo $page->renderFullPage();

?>