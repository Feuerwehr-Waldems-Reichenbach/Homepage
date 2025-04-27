<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

$page = new PageBuilder(
    title: 'Grillhütte Reichenbach | Mieten für Feiern & Events in Waldems',
    description: 'Mieten Sie die gemütliche Grillhütte der Freiwilligen Feuerwehr Reichenbach in Waldems für Ihre Feier. Hier finden Sie alle Infos zur Reservierung, Mietbedingungen, Ausstattung und Lage (Obergasse 31).',
    keywords: 'Grillhütte mieten, Grillhütte Waldems, Grillhütte Reichenbach, Hütte mieten Waldems, Veranstaltungsort Reichenbach, Feierlocation Waldems, Grillplatz mieten, Grillhütte Reservierung, Feuerwehr Reichenbach Grillhütte, Waldems, Reichenbach Obergasse 31',
    canonicalUrl: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
    
);

// Füge den Fullscreen Hero Abschnitt hinzu
$page->addContent($page->renderFullscreenHero(
    id: 'header17-2w',
    cidSuffix: 'Hero-Grillhuette',
    title: 'Grillhütte',
    subtitle: '', // Kein Untertitel im ursprünglichen Header
    buttonText: 'Erfahre mehr!',
    buttonHref: '#features019-4d', // Link zum ersten Inhaltsblock
    jarallaxSpeed: 0.8,
    overlayOpacity: 0.5,
    overlayColor: 'rgb(0, 0, 0)',
    btnClass: 'btn-white-outline' // Passe die Button-Klasse an
));

// Füge den Features Abschnitt (Grillhütte Infos) hinzu
$page->addContent($page->renderFeatureSection(
    id: 'features019-4d',
    cidSuffix: 'Feature-Cards-Grillhuette',
    title: 'Die Grillhütte in Waldems Reichenbach',
    features: [
        [
            'title' => 'Reservierung',
            'text' => 'Reservieren Sie die Grillhütte bequem über unseren Kalender. Registrieren Sie sich und wählen Sie Ihr Wunschdatum.',
        ],
        [
            'title' => 'Mietbedingungen',
            'text' => 'Die Miete beträgt 100 € pro Tag (von 12:00 Uhr bis 12:00 Uhr des nächsten Tages). Eine Kaution von 100 € ist erforderlich.',
        ],
        [
            'title' => 'Ausstattung',
            'text' => 'Im Mietpreis enthalten sind 1 m³ Wasser, 5 kW/h Strom und 5 Biertisch-Garnituren. Jede zusätzliche Garnitur kostet 1 €.',
        ],
        [
            'title' => 'Sauberkeit',
            'text' => 'Bitte hinterlassen Sie die Grillhütte und die Toiletten sauber. Dies hilft uns, die Anlage in gutem Zustand zu halten.',
        ],
        [
            'title' => 'Schlüsselübergabe',
            'text' => 'Die Schlüsselübergabe und -rückgabe erfolgt durch den/die Verantwortliche(n) der Grillhütte. Die Rückgabe der Hütte muss spätestens bis 12:00 Uhr am folgenden Tag erfolgen.<br>',
        ],
        [
            'title' => 'Adresse',
            'text' => 'Die Grillhütte befindet sich in der Obergasse 31, 65529 Waldems. Unten auf der Seite finden Sie eine Google Maps-Karte zur genauen Standortanzeige.',
        ],
    ]
));

// Füge den Abschnitt "Jetzt Reservieren" (Bild + Text + Button) hinzu
$page->addContent($page->renderImageTeaser(
    id: 'image08-4o',
    cidSuffix: 'Image-Info-Image-Right',
    title: 'Jetzt Reservieren',
    subtitle: 'Unsere Grillhütte in Waldems Reichenbach ist von schöner Natur umgeben und bietet einen herrlichen Ausblick. Perfekt für entspannte Feiern und gesellige Treffen.',
    linkHref: '/Grillhuette/Reservierung/',
    linkText: 'Jetzt reservieren',
    imageSrc: '../assets/images/img20240514182422.webp',
    imageAlt: 'Grillhütte Waldems Reichenbach',
    btnClass: 'btn-secondary'
));

// Füge den Abschnitt "Sanitäre Einrichtungen" (Bild + Text) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-4i',
    cidSuffix: 'Image-Info-Image-Left',
    title: 'Sanitäre Einrichtungen',
    subtitle: 'Unsere Grillhütte verfügt über eine Toilette. Strom- und Wasseranschluss sind ebenfalls vorhanden.',
    imageSrc: '../assets/images/img20240629134359.webp',
    imageAlt: 'Grillhütte Waldems Reichenbach'
));

// Füge den Abschnitt "Gemütliche Atmosphäre" (Bild + Text) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-4m',
    cidSuffix: 'Image-Info-Image-Right',
    title: 'Gemütliche Atmosphäre',
    subtitle: 'Die Hütte bietet eine rustikale und gemütliche Atmosphäre für Ihre Gäste. Der überdachte Bereich schützt bei jedem Wetter und sorgt für ein angenehmes Ambiente.',
    imageSrc: '../assets/images/img20240629134839.webp',
    imageAlt: 'Grillhütte Waldems Reichenbach'
));

// Füge den Call-to-Action Banner (Parallax) hinzu
$page->addContent($page->renderCallToActionBanner(
    id: 'header14-4n',
    cidSuffix: 'CTA-Grillhuette',
    title: 'Interesse geweckt?',
    buttonHref: '/Grillhuette/Reservierung/',
    buttonText: 'Jetzt reservieren',
    btnClass: 'btn-primary'
    // Parallax background und Overlay werden durch die Methode gehandhabt
));

// Füge den Abschnitt "Weitläufiges Gelände" (Bild + Text) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-4j',
    cidSuffix: 'Image-Info-Image-Left',
    title: 'Weitläufiges Gelände',
    subtitle: 'Das großzügige Gelände bietet ausreichend Platz für Biertischgarnituren und zum Feiern unter freiem Himmel. Die natürliche Umgebung mit Bäumen spendet angenehmen Schatten.',
    imageSrc: '../assets/images/img20240514182602.webp',
    imageAlt: 'Grillhütte Waldems Reichenbach'
));

// Füge den Abschnitt "Perfekt für Feiern" (Bild + Text) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-4l',
    cidSuffix: 'Image-Info-Image-Right',
    title: 'Perfekt für Feiern',
    subtitle: 'Ob Geburtstag, Familientreffen oder Vereinsfeier, unsere Grillhütte bietet den idealen Rahmen für Ihre Veranstaltung. Das überdachte Häuschen ermöglicht auch bei wechselhaftem Wetter einen angenehmen Aufenthalt.',
    imageSrc: '../assets/images/img20240629170654.webp',
    imageAlt: 'Grillhütte Waldems Reichenbach'
));

// Füge den Google Maps Abschnitt hinzu
$page->addContent($page->renderGoogleMap(
    id: 'map01-1z',
    cidSuffix: 'Map-Google',
    iframeSrc: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4288.739201693983!2d8.374920483868253!3d50.27139912395397!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47bdb1bf3444aa6f%3A0x53abe310515b94df!2sGrillh%C3%BCtte%20Reichenbach!5e0!3m2!1sde!2sde!4v1712062474346!5m2!1sde!2sde' // Verwende die URL aus dem Original
));

// Rendere die vollständige Seite inklusive Head, Includes und Scripts
echo $page->renderFullPage();

?>