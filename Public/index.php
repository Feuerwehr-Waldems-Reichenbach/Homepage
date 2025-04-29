<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

$page = new PageBuilder(
    title: 'Freiwillige Feuerwehr Reichenbach | Offizielle Website',
    description: 'Willkommen bei der Freiwilligen Feuerwehr Reichenbach in Waldems. Erfahren Sie mehr über unsere Einsatzabteilung, Jugend- und Kinderfeuerwehr, aktuelle Einsätze und vieles mehr. Ihre Feuerwehr vor Ort.',
    keywords: 'Feuerwehr Reichenbach, Freiwillige Feuerwehr Waldems, Feuerwehr Reichenbach Waldems, Einsatzabteilung, Jugendfeuerwehr, Kinderfeuerwehr, Feuerwehrverein, Brandbekämpfung, Hilfeleistung, Notfall, Ehrenamt, Mitmachen, Waldems, Reichenbach, Unfalldarstellung,',
    canonicalUrl: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
);

$page->addContent($page->renderFullscreenHero(
    'header17-e',
    'Feuerwehr Reichenbach',
    'Im Glanz der Flammen, im Herzen des Dorfes.',
    'Erfahre mehr',
    '#image08-h',
    'Hero-Homepage'
));

$page->addContent($page->renderImageTeaser(
    'image08-h',
    'ImageTeaser-Image-Right',
    'Die Einsatzabteilung',
    'Unsere Einsatzabteilung ist immer bereit für schnelle und effiziente Hilfe bei Notfällen.',
    '/Einsatzabteilung',
    'Erfahre mehr',
    'assets/images/img20240617192228-1.webp',
    'Einsatzabteilung'
));

$page->addContent($page->renderImageTeaser(
    'image08-2f',
    'ImageTeaser-Image-Left',
    'Die Voraus-Helfer',
    'Unsere Voraus-Helfer leisten als Erste am Einsatzort lebensrettende Maßnahmen.',
    '/Voraus-Helfer',
    'Erfahre mehr',
    'assets/images/img20241021203058.webp',
    'Voraus-Helfer'
));

//Call to action
$page->addContent($page->renderCallToActionBanner(
    'header14-o',
    'Bereit für den Einsatz deines Lebens?',
    '/Mitmachen',
    'Jetzt mitmachen',
    'CTA-Startseite'
));

$page->addContent($page->renderImageTeaser(
    'image08-i',
    'ImageTeaser-Image-Right',
    'Die realistische Unfalldarstellung',
    'Unsere Abteilung für realistische Unfalldarstellung simuliert Unfälle, um Einsatzkräfte optimal zu schulen.',
    '/Realistische-Unfalldarstellung',
    'Erfahre mehr',
    'assets/images/img20240825115107.webp',
    'Realistische Unfalldarstellung'
));


// foto slider

$page->addContent($page->renderAnimatedGallery(
    id: 'gallery07-k',
    cidSuffix: 'Image-Slider-On-Scroll',
   rows: [
    [
        ['src' => 'assets/images/img20240715191603.webp', 'alt' => 'Feuerwehr Reichenbach'],
        ['src' => 'assets/images/img-3957.webp', 'alt' => 'Feuerwehr Reichenbach'],
        ['src' => 'assets/images/img-3392.webp', 'alt' => 'Feuerwehr Reichenbach'],
        ['src' => 'assets/images/img20240715200605.webp', 'alt' => 'Feuerwehr Reichenbach']
    ] ],
));


$page->addContent($page->renderImageTeaser(
    'image08-2h',
    'ImageTeaser-Image-Left',
    'Die Jugendfeuerwehr',
    'Unsere Jugendfeuerwehr bietet Jugendlichen im Alter von 10 bis 17 Jahren praxisnahe Einblicke in die Feuerwehrarbeit.',
    '/Jugendfeuerwehr',
    'Erfahre mehr',
    'assets/images/whatsapp-bild-2024-08-13-um-19.38.10-b7588198-kopie.webp',
    'Jugendfeuerwehr'
));

$page->addContent($page->renderImageTeaser(
    'image08-2i',
    'ImageTeaser-Image-Right',
    'Die Kinderfeuerwehr',
    'Unsere Kinderfeuerwehr richtet sich an Kinder von 6 bis 10 Jahren und vermittelt spielerisch die Grundlagen der Feuerwehrarbeit.',
    '/Kinderfeuerwehr',
    'Erfahre mehr',
    'assets/images/img-20240821-wa0082.webp',
    'Kinderfeuerwehr'
));

$page->addContent($page->renderAccordionList(
    'list01-q',
    'Wissenswertes für den Notfall',
    [
        ['q' => 'Flammen im Haus... was tun?', 'a' => 'Sofort raus und die 112 rufen. Wir kümmern uns um den Rest. Deine Sicherheit zuerst.'],
        ['q' => 'Feuerlöscher-Einsatz: Was muss ich wissen?', 'a' => 'Merk dir das Kurzwort "PASS": Ziehe die Sicherung (Pull), ziel auf die Basis des Feuers (Aim), drücke den Hebel, um zu löschen (Squeeze), und schwenke den Löscher seitlich über die Flammen (Sweep).'],
        ['q' => 'Fettbrand in der Küche: Wie reagiere ich richtig?', 'a' => 'Bloß kein Wasser! Deckel oder Löschdecke drauf, um die Flammen zu ersticken. Und immer einen Küchenlöscher parat haben.'],
        ['q' => 'Wie verhalte ich mich, wenn ich Erster am Unfallort bin?', 'a' => 'Sichere die Unfallstelle und leiste Erste Hilfe, wenn du dazu in der Lage bist. Alarmiere umgehend die Rettungsdienste. Wichtig ist, ruhig und besonnen zu handeln.'],
        ['q' => 'Was sind die wichtigsten Erste-Hilfe-Maßnahmen, die jeder kennen sollte?', 'a' => 'Die stabile Seitenlage und die Herz-Lungen-Wiederbelebung sind lebensrettende Maßnahmen, die jeder beherrschen sollte. Auch das richtige Anlegen eines Druckverbandes kann im Notfall entscheidend sein.'],
        ['q' => 'Welche Informationen sind für die Einsatzkräfte wichtig, wenn ich einen Unfall melde?', 'a' => 'Gib genau an, wo der Unfall passiert ist, was geschehen ist und ob Personen verletzt sind. Informationen über Gefahrenstoffe oder besondere Risiken sind ebenfalls hilfreich.']
    ],
    'FAQ'
));

$page->addContent($page->renderPopup());

// Seite vollständig ausgeben
echo $page->renderFullPage();
