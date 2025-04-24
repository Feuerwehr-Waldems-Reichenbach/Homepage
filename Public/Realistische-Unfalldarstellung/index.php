<?php
// Passe den Pfad zur PageBuilder.php-Datei an
require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

include '../assets/includes/warningModal.php';

$page = new PageBuilder(
    title: 'Realistische Unfalldarstellung',
    description: 'Die realistische Unfalldarstellung der Freiwilligen Feuerwehr Reichenbach hilft uns und auch anderen, auf den Ernstfall vorbereitet zu sein.',
    // Favicon wird hier nicht explizit gesetzt, es wird der Standardwert der PageBuilder-Klasse verwendet.
);

// Füge den Fullscreen Hero Abschnitt hinzu
$page->addContent($page->renderFullscreenHero(
    id: 'header17-3a',
    cidSuffix: 'Hero-RUD',
    title: 'Realistische Unfalldarstellung',
    subtitle: '', // Kein Untertitel im ursprünglichen Header
    buttonText: 'Erfahre mehr!',
    buttonHref: '#image08-3b', // Link zum ersten Inhaltsblock
    jarallaxSpeed: 0.8,
    overlayOpacity: 0.5,
    overlayColor: 'rgb(0, 0, 0)',
    btnClass: 'btn-white-outline' // Passe die Button-Klasse an
));

// Füge den Abschnitt "R(ealistische)U(nfall)D(arstellung)" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-3b',
    cidSuffix: 'u8ZjuKvE8f',
    title: 'R(ealistische)U(nfall)D(arstellung)',
    subtitle: 'Um für Einsätze gewappnet zu sein, üben unsere Einsatzkräfte selbstverständlich regelmäßig. Damit dies möglichst realitätsnah ist, haben wir vor einigen Jahren unsere R(ealistische)U(nfall)D(arstellungs)-Gruppe gegründet.',
    imageSrc: '../assets/images/img20240706082638-1.webp',
    imageAlt: 'Realistische Unfalldarstellung Training' // Füge einen beschreibenden Alt-Text hinzu
));

// Füge den Call-to-Action Banner (Parallax) mit Untertitel hinzu
$page->addContent($page->renderDownloadHeaderAndTextBanner(
    id: 'header14-56',
    cidSuffix: 'uqDhRWcJbH',
    title: 'Bereit für realistische Einsätze?',
    text: 'Unsere RUD-Gruppe bietet Ihnen die Möglichkeit, realistische Trainingsszenarien zu erleben, die Ihre Einsatzkräfte optimal vorbereiten.', // Verwende den Textparameter für den Untertitel
    buttonLabel: 'Jetzt Kontakt aufnehmen',
    buttonHref: '#image08-55',
    buttonClass: 'btn-primary'
    // Parallax background und Overlay werden durch die Methode gehandhabt
));

// Füge den Abschnitt "Für authentisches Training" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-3c',
    cidSuffix: 'u8Zjvd54Wk',
    title: 'Für authentisches Training',
    subtitle: 'Die RUD-Gruppe schminkt unterschiedliche Arten von Wunden und mimt Betroffene, sodass die Einsatzkräfte unter fast realen Einsatzbedingungen lernen richtig zu reagieren.<div><br></div>', // Behalte die Zeilenumbrüche bei
    imageSrc: '../assets/images/img-20231117-wa0015.webp',
    imageAlt: 'RUD-Gruppe schminkt Wunden' // Füge einen beschreibenden Alt-Text hinzu
));

// Füge den Abschnitt "Einsatzbereitschaft über Reichenbach hinaus" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-3d',
    cidSuffix: 'u8Zjyzi0Q3',
    title: 'Einsatzbereitschaft über Reichenbach hinaus',
    subtitle: '<div>Mittlerweile ist unser Team nicht nur für die Fw Reichenbach im Einsatz, sondern ist überall bei unterschiedlichen Hilfsorganisationen oder auch für den RTK im Einsatz.</div>', // Behalte die div-Struktur bei
    imageSrc: '../assets/images/img20240706125716.webp',
    imageAlt: 'RUD-Gruppe bei einem Einsatz' // Füge einen beschreibenden Alt-Text hinzu
));

// Füge den Abschnitt "Wir unterstützen Ihre Übungen" (Bild rechts, Text links) mit E-Mail-Link hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-55',
    cidSuffix: 'uqCga1hIYy',
    title: 'Wir unterstützen Ihre Übungen',
    subtitle: '<div><span style="font-size: 1.4rem;">Solltet ihr Interesse daran haben, eine Übung realistisch zu planen und durchzuführen, könnt ihr euch gerne&nbsp;an&nbsp;uns&nbsp;wenden.</span></div><br><div><span style="font-size: 1.4rem;"><em><a href="mailto:rud@feuerwehr-waldems-reichenbach.de" class="text-info">rud@feuerwehr-waldems-reichenbach.de</a></em><br></span><br></div>', // Behalte die HTML-Struktur bei
    imageSrc: '../assets/images/img20240825115127.webp',
    imageAlt: 'RUD-Gruppe Kontakt' // Füge einen beschreibenden Alt-Text hinzu
));

// Füge die Galerie mit Lightbox anstelle des Sliders hinzu
$page->addContent($page->renderGalleryWithLightbox(
    id: 'gallery01-5b', // Verwende eine passende ID für die Galerie mit Lightbox
    cidSuffix: 'einsatzabteilung-gallery-card', // Behalte das ursprüngliche CID-Suffix bei
    title: 'Bildergalerie RUD', // Füge einen Titel für die Galerie hinzu
    images: [
        ['src' => '../assets/images/img20240706082638-1.webp', 'alt' => 'RUD Demonstration Bild 1'],
        ['src' => '../assets/images/img-20231117-wa0009.webp', 'alt' => 'RUD Demonstration Bild 2'],
        ['src' => '../assets/images/img20240706125716.webp', 'alt' => 'RUD Demonstration Bild 3'],
        ['src' => '../assets/images/img20241115183014.webp', 'alt' => 'RUD Demonstration Bild 4'],
        ['src' => '../assets/images/img20240706091207.webp', 'alt' => 'RUD Demonstration Bild 5'],
        ['src' => '../assets/images/img20241115183225.webp', 'alt' => 'RUD Demonstration Bild 6'],
        ['src' => '../assets/images/img20241115171343.webp', 'alt' => 'RUD Demonstration Bild 7'],
        ['src' => '../assets/images/img20240825112331.webp', 'alt' => 'RUD Demonstration Bild 8'],
        ['src' => '../assets/images/img20240825115142.webp', 'alt' => 'RUD Demonstration Bild 9'],
    ]
));

// Rendere die vollständige Seite inklusive Head, Includes und Scripts
echo $page->renderFullPage();



?>