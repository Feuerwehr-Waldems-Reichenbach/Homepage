<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/PageBuilder.php'; // Passe den Pfad zur PageBuilder.php-Datei an

$page = new PageBuilder(
    title: 'Feuerwehrhaus',
    description: 'Erfahre mehr über das Feuerwehrhaus der Freiwilligen Feuerwehr Reichenbach. Schau dir unsere moderne Ausstattung und Einrichtungen an.',
);

// Füge den Fullscreen Hero Abschnitt hinzu
$page->addContent($page->renderFullscreenHero(
    id: 'header17-2w',
    cidSuffix: 'u8UNI2kWf6',
    title: 'Feuerwehrhaus',
    subtitle: '', // Kein Untertitel im ursprünglichen Header
    buttonText: 'Erfahre mehr!',
    buttonHref: '#image08-2k1', // Link zum ersten Inhaltsblock korrigiert
    jarallaxSpeed: 0.8,
    overlayOpacity: 0.5,
    overlayColor: 'rgb(0, 0, 0)',
    btnClass: 'btn-white-outline' // Passe die Button-Klasse an
));

// Füge den Abschnitt "Unsere Umkleideräume" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-2k1',
    cidSuffix: 'einsatzabteilung-image-card-right', // Die CID
    title: 'Unsere Umkleideräume',
    subtitle: 'Die Umkleideräume sind ein zentraler Bestandteil unseres Feuerwehrhauses. Hier bereiten sich unsere Einsatzkräfte auf ihre Einsätze vor und legen ihre persönliche Schutzausrüstung an. <br><br>Für jedes Mitglied steht ein eigener, klar gekennzeichneter Spind bereit, in dem Helm, Schutzkleidung und Zusatzausrüstung übersichtlich und jederzeit griffbereit verstaut sind.',
    imageSrc: '../assets/images/5i0r9-zlsem.webp',
    imageAlt: 'Umkleideräume und Spinde'
));

// Füge den Abschnitt "Unser Schulungsraum" (Bild rechts, Text links) hinzu
// Hinweis: Das Original-HTML verwendet hier eine Klasse "image08 cid-einsatzabteilung-image-card-left",
// aber die Struktur im HTML selbst (Text-Spalte zuerst, dann Bild-Spalte) führt zu Text links, Bild rechts,
// was der renderImageInfoBlock-Methode entspricht. Die cidSuffix ist nur ein Bezeichner.
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-2l2',
    cidSuffix: 'einsatzabteilung-image-card-left', // Die CID aus dem Original
    title: 'Unser Schulungsraum',
    subtitle: 'In unserem Schulungsraum werden Theoriephasen und Lehrgänge Wie zum Beispiel Erste‑Hilfe‑Kurse durchgeführt. <br><br>Die Räumlichkeit entstand in Eigenarbeit unserer Einsatzkräfte und bietet heute eine ruhige, gut ausgestattete Umgebung für Aus‑ und Weiterbildungen. Eine kleine Küche erleichtert den Ablauf längerer Veranstaltungen. <br><br>Dass wir den Raum in dieser Qualität nutzen können, verdanken wir der Unterstützung unseres Fördervereins und zahlreicher Spenderinnen und Spender.',
    imageSrc: '../assets/images/yszsn-llw6i.webp',
    imageAlt: 'Schulungsraum mit kleiner Küche'
));

// Füge den Abschnitt "Unser Gemeinschaftsraum" (Bild rechts, Text links) hinzu
$page->addContent($page->renderImageInfoBlock(
    id: 'image08-2k3',
    cidSuffix: 'einsatzabteilung-image-card-right', // Die CID
    title: 'Unser Gemeinschaftsraum',
    subtitle: 'Unser Aufenthaltsraum ist das Ergebnis vieler ehrenamtlich geleisteter Arbeitsstunden unserer Mitglieder. <br><br>In sorgfältiger Handarbeit entstand ein rustikaler Raum mit sichtbarem Gebälk, Holzwänden und langen Tischen, deren rote Sitzpolster einen warmen Akzent setzen. Die aufgehängten Geräte erinnern dezent an unseren Einsatzalltag und verleihen der Stube ihren einzigartigen Charakter.<br><br>Dieses Projekt wäre ohne unsere fleißigen Helfer und finanzielle Unterstützung nicht möglich gewesen. Heute bildet der Raum einen zentralen Treffpunkt für Besprechungen, kameradschaftliche Veranstaltungen und gemeinsame Stunden nach Einsätzen und Übungen.',
    imageSrc: '../assets/images/234ae-8w1gl.webp',
    imageAlt: 'Aufenthaltsraum'
));

// Rendere die vollständige Seite inklusive Head, Includes und Scripts
echo $page->renderFullPage();

?>