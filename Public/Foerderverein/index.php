<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

$page = new PageBuilder(
    title: 'Förderverein',
    description: 'Der Förderverein der Freiwilligen Feuerwehr Reichenbach unterstützt unsere Arbeit durch verschiedene Projekte und Aktionen. Erfahre mehr über unsere Arbeit und wie du Mitglied werden kannst.',
    canonicalUrl: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
    
);

// Füge den Fullscreen Hero Abschnitt hinzu
$page->addContent($page->renderFullscreenHero(
    id: 'header17-58',
    cidSuffix: 'Hero-Foerderverein',
    title: 'Förderverein',
    subtitle: '', // Kein Untertitel im ursprünglichen Header
    buttonText: 'Erfahre mehr!',
    buttonHref: '#article07-57', // Link zum ersten Inhaltsblock
    jarallaxSpeed: 0.8,
    overlayOpacity: 0.5,
    overlayColor: 'rgb(0, 0, 0)',
    btnClass: 'btn-white-outline' // Passe die Button-Klasse an
));

// Füge den Textabschnitt "Förderverein" hinzu
$page->addContent($page->renderTextSection(
    id: 'article07-57',
    cidSuffix: 'Text-Section-Foerderverein',
    mainTitle: 'Förderverein',
    sections: [
        [
            'subtitle' => '', // Kein Untertitel für diesen Textblock
            'text' => 'Der Förderverein der Fw Reichenbach e. V. hat viele Aufgaben.<br><br>Der Verein fördert unter anderem die Nachwuchsarbeit. Die Kinder und Jugendfeuerwehr werden durch Kleidung, Übungsmaterial und Zuschüsse für Ausflüge und Kreiszeltlager unterstützt.<br><br>Zudem wird der Brandschutz des Ortes durch Ergänzung der Ausrüstung und Ausbildung der Einsatzabteilung gefördert. Durch den Förderverein konnte viel Ausrüstungsmaterial zusätzlich beschafft werden.<br><br>Nicht zu vergessen ist natürlich auch der soziale Aspekt für den gesamten Ort.<br>Der Förderverein richtet mit Hilfe des Festausschusses unterschiedliche Feste aus. Beispielsweise das traditionelle Hähnchengrillen, bei dem frische Hähnchen noch von Hand über Feuer gegrillt werden.<br>',
        ],
    ]
));

// Füge den Call-to-Action Banner hinzu
$page->addContent($page->renderCenteredCTA(
    id: 'header14-3o',
    cidSuffix: 'CTA-Foerderverein',
    title: 'Du möchtest uns unterstützen?',
    buttonLabel: 'Erfahre mehr',
    buttonHref: '/Unterstuetzen' // Link zum Unterstützen
));


// Rendere die vollständige Seite inklusive Head, Includes und Scripts
echo $page->renderFullPage();

?>