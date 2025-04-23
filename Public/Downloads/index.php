<?php
// Pfad zur PageBuilder-Klasse anpassen, falls nötig
require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

// 1. PageBuilder Instanz erstellen mit Seitentitel und Beschreibung
$page = new PageBuilder(
    'Downloads',
    'Downloads der Freiwilligen Feuerwehr Reichenbach - Wichtige Dokumente und Links für Mitglieder und Interessierte.'
    // Weitere Meta-Informationen können hier im Konstruktor oder über set-Methoden gesetzt werden
    // z.B. $page->setKeywords('Feuerwehr, Reichenbach, Downloads, Dokumente');
);

// 2. Inhaltsblöcke hinzufügen

// --- Downloads Header ---
$page->addContent($page->renderSectionHeader(
    'download-header', // Eigene ID für den Abschnitt
    'Downloads',
    'Hier finden Sie alle wichtigen Dokumente und Links zum Download',
    'download-header', // CID Suffix (optional)
    '5.1',
    'margin-top: 12rem;' // Zusätzlicher Style für den Container
));

// --- Dokumente Bereich ---
$dokumente = [
    [
        'title' => 'Aufnahmeantrag Förderverein',
        'description' => 'Formular für neue Mitglieder des Fördervereins der Freiwilligen Feuerwehr Reichenbach',
        'href' => '/Unterstuetzen/Datei/Aufnahmeantrag.pdf'
    ],
    [
        'title' => 'Aufnahmeantrag Kinderfeuerwehr',
        'description' => 'Formular für neue Mitglieder der Kinderfeuerwehr der Freiwilligen Feuerwehr Reichenbach',
        'href' => '/Mitmachen/Datei/Aufnahmeantrag%20Kinderfeuerwehr%2013.04.2025.pdf'
    ],
    [
        'title' => 'Jahreskalender (PDF)',
        'description' => 'Jahreskalender der Feuerwehr Reichenbach als PDF-Dokument',
        'href' => '/Mitmachen/Datei/Jahreskalender%20Feuerwehr%20Reichenbach%20final.pdf'
    ],
    [
        'title' => 'Jahreskalender (JPG)',
        'description' => 'Jahreskalender der Feuerwehr Reichenbach als Bild-Datei',
        'href' => '/Mitmachen/Datei/Jahreskalender%20Feuerwehr%20Reichenbach.jpg'
    ],
    [
        'title' => 'Satzung Feuerwehr Waldems',
        'description' => 'Aktuelle Feuerwehr Satzung nach GVE vom 16.09.2024 Waldems',
        'href' => '/Satzung/Datei/FW_Satzung_nach-GVE_16.09.2024_Waldems.pdf'
    ],
    [
        'title' => 'Satzung Förderverein',
        'description' => 'Satzung des Fördervereins der Freiwilligen Feuerwehr Waldems-Reichenbach e.V.',
        'href' => '/Satzung/Datei/Vereinssatzung%20des%20Fördervereins%20der%20Freiwilligen%20Feuerwehr%20.pdf'
    ],
];

$page->addContent($page->renderDownloadList(
    'download-documents-section', // Eigene ID
    'Dokumente',
    $dokumente,
    'download-documents-section' // CID Suffix (optional)
));


// --- Weitere Seiten Bereich ---
$weitereSeiten = [
    [
        'title' => 'Impressum',
        'description' => 'Rechtliche Informationen',
        'href' => '/Impressum/',
        'button' => 'Zur Seite' // Standard ist 'Zur Seite', kann angepasst werden
    ],
    [
        'title' => 'Datenschutz',
        'description' => 'Unsere Datenschutzerklärung',
        'href' => '/Datenschutz/'
    ],
    [
        'title' => 'Veranstaltungen',
        'description' => 'Flyer für kommende Events',
        'href' => '/Veranstaltungen/Flyer/'
    ],
    [
        'title' => 'Grillhütte',
        'description' => 'Anleitung zur Reservierung',
        'href' => '/Grillhuette/Reservierung/Anleitung/'
    ],
    [
        'title' => 'Grillhütte',
        'description' => 'Nutzungsbedingungen der Grillhütte',
        'href' => '/Grillhuette/Reservierung/Nutzungsbedingungen/'
    ]
];

$page->addContent($page->renderLinkCardGrid(
    'download-other-pages-section', // Eigene ID
    'Weitere Seiten',
    $weitereSeiten,
    'download-other-pages-section' // CID Suffix (optional)
));


// 3. Gesamte Seite rendern (inklusive Head, Navbar, Footer, Scripts)
echo $page->renderFullPage();

?>