<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

$page = new PageBuilder(
    title: 'Kinderfeuerwehr Reichenbach | Spielerisch lernen in Waldems',
    description: 'Bei der Kinderfeuerwehr Reichenbach in Waldems entdecken Kinder spielerisch die Welt der Feuerwehr, lernen Erste Hilfe und richtiges Verhalten im Notfall. Jetzt mehr erfahren!',
    keywords: 'Kinderfeuerwehr Reichenbach, Feuerwehr Waldems Kinder, Feuerwehr für Kinder, Brandschutzerziehung, Erste Hilfe für Kinder, Feuerwehr spielerisch lernen, Kindergruppe Feuerwehr, Feuerwehr Reichenbach Waldems, Mitmachen Kinderfeuerwehr',
    canonicalUrl: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
    
);

// Füge den Fullscreen Hero Abschnitt hinzu
$page->addContent($page->renderFullscreenHero(
    id: 'header17-3i',
    cidSuffix: 'Hero-Kinderfeuerwehr',
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
    cidSuffix: 'Image-Info-Image-Right',
    title: 'Unsere Kinderfeuerwehr',
    subtitle: 'Ein Mal im Monat treffen wir uns, um gemeinsam in die Welt der Feuerwehr einzutauchen.&nbsp;<br><br>Es gibt viel zu erleben und zu entdecken, von spannenden Übungen bis hin zu interessanten Geschichten rund um die Feuerwehr.<br><br>&nbsp;', // Behalte die Zeilenumbrüche bei
    imageSrc: '../assets/images/img-20240821-wa0061.webp',
    imageAlt: 'Kinderfeuerwehr beim Lernen' // Füge einen beschreibenden Alt-Text hinzu
));

// Füge den Abschnitt "Spielerisch lernen" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-3k',
    cidSuffix: 'Image-Info-Image-Left',
    title: 'Spielerisch lernen',
    subtitle: 'Auf altersgerechte Weise lernen die Kinder, wie sie im Notfall richtig handeln können. <br><br>Erste-Hilfe gehört genauso zum Programm wie spannende Übungen zur Brandbekämpfung. <br><br>Dabei dürfen die Kinder selbst aktiv werden und kleine Herausforderungen meistern.', // Behalte die Zeilenumbrüche bei
    imageSrc: '../assets/images/img-20240821-wa0058.webp',
    imageAlt: 'Kinderfeuerwehr bei einer Übung' // Füge einen beschreibenden Alt-Text hinzu
));

// Füge den Call-to-Action Banner (Parallax) hinzu
$page->addContent($page->renderCallToActionBanner(
    id: 'header14-4z',
    cidSuffix: 'CTA-Kinderfeuerwehr',
    title: 'Interesse geweckt?',
    buttonHref: '/Mitmachen',
    buttonText: 'Jetzt mitmachen',
    btnClass: 'btn-primary'
    // Parallax background und Overlay werden durch die Methode gehandhabt
));

// Füge den Abschnitt "Entdecke die Welt der Feuerwehr" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-4x',
    cidSuffix: 'Image-Info-Image-Right',
    title: 'Entdecke die Welt der Feuerwehr',
    subtitle: 'Unsere Kinderfeuerwehr bietet eine wunderbare Gelegenheit, das Wissen über die vielfältigen Aufgaben der Feuerwehr zu vertiefen. <br><br>In interessanten und abwechslungsreichen Treffen können die Kinder viel Neues lernen und erleben.&nbsp;', // Behalte die Zeilenumbrüche bei
    imageSrc: '../assets/images/img-20240821-wa0079.webp',
    imageAlt: 'Kinderfeuerwehr erkundet die Feuerwehr' // Füge einen beschreibenden Alt-Text hinzu
));

// Rendere die vollständige Seite inklusive Head, Includes und Scripts
echo $page->renderFullPage();

?>