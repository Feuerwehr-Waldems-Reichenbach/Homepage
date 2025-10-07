<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php';

$page = new PageBuilder(
    title: 'PageBuilder Testseite | Feuerwehr Reichenbach',
    description: 'Demoseite für alle verfügbaren PageBuilder-Module inklusive Standard-IDs und CID-Klassen.',
    keywords: 'PageBuilder Demo, Feuerwehr Reichenbach, Komponentenübersicht',
    canonicalUrl: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
);

// Vollflächiger Hero mit Hintergrundbild
$page->addContent($page->renderFullscreenHero(
    id: 'pb-fullscreen-hero',
    cidSuffix: 'PB-FullscreenHero',
    title: 'PageBuilder Komponentengalerie',
    subtitle: 'Diese Seite zeigt alle verfügbaren Layout-Bausteine mit den empfohlenen IDs und CID-Klassen.',
    buttonText: 'Zu den Inhaltssektionen',
    buttonHref: '#pb-section-header-layouts',
    jarallaxSpeed: 0.6,
    overlayOpacity: 0.55,
    overlayColor: 'rgba(0, 0, 0, 0.65)',
    btnClass: 'btn-primary',
    backgroundImage: '/assets/images/Feuerwehr im Einsatz gegen Flammen.webp'
));

// Vollflächiger Hero ohne Hintergrundbild
$page->addContent($page->renderFullscreenHero(
    id: 'pb-fullscreen-hero-plain',
    cidSuffix: 'PB-FullscreenHero',
    title: 'Variante ohne Hintergrundbild',
    subtitle: 'Das optionale Hintergrundbild wurde ausgelassen, um das Standard-Styling zu demonstrieren.',
    buttonText: 'Direkt zu den Karten',
    buttonHref: '#pb-feature-cards-images',
    jarallaxSpeed: 0.6,
    overlayOpacity: 0.4,
    overlayColor: 'rgba(0, 0, 0, 0.45)',
    btnClass: 'btn-secondary'
));

// Abschnittsüberschrift für Inhaltsmodule
$page->addContent($page->renderSectionHeader(
    id: 'pb-section-header-layouts',
    cidSuffix: 'PB-SectionHeader',
    title: 'Layout- und Inhaltsmodule',
    subtitle: 'Alle Komponenten verwenden die empfohlenen Abschnitts-IDs und CID-Suffixe.'
));

$page->addContent($page->renderImageTeaser(
    id: 'pb-image-teaser',
    cidSuffix: 'PB-ImageTeaser',
    title: 'Bild-Text-Teaser',
    subtitle: 'Ein kombinierter Einstieg mit Bild und Button.',
    linkHref: '#pb-accordion',
    linkText: 'Zum FAQ-Block',
    imageSrc: '/assets/images/Feuerwehr im Einsatzbereit-Modus.webp',
    imageAlt: 'Einsatzbereit'
));

$page->addContent($page->renderImageInfoBlock(
    id: 'pb-image-info',
    cidSuffix: 'PB-ImageInfo',
    title: 'Infoblock mit Bild',
    subtitle: 'Ideal für kurze Beschreibungen mit Bild rechts.',
    imageSrc: '/assets/images/Feuerwehr im Einsatz nach Unfall.webp',
    imageAlt: 'Infobild'
));

$page->addContent($page->renderFeatureCardsWithImages(
    id: 'pb-feature-cards-images',
    cidSuffix: 'PB-FeatureCardsImages',
    title: 'Feature-Karten mit Bild',
    features: [
        [
            'img' => '/assets/images/93903ff0-9517-484a-bb52-d18879a1f168.webp',
            'alt' => 'Ausbildung',
            'title' => 'Ausbildung',
            'text' => 'Modul zur Darstellung von Lern- und Trainingsinhalten.'
        ],
        [
            'img' => '/assets/images/Feuerwehr bei Sturm und Überschwemmung.webp',
            'alt' => 'Einsätze',
            'title' => 'Einsätze',
            'text' => 'Zeigt prägnante Highlights aus vergangenen Einsätzen.'
        ],
        [
            'img' => '/assets/images/234ae-8w1gl.webp',
            'alt' => 'Team',
            'title' => 'Team',
            'text' => 'Betont die Stärke des Teams in der Feuerwehr Waldems Reichenbach.'
        ],
    ]
));

