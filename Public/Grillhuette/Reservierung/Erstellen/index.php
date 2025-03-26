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
    // Formularfelder validieren
    $startDate = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
    $endDate = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
    $startTime = isset($_POST['start_time']) ? trim($_POST['start_time']) : '12:00';
    $endTime = isset($_POST['end_time']) ? trim($_POST['end_time']) : '12:00';
    $message = isset($_POST['message']) ? trim($_POST['message']) : null;
    
    // Datumsvalidierung
    if (empty($startDate) || empty($endDate)) {
        $_SESSION['flash_message'] = 'Bitte wählen Sie ein Start- und Enddatum aus.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . getRelativePath('home'));
        exit;
    }
    
    // Zeitvalidierung
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $startTime) || 
        !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $endTime)) {
        $_SESSION['flash_message'] = 'Bitte geben Sie gültige Uhrzeiten ein (Format: HH:MM).';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . getRelativePath('home'));
        exit;
    }
    
    // Start- und Enddatum mit Uhrzeit kombinieren
    $startDatetime = $startDate . ' ' . $startTime . ':00';
    $endDatetime = $endDate . ' ' . $endTime . ':00';
    
    // Überprüfen, ob das Enddatum nach dem Startdatum liegt
    if (strtotime($endDatetime) <= strtotime($startDatetime)) {
        $_SESSION['flash_message'] = 'Das Enddatum muss nach dem Startdatum liegen.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . getRelativePath('home'));
        exit;
    }
    
    // Reservierung erstellen
    $reservation = new Reservation();
    $result = $reservation->create($_SESSION['user_id'], $startDatetime, $endDatetime, $message);
    
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