<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';


$page = new PageBuilder(
    'Downloads',
    'Downloads der Freiwilligen Feuerwehr Reichenbach - Wichtige Dokumente und Links für Mitglieder und Interessierte.',
    canonicalUrl: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
);

// 2. Inhaltsblöcke hinzufügen

// --- Downloads Header ---
$page->addContent($page->renderSectionHeader(
    'Download-header', // Eigene ID für den Abschnitt
    'Downloads',
    'Hier finden Sie alle wichtigen Dokumente und Links zum Download',
    'Download-header', // CID Suffix (optional)
    '5.1',
    'margin-top: 12rem;' // Zusätzlicher Style für den Container
));

// --- Dokumente Bereich ---
$dokumente = [
    [
        'title' => 'Aufnahmeantrag Förderverein',
        'description' => 'Formular für neue Mitglieder des Fördervereins der Freiwilligen Feuerwehr Reichenbach',
        'href' => '/assets/files/Aufnahmeantrag.pdf'
    ],
    [
        'title' => 'Aufnahmeantrag Kinderfeuerwehr',
        'description' => 'Formular für neue Mitglieder der Kinderfeuerwehr der Freiwilligen Feuerwehr Reichenbach',
        'href' => '/assets/files/Aufnahmeantrag%20Kinderfeuerwehr%2013.04.2025.pdf'
    ],
    [
        'title' => 'Jahreskalender (PDF)',
        'description' => 'Jahreskalender der Feuerwehr Reichenbach als PDF-Dokument',
        'href' => '/assets/files/Jahreskalender%20Feuerwehr%20Reichenbach%20final.pdf'
    ],
    [
        'title' => 'Jahreskalender (JPG)',
        'description' => 'Jahreskalender der Feuerwehr Reichenbach als Bild-Datei',
        'href' => '/assets/files/Jahreskalender%20Feuerwehr%20Reichenbach.jpg'
    ],
    [
        'title' => 'Satzung Feuerwehr Waldems',
        'description' => 'Aktuelle Feuerwehr Satzung nach GVE vom 16.09.2024 Waldems',
        'href' => '/assets/files/FW_Satzung_nach-GVE_16.09.2024_Waldems.pdf'
    ],
    [
        'title' => 'Satzung Förderverein',
        'description' => 'Satzung des Fördervereins der Freiwilligen Feuerwehr Waldems-Reichenbach e.V.',
        'href' => '/assets/files/Vereinssatzung%20des%20Fördervereins%20der%20Freiwilligen%20Feuerwehr%20.pdf'
    ],
];

$page->addContent($page->renderDownloadList(
    'Download-Documents-Section', // Eigene ID
    'Dokumente',
    $dokumente,
    'Download-Documents-Section' // CID Suffix (optional)
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
        'href' => '/assets/Flyer/'
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
    'Download-Other-Pages-Section', // Eigene ID
    'Weitere Seiten',
    $weitereSeiten,
    'Download-Other-Pages-Section' // CID Suffix (optional)
));


// 3. Gesamte Seite rendern (inklusive Head, Navbar, Footer, Scripts)
echo $page->renderFullPage();

?>