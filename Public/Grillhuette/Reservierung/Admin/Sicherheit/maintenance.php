<?php
require_once '../../includes/config.php';
require_once '../../includes/User.php';
// Nur für angemeldete Administratoren zugänglich
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['flash_message'] = 'Sie haben keine Berechtigung, auf diese Seite zuzugreifen.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . getRelativePath('home'));
    exit;
}
// User-Objekt initialisieren
$user = new User();
// POST-Anfragen verarbeiten
$message = '';
$alertType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        $alertType = 'danger';
    } else {
        // Protokollbereinigung durchführen
        if (isset($_POST['run_maintenance'])) {
            $result = $user->performLogMaintenance();
            if ($result['success']) {
                $message = "Wartung erfolgreich durchgeführt: {$result['cleaned_count']} Einträge bereinigt.";
                $alertType = 'success';
                if ($result['cleaned_count'] > 0) {
                    $message .= " ({$result['login_attempts_cleaned']} Anmeldeversuche, {$result['security_log_cleaned']} Sicherheitsprotokolleinträge)";
                } else {
                    $message .= " Es wurden keine veralteten Einträge gefunden.";
                }
            } else {
                $message = 'Fehler bei der Durchführung der Wartung.';
                $alertType = 'danger';
            }
        }
    }
}
// Titel für die Seite
$pageTitle = 'Sicherheitswartung';
// Header einbinden
require_once '../../includes/header.php';
?>
<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Sicherheitswartung</h1>
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $alertType; ?> mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3>Protokollbereinigung</h3>
            </div>
            <div class="card-body">
                <p>Die Protokollbereinigung erfolgt automatisch beim Besuch der Benutzerverwaltungsseite. Sie können die
                    Wartung hier aber auch manuell starten.</p>
                <div class="alert alert-info">
                    <h4 class="alert-heading"><i class="bi bi-info-circle"></i> Bereinigungsregeln:</h4>
                    <ul class="mb-0">
                        <li>Fehlgeschlagene Anmeldeversuche: älter als 30 Tage</li>
                        <li>Erfolgreiche Anmeldungen: älter als 90 Tage</li>
                        <li>Fehlgeschlagene Anmeldungen: älter als 180 Tage</li>
                        <li>Sicherheitswarnungen (nicht kritisch): älter als 365 Tage</li>
                        <li>Passwort- und E-Mail-Änderungen: älter als 180 Tage</li>
                        <li>Kritische Sicherheitsereignisse werden <strong>nicht</strong> gelöscht</li>
                    </ul>
                </div>
                <form method="post" class="mt-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="run_maintenance" value="1">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-gear"></i> Protokollbereinigung jetzt durchführen
                    </button>
                </form>
            </div>
        </div>
        <div class="alert alert-info">
            <h4 class="alert-heading"><i class="bi bi-info-circle"></i> Hinweis zur Datenspeicherung</h4>
            <p>Die Sicherheitsprotokolle dienen zum Schutz der Anwendung und ihrer Benutzer vor potenziellen Angriffen.
            </p>
            <p>Die Speicherung dieser Daten erfolgt gemäß der DSGVO, wobei nur die notwendigen Informationen für einen
                angemessenen Zeitraum aufbewahrt werden.</p>
            <p>Bei Fragen zur Datenspeicherung wenden Sie sich bitte an den Systemadministrator.</p>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>