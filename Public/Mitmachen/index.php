<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

$page = new PageBuilder(
    title: 'Mach mit | Feuerwehr Reichenbach',
    description: 'Engagiere dich bei der Freiwilligen Feuerwehr Reichenbach! Informiere dich über Möglichkeiten in der Einsatzabteilung, Jugend- oder Kinderfeuerwehr und lade unseren Übungsplan herunter.',
    keywords: 'Mitmachen Feuerwehr, Feuerwehr Reichenbach beitreten, Ehrenamt Waldems, Freiwillige Feuerwehr Waldems, Einsatzabteilung Mitmachen, Jugendfeuerwehr Mitmachen, Kinderfeuerwehr Mitmachen, Feuerwehr Training Waldems, Übungsplan Feuerwehr, Reichenbach Waldems',
    canonicalUrl: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
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
    btnClass: 'btn-white-outline', // Passe die Button-Klasse an
    backgroundImage: '/assets/images/a5ar3-lq538.webp'
));

// Füge den Features Abschnitt (Einsatz-, Jugend-, Kinderfeuerwehr) hinzu
$page->addContent($page->renderFeatureCardsWithImages(
    id: 'features019-1j',
    cidSuffix: 'Feature-Cards-With-Images',
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
    cidSuffix: 'Text-Article',
    title: 'Interessiert?',
    text: 'Wenn du neugierig bist und mehr über unsere Arbeit erfahren möchtest, bist du herzlich eingeladen, zu einem unserer Termine vorbeizukommen. Ob Einsatzabteilung, Jugendfeuerwehr oder Kinderfeuerwehr – schau einfach vorbei, lerne uns kennen und finde heraus, wie spannend und erfüllend das Engagement bei der Feuerwehr sein kann!',
));

// Füge den Interaktiven Jahresplan hinzu
$page->addContent(<<<HTML
<section id="calendar-section" class="py-5 bg-light">
    <div class="container" style="max-width: 95%;">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="text-center mb-4">
                    <h2 class="fw-bold">Unser Übungsplan</h2>
                    <p class="text-muted">Hier findest du unseren aktuellen Jahresdienstplan. Du kannst ihn interaktiv ansehen oder herunterladen.</p>
                </div>
                
                <!-- Calendar Container (Similar to Admin) -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="fas fa-calendar-alt me-2"></i>Dienstplan <span id="yearDisplay"></span></span>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-light" id="exportPngBtn"><i class="fas fa-file-image me-1"></i> PNG</button>
                            <button class="btn btn-sm btn-outline-light" id="exportPdfBtn"><i class="fas fa-file-pdf me-1"></i> PDF</button>
                        </div>
                    </div>
                    <div class="card-body p-0 bg-white overflow-auto" style="min-height: 600px;">
                        <!-- The Calendar DOM Structure -->
                        <div id="calendarContainer" class="p-4 bg-white text-dark">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h2 class="text-center fw-bold m-0 flex-grow-1">Jahresdienstplan Feuerwehr Reichenbach <span id="calendarYearTitle"></span></h2>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm text-center align-middle" id="annualPlanTable">
                                    <thead>
                                        <tr id="monthHeaderRow">
                                            <!-- JS generated -->
                                        </tr>
                                    </thead>
                                    <tbody id="calendarBody">
                                        <!-- JS generated -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 row" id="calendarFooter">
                                <div class="col-md-3">
                                    <h5>Legende</h5>
                                    <div id="legendContainer"></div>
                                </div>
                                <div class="col-md-3">
                                    <h5>Termine</h5>
                                    <ul class="list-unstyled small" id="specialEventsFooter"></ul>
                                </div>
                                <div class="col-md-3">
                                    <h5>Ferien</h5>
                                    <ul class="list-unstyled small" id="vacationsFooter"></ul>
                                </div>
                                <div class="col-md-3">
                                    <h5>Feiertage</h5>
                                    <ul class="list-unstyled small" id="holidaysFooter"></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Flag for Read-Only Mode -->
<script>
    window.calendarReadOnly = true;
</script>
<!-- Dependencies -->
<script src="/assets/js/libs/html2canvas.min.js"></script>
<script src="/assets/js/libs/jspdf.umd.min.js"></script>
<!-- Shared Logic -->
<script src="/assets/js/calendar.js"></script>
<!-- CSS for Calendar -->
<link rel="stylesheet" href="/Verwaltung/Jahresplan/style.css">
HTML
);

// Rendere die vollständige Seite inklusive Head, Includes und Scripts
echo $page->renderFullPage();

?>