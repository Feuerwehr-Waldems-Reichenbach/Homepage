<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php'; 

$page = new PageBuilder(
    title: 'Unsere Fahrzeuge | Feuerwehr Reichenbach',
    description: 'Entdecken Sie die Fahrzeuge der Freiwilligen Feuerwehr Reichenbach in Waldems. Erfahren Sie mehr über unser TSF-W für Brandbekämpfung und technische Hilfe sowie unser MTF für Mannschaftstransport und Logistik.',
    keywords: 'Feuerwehr Fahrzeuge, Feuerwehr Reichenbach, TSF-W, MTF, Tragkraftspritzenfahrzeug, Mannschaftstransportfahrzeug, Feuerwehrtechnik, Einsatzfahrzeuge, Fuhrpark, Feuerwehr Waldems, Reichenbach',
    canonicalUrl: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
);

// Füge den Fullscreen Hero Abschnitt hinzu
$page->addContent($page->renderFullscreenHero(
    id: 'header17-2q',
    cidSuffix: 'Hero-Fahrzeuge',
    title: 'Fahrzeuge',
    subtitle: '', // Kein Untertitel im ursprünglichen Header
    buttonText: 'Erfahre mehr',
    buttonHref: '#image08-2r',
    jarallaxSpeed: 0.8,
    overlayOpacity: 0.5,
    overlayColor: 'rgb(0, 0, 0)',
    btnClass: 'btn-white-outline' // Passe die Button-Klasse an
));

// Füge den TSF-W Abschnitt (Bild + Text) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-2r',
    cidSuffix: 'Image-Info-Image-Left',
    title: 'TSF-W (Tragkraftspritzenfahrzeug mit Wassertank)',
    subtitle: 'Unser TSF-W ist ein vielseitiges Einsatzfahrzeug, das speziell für die Brandbekämpfung und technische Hilfeleistungen ausgestattet ist. Mit einem integrierten Wassertank und leistungsstarker Pumpe sind wir in der Lage, schnell und effektiv auf Brände zu reagieren. Das Fahrzeug verfügt zudem über moderne Ausrüstung für Rettungseinsätze, wodurch es ein unverzichtbarer Bestandteil unserer Einsatztabteilung ist.',
    imageSrc: '../assets/images/img-3899.webp',
    imageAlt: 'TSF-W Feuerwehr Reichenbach' // Füge einen beschreibenden Alt-Text hinzu
));

// Füge den MTF Abschnitt (Bild + Text) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-2u',
    cidSuffix: 'Image-Info-Image-Right',
    title: 'MTF (Mannschaftstransportfahrzeug)',
    subtitle: 'Unser MTF dient hauptsächlich dem sicheren Transport unserer Einsatzkräfte zum Einsatzort. Es bietet ausreichend Platz für die gesamte Mannschaft und die notwendige Ausrüstung. Darüber hinaus wird das MTF für logistische Aufgaben und als mobiles Einsatzleitfahrzeug genutzt. Flexibilität und Schnelligkeit machen unser MTF zu einem wichtigen Fahrzeug in unserer Flotte.',
    imageSrc: '../assets/images/img20240819093838-1.webp',
    imageAlt: 'MTF Feuerwehr Reichenbach' // Füge einen beschreibenden Alt-Text hinzu
));

// Füge den Galerie Abschnitt (Animierte Galerie) hinzu
$page->addContent($page->renderAnimatedGallery(
    id: 'gallery04-2t',
    cidSuffix: 'Image-Slider-On-Scroll',
    rows: [
        // Erste Reihe
        [
            ['src' => '../assets/images/img-3672.webp', 'alt' => 'Feuerwehr Reichenbach'],
            ['src' => '../assets/images/ac95b9a1-230d-438b-bea7-d28b9ce2fdeb.webp', 'alt' => 'Feuerwehr Reichenbach'],
            ['src' => '../assets/images/img20240715193842.webp', 'alt' => 'Feuerwehr Reichenbach'],
            ['src' => '../assets/images/img-3392.webp', 'alt' => 'Feuerwehr Reichenbach']
        ],
        // Zweite Reihe
        [
            ['src' => '../assets/images/img-4899.webp', 'alt' => 'Feuerwehr Reichenbach'],
            ['src' => '../assets/images/img-4072.webp', 'alt' => 'Feuerwehr Reichenbach'],
            ['src' => '../assets/images/img-3958.webp', 'alt' => 'Feuerwehr Reichenbach'],
            ['src' => '../assets/images/787360b5-c8a3-4574-9109-9de8440c0bed.webp', 'alt' => 'Feuerwehr Reichenbach']
        ]
    ]
));

// Rendere die vollständige Seite inklusive Head, Includes und Scripts
echo $page->renderFullPage();

?>