$page->addContent($page->renderFeatureCardsWithButtons(
    id: 'pb-feature-cards-buttons',
    cidSuffix: 'PB-FeatureCardsButtons',
    features: [
        [
            'title' => 'Einsatzdokumentation',
            'text' => 'Schneller Verweis auf das Einsatzarchiv.',
            'button' => [
                'label' => 'Einsätze ansehen',
                'href' => '/Einsaetze/',
                'class' => 'btn-primary'
            ],
        ],
        [
            'title' => 'Mitglied werden',
            'text' => 'Call-to-Action für Interessierte.',
            'button' => [
                'label' => 'Jetzt informieren',
                'href' => '/Mitmachen/',
                'class' => 'btn-secondary'
            ],
        ],
        [
            'title' => 'Kontakt aufnehmen',
            'text' => 'Leitet Besucher zum Kontaktformular.',
            'button' => [
                'label' => 'Kontaktformular',
                'href' => '/Kontakt/',
                'class' => 'btn-primary'
            ],
        ],
    ]
));

$page->addContent($page->renderFeatureSection(
    id: 'pb-feature-section',
    cidSuffix: 'PB-FeatureSection',
    title: 'Feature-Sektion ohne Bilder',
    features: [
        [
            'title' => 'Flexible Reihenfolge',
            'text' => 'Die Module können beliebig angeordnet werden.'
        ],
        [
            'title' => 'Einheitliches Design',
            'text' => 'Dank Standard-IDs greifen überall die definierten Styles.'
        ],
        [
            'title' => 'Barrierearm',
            'text' => 'Semantische Struktur und klare Buttons unterstützen die Bedienung.'
        ],
    ]
));

$page->addContent($page->renderAccordionList(
    id: 'pb-accordion',
    cidSuffix: 'PB-Accordion',
    title: 'FAQ Accordion',
    items: [
        ['q' => 'Wie werden Abschnitte hinzugefügt?', 'a' => 'Über $page->addContent() lassen sich alle Bausteine kombinieren.'],
        ['q' => 'Welche Styles gelten?', 'a' => 'Die empfohlenen IDs und CID-Klassen aktivieren das Standard-Styling.'],
        ['q' => 'Kann eigenes CSS ergänzt werden?', 'a' => 'Ja, weitere Styles lassen sich über eigene Stylesheets einbinden.'],
    ]
));

$page->addContent($page->renderTextArticle(
    id: 'pb-text-article',
    cidSuffix: 'PB-TextArticle',
    title: 'Kompakter Artikelblock',
    text: 'Dieser Block eignet sich für einleitende Texte oder wichtige Hinweise, die sich optisch abheben sollen.'
));

$page->addContent($page->renderTextSection(
    id: 'pb-text-section',
    cidSuffix: 'PB-TextSection',
    mainTitle: 'Mehrteilige Textsektion',
    sections: [
        [
            'subtitle' => 'Unterabschnitt 1',
            'text' => 'Kann umfangreichen Text mit Listen oder Links enthalten.'
        ],
        [
            'subtitle' => 'Unterabschnitt 2',
            'text' => 'Auch HTML wie <strong>fett</strong> oder <em>kursiv</em> ist möglich.'
        ],
    ]
));

$page->addContent($page->renderImageSection(
    id: 'pb-image-section',
    cidSuffix: 'PB-ImageSection',
    imageSrc: '/assets/images/Feuerwehr im Einsatzbereit-Modus.webp',
    imageAlt: 'Volle Bildbreite'
));

$page->addContent($page->renderGalleryWithLightbox(
    id: 'pb-gallery-lightbox',
    cidSuffix: 'PB-GalleryLightbox',
    title: 'Galerie mit Lightbox',
    images: [
        ['src' => '/assets/images/5i0r9-zlsem.webp', 'alt' => 'Einsatzfahrzeug'],
        ['src' => '/assets/images/8f510736-e9b6-448f-a4a2-3ece5b30602d.webp', 'alt' => 'Übung'],
        ['src' => '/assets/images/Feuerwehr im Einsatz gegen Flammen.webp', 'alt' => 'Brandbekämpfung'],
        ['src' => '/assets/images/787360b5-c8a3-4574-9109-9de8440c0bed.webp', 'alt' => 'Teamarbeit'],
        ['src' => '/assets/images/1024d6e5-9a7b-4004-9dc4-8b416865dfe1.webp', 'alt' => 'Feuerwehrhaus'],
        ['src' => '/assets/images/234ae-8w1gl.webp', 'alt' => 'Gerätehaus']
    ]
));

