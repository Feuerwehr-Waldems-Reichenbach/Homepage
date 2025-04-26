<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

$page = new PageBuilder(
    title: 'Jugendfeuerwehr',
    description: 'Die Jugendfeuerwehr Reichenbach bietet Jugendlichen die Möglichkeit, in die Welt der Feuerwehr einzutauchen. Erfahre mehr über unsere Aktivitäten und wie du mitmachen kannst.',
    canonicalUrl: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
    
);

// Füge den Fullscreen Hero Abschnitt hinzu
$page->addContent($page->renderFullscreenHero(
    id: 'header17-3f',
    cidSuffix: 'Hero-Jugendfeuerwehr',
    title: 'Jugendfeuerwehr',
    subtitle: '', // Kein Untertitel im ursprünglichen Header
    buttonText: 'Erfahre mehr!',
    buttonHref: '#image08-3g', // Link zum ersten Inhaltsblock
    jarallaxSpeed: 0.8,
    overlayOpacity: 0.5,
    overlayColor: 'rgb(0, 0, 0)',
    btnClass: 'btn-white-outline' // Passe die Button-Klasse an
));

// Füge den Abschnitt "Unsere Jugendfeuerwehr" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-3g',
    cidSuffix: 'Image-Info-Image-Right',
    title: 'Unsere Jugendfeuerwehr',
    subtitle: 'In den wöchentlichen Übungen erhalten die Jugendlichen eine umfassende Ausbildung in Feuerwehrtechniken, die sie optimal auf die Einsatzabteilung vorbereiten. Dabei stehen Teamarbeit und der Spaß an der gemeinsamen Tätigkeit im Vordergrund.<br>',
    imageSrc: '../assets/images/whatsapp-bild-2024-08-13-um-19.27.44-b92daf36.webp',
    imageAlt: 'Jugendfeuerwehr Übung' // Füge einen beschreibenden Alt-Text hinzu
));

// Füge den Abschnitt "Mehr als Feuerlöschen" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-3h',
    cidSuffix: 'Image-Info-Image-Left',
    title: 'Mehr als Feuerlöschen: Vielseitige Fähigkeiten für den Ernstfall',
    subtitle: 'Neben den Grundlagen der Brandbekämpfung werden auch die Fähigkeiten der Technischen Hilfeleistung und der Ersten Hilfe geübt.',
    imageSrc: '../assets/images/whatsapp-bild-2024-08-13-um-19.19.20-e6c3d2c2.webp',
    imageAlt: 'Jugendfeuerwehr Erste Hilfe Übung' // Füge einen beschreibenden Alt-Text hinzu
));

// Füge den Call-to-Action Banner (Parallax) hinzu
$page->addContent($page->renderCallToActionBanner(
    id: 'header14-4y',
    cidSuffix: 'CTA-Jugendfeuerwehr',
    title: 'Interesse geweckt?',
    buttonHref: '/Mitmachen',
    buttonText: 'Jetzt mitmachen',
    btnClass: 'btn-primary'
    // Parallax background und Overlay werden durch die Methode gehandhabt
));

// Füge den Abschnitt "Abenteuer und Teamgeist" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-4u',
    cidSuffix: 'Image-Info-Image-Right',
    title: 'Abenteuer und Teamgeist: Zeltlager der Jugendfeuerwehr',
    subtitle: 'Einmal im Jahr verbringt die Jugendfeuerwehr eine Woche im Zeltlager. Neben der spannenden Lagerolympiade stehen vielfältige Freizeitaktivitäten auf dem Programm, darunter Kanufahren, Klettern, Minigolf und Ausflüge in Freizeitparks.',
    imageSrc: '../assets/images/img-20240813-wa0082.webp',
    imageAlt: 'Jugendfeuerwehr Zeltlager' // Füge einen beschreibenden Alt-Text hinzu
));

// Füge den Abschnitt "Jährliche Weihnachtsbaum-Sammelaktion" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-4v',
    cidSuffix: 'Image-Info-Image-Left',
    title: 'Jährliche Weihnachtsbaum-Sammelaktion',
    subtitle: '<em>"Grünt der Weihnachtsbaum nicht mehr,</em><div><em>kommt die Jugendfeuerwehr daher.</em></div><div><em>Sammelt fleißig Bäume ein,</em></div><div><em>und verbrennt sie dann im Feuerschein."</em></div><br><br><div>Gegen eine kleine Spende sammeln wir Anfang des Jahres die alten Weihnachtsbäume in Reichenbach ein.</div>',
    imageSrc: '../assets/images/whatsapp-bild-2024-08-13-um-19.13.07-5fa961a0-1.webp',
    imageAlt: 'Jugendfeuerwehr Weihnachtsbaum sammeln' // Füge einen beschreibenden Alt-Text hinzu
));

// Rendere die vollständige Seite inklusive Head, Includes und Scripts
echo $page->renderFullPage();

?>