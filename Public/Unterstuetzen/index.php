<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

$page = new PageBuilder(
    title: 'Unterstützen Sie die Feuerwehr Reichenbach',
    description: 'Erfahren Sie, wie Sie die Freiwillige Feuerwehr Reichenbach unterstützen können, als aktives oder passives Mitglied, mit Spenden oder durch Besuch unserer Veranstaltungen. Jetzt Aufnahmeantrag herunterladen.',
    keywords: 'Feuerwehr unterstützen, Feuerwehr spenden, Mitglied werden Feuerwehr Reichenbach, Passives Mitglied Feuerwehr, Ehrenamt Waldems, Freiwillige Feuerwehr Waldems, Spenden Feuerwehr Reichenbach, Aufnahmeantrag Feuerwehr, Feuerwehr Reichenbach Waldems',
    canonicalUrl: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
    
);

// Füge den Fullscreen Hero Abschnitt hinzu
$page->addContent($page->renderFullscreenHero(
    id: 'header17-1p',
    cidSuffix: 'Hero-Untertuetzen',
    title: 'Unterstützen',
    subtitle: '', // Kein Untertitel im ursprünglichen Header
    buttonText: 'Wie kann ich helfen?',
    buttonHref: '#features05-1q', // Link zum ersten Inhaltsblock
    jarallaxSpeed: 0.8,
    overlayOpacity: 0.5,
    overlayColor: 'rgb(0, 0, 0)',
    btnClass: 'btn-white-outline' // Passe die Button-Klasse an
));

// Füge den Features Abschnitt (Unterstützungsmöglichkeiten) hinzu
$page->addContent($page->renderFeatureCardsWithButtons(
    id: 'features05-1q',
    cidSuffix: 'Feature-Cards',
    features: [
        [
            'title' => 'Aktives Mitglied werden',
            'text' => 'Werde Teil unserer Einsatzabteilung und hilf uns bei Einsätzen, Übungen und Lehrgängen. Du bekommst eine gute Ausbildung und bist Teil einer starken Gemeinschaft.',
            'button' => [
                'label' => 'Erfahre mehr',
                'href' => '/Mitmachen',
                'class' => 'btn-secondary',
            ],
        ],
        [
            'title' => 'Passives Mitglied werden',
            'text' => 'Unterstütze uns als passives Mitglied durch finanzielle Beiträge. Deine Unterstützung hilft uns, notwendige Ausrüstung zu beschaffen und unsere Einsatzbereitschaft zu sichern.',
            'button' => [
                'label' => 'Erfahre mehr',
                'href' => '#header14-1r',
                'class' => 'btn-secondary',
            ],
        ],
        [
            'title' => 'Unterstützung bei Veranstaltungen',
            'text' => 'Besuche unsere Feste und Veranstaltungen. Deine Teilnahme trägt dazu bei, unsere Gemeinschaft zu stärken und finanzielle Mittel zu sammeln.',
            'button' => [
                'label' => 'Erfahre mehr',
                'href' => '/Veranstaltungen',
                'class' => 'btn-secondary',
            ],
        ],
    ]
));

// Füge den Download Header und Text Banner hinzu
$page->addContent($page->renderCTAHeaderTextButtonBanner(
    id: 'header14-1r',
    cidSuffix: 'CTA-Unterstuetzen',
    title: 'Lade dir den Aufnahmeantrag herunter',
    text: 'Als passives Mitglied unterstützt du die Feuerwehr mit einem jährlichen Beitrag. Diese Mittel helfen uns dabei, wichtige Ausrüstung anzuschaffen und unsere Einsatzbereitschaft aufrechtzuerhalten.',
    buttonLabel: 'Jetzt herunterladen',
    buttonHref: '/assets/files/Aufnahmeantrag.pdf',
    buttonClass: 'btn-primary'
));

// Rendere die vollständige Seite inklusive Head, Includes und Scripts
echo $page->renderFullPage();

?>