$page->addContent($page->renderAnimatedGallery(
    id: 'pb-animated-gallery',
    cidSuffix: 'PB-AnimatedGallery',
    rows: [
        [
            ['src' => '/assets/images/Feuerwehr im Einsatz gegen Flammen.webp', 'alt' => 'Flammen'],
            ['src' => '/assets/images/Feuerwehr bei Sturm und Überschwemmung.webp', 'alt' => 'Unwetter'],
            ['src' => '/assets/images/Feuerwehr im Einsatz nach Unfall.webp', 'alt' => 'Technische Hilfeleistung']
        ],
        [
            ['src' => '/assets/images/93903ff0-9517-484a-bb52-d18879a1f168.webp', 'alt' => 'Jugendarbeit'],
            ['src' => '/assets/images/8f510736-e9b6-448f-a4a2-3ece5b30602d.webp', 'alt' => 'Ausbildung'],
            ['src' => '/assets/images/5i0r9-zlsem.webp', 'alt' => 'Fahrzeugflotte']
        ],
    ]
));

$page->addContent($page->renderDocumentDownloadCards(
    id: 'pb-document-cards',
    cidSuffix: 'PB-DocumentCards',
    title: 'Dokumentkarten',
    description: 'Ideal für Flyer, Satzungen oder Formulare mit Button.',
    documents: [
        [
            'title' => 'Aufnahmeantrag Einsatzabteilung',
            'description' => 'PDF mit allen Informationen zum Eintritt.',
            'href' => '/assets/files/Aufnahmeantrag.pdf'
        ],
        [
            'title' => 'Satzung des Fördervereins',
            'description' => 'Aktuelle Vereins- und Förderinformationen.',
            'href' => '/assets/files/Vereinssatzung des Fördervereins der Freiwilligen Feuerwehr .pdf'
        ],
    ]
));

$page->addContent($page->renderDownloadList(
    id: 'pb-download-list',
    cidSuffix: 'PB-DownloadList',
    title: 'Downloadliste',
    downloads: [
        [
            'title' => 'Jahreskalender 2024',
            'description' => 'Alle Termine der Feuerwehr Reichenbach auf einen Blick.',
            'href' => '/assets/files/Kalender2024.pdf'
        ],
        [
            'title' => 'Kinderfeuerwehr Anmeldung',
            'description' => 'Anmeldeformular für unsere jüngsten Mitglieder.',
            'href' => '/assets/files/Aufnahmeantrag Kinderfeuerwehr 13.04.2025.pdf'
        ],
    ]
));

$page->addContent($page->renderLinkCardGrid(
    id: 'pb-link-grid',
    cidSuffix: 'PB-LinkGrid',
    title: 'Link-Kartengrid',
    pages: [
        [
            'title' => 'Jugendfeuerwehr',
            'description' => 'Infos rund um unsere Nachwuchsabteilung.',
            'href' => '/Jugendfeuerwehr/'
        ],
        [
            'title' => 'Kinderfeuerwehr',
            'description' => 'Spielerisch lernen für die Jüngsten.',
            'href' => '/Kinderfeuerwehr/'
        ],
        [
            'title' => 'Förderverein',
            'description' => 'Unterstütze unsere Arbeit als Fördermitglied.',
            'href' => '/Foerderverein/'
        ],
        [
            'title' => 'Veranstaltungen',
            'description' => 'Kommende Termine in der Übersicht.',
            'href' => '/Veranstaltungen/'
        ],
    ]
));

// CTA-Varianten mit und ohne Hintergrundbild
$page->addContent($page->renderCallToActionBanner(
    id: 'pb-call-to-action',
    cidSuffix: 'PB-CallToAction',
    title: 'CTA mit Hintergrundbild',
    buttonHref: '/Mitmachen/',
    buttonText: 'Jetzt mitmachen',
    btnClass: 'btn-primary',
    backgroundImage: '/assets/images/Feuerwehr im Einsatz nach Unfall.webp'
));

$page->addContent($page->renderCallToActionBanner(
    id: 'pb-call-to-action-plain',
    cidSuffix: 'PB-CallToAction',
    title: 'CTA ohne Hintergrundbild',
    buttonHref: '/Foerderverein/',
    buttonText: 'Förderverein besuchen',
    btnClass: 'btn-secondary'
));

