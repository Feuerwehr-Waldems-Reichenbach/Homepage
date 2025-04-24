<?php
// Passe den Pfad zur PageBuilder.php-Datei an
require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

$page = new PageBuilder(
    title: 'Kinderfeuerwehr',
    description: 'Unsere Kinderfeuerwehr bringt den Kleinsten spielerisch die Grundlagen der Feuerwehrarbeit näher. Erfahre mehr über unsere spannenden Programme.',
    // Favicon wird hier nicht explizit gesetzt, es wird der Standardwert der PageBuilder-Klasse verwendet.
);

// Füge den Fullscreen Hero Abschnitt hinzu
$page->addContent($page->renderFullscreenHero(
    id: 'header17-3i',
    cidSuffix: 'u8ZkdG7dw2',
    title: 'Kinderfeuerwehr',
    subtitle: '', // Kein Untertitel im ursprünglichen Header
    buttonText: 'Erfahre mehr!',
    buttonHref: '#image08-3j', // Link zum ersten Inhaltsblock
    jarallaxSpeed: 0.8,
    overlayOpacity: 0.5,
    overlayColor: 'rgb(0, 0, 0)',
    btnClass: 'btn-white-outline' // Passe die Button-Klasse an
));

// Füge den Abschnitt "Unsere Kinderfeuerwehr" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-3j',
    cidSuffix: 'u8ZkftLkKu',
    title: 'Unsere Kinderfeuerwehr',
    subtitle: 'Ein mal im Monat treffen wir uns, um gemeinsam in die Welt der Feuerwehr einzutauchen.&nbsp;<br><br>Es gibt viel zu erleben und zu entdecken, von spannenden Übungen bis hin zu interessanten Geschichten rund um die Feuerwehr.<br><br>&nbsp;', // Behalte die Zeilenumbrüche bei
    imageSrc: '../assets/images/img-20240821-wa0061.webp',
    imageAlt: 'Kinderfeuerwehr beim Lernen' // Füge einen beschreibenden Alt-Text hinzu
));

// Füge den Abschnitt "Spielerisch lernen" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-3k',
    cidSuffix: 'u8ZkfUVryG',
    title: 'Spielerisch lernen',
    subtitle: 'Auf altersgerechte Weise lernen die Kinder, wie sie im Notfall richtig handeln können. <br><br>Erste-Hilfe gehört genauso zum Programm wie spannende Übungen zur Brandbekämpfung. <br><br>Dabei dürfen die Kinder selbst aktiv werden und kleine Herausforderungen meistern.', // Behalte die Zeilenumbrüche bei
    imageSrc: '../assets/images/img-20240821-wa0058.webp',
    imageAlt: 'Kinderfeuerwehr bei einer Übung' // Füge einen beschreibenden Alt-Text hinzu
));

// Füge den Call-to-Action Banner (Parallax) hinzu
$page->addContent($page->renderCallToActionBanner(
    id: 'header14-4z',
    cidSuffix: 'unSlpLFA8j',
    title: 'Interesse geweckt?',
    buttonHref: '/Mitmachen',
    buttonText: 'Jetzt mitmachen',
    btnClass: 'btn-primary'
    // Parallax background und Overlay werden durch die Methode gehandhabt
));

// Füge den Abschnitt "Entdecke die Welt der Feuerwehr" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-4x',
    cidSuffix: 'unSkfgCHrc',
    title: 'Entdecke die Welt der Feuerwehr',
    subtitle: 'Unsere Kinderfeuerwehr bietet eine wunderbare Gelegenheit, das Wissen über die vielfältigen Aufgaben der Feuerwehr zu vertiefen. <br><br>In interessanten und abwechslungsreichen Treffen können die Kinder viel Neues lernen und erleben.&nbsp;', // Behalte die Zeilenumbrüche bei
    imageSrc: '../assets/images/img-20240821-wa0079.webp',
    imageAlt: 'Kinderfeuerwehr erkundet die Feuerwehr' // Füge einen beschreibenden Alt-Text hinzu
));

// Rendere die vollständige Seite inklusive Head, Includes und Scripts
echo $page->renderFullPage();

?>