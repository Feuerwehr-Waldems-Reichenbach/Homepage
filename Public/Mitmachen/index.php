<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

$page = new PageBuilder(
    title: 'Mach mit | Feuerwehr Reichenbach',
    description: 'Engagiere dich bei der Freiwilligen Feuerwehr Reichenbach! Informiere dich über Möglichkeiten in der Einsatzabteilung, Jugend- oder Kinderfeuerwehr und lade unseren Übungsplan herunter.',
    keywords: 'Mitmachen Feuerwehr, Feuerwehr Reichenbach beitreten, Ehrenamt Waldems, Freiwillige Feuerwehr Waldems, Einsatzabteilung Mitmachen, Jugendfeuerwehr Mitmachen, Kinderfeuerwehr Mitmachen, Feuerwehr Training Waldems, Übungsplan Feuerwehr, Reichenbach Waldems',
    canonicalUrl: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
);

// CSS
$page->addStyle('/assets/css/calendar.css');

// Scripts
$page->addScript('/assets/js/html2canvas.min.js');
$page->addScript('/assets/js/jspdf.umd.min.js');
$page->addScript('/assets/js/calendar-renderer.js');
$page->addScript('/Mitmachen/public-calendar.js');

// Füge den Fullscreen Hero Abschnitt hinzu
$page->addContent($page->renderFullscreenHero(
    'header17-1i',
    'Mach mit!',
    '',
    'Erfahre mehr!',
    '#features019-1j',
    'Hero-Mitmachen',
    0.8,
    0.5,
    'rgb(0, 0, 0)',
    'btn-white-outline',
    '/assets/images/a5ar3-lq538.webp'
));

// Füge den Features Abschnitt (Einsatz-, Jugend-, Kinderfeuerwehr) hinzu
$page->addContent($page->renderFeatureCardsWithImages(
    'features019-1j',
    '',
    [
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
    ],
    'Feature-Cards-With-Images'
));

// Füge den Textabschnitt "Interessiert?" hinzu
$page->addContent($page->renderTextArticle(
    'article13-4s',
    'Interessiert?',
    'Wenn du neugierig bist und mehr über unsere Arbeit erfahren möchtest, bist du herzlich eingeladen, zu einem unserer Termine vorbeizukommen. Ob Einsatzabteilung, Jugendfeuerwehr oder Kinderfeuerwehr – schau einfach vorbei, lerne uns kennen und finde heraus, wie spannend und erfüllend das Engagement bei der Feuerwehr sein kann!',
    'Text-Article'
));

// Calendar Section
$calendarHtml = '
<div class="section-calendar cid-Calendar ffr-content-center" id="calendar-section">
    <div class="container-fluid">
        <div class="row justify-content-center">
             <div class="col-12">
                  <div class="calendar-scroll-wrapper">
                      <div id="publicCalendarContainer">
                            <h2 class="text-center">Jahresplan Feuerwehr Reichenbach <span id="publicYearTitle"></span></h2>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm text-center align-middle" id="publicCalendarTable">
                                     <thead><tr id="publicMonthHeader"></tr></thead>
                                     <tbody id="publicCalendarBody"></tbody>
                                </table>
                            </div>
                            <div id="publicCalendarFooter">
                                 <div style="width: 25%; padding-right:5px;"><h5>Legende</h5><div id="publicLegendContainer"></div></div>
                                 <div style="width: 25%; padding-right:5px;"><h5>Termine</h5><ul class="list-unstyled small" id="publicSpecialFooter"></ul></div>
                                 <div style="width: 25%; padding-right:5px;"><h5>Ferien</h5><ul class="list-unstyled small" id="publicVacationFooter"></ul></div>
                                 <div style="width: 25%;"><h5>Feiertage</h5><ul class="list-unstyled small" id="publicHolidayFooter"></ul></div>
                            </div>
                      </div>
                  </div>
             </div>
        </div>
    </div>
</div>
';
$page->addContent($calendarHtml);

// Füge den Header mit Download-Buttons hinzu
$page->addContent($page->renderDownloadHeaderWithButtons(
    'header14-1n',
    'Hier gibt\'s unseren Übungsplan',
    [
        [
            'label' => 'PDF herunterladen',
            'href' => '/Mitmachen/jahresplan.pdf',
            'class' => 'btn-primary',
        ],
        [
            'label' => 'Bild herunterladen',
            'href' => '/Mitmachen/jahresplan.png',
            'class' => 'btn-primary',
        ],
    ],
    'Download-header-With-Buttons'
));

// Rendere die vollständige Seite inklusive Head, Includes und Scripts
echo $page->renderFullPage();

?>