$page->addContent($page->renderCTAHeaderTextButtonBanner(
    id: 'pb-cta-header',
    cidSuffix: 'PB-CTAHeader',
    title: 'CTA-Header mit Bild',
    text: 'Ideal für aufmerksamkeitsstarke Botschaften.',
    buttonLabel: 'Mehr erfahren',
    buttonHref: '/Unterstuetzen/',
    buttonClass: 'btn-primary',
    backgroundImage: '/assets/images/Feuerwehr bei Sturm und Überschwemmung.webp'
));

$page->addContent($page->renderCTAHeaderTextButtonBanner(
    id: 'pb-cta-header-plain',
    cidSuffix: 'PB-CTAHeader',
    title: 'CTA-Header ohne Bild',
    text: 'Die Hintergrundgrafik wurde weggelassen, das Layout bleibt identisch.',
    buttonLabel: 'Zur Kontaktseite',
    buttonHref: '/Kontakt/',
    buttonClass: 'btn-secondary'
));

$page->addContent($page->renderDownloadHeaderWithButtons(
    id: 'pb-download-header',
    cidSuffix: 'PB-DownloadHeader',
    title: 'Download-Header mit Bild',
    buttons: [
        ['label' => 'PDF ansehen', 'href' => '/assets/files/Jahreskalender Feuerwehr Reichenbach final.pdf', 'class' => 'btn-primary'],
        ['label' => 'JPG ansehen', 'href' => '/assets/files/Jahreskalender Feuerwehr Reichenbach.jpg', 'class' => 'btn-secondary'],
    ],
    backgroundImage: '/assets/images/787360b5-c8a3-4574-9109-9de8440c0bed.webp'
));

$page->addContent($page->renderDownloadHeaderWithButtons(
    id: 'pb-download-header-plain',
    cidSuffix: 'PB-DownloadHeader',
    title: 'Download-Header ohne Bild',
    buttons: [
        ['label' => 'Zur Satzung', 'href' => '/assets/files/FW_Satzung_nach-GVE_16.09.2024_Waldems.pdf', 'class' => 'btn-primary'],
    ]
));

$page->addContent($page->renderCenteredCTA(
    id: 'pb-centered-cta',
    cidSuffix: 'PB-CenteredCTA',
    title: 'Zentrierter CTA mit Bild',
    buttonLabel: 'Kontakt aufnehmen',
    buttonHref: '/Kontakt/',
    backgroundImage: '/assets/images/93903ff0-9517-484a-bb52-d18879a1f168.webp'
));

$page->addContent($page->renderCenteredCTA(
    id: 'pb-centered-cta-plain',
    cidSuffix: 'PB-CenteredCTA',
    title: 'Zentrierter CTA ohne Bild',
    buttonLabel: 'Zum Veranstaltungskalender',
    buttonHref: '/Veranstaltungen/'
));

$page->addContent($page->renderGoogleMap(
    id: 'pb-google-map',
    cidSuffix: 'PB-GoogleMap',
    iframeSrc: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4288.739201693983!2d8.374920483868253!3d50.27139912395397!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47bdb1bf3444aa6f%3A0x53abe310515b94df!2sGrillh%C3%BCtte%20Reichenbach!5e0!3m2!1sde!2sde!4v1712062474346!5m2!1sde!2sde',
    theme: 'standard',
    title: 'Google Maps Platzhalter',
    description: 'Zwei-Klick-Lösung mit Einwilligung für externe Inhalte.'
));

// Optionales Popup (wird nur ausgegeben, wenn verfügbar)
$page->addContent($page->renderSectionHeader(
    id: 'pb-section-header-popup',
    cidSuffix: 'PB-SectionHeader',
    title: 'Optionale Pop-up-Integration',
    subtitle: 'Das Pop-up stammt aus assets/includes/neuigkeiten.php und kann systemabhängig deaktiviert sein.'
));

try {
    $popupDemo = $page->renderPopup();
} catch (Throwable $throwable) {
    $popupDemo = $page->renderTextArticle(
        id: 'pb-popup-fallback',
        cidSuffix: 'PB-TextArticle',
        title: 'Pop-up-Demo aktuell deaktiviert',
        text: 'Im lokalen Setup ist keine Datenbankkonfiguration vorhanden. Auf Live-Systemen kann das Pop-up über renderPopup() aktiviert werden.'
    );
}

$page->addContent($popupDemo);

// Rendere die vollständige Seite inklusive Navigation, Footer und Scripts
echo $page->renderFullPage();

?>
