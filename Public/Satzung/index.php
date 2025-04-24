<?php
// Passe den Pfad zur PageBuilder.php-Datei an
require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

$page = new PageBuilder(
    title: 'Satzungen',
    description: 'Unterstütze die Freiwillige Feuerwehr Reichenbach durch Spenden oder ehrenamtliches Engagement. Erfahre, wie du uns helfen kannst, noch besser zu werden.',
    // Favicon wird hier nicht explizit gesetzt, es wird der Standardwert der PageBuilder-Klasse verwendet.
);

// Füge das Navbar Include hinzu (obwohl renderFullPage dies standardmäßig tut, fügen wir es hier hinzu,
// um der Struktur des Original-HTML im Body zu entsprechen)
// $page->addContent($page->renderInclude('../assets/includes/navbar.php')); // renderFullPage handles this

// Füge den Abschnitt für die Satzungen (Titel, Beschreibung, Download-Karten) hinzu
$page->addContent($page->renderDocumentDownloadCards(
    id: 'content5-1',
    cidSuffix: 'u8NdAjmIcn',
    title: 'Satzungen',
    description: 'Hier finden Sie die aktuellen Satzungen zum Download:',
    documents: [
        [
            'title' => 'Feuerwehr Satzung',
            'description' => 'Feuerwehr Satzung nach GVE vom 16.09.2024 Waldems',
            'href' => 'Datei/FW_Satzung_nach-GVE_16.09.2024_Waldems.pdf',
            'button' => 'Herunterladen',
        ],
        [
            'title' => 'Förderverein Satzung',
            'description' => 'Vereinssatzung des Fördervereins der Freiwilligen Feuerwehr',
            'href' => 'Datei/Vereinssatzung des Fördervereins der Freiwilligen Feuerwehr .pdf',
            'button' => 'Herunterladen',
        ],
    ],
    // Lasse textColorClass weg, da der Standardtext im Original-HTML weiß ist und
    // die Methode dies handhaben sollte oder Bootstrap-Klassen verwendet werden.
    // Prüfe die Methode, um sicherzustellen, dass 'text-white' korrekt angewendet wird,
    // oder entferne es, wenn es nicht als Parameter unterstützt wird.
    // Die Methode renderDocumentDownloadCards hat textColorClass als Parameter.
    textColorClass: 'text-white' // Passe die Textfarbe an das Original an
));

// Füge die Footer Includes hinzu (renderFullPage handhabt diese standardmäßig)
// $page->addContent($page->renderInclude('../assets/includes/socialFooter.php'));
// $page->addContent($page->renderInclude('../assets/includes/footer.php'));

// Rendere die vollständige Seite inklusive Head, Includes und Scripts
echo $page->renderFullPage();

?>