<?php
// Passe den Pfad zur PageBuilder.php-Datei an
require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

$page = new PageBuilder(
    title: 'Mitmachen',
    description: 'Möchtest du Teil der Freiwilligen Feuerwehr Reichenbach werden? Erfahre mehr darüber, wie du mitmachen und einen Unterschied machen kannst.',
    // Favicon wird hier nicht explizit gesetzt, es wird der Standardwert der PageBuilder-Klasse verwendet.
);

// Füge den Fullscreen Hero Abschnitt hinzu
$page->addContent($page->renderFullscreenHero(
    id: 'header17-1i',
    cidSuffix: 'Hero-Mitmachen',
    title: 'Mach mit!',
    subtitle: '', // Kein Untertitel im ursprünglichen Header
    buttonText: 'Erfahre mehr!',
    buttonHref: '#features019-1j', // Link zum ersten Inhaltsblock
    jarallaxSpeed: 0.8,
    overlayOpacity: 0.5,
    overlayColor: 'rgb(0, 0, 0)',
    btnClass: 'btn-white-outline' // Passe die Button-Klasse an
));

// Füge den Features Abschnitt (Einsatz-, Jugend-, Kinderfeuerwehr) hinzu
$page->addContent($page->renderFeatureCardsWithImages(
    id: 'features019-1j',
    cidSuffix: 'u8N1UzkJC0',
    title: '', // Kein Titel oberhalb der Karten im Original
    features: [
        [
            'img' => '../assets/images/1024d6e5-9a7b-4004-9dc4-8b416865dfe1.webp',
            'alt' => 'Einsatzabteilung',
            'title' => 'Einsatzabteilung',
            'text' => 'Unsere Einsatzabteilung trifft sich alle zwei Wochen montags zu Übungen. Hier bereiten wir uns auf verschiedene Einsatzszenarien vor und verbessern unsere Fähigkeiten im Team.',
        ],
        [
            'img' => '../assets/images/whatsapp-bild-2024-08-13-um-19.29.12-f774f342-1.webp',
            'alt' => 'Jugendfeuerwehr',
            'title' => 'Jugendfeuerwehr',
            'text' => 'Die Jugendfeuerwehr trifft sich alle zwei Wochen dienstags. Jugendliche lernen hier spielerisch die Grundlagen der Feuerwehrarbeit und erleben spannende Aktivitäten.',
        ],
        [
            'img' => '../assets/images/img-20240821-wa0061.webp',
            'alt' => 'Kinderfeuerwehr',
            'title' => 'Kinderfeuerwehr',
            'text' => 'Die Kinderfeuerwehr trifft sich alle vier Wochen montags. Hier werden die Kinder spielerisch an die Aufgaben der Feuerwehr herangeführt und nehmen an altersgerechten Übungen und Aktivitäten teil.',
        ],
    ]
));

// Füge den Textabschnitt "Interessiert?" hinzu
$page->addContent($page->renderTextArticle(
    id: 'article13-4s',
    cidSuffix: 'ukzYHmVHQq',
    title: 'Interessiert?',
    text: 'Wenn du neugierig bist und mehr über unsere Arbeit erfahren möchtest, bist du herzlich eingeladen, zu einem unserer Termine vorbeizukommen. Ob Einsatzabteilung, Jugendfeuerwehr oder Kinderfeuerwehr – schau einfach vorbei, lerne uns kennen und finde heraus, wie spannend und erfüllend das Engagement bei der Feuerwehr sein kann!',
));

// Füge den Bildabschnitt hinzu
$page->addContent($page->renderImageSection(
    id: 'image04-1l',
    cidSuffix: 'u8N5hHePcj',
    imageSrc: 'Datei/Jahreskalender Feuerwehr Reichenbach.jpg',
    imageAlt: 'Kalender'
));

// Füge den Header mit Download-Buttons hinzu
$page->addContent($page->renderDownloadHeaderWithButtons(
    id: 'header14-1n',
    cidSuffix: 'u8N5PcjmYp',
    title: 'Hier gibt\'s unseren Übungsplan',
    buttons: [
        [
            'label' => 'PDF herunterladen',
            'href' => 'Datei/Jahreskalender Feuerwehr Reichenbach final.pdf',
            'class' => 'btn-primary',
        ],
        [
            'label' => 'Bild herunterladen',
            'href' => 'Datei/Jahreskalender Feuerwehr Reichenbach.jpg',
            'class' => 'btn-primary',
        ],
    ]
));

// Rendere die vollständige Seite inklusive Head, Includes und Scripts
echo $page->renderFullPage();

?>