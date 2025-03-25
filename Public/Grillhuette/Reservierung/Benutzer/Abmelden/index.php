<?php
require_once '../../includes/config.php';

// Session löschen
session_destroy();

// Zurück zur Startseite
header('Location: ' . getRelativePath('home'));
exit;
?> 