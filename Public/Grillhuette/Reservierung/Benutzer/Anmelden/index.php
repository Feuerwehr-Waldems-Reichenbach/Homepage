<?php
require_once '../../includes/config.php';
require_once '../../includes/User.php';

// Wenn der Benutzer bereits angemeldet ist, weiterleiten
if (isset($_SESSION['user_id'])) {
    header('Location: ' . getRelativePath('home'));
    exit;
}

// POST-Anfrage verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['login_error'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
    } else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        // Validierung
        $errors = [];
        if (empty($email)) {
            $errors[] = 'Bitte geben Sie Ihre E-Mail-Adresse ein.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
        }
        
        if (empty($password)) {
            $errors[] = 'Bitte geben Sie Ihr Passwort ein.';
        }
        
        // Rate-Limiting prüfen
        if (empty($errors)) {
            $rateLimitCheck = checkLoginRateLimit($email);
            if (!$rateLimitCheck['allowed']) {
                // Setze die Sperrzeitinformationen in die Session
                $_SESSION['lockout_expiry'] = $rateLimitCheck['expiry_time'];
                $_SESSION['lockout_remaining'] = $rateLimitCheck['remaining_seconds'];
                $_SESSION['lockout_detected'] = true;
                
                $minutes = floor($rateLimitCheck['remaining_seconds'] / 60);
                $seconds = $rateLimitCheck['remaining_seconds'] % 60;
                
                // Unterschiedliche Meldungen je nach Dauer der Sperrzeit
                if ($rateLimitCheck['remaining_seconds'] > 3600) {
                    // Mehr als eine Stunde - wahrscheinlich ein verteilter Angriff
                    $hours = floor($minutes / 60);
                    $remainingMinutes = $minutes % 60;
                    $errors[] = "Zu viele Anmeldeversuche von verschiedenen Geräten erkannt. Ihr Konto wurde aus Sicherheitsgründen vorübergehend gesperrt. Bitte versuchen Sie es in {$hours} Stunde(n) und {$remainingMinutes} Minute(n) erneut oder setzen Sie Ihr Passwort zurück.";
                } else if ($rateLimitCheck['remaining_seconds'] > 900) {
                    // Mehr als 15 Minuten - wiederholte Fehlversuche
                    $errors[] = "Zu viele Anmeldeversuche. Ihr Konto wurde vorübergehend gesperrt. Bitte versuchen Sie es in {$minutes} Minute(n) erneut oder <a href='" . getRelativePath('Benutzer/Passwort-vergessen') . "'>setzen Sie Ihr Passwort zurück</a>.";
                } else {
                    // Standard-Sperrzeit
                    $errors[] = "Zu viele Anmeldeversuche. Bitte warten Sie noch {$minutes} Minute(n) und {$seconds} Sekunde(n), bevor Sie es erneut versuchen.";
                }
                
                // Zusätzlich verzögern, um Timing-Angriffe zu erschweren
                sleep(2);
            }
        }
        
        // Wenn keine Fehler, Login versuchen
        if (empty($errors)) {
            $user = new User();
            $result = $user->login($email, $password);
            
            if ($result['success']) {
                // Weiterleitung zur Startseite oder vorherigen Seite, falls gesetzt
                $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : getRelativePath('home');
                unset($_SESSION['redirect_after_login']);
                
                header('Location: ' . $redirect);
                exit;
            } else {
                // Fehlgeschlagenen Login protokollieren
                logFailedLogin($email);
                
                $_SESSION['login_error'] = $result['message'];
                $_SESSION['login_email'] = $email; // Email für Wiederanzeige speichern
                
                // Verzögerung hinzufügen, um Brute-Force zu erschweren
                sleep(1);
            }
        } else {
            $_SESSION['login_error'] = implode('<br>', $errors);
            $_SESSION['login_email'] = $email; // Email für Wiederanzeige speichern
        }
    }
    
    // PRG-Muster: Nach POST-Anfrage zurück zur Login-Seite weiterleiten
    header('Location: ' . getRelativePath('Benutzer/Anmelden'));
    exit;
}

