<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

$page = new PageBuilder(
    title: 'Voraus-Helfer Feuerwehr Reichenbach | Feuerwehr Reichenbach',
    description: 'Unsere Voraus-Helfer der Freiwilligen Feuerwehr Reichenbach in Waldems bieten schnelle medizinische Erstversorgung bei lebensbedrohlichen Zuständen, wenn der Rettungsdienst länger braucht. Erfahren Sie mehr über ihre wichtige ehrenamtliche Arbeit.',
    keywords: 'Voraus-Helfer, Feuerwehr Reichenbach, First Responder Waldems, Schnelle Notfallhilfe, medizinische Erstversorgung, lebensbedrohlicher Zustand, Feuerwehr Waldems Reichenbach, Ehrenamtliche Helfer, Helfer vor Ort, Spenden Feuerwehr Reichenbach',
    canonicalUrl: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
    
);

// Füge den Fullscreen Hero Abschnitt hinzu
$page->addContent($page->renderFullscreenHero(
    id: 'header17-35',
    cidSuffix: 'Hero-Voraus-Helfer',
    title: 'Voraus-Helfer',
    subtitle: '', // Kein Untertitel im ursprünglichen Header
    buttonText: 'Erfahre mehr!',
    buttonHref: '#image08-34', // Link zum ersten Inhaltsblock
    jarallaxSpeed: 0.8,
    overlayOpacity: 0.5,
    overlayColor: 'rgb(0, 0, 0)',
    btnClass: 'btn-white-outline' // Passe die Button-Klasse an
));

// Füge den Abschnitt "Unsere Voraushelfer" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-34',
    cidSuffix: 'Image-Info-Image-Right',
    title: 'Unsere Voraushelfer',
    subtitle: 'Unsere Voraushelfer sind alle aktive Mitglieder der Einsatzabteilung. Sie kommen zum Einsatz, wenn der Rettungswagen die Hilfsfrist nicht einhalten kann und ein lebensbedrohlicher Zustand vorliegt. Ursprünglich planten wir nur die Anschaffung eines AED-Geräts zum Eigenschutz unserer Einsatzkräfte. Doch schließlich entschlossen wir uns, eine Voraushelfer-Gruppe für ganz Reichenbach zu gründen, zum Nutzen aller Bürger.',
    imageSrc: '../assets/images/img20241021203058.webp',
    imageAlt: 'Voraushelfer der Feuerwehr Reichenbach' // Füge einen beschreibenden Alt-Text hinzu
));

// Füge den Abschnitt "Einsatz und Auswirkungen" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-36',
    cidSuffix: 'Image-Info-Image-Left',
    title: 'Einsatz und Auswirkungen',
    subtitle: 'Zu unserem Ausrückebereich zählen neben Reichenbach noch drei weitere Waldemser Ortsteile. Mit einer Ausrückzeit von unter 3 Minuten können wir die therapiefreie Zeit wesentlich verkürzen. Die Patienten sind oft erleichtert, ein bekanntes Gesicht zu sehen. So tragen wir dazu bei, in Notfällen schnelle Hilfe zu leisten.',
    imageSrc: '../assets/images/img20241021204127.webp',
    imageAlt: 'Voraushelfer im Einsatzgebiet' // Füge einen beschreibenden Alt-Text hinzu
));

// Füge den Abschnitt "Organisation und Finanzierung" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-5a',
    cidSuffix: 'Image-Info-Image-Right',
    title: 'Organisation und Finanzierung',
    subtitle: 'Wir finanzieren uns ausschließlich durch Spenden. Unsere Helfer zeigen großes Engagement und bezahlen Teile ihrer Ausrüstung sogar privat. Mittlerweile sind wir weit über Reichenbach hinaus als sehr motivierte und gut ausgebildete Truppe bekannt. Diese Anerkennung bestärkt uns in unserer wichtigen Arbeit.',
    imageSrc: '../assets/images/img-20240821-wa0031.webp',
    imageAlt: 'Voraushelfer Ausrüstung' // Füge einen beschreibenden Alt-Text hinzu
));


// Rendere die vollständige Seite inklusive Head, Includes und Scripts
echo $page->renderFullPage();

?>