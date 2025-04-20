<?php
require_once '../includes/config.php';
require_once '../includes/Reservation.php';
// Nur für angemeldete Benutzer mit verifizierten E-Mails
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_verified']) || !$_SESSION['is_verified']) {
    $_SESSION['flash_message'] = 'Sie müssen angemeldet sein und Ihre E-Mail-Adresse bestätigt haben, um eine Reservierung vornehmen zu können.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . getRelativePath('home'));
    exit;
}
// POST-Anfrage prüfen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . getRelativePath('home'));
        exit;
    }
    // Formularfelder validieren
    $startDate = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
    $endDate = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : null;
    $receiptRequested = isset($_POST['receipt_requested']) ? 1 : 0;
    // New fields for public events
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    $eventName = null;
    $displayStartDate = null;
    $displayEndDate = null;
    // Only process these if it's a public event
    if ($isPublic) {
        $eventName = isset($_POST['event_name']) ? trim($_POST['event_name']) : null;
        $displayStartDate = isset($_POST['display_start_date']) ? trim($_POST['display_start_date']) : null;
        $displayEndDate = isset($_POST['display_end_date']) ? trim($_POST['display_end_date']) : null;
        // Validate event name for public events
        if (empty($eventName)) {
            $_SESSION['flash_message'] = 'Bitte geben Sie einen Veranstaltungsnamen für öffentliche Reservierungen ein.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . getRelativePath('home'));
            exit;
        }
        // Validate display dates
        if (empty($displayStartDate) || empty($displayEndDate)) {
            $_SESSION['flash_message'] = 'Bitte wählen Sie ein Anzeige-Start und -Enddatum für öffentliche Reservierungen.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . getRelativePath('home'));
            exit;
        }
        // Validate that display dates are within reservation period
        if (
            strtotime($displayStartDate) < strtotime($startDate) ||
            strtotime($displayEndDate) > strtotime($endDate)
        ) {
            $_SESSION['flash_message'] = 'Die Anzeigedaten müssen innerhalb des Reservierungszeitraums liegen.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . getRelativePath('home'));
            exit;
        }
    }
    // Datumsvalidierung
    if (empty($startDate) || empty($endDate)) {
        $_SESSION['flash_message'] = 'Bitte wählen Sie ein Start- und Enddatum aus.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . getRelativePath('home'));
        exit;
    }
    // Standardzeiten setzen (00:00 für Startdatum, 23:59 für Enddatum)
    // um ganze Tage zu buchen
    $startDatetime = $startDate . ' 00:00:00';
    $endDatetime = $endDate . ' 23:59:59';
    // Überprüfen, ob das Enddatum nach dem Startdatum liegt
    if (strtotime($endDatetime) <= strtotime($startDatetime)) {
        $_SESSION['flash_message'] = 'Das Enddatum muss nach dem Startdatum liegen.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . getRelativePath('home'));
        exit;
    }
    // Überprüfen, ob der Mindestbuchungszeitraum von 1 Tag eingehalten wird
    $startDt = new DateTime($startDatetime);
    $endDt = new DateTime($endDatetime);
    // Berechne die Differenz in Tagen
    $diff = $startDt->diff($endDt);
    $days = $diff->days + 1; // +1 weil wir inklusive Start- und Enddatum rechnen
    if ($days < 1) {
        $_SESSION['flash_message'] = 'Der Mindestbuchungszeitraum beträgt 1 Tag.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . getRelativePath('home'));
        exit;
    }
    // Reservierung erstellen
    $reservation = new Reservation();
    $result = $reservation->create(
        $_SESSION['user_id'],
        $startDatetime,
        $endDatetime,
        $message,
        $receiptRequested,
        $isPublic,
        $eventName,
        $displayStartDate,
        $displayEndDate
    );
    if ($result['success']) {
        $_SESSION['flash_message'] = $result['message'];
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = $result['message'];
        $_SESSION['flash_type'] = 'danger';
    }
    // Weiterleiten zu den eigenen Reservierungen (PRG-Muster)
    header('Location: ' . getRelativePath('Benutzer/Meine-Reservierungen'));
    exit;
} else {
    // Bei direktem Zugriff auf die Seite zurück zur Startseite
    header('Location: ' . getRelativePath('home'));
    exit;
}