// Temporäre Fehler und Email-Adresse aus der Session auslesen und entfernen
$error = '';
$email = '';

if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if (isset($_SESSION['login_email'])) {
    $email = $_SESSION['login_email'];
    unset($_SESSION['login_email']);
}

// Prüfe, ob die Sperrzeit abgelaufen ist
if (isset($_SESSION['lockout_expiry']) && time() > $_SESSION['lockout_expiry']) {
    // Sperrzeit ist abgelaufen, entferne die Variablen
    unset($_SESSION['lockout_expiry']);
    unset($_SESSION['lockout_remaining']);
}

// Titel für die Seite
$pageTitle = 'Anmelden';

// Header einbinden
require_once '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2>Anmelden</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">E-Mail-Adresse</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($email); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Passwort</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-primary">Anmelden</button>
                        <a href="<?php echo getRelativePath('Benutzer/Passwort-vergessen'); ?>">Passwort vergessen?</a>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                Noch kein Konto? <a href="<?php echo getRelativePath('Benutzer/Registrieren'); ?>">Jetzt registrieren</a>
            </div>
        </div>
    </div>
</div>

<script nonce="<?php echo $cspNonce; ?>">
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    
    togglePassword.addEventListener('click', function() {
        // Passworttyp umschalten
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // Icon umschalten
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });
    
    // Timer für die Sperrzeit
    <?php if (isset($_SESSION['lockout_detected']) && isset($_SESSION['lockout_expiry'])): ?>
    const countdownElement = document.createElement('div');
    countdownElement.classList.add('mt-3', 'p-2', 'bg-light', 'border', 'rounded', 'text-center');
    
    // Füge den Countdown zur Fehlermeldung hinzu
    const alertElement = document.querySelector('.alert-danger');
    if (alertElement) {
        alertElement.parentNode.insertBefore(countdownElement, alertElement.nextSibling);
    }
    
    // Funktion zum Formatieren der Zeit
    function formatTime(seconds) {
        if (seconds > 3600) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            return `${hours}h ${minutes}m`;
        } else {
            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${minutes}m ${secs < 10 ? '0' : ''}${secs}s`;
        }
    }
    
    // Funktion zum Aktualisieren des Timers
    function updateTimer() {
        const now = Math.floor(Date.now() / 1000);
        const expiry = <?php echo isset($_SESSION['lockout_expiry']) ? $_SESSION['lockout_expiry'] : 0; ?>;
        const remaining = Math.max(0, expiry - now);
        
        if (remaining <= 0) {
            countdownElement.innerHTML = `
                <div class="d-flex align-items-center justify-content-center">
                    <i class="bi bi-check-circle text-success me-2" style="font-size: 1.2rem;"></i>
                    <span>Die Sperrzeit ist abgelaufen. Sie können sich jetzt wieder anmelden.</span>
                </div>
            `;
            setTimeout(() => {
                window.location.reload();
            }, 1500);
            return;
        }
        
        // Fortschrittsbalken berechnen
        const totalTime = <?php echo isset($_SESSION['lockout_remaining']) ? $_SESSION['lockout_remaining'] : 900; ?>; // Default zu 15 Minuten
        const percentage = Math.min(100, Math.max(0, ((totalTime - remaining) / totalTime) * 100)).toFixed(0);
        
        countdownElement.innerHTML = `
            <div class="mb-1">Verbleibende Sperrzeit: <strong>${formatTime(remaining)}</strong></div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: ${percentage}%" 
                     aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="mt-2 small text-muted">
                <i class="bi bi-info-circle"></i> 
                Aus Sicherheitsgründen wurde Ihr Konto vorübergehend gesperrt.
                ${remaining > 900 ? ' Sie können auch <a href="' + '<?php echo getRelativePath("Benutzer/Passwort-vergessen"); ?>' + '">Ihr Passwort zurücksetzen</a>.' : ''}
            </div>
        `;
        
        setTimeout(updateTimer, 1000);
    }
    
    // Starte den Timer
    updateTimer();
    <?php endif; ?>
});
</script>

<?php require_once '../../includes/footer.php'; ?> 