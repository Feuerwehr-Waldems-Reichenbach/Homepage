<?php
require_once 'includes/config.php';
require_once 'includes/User.php';

// Nur für angemeldete Benutzer zugänglich
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bitte melden Sie sich an, um auf Ihr Profil zuzugreifen.';
    $_SESSION['flash_type'] = 'warning';
    $_SESSION['redirect_after_login'] = 'profile.php';
    header('Location: login.php');
    exit;
}

// Benutzerinformationen abrufen
$user = new User();
$userData = $user->getUserById($_SESSION['user_id']);

if (!$userData) {
    $_SESSION['flash_message'] = 'Benutzer nicht gefunden.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$errors = [];
$success = false;
$emailChangeSuccess = false;
$passwordChangeSuccess = false;

// POST-Anfrage für Profilaktualisierung verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
    } else {
        // Profil-Update
        if (isset($_POST['update_profile'])) {
            $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
            $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
            
            // Validierung
            if (empty($firstName)) {
                $errors[] = 'Bitte geben Sie Ihren Vornamen ein.';
            }
            
            if (empty($lastName)) {
                $errors[] = 'Bitte geben Sie Ihren Nachnamen ein.';
            }
            
            // Wenn keine Fehler, Profil aktualisieren
            if (empty($errors)) {
                $result = $user->updateProfile($_SESSION['user_id'], $firstName, $lastName, $phone);
                
                if ($result['success']) {
                    $success = true;
                    $userData['first_name'] = $firstName;
                    $userData['last_name'] = $lastName;
                    $userData['phone'] = $phone;
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
        
        // E-Mail-Update
        if (isset($_POST['update_email'])) {
            $newEmail = isset($_POST['new_email']) ? trim($_POST['new_email']) : '';
            $password = isset($_POST['email_password']) ? $_POST['email_password'] : '';
            
            // Validierung
            if (empty($newEmail)) {
                $errors[] = 'Bitte geben Sie Ihre neue E-Mail-Adresse ein.';
            } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
            }
            
            if (empty($password)) {
                $errors[] = 'Bitte geben Sie Ihr aktuelles Passwort ein, um Ihre E-Mail-Adresse zu ändern.';
            }
            
            // Wenn keine Fehler, E-Mail aktualisieren
            if (empty($errors)) {
                $result = $user->updateEmail($_SESSION['user_id'], $newEmail, $password);
                
                if ($result['success']) {
                    $emailChangeSuccess = true;
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
        
        // Passwort-Update
        if (isset($_POST['update_password'])) {
            $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
            $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            
            // Validierung
            if (empty($currentPassword)) {
                $errors[] = 'Bitte geben Sie Ihr aktuelles Passwort ein.';
            }
            
            if (empty($newPassword)) {
                $errors[] = 'Bitte geben Sie ein neues Passwort ein.';
            } elseif (strlen($newPassword) < 8) {
                $errors[] = 'Das neue Passwort muss mindestens 8 Zeichen lang sein.';
            }
            
            if ($newPassword !== $confirmPassword) {
                $errors[] = 'Die neuen Passwörter stimmen nicht überein.';
            }
            
            // Wenn keine Fehler, Passwort aktualisieren
            if (empty($errors)) {
                $result = $user->updateProfile($_SESSION['user_id'], $userData['first_name'], $userData['last_name'], $userData['phone'], $currentPassword, $newPassword);
                
                if ($result['success']) {
                    $passwordChangeSuccess = true;
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
        
        // Daten per E-Mail senden
        if (isset($_POST['send_user_data'])) {
            require_once BASE_PATH . '/Private/Email/emailSender.php';
            
            // Reservierungen des Benutzers abrufen
            require_once 'includes/Reservation.php';
            $reservation = new Reservation();
            $userReservations = $reservation->getByUserId($_SESSION['user_id']);
            
            // HTML für die E-Mail erstellen
            $emailBody = '<h2>Ihre gespeicherten Daten</h2>';
            
            // Persönliche Daten
            $emailBody .= '<h3>Persönliche Informationen</h3>';
            $emailBody .= '<table style="border-collapse: collapse; width: 100%; margin-bottom: 20px;">';
            $emailBody .= '<tr><td style="padding: 8px; border: 1px solid #ddd; width: 30%;"><strong>Name:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . escape($userData['first_name'] . ' ' . $userData['last_name']) . '</td></tr>';
            $emailBody .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>E-Mail:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . escape($userData['email']) . '</td></tr>';
            $emailBody .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Telefon:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . escape($userData['phone'] ?: '-') . '</td></tr>';
            $emailBody .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Registriert am:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . ($userData['created_at'] ? date('d.m.Y H:i', strtotime($userData['created_at'])) : '-') . '</td></tr>';
            $emailBody .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Status:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . ($userData['is_verified'] ? 'Verifiziert' : 'Nicht verifiziert') . '</td></tr>';
            $emailBody .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Rolle:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . ($userData['is_admin'] ? 'Administrator' : 'Benutzer') . '</td></tr>';
            $emailBody .= '</table>';
            
            // Reservierungen
            if (!empty($userReservations)) {
                $emailBody .= '<h3>Ihre Reservierungen</h3>';
                $emailBody .= '<table style="border-collapse: collapse; width: 100%;">';
                $emailBody .= '<tr style="background-color: #f2f2f2;">';
                $emailBody .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Zeitraum</th>';
                $emailBody .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Status</th>';
                $emailBody .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Ihre Nachricht</th>';
                $emailBody .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Admin-Nachricht</th>';
                $emailBody .= '</tr>';
                
                foreach ($userReservations as $res) {
                    $statusText = '';
                    switch ($res['status']) {
                        case 'pending':
                            $statusText = 'Ausstehend';
                            break;
                        case 'confirmed':
                            $statusText = 'Bestätigt';
                            break;
                        case 'canceled':
                            $statusText = 'Storniert';
                            break;
                        default:
                            $statusText = ucfirst($res['status']);
                    }
                    
                    $emailBody .= '<tr>';
                    $emailBody .= '<td style="padding: 8px; border: 1px solid #ddd;">' . date('d.m.Y H:i', strtotime($res['start_datetime'])) . ' - ' . date('d.m.Y H:i', strtotime($res['end_datetime'])) . '</td>';
                    $emailBody .= '<td style="padding: 8px; border: 1px solid #ddd;">' . $statusText . '</td>';
                    $emailBody .= '<td style="padding: 8px; border: 1px solid #ddd;">' . (empty($res['user_message']) ? '-' : nl2br(escape($res['user_message']))) . '</td>';
                    $emailBody .= '<td style="padding: 8px; border: 1px solid #ddd;">' . (empty($res['admin_message']) ? '-' : nl2br(escape($res['admin_message']))) . '</td>';
                    $emailBody .= '</tr>';
                }
                
                $emailBody .= '</table>';
            } else {
                $emailBody .= '<h3>Ihre Reservierungen</h3>';
                $emailBody .= '<p>Sie haben bisher keine Reservierungen vorgenommen.</p>';
            }
            
            // Datenschutzhinweis
            $emailBody .= '<p style="margin-top: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 4px;">Dies ist eine Zusammenfassung der über Sie gespeicherten Daten. Gemäß DSGVO haben Sie das Recht, Ihre Daten zu korrigieren oder zu löschen. Bei Fragen wenden Sie sich bitte an den Administrator.</p>';
            
            // E-Mail senden
            $subject = 'Ihre gespeicherten Daten bei der Grillhütte Reichenbach';
            $result = sendEmail($userData['email'], $subject, $emailBody);
            
            if ($result['success']) {
                $_SESSION['flash_message'] = 'Eine E-Mail mit Ihren gespeicherten Daten wurde an Ihre E-Mail-Adresse gesendet.';
                $_SESSION['flash_type'] = 'success';
            } else {
                $errors[] = 'Fehler beim Senden der E-Mail: ' . $result['message'];
            }
        }
    }
}

// Titel für die Seite
$pageTitle = 'Mein Profil';

// Header einbinden
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Mein Profil</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escape($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                Ihr Profil wurde erfolgreich aktualisiert.
            </div>
        <?php endif; ?>
        
        <?php if ($emailChangeSuccess): ?>
            <div class="alert alert-success">
                Ihre E-Mail-Adresse wurde aktualisiert. Bitte bestätigen Sie Ihre neue E-Mail-Adresse, indem Sie auf den Link klicken, den wir Ihnen gesendet haben.
                Sie werden nun abgemeldet. Bitte melden Sie sich nach der Bestätigung Ihrer neuen E-Mail-Adresse wieder an.
                <script>
                    setTimeout(function() {
                        window.location.href = 'logout.php';
                    }, 5000);
                </script>
            </div>
        <?php endif; ?>
        
        <?php if ($passwordChangeSuccess): ?>
            <div class="alert alert-success">
                Ihr Passwort wurde erfolgreich aktualisiert.
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <!-- Profildaten -->
                <div class="card">
                    <div class="card-header">
                        <h3>Persönliche Daten</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" action="profile.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="mb-3">
                                <label for="first_name" class="form-label">Vorname</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo escape($userData['first_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Nachname</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo escape($userData['last_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Telefonnummer (optional)</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo escape($userData['phone']); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Aktualisieren</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <!-- E-Mail ändern -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>E-Mail-Adresse ändern</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Aktuelle E-Mail-Adresse</label>
                            <input type="email" class="form-control" value="<?php echo escape($userData['email']); ?>" disabled>
                        </div>
                        
                        <form method="post" action="profile.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="update_email" value="1">
                            
                            <div class="mb-3">
                                <label for="new_email" class="form-label">Neue E-Mail-Adresse</label>
                                <input type="email" class="form-control" id="new_email" name="new_email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email_password" class="form-label">Aktuelles Passwort</label>
                                <input type="password" class="form-control" id="email_password" name="email_password" required>
                                <div class="form-text">Zur Bestätigung Ihrer Identität benötigen wir Ihr aktuelles Passwort.</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">E-Mail-Adresse ändern</button>
                        </form>
                    </div>
                </div>
                
                <!-- Passwort ändern -->
                <div class="card">
                    <div class="card-header">
                        <h3>Passwort ändern</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" action="profile.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="update_password" value="1">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Aktuelles Passwort</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Neues Passwort</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">Mindestens 8 Zeichen.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Neues Passwort bestätigen</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Passwort ändern</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3>Datenschutz & Datenauskunft</h3>
                    </div>
                    <div class="card-body">
                        <p>Gemäß der Datenschutz-Grundverordnung (DSGVO) haben Sie das Recht, Auskunft über Ihre gespeicherten personenbezogenen Daten zu erhalten.</p>
                        <p>Klicken Sie auf den Button unten, um sich eine Zusammenfassung aller über Sie gespeicherten Daten per E-Mail zukommen zu lassen.</p>
                        
                        <form method="post" action="profile.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <button type="submit" name="send_user_data" class="btn btn-info">
                                <i class="bi bi-envelope"></i> Meine Daten per E-Mail erhalten
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 