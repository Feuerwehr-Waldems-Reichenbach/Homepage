<?php
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function register($email, $password, $firstName, $lastName, $phone) {
        try {
            // Prüfen, ob E-Mail bereits existiert
            $stmt = $this->db->prepare("SELECT id FROM gh_users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'Diese E-Mail-Adresse ist bereits registriert.'
                ];
            }
            
            // Passwort hashen
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Verifikationstoken generieren
            $verificationToken = generate_token();
            
            // Benutzer anlegen
            $stmt = $this->db->prepare("
                INSERT INTO gh_users (email, password, first_name, last_name, phone, verification_token) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$email, $hashedPassword, $firstName, $lastName, $phone, $verificationToken]);
            
            // E-Mail zur Verifikation senden
            $subject = 'Bestätigen Sie Ihre E-Mail-Adresse';
            $verifyUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Benutzer/Verifizieren') . '?token=' . $verificationToken;
            
            $body = '
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #A72920; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { background-color: #ffffff; padding: 20px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; }
                        .button { display: inline-block; padding: 10px 20px; background-color: #A72920; color: white !important; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                        .info-box { background-color: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>E-Mail-Adresse bestätigen</h2>
                        </div>
                        <div class="content">
                            <h3>Hallo ' . $firstName . ' ' . $lastName . ',</h3>
                            <p>vielen Dank für Ihre Registrierung im Reservierungssystem für die Grillhütte Waldems Reichenbach.</p>
                            
                            <div class="info-box">
                                <p>Um Ihre Registrierung abzuschließen, klicken Sie bitte auf den folgenden Button:</p>
                                <a href="' . $verifyUrl . '" class="button">E-Mail-Adresse bestätigen</a>
                            </div>
                            
                            <p>Alternativ können Sie auch diesen Link verwenden:<br>
                            <a href="' . $verifyUrl . '">' . $verifyUrl . '</a></p>
                            
                            <p>Dieser Link ist 24 Stunden gültig.</p>
                            
                            <div class="footer">
                                <p>Falls Sie sich nicht für diesen Dienst registriert haben, können Sie diese E-Mail ignorieren.</p>
                                <p>Ihr Team der Grillhütte Waldems Reichenbach</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ';
            
            $emailResult = sendEmail($email, $subject, $body);
            
            if (!$emailResult['success']) {
                // Wenn die E-Mail nicht gesendet werden konnte, trotzdem den Benutzer anmelden
                // aber mit einer Warnung zurückkehren
                return [
                    'success' => true,
                    'message' => 'Registrierung erfolgreich, aber die Bestätigungs-E-Mail konnte nicht gesendet werden.',
                    'warning' => true
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Registrierung erfolgreich. Bitte überprüfen Sie Ihre E-Mail, um Ihr Konto zu bestätigen.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Ein Fehler ist bei der Registrierung aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function login($email, $password) {
        try {
            // IP-Adresse für Protokollierung
            $ip = $_SERVER['REMOTE_ADDR'];
            
            // Email validieren
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Ungültige E-Mail-Adresse.'
                ];
            }
            
            // Rate-Limiting prüfen
            $rateLimitResult = checkLoginRateLimit($email);
            if (!$rateLimitResult['allowed']) {
                // Verzögerung, um Timing-Angriffe zu erschweren
                sleep(rand(1, 2));
                
                // Berechne verbleibende Zeit für die Fehlermeldung
                $minutes = ceil($rateLimitResult['remaining_seconds'] / 60);
                
                // Verdächtigen Login-Versuch während einer Sperrzeit protokollieren
                $this->logLoginActivity($email, $ip, false);
                
                return [
                    'success' => false,
                    'message' => 'Zu viele Anmeldeversuche. Bitte versuchen Sie es in ' . $minutes . ' Minuten erneut.',
                    'rate_limited' => true,
                    'remaining_seconds' => $rateLimitResult['remaining_seconds']
                ];
            }
            
            // Benutzer über E-Mail suchen
            $stmt = $this->db->prepare("SELECT * FROM gh_users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Benutzer nicht gefunden
            if (!$user) {
                // Fehlgeschlagenen Login protokollieren
                logFailedLogin($email);
                $this->logLoginActivity($email, $ip, false);
                
                // Verzögerung, um Timing-Angriffe zu erschweren
                sleep(rand(1, 2));
                
                return [
                    'success' => false,
                    'message' => 'Ungültige Zugangsdaten.'
                ];
            }
            
            // Passwort überprüfen
            if (!password_verify($password, $user['password'])) {
                // Fehlgeschlagenen Login protokollieren
                logFailedLogin($email);
                $this->logLoginActivity($email, $ip, false);
                
                // Verzögerung, um Timing-Angriffe zu erschweren
                sleep(rand(1, 2));
                
                return [
                    'success' => false,
                    'message' => 'Ungültige Zugangsdaten.'
                ];
            }
            
            // Prüfen, ob Benutzer verifiziert ist
            if (!$user['is_verified']) {
                // Unverifizierte Anmeldung protokollieren
                $this->logLoginActivity($email, $ip, false);
                
                return [
                    'success' => false,
                    'message' => 'Bitte bestätigen Sie zuerst Ihre E-Mail-Adresse. Prüfen Sie Ihren Posteingang oder <a href="' . getRelativePath('Benutzer/Email-Verifizierung') . '">fordern Sie einen neuen Bestätigungslink an</a>.'
                ];
            }
            
            // Session erneuern, um Session-Fixation-Angriffe zu verhindern
            regenerateSession();
            
            // Benutzer einloggen
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            $_SESSION['is_verified'] = (bool)$user['is_verified'];
            $_SESSION['is_Feuerwehr'] = (bool)$user['is_Feuerwehr'];
            $_SESSION['is_aktives_Mitglied'] = (bool)$user['is_aktives_Mitglied'];
            
            // Login-Timestamp aktualisieren
            $stmt = $this->db->prepare("UPDATE gh_users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Erfolgreichen Login protokollieren
            $this->logLoginActivity($email, $ip, true);
            
            return [
                'success' => true,
                'message' => 'Erfolgreich angemeldet.'
            ];
        } catch (PDOException $e) {
            // Fehlerhafte Anmeldung protokollieren
            if (isset($email)) {
                $this->logLoginActivity($email, $_SERVER['REMOTE_ADDR'], false);
            }
            
            return [
                'success' => false,
                'message' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function verify($token) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM gh_users WHERE verification_token = ? AND is_verified = 0");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Ungültiger oder bereits verwendeter Bestätigungslink.'
                ];
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Benutzer verifizieren
            $stmt = $this->db->prepare("UPDATE gh_users SET is_verified = 1, verification_token = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            return [
                'success' => true,
                'message' => 'Ihre E-Mail-Adresse wurde erfolgreich bestätigt. Sie können sich jetzt anmelden.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Ein Fehler ist bei der Verifikation aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function requestPasswordReset($email) {
        try {
            // E-Mail validieren
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // Log invalid email format attempt
                $this->logSecurityEvent($email, $_SERVER['REMOTE_ADDR'], 'password_reset', 'failure', 
                    ['reason' => 'invalid_email_format']);
                
                return [
                    'success' => false,
                    'message' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.'
                ];
            }

            // Benutzer über E-Mail suchen
            $stmt = $this->db->prepare("SELECT * FROM gh_users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() == 0) {
                // Log attempt for non-existent email (for security auditing)
                // Still return success to prevent user enumeration
                $this->logSecurityEvent($email, $_SERVER['REMOTE_ADDR'], 'password_reset', 'failure', 
                    ['reason' => 'non_existent_email', 'user_enumeration_prevented' => true]);
                
                // Zur Vermeidung von User Enumeration trotzdem Erfolg zurückgeben
                return [
                    'success' => true,
                    'message' => 'Eine E-Mail mit Anweisungen zum Zurücksetzen Ihres Passworts wurde an die angegebene Adresse gesendet.'
                ];
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Altes Token löschen falls vorhanden
            $stmt = $this->db->prepare("DELETE FROM gh_password_reset WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            
            // Neues Token generieren
            $token = generate_token();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+2 hours')); // 2 Stunden gültig
            
            // Token speichern
            $stmt = $this->db->prepare("INSERT INTO gh_password_reset (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, $expiresAt]);
            
            // E-Mail senden
            $subject = 'Zurücksetzen Ihres Passworts';
            $resetUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Benutzer/Passwort-zuruecksetzen') . '?token=' . $token;
            
            $body = '
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #A72920; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { background-color: #ffffff; padding: 20px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; }
                        .button { display: inline-block; padding: 10px 20px; background-color: #A72920; color: white !important; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                        .info-box { background-color: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>Passwort zurücksetzen</h2>
                        </div>
                        <div class="content">
                            <h3>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h3>
                            <p>Sie haben eine Anfrage zum Zurücksetzen Ihres Passworts gestellt.</p>
                            
                            <div class="info-box">
                                <p>Um Ihr Passwort zurückzusetzen, klicken Sie bitte auf den folgenden Button:</p>
                                <a href="' . $resetUrl . '" class="button">Passwort zurücksetzen</a>
                            </div>
                            
                            <p>Alternativ können Sie auch diesen Link verwenden:<br>
                            <a href="' . $resetUrl . '">' . $resetUrl . '</a></p>
                            
                            <p>Dieser Link ist 2 Stunden gültig.</p>
                            
                            <div class="footer">
                                <p>Falls Sie keine Anfrage zum Zurücksetzen Ihres Passworts gestellt haben, können Sie diese E-Mail ignorieren.</p>
                                <p>Ihr Team der Grillhütte Waldems Reichenbach</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ';
            
            $emailResult = sendEmail($email, $subject, $body);
            
            if (!$emailResult['success']) {
                // Log email sending failure
                $this->logSecurityEvent($email, $_SERVER['REMOTE_ADDR'], 'password_reset', 'failure', 
                    ['reason' => 'email_sending_failure']);
                
                return [
                    'success' => false,
                    'message' => 'E-Mail konnte nicht gesendet werden. Bitte versuchen Sie es später erneut.'
                ];
            }
            
            // Log successful password reset request
            $this->logSecurityEvent($email, $_SERVER['REMOTE_ADDR'], 'password_reset', 'warning', 
                ['action' => 'reset_requested', 'expires_at' => $expiresAt]);
            
            return [
                'success' => true,
                'message' => 'Eine E-Mail mit Anweisungen zum Zurücksetzen Ihres Passworts wurde an die angegebene Adresse gesendet.'
            ];
            
        } catch (PDOException $e) {
            // Log database error
            if (isset($email)) {
                $this->logSecurityEvent($email, $_SERVER['REMOTE_ADDR'], 'password_reset', 'failure', 
                    ['reason' => 'database_error']);
            }
            
            return [
                'success' => false,
                'message' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function resetPassword($token, $newPassword) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.user_id 
                FROM gh_password_reset r 
                WHERE r.token = ? AND r.expires_at > NOW()
            ");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() == 0) {
                // Log failed password reset attempt
                $this->logSecurityEvent(null, $_SERVER['REMOTE_ADDR'], 'password_reset', 'failure', 
                    ['reason' => 'invalid_or_expired_token']);
                
                return [
                    'success' => false,
                    'message' => 'Ungültiger oder abgelaufener Token.'
                ];
            }
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $userId = $row['user_id'];
            
            // Benutzer-Email für das Protokoll abrufen
            $stmt = $this->db->prepare("SELECT email FROM gh_users WHERE id = ?");
            $stmt->execute([$userId]);
            $userEmail = $stmt->fetchColumn();
            
            // Passwort aktualisieren
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE gh_users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            // Token löschen
            $stmt = $this->db->prepare("DELETE FROM gh_password_reset WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Log successful password reset
            $this->logSecurityEvent($userEmail, $_SERVER['REMOTE_ADDR'], 'password_reset', 'success');
            
            return [
                'success' => true,
                'message' => 'Ihr Passwort wurde erfolgreich zurückgesetzt. Sie können sich jetzt mit Ihrem neuen Passwort anmelden.'
            ];
            
        } catch (PDOException $e) {
            // Log error in password reset
            $this->logSecurityEvent(null, $_SERVER['REMOTE_ADDR'], 'password_reset', 'failure', 
                ['error' => 'database_error']);
                
            return [
                'success' => false,
                'message' => 'Ein Fehler ist beim Zurücksetzen des Passworts aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function updateProfile($userId, $firstName, $lastName, $phone, $currentPassword = null, $newPassword = null) {
        try {
            // Prüfen, ob der Benutzer existiert
            $stmt = $this->db->prepare("SELECT * FROM gh_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Benutzer nicht gefunden.'
                ];
            }
            
            // Wenn Passwort geändert werden soll
            if (!empty($currentPassword) && !empty($newPassword)) {
                if (!password_verify($currentPassword, $user['password'])) {
                    return [
                        'success' => false,
                        'message' => 'Das aktuelle Passwort ist nicht korrekt.'
                    ];
                }
                
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("UPDATE gh_users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);
            }
            
            // Profilinformationen aktualisieren
            $stmt = $this->db->prepare("UPDATE gh_users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$firstName, $lastName, $phone, $userId]);
            
            // Session aktualisieren
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            
            return [
                'success' => true,
                'message' => 'Profil erfolgreich aktualisiert.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Ein Fehler ist beim Aktualisieren des Profils aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function updateEmail($userId, $newEmail, $password) {
        try {
            // Prüfen, ob der Benutzer existiert und das Passwort korrekt ist
            $stmt = $this->db->prepare("SELECT * FROM gh_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password'])) {
                // Log failed email change attempt due to invalid password
                $this->logSecurityEvent($user ? $user['email'] : null, $_SERVER['REMOTE_ADDR'], 'email_change', 'failure', 
                    ['reason' => 'invalid_password', 'attempted_new_email' => $newEmail]);
                
                return [
                    'success' => false,
                    'message' => 'Ungültiges Passwort.'
                ];
            }
            
            // Prüfen, ob die neue E-Mail-Adresse bereits existiert
            $stmt = $this->db->prepare("SELECT id FROM gh_users WHERE email = ? AND id != ?");
            $stmt->execute([$newEmail, $userId]);
            if ($stmt->rowCount() > 0) {
                // Log failed email change attempt due to email already in use
                $this->logSecurityEvent($user['email'], $_SERVER['REMOTE_ADDR'], 'email_change', 'failure', 
                    ['reason' => 'email_already_in_use', 'attempted_new_email' => $newEmail]);
                
                return [
                    'success' => false,
                    'message' => 'Diese E-Mail-Adresse wird bereits verwendet.'
                ];
            }
            
            // Verifikationstoken generieren
            $verificationToken = generate_token();
            
            // E-Mail aktualisieren und als nicht verifiziert markieren
            $stmt = $this->db->prepare("
                UPDATE gh_users 
                SET email = ?, is_verified = 0, verification_token = ? 
                WHERE id = ?
            ");
            $stmt->execute([$newEmail, $verificationToken, $userId]);
            
            // E-Mail zur Verifikation senden
            $subject = 'Bestätigen Sie Ihre neue E-Mail-Adresse';
            $verifyUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Benutzer/Verifizieren') . '?token=' . $verificationToken;
            
            $body = '
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #A72920; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { background-color: #ffffff; padding: 20px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; }
                        .button { display: inline-block; padding: 10px 20px; background-color: #A72920; color: white !important; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                        .info-box { background-color: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                        .warning-box { background-color: #fff3cd; border: 1px solid #ffeeba; padding: 15px; border-radius: 5px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>Neue E-Mail-Adresse bestätigen</h2>
                        </div>
                        <div class="content">
                            <h3>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h3>
                            <p>Sie haben eine Änderung Ihrer E-Mail-Adresse im Reservierungssystem für die Grillhütte Waldems Reichenbach angefordert.</p>
                            
                            <div class="info-box">
                                <p>Um Ihre neue E-Mail-Adresse zu bestätigen, klicken Sie bitte auf den folgenden Button:</p>
                                <a href="' . $verifyUrl . '" class="button">E-Mail-Adresse bestätigen</a>
                            </div>
                            
                            <p>Alternativ können Sie auch diesen Link verwenden:<br>
                            <a href="' . $verifyUrl . '">' . $verifyUrl . '</a></p>
                            
                            <div class="warning-box">
                                <p><strong>Wichtiger Hinweis:</strong><br>
                                Falls Sie diese Änderung nicht angefordert haben, setzen Sie sich bitte umgehend mit dem Administrator in Verbindung.</p>
                            </div>
                            
                            <div class="footer">
                                <p>Dieser Link ist 24 Stunden gültig.</p>
                                <p>Ihr Team der Grillhütte Waldems Reichenbach</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ';
            
            $emailResult = sendEmail($newEmail, $subject, $body);
            
            if (!$emailResult['success']) {
                // Log failed email change due to email sending failure
                $this->logSecurityEvent($user['email'], $_SERVER['REMOTE_ADDR'], 'email_change', 'failure', 
                    ['reason' => 'verification_email_send_failure', 'new_email' => $newEmail]);
                
                return [
                    'success' => false,
                    'message' => 'E-Mail konnte nicht gesendet werden. Bitte versuchen Sie es später erneut.'
                ];
            }
            
            // Log successful email change (pending verification)
            $this->logSecurityEvent($user['email'], $_SERVER['REMOTE_ADDR'], 'email_change', 'warning', 
                ['action' => 'change_initiated', 'old_email' => $user['email'], 'new_email' => $newEmail, 'requires_verification' => true]);
            
            // Benutzer ausloggen, um erneute Verifikation zu erzwingen
            session_destroy();
            
            return [
                'success' => true,
                'message' => 'Ihre E-Mail-Adresse wurde aktualisiert. Bitte überprüfen Sie Ihre neue E-Mail, um sie zu bestätigen, und melden Sie sich dann erneut an.'
            ];
            
        } catch (PDOException $e) {
            // Log database error during email change
            if (isset($user) && isset($user['email'])) {
                $this->logSecurityEvent($user['email'], $_SERVER['REMOTE_ADDR'], 'email_change', 'failure', 
                    ['reason' => 'database_error', 'attempted_new_email' => $newEmail]);
            }
            
            return [
                'success' => false,
                'message' => 'Ein Fehler ist beim Aktualisieren der E-Mail-Adresse aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }

    public function getUserById($userId) {
        try {
            $stmt = $this->db->prepare("SELECT id, email, first_name, last_name, phone, is_admin, is_verified, is_AktivesMitglied, is_Feuerwehr, created_at FROM gh_users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getAllUsers() {
        try {
            $stmt = $this->db->prepare("SELECT id, email, first_name, last_name, phone, is_admin, is_verified, is_AktivesMitglied, is_Feuerwehr, created_at FROM gh_users ORDER BY id");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function toggleAdmin($userId) {
        try {
            // Get the current admin user who is making the change
            $adminId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $adminEmail = null;
            
            if ($adminId) {
                $adminStmt = $this->db->prepare("SELECT email FROM gh_users WHERE id = ?");
                $adminStmt->execute([$adminId]);
                $adminEmail = $adminStmt->fetchColumn();
            }
            
            // Aktuellen Status abrufen
            $stmt = $this->db->prepare("SELECT email, is_admin FROM gh_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                // Log failed admin toggle attempt for non-existent user
                $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'admin_privilege', 'failure', 
                    ['reason' => 'user_not_found', 'target_user_id' => $userId]);
                
                return [
                    'success' => false,
                    'message' => 'Benutzer nicht gefunden.'
                ];
            }
            
            // Status umkehren
            $newStatus = $user['is_admin'] ? 0 : 1;
            $actionDescription = $newStatus ? 'granted' : 'revoked';
            
            $stmt = $this->db->prepare("UPDATE gh_users SET is_admin = ? WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            
            // Log the admin privilege change
            $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'admin_privilege', 'warning', 
                ['action' => $actionDescription, 'target_user' => $user['email'], 'target_user_id' => $userId, 'new_status' => $newStatus]);
            
            // Wenn der aktuell eingeloggte Benutzer betroffen ist, Session aktualisieren
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                // Session regenerieren für Sicherheit
                regenerateSession();
                
                // Admin-Status in der Session aktualisieren
                $_SESSION['is_admin'] = (bool)$newStatus;
            }
            
            return [
                'success' => true,
                'message' => 'Administrator-Status geändert.',
                'is_admin' => $newStatus
            ];
        } catch (PDOException $e) {
            // Log database error
            $adminEmail = isset($_SESSION['email']) ? $_SESSION['email'] : null;
            $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'admin_privilege', 'failure', 
                ['reason' => 'database_error', 'target_user_id' => $userId]);
            
            return [
                'success' => false,
                'message' => 'Ein Fehler ist beim Ändern des Admin-Status aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }

    public function createUserByAdmin($email, $password, $firstName, $lastName, $phone, $isAdmin = 0, $isVerified = 1, $isAktivesMitglied = 0, $isFeuerwehr = 0) {
        try {
            // Get the current admin user who is creating the new user
            $adminId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $adminEmail = null;
            
            if ($adminId) {
                $adminStmt = $this->db->prepare("SELECT email FROM gh_users WHERE id = ?");
                $adminStmt->execute([$adminId]);
                $adminEmail = $adminStmt->fetchColumn();
            }
            
            // Prüfen, ob E-Mail bereits existiert
            $stmt = $this->db->prepare("SELECT id FROM gh_users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                // Log failed user creation attempt due to email already in use
                $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'user_management', 'failure', 
                    ['action' => 'create_user', 'reason' => 'email_already_exists', 'attempted_email' => $email]);
                
                return [
                    'success' => false,
                    'message' => 'Diese E-Mail-Adresse ist bereits registriert.'
                ];
            }
            
            // Passwort hashen
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Benutzer anlegen (mit dem angegebenen Verifikationsstatus)
            $stmt = $this->db->prepare("
                INSERT INTO gh_users (email, password, first_name, last_name, phone, is_verified, is_admin, is_AktivesMitglied, is_Feuerwehr) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$email, $hashedPassword, $firstName, $lastName, $phone, $isVerified, $isAdmin, $isAktivesMitglied, $isFeuerwehr]);
            
            // Get the ID of the newly created user
            $newUserId = $this->db->lastInsertId();
            
            // Log successful user creation
            $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'user_management', 'warning', 
                ['action' => 'create_user', 'new_user_email' => $email, 'new_user_id' => $newUserId, 
                 'is_admin' => (bool)$isAdmin, 'is_verified' => (bool)$isVerified]);
            
            return [
                'success' => true,
                'message' => 'Benutzer erfolgreich erstellt.'
            ];
            
        } catch (PDOException $e) {
            // Log database error during user creation
            $adminEmail = isset($_SESSION['email']) ? $_SESSION['email'] : null;
            $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'user_management', 'failure', 
                ['action' => 'create_user', 'error' => 'database_error', 'attempted_email' => $email]);
            
            return [
                'success' => false,
                'message' => 'Ein Fehler ist beim Erstellen des Benutzers aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function deleteUser($userId) {
        try {
            // Get the current admin user who is deleting the user
            $adminId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $adminEmail = null;
            
            if ($adminId) {
                $adminStmt = $this->db->prepare("SELECT email FROM gh_users WHERE id = ?");
                $adminStmt->execute([$adminId]);
                $adminEmail = $adminStmt->fetchColumn();
            }
            
            // Prüfen, ob der Benutzer existiert
            $stmt = $this->db->prepare("SELECT email, is_admin FROM gh_users WHERE id = ?");
            $stmt->execute([$userId]);
            
            if ($stmt->rowCount() == 0) {
                // Log failed user deletion attempt for non-existent user
                $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'user_management', 'failure', 
                    ['action' => 'delete_user', 'reason' => 'user_not_found', 'target_user_id' => $userId]);
                
                return [
                    'success' => false,
                    'message' => 'Benutzer nicht gefunden.'
                ];
            }
            
            // Get user details for logging
            $userToDelete = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Passwort-Reset-Tokens löschen
            $stmt = $this->db->prepare("DELETE FROM gh_password_reset WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Benutzer löschen
            $stmt = $this->db->prepare("DELETE FROM gh_users WHERE id = ?");
            $stmt->execute([$userId]);
            
            // Log successful user deletion
            $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'user_management', 'warning', 
                ['action' => 'delete_user', 'deleted_user_email' => $userToDelete['email'], 
                 'deleted_user_id' => $userId, 'deleted_user_was_admin' => (bool)$userToDelete['is_admin']]);
            
            return [
                'success' => true,
                'message' => 'Benutzer erfolgreich gelöscht.'
            ];
            
        } catch (PDOException $e) {
            // Log database error during user deletion
            $adminEmail = isset($_SESSION['email']) ? $_SESSION['email'] : null;
            $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'user_management', 'failure', 
                ['action' => 'delete_user', 'error' => 'database_error', 'target_user_id' => $userId]);
            
            return [
                'success' => false,
                'message' => 'Ein Fehler ist beim Löschen des Benutzers aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function authenticate($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM gh_users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Ungültige E-Mail-Adresse oder Passwort.'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Authentifizierung erfolgreich.',
                'user_id' => $user['id']
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Ein Fehler ist bei der Authentifizierung aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function updateUser($userId, $email, $firstName, $lastName, $phone, $isAdmin = 0, $newPassword = null, $isVerified = 1, $isAktivesMitglied = 0, $isFeuerwehr = 0) {
        try {
            // Get the current admin user who is updating the user
            $adminId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $adminEmail = null;
            
            if ($adminId) {
                $adminStmt = $this->db->prepare("SELECT email FROM gh_users WHERE id = ?");
                $adminStmt->execute([$adminId]);
                $adminEmail = $adminStmt->fetchColumn();
            }
            
            // Prüfen, ob der Benutzer existiert
            $stmt = $this->db->prepare("SELECT * FROM gh_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                // Log failed user update attempt for non-existent user
                $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'user_management', 'failure', 
                    ['action' => 'update_user', 'reason' => 'user_not_found', 'target_user_id' => $userId]);
                
                return [
                    'success' => false,
                    'message' => 'Benutzer nicht gefunden.'
                ];
            }
            
            // Prüfen, ob die E-Mail bereits von einem anderen Benutzer verwendet wird
            if ($email !== $user['email']) {
                $stmt = $this->db->prepare("SELECT id FROM gh_users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->rowCount() > 0) {
                    // Log failed user update attempt due to email already in use
                    $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'user_management', 'failure', 
                        ['action' => 'update_user', 'reason' => 'email_already_in_use', 
                         'target_user_id' => $userId, 'attempted_email' => $email]);
                    
                    return [
                        'success' => false,
                        'message' => 'Diese E-Mail-Adresse wird bereits verwendet.'
                    ];
                }
            }
            
            // Prepare changes log for security audit
            $changes = [];
            if ($email !== $user['email']) $changes['email'] = ['old' => $user['email'], 'new' => $email];
            if ($firstName !== $user['first_name']) $changes['first_name'] = ['old' => $user['first_name'], 'new' => $firstName];
            if ($lastName !== $user['last_name']) $changes['last_name'] = ['old' => $user['last_name'], 'new' => $lastName];
            if ($phone !== $user['phone']) $changes['phone'] = ['old' => $user['phone'], 'new' => $phone];
            if ($isAdmin != $user['is_admin']) $changes['is_admin'] = ['old' => (bool)$user['is_admin'], 'new' => (bool)$isAdmin];
            if ($isVerified != $user['is_verified']) $changes['is_verified'] = ['old' => (bool)$user['is_verified'], 'new' => (bool)$isVerified];
            if ($isAktivesMitglied != $user['is_AktivesMitglied']) $changes['is_AktivesMitglied'] = ['old' => (bool)$user['is_AktivesMitglied'], 'new' => (bool)$isAktivesMitglied];
            if ($isFeuerwehr != $user['is_Feuerwehr']) $changes['is_Feuerwehr'] = ['old' => (bool)$user['is_Feuerwehr'], 'new' => (bool)$isFeuerwehr];
            if (!empty($newPassword)) $changes['password'] = ['changed' => true];
            
            // SQL-Statement vorbereiten
            $sql = "UPDATE gh_users SET email = ?, first_name = ?, last_name = ?, phone = ?, is_admin = ?, is_verified = ?, is_AktivesMitglied = ?, is_Feuerwehr = ?";
            $params = [$email, $firstName, $lastName, $phone, $isAdmin, $isVerified, $isAktivesMitglied, $isFeuerwehr];
            
            // Passwort aktualisieren, wenn ein neues angegeben wurde
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $sql .= ", password = ?";
                $params[] = $hashedPassword;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $userId;
            
            // Update ausführen
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            // Log user update
            $securityLevel = (isset($changes['is_admin']) || isset($changes['password'])) ? 'warning' : 'success';
            $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'user_management', $securityLevel, 
                ['action' => 'update_user', 'target_user_id' => $userId, 'target_user_email' => $user['email'], 
                 'changes' => $changes]);
            
            // Wenn der aktuell eingeloggte Benutzer betroffen ist, Session aktualisieren
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                // Prüfen, ob privilegierte Attribute geändert wurden
                $hasPrivilegedChanges = ($user['is_admin'] != $isAdmin || 
                                         $user['is_verified'] != $isVerified ||
                                         $user['is_AktivesMitglied'] != $isAktivesMitglied ||
                                         $user['is_Feuerwehr'] != $isFeuerwehr ||
                                         !empty($newPassword));
                
                // Session bei Privilegienänderungen oder kritischen Feldern regenerieren
                if ($hasPrivilegedChanges) {
                    regenerateSession();
                }
                
                // Session-Daten aktualisieren
                $_SESSION['user_name'] = $firstName;
                $_SESSION['is_admin'] = (bool)$isAdmin;
                $_SESSION['is_verified'] = (bool)$isVerified;
                $_SESSION['is_aktives_Mitglied'] = (bool)$isAktivesMitglied;
                $_SESSION['is_Feuerwehr'] = (bool)$isFeuerwehr;
            }
            
            return [
                'success' => true,
                'message' => 'Benutzer erfolgreich aktualisiert.'
            ];
            
        } catch (PDOException $e) {
            // Log database error during user update
            $adminEmail = isset($_SESSION['email']) ? $_SESSION['email'] : null;
            $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'user_management', 'failure', 
                ['action' => 'update_user', 'error' => 'database_error', 'target_user_id' => $userId]);
            
            return [
                'success' => false,
                'message' => 'Ein Fehler ist beim Aktualisieren des Benutzers aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }

    public function toggleVerification($userId) {
        try {
            // Get the current admin user who is toggling verification
            $adminId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $adminEmail = null;
            
            if ($adminId) {
                $adminStmt = $this->db->prepare("SELECT email FROM gh_users WHERE id = ?");
                $adminStmt->execute([$adminId]);
                $adminEmail = $adminStmt->fetchColumn();
            }
            
            // Benutzer-ID prüfen
            $stmt = $this->db->prepare("SELECT id, is_verified, email, first_name, last_name FROM gh_users WHERE id = ?");
            $stmt->execute([$userId]);
            
            if ($stmt->rowCount() === 0) {
                // Log failed verification toggle for non-existent user
                $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'user_management', 'failure', 
                    ['action' => 'toggle_verification', 'reason' => 'user_not_found', 'target_user_id' => $userId]);
                
                return [
                    'success' => false,
                    'message' => 'Benutzer nicht gefunden.'
                ];
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $newStatus = $user['is_verified'] ? 0 : 1;
            $statusText = $newStatus ? 'verifiziert' : 'unverifiziert';
            $actionDescription = $newStatus ? 'verification_granted' : 'verification_revoked';
            
            // Status ändern
            $stmt = $this->db->prepare("UPDATE gh_users SET is_verified = ?, verification_token = NULL WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            
            // Log verification status change
            $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'user_management', 'warning', 
                ['action' => $actionDescription, 'target_user_email' => $user['email'], 
                 'target_user_id' => $userId, 'new_status' => $newStatus]);
            
            return [
                'success' => true,
                'message' => 'Der Benutzer ' . $user['first_name'] . ' ' . $user['last_name'] . ' wurde erfolgreich als ' . $statusText . ' markiert.'
            ];
            
        } catch (PDOException $e) {
            // Log database error during verification toggle
            $adminEmail = isset($_SESSION['email']) ? $_SESSION['email'] : null;
            $this->logSecurityEvent($adminEmail, $_SERVER['REMOTE_ADDR'], 'user_management', 'failure', 
                ['action' => 'toggle_verification', 'error' => 'database_error', 'target_user_id' => $userId]);
            
            return [
                'success' => false,
                'message' => 'Ein Fehler ist beim Ändern des Verifikations-Status aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }

    /**
     * Protokolliert ein Sicherheitsereignis in der Sicherheitslog-Tabelle
     *
     * @param string|null $email Die E-Mail-Adresse (falls vorhanden)
     * @param string $ipAddress Die IP-Adresse
     * @param string $actionType Art der Aktion (login, security_alert, password_reset, account_lockout)
     * @param string $status Status des Ereignisses (success, failure, warning, critical)
     * @param array|null $details Zusätzliche Details als Array (wird zu JSON konvertiert)
     * @return void
     */
    public function logSecurityEvent($email, $ipAddress, $actionType, $status, $details = null) {
        try {
            if ($details !== null) {
                $detailsJson = json_encode($details);
                $stmt = $this->db->prepare("
                    INSERT INTO gh_security_log 
                    (email, ip_address, action_type, status, details, timestamp) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$email, $ipAddress, $actionType, $status, $detailsJson]);
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO gh_security_log 
                    (email, ip_address, action_type, status, timestamp) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$email, $ipAddress, $actionType, $status]);
            }
        } catch (PDOException $e) {
            // Fehler bei der Protokollierung - still scheitern, um die Benutzerinteraktion nicht zu beeinträchtigen
            error_log("Fehler bei der Sicherheitsprotokollierung: " . $e->getMessage());
        }
    }
    
    /**
     * Überprüft und protokolliert verdächtige Anmeldemuster für Sicherheitsanalysen
     * 
     * @param string $email Die E-Mail-Adresse, die versucht wurde
     * @param string $ip Die IP-Adresse des Anmeldeversuchs
     * @param bool $successful Ob der Anmeldeversuch erfolgreich war
     * @return void
     */
    public function logLoginActivity($email, $ip, $successful = false) {
        try {
            // Anmeldeversuch im Sicherheitslog protokollieren
            $status = $successful ? 'success' : 'failure';
            $this->logSecurityEvent($email, $ip, 'login', $status);
            
            // Bei erfolgreicher Anmeldung die fehlgeschlagenen Versuche für diesen Benutzer löschen
            if ($successful) {
                $stmt = $this->db->prepare("DELETE FROM gh_login_attempts WHERE email = ?");
                $stmt->execute([$email]);
            }
            
            // Bei zu vielen fehlgeschlagenen Versuchen für eine E-Mail von verschiedenen IPs
            // einen möglichen verteilten Angriff erkennen und protokollieren
            if (!$successful) {
                $stmt = $this->db->prepare("
                    SELECT COUNT(DISTINCT ip) as unique_ips 
                    FROM gh_login_attempts 
                    WHERE email = ? AND attempt_time > (NOW() - INTERVAL 24 HOUR)
                ");
                $stmt->execute([$email]);
                $uniqueIPs = $stmt->fetchColumn();
                
                // Wenn mehr als 3 verschiedene IPs versuchen, sich mit derselben E-Mail anzumelden
                if ($uniqueIPs >= 3) {
                    // Potenziellen verteilten Angriff protokollieren
                    $this->logSecurityEvent($email, $ip, 'security_alert', 'warning', [
                        'alert_type' => 'potential_distributed_attack',
                        'unique_ips' => $uniqueIPs,
                        'time_frame' => '24h'
                    ]);
                }
            }
        } catch (PDOException $e) {
            // Fehler bei der Protokollierung - still scheitern, um die Benutzerinteraktion nicht zu beeinträchtigen
            error_log("Fehler bei der Sicherheitsprotokollierung: " . $e->getMessage());
        }
    }

    public function verifyEmail($token) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id, u.email, u.first_name, u.last_name 
                FROM gh_users u 
                JOIN gh_email_verification ev ON u.id = ev.user_id 
                WHERE ev.token = ? AND ev.expires_at > NOW()
            ");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() == 0) {
                // Log failed email verification
                $this->logSecurityEvent(null, $_SERVER['REMOTE_ADDR'], 'email_verification', 'failure', 
                    ['reason' => 'invalid_or_expired_token']);
                    
                return [
                    'success' => false,
                    'message' => 'Ungültiger oder abgelaufener Verifizierungstoken.'
                ];
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Update user status
            $stmt = $this->db->prepare("UPDATE gh_users SET is_email_verified = 1 WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Delete token
            $stmt = $this->db->prepare("DELETE FROM gh_email_verification WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            
            // Log successful email verification
            $this->logSecurityEvent($user['email'], $_SERVER['REMOTE_ADDR'], 'email_verification', 'success');
            
            return [
                'success' => true,
                'message' => 'Ihre E-Mail-Adresse wurde erfolgreich bestätigt. Sie können sich jetzt anmelden.'
            ];
            
        } catch (PDOException $e) {
            // Log error during email verification
            $this->logSecurityEvent(null, $_SERVER['REMOTE_ADDR'], 'email_verification', 'failure', 
                ['reason' => 'database_error']);
                
            return [
                'success' => false,
                'message' => 'Ein Fehler ist bei der Verifikation aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }

    /**
     * Sendet einen neuen Bestätigungslink an einen nicht verifizierten Benutzer
     * 
     * @param string $email Die E-Mail-Adresse des Benutzers
     * @return array Ergebnis der Operation mit Erfolgs- und Nachrichtenfeld
     */
    public function resendVerificationEmail($email) {
        try {
            // Benutzer mit der angegebenen E-Mail-Adresse suchen
            $stmt = $this->db->prepare("SELECT id, first_name, last_name, is_verified FROM gh_users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() == 0) {
                // Log failed verification email resend attempt for non-existent email
                $this->logSecurityEvent($email, $_SERVER['REMOTE_ADDR'], 'verification_resend', 'failure', 
                    ['reason' => 'email_not_found']);
                    
                return [
                    'success' => false,
                    'message' => 'E-Mail-Adresse nicht gefunden.'
                ];
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Prüfen, ob der Benutzer bereits verifiziert ist
            if ($user['is_verified'] == 1) {
                // Log unnecessary verification resend for already verified user
                $this->logSecurityEvent($email, $_SERVER['REMOTE_ADDR'], 'verification_resend', 'warning', 
                    ['reason' => 'already_verified']);
                    
                return [
                    'success' => false,
                    'message' => 'Ihre E-Mail-Adresse ist bereits verifiziert. Sie können sich jetzt anmelden.'
                ];
            }
            
            // Neuen Verifizierungstoken generieren
            $verificationToken = generate_token();
            
            // Token in der Datenbank aktualisieren
            $stmt = $this->db->prepare("UPDATE gh_users SET verification_token = ? WHERE id = ?");
            $stmt->execute([$verificationToken, $user['id']]);
            
            // E-Mail zur Verifikation senden
            $subject = 'Bestätigen Sie Ihre E-Mail-Adresse';
            $verifyUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Benutzer/Verifizieren') . '?token=' . $verificationToken;
            
            $body = '
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #A72920; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { background-color: #ffffff; padding: 20px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; }
                        .button { display: inline-block; padding: 10px 20px; background-color: #A72920; color: white !important; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                        .info-box { background-color: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>E-Mail-Adresse bestätigen</h2>
                        </div>
                        <div class="content">
                            <h3>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h3>
                            <p>Sie haben einen neuen Bestätigungslink für Ihre E-Mail-Adresse angefordert.</p>
                            
                            <div class="info-box">
                                <p>Um Ihre Registrierung abzuschließen, klicken Sie bitte auf den folgenden Button:</p>
                                <a href="' . $verifyUrl . '" class="button">E-Mail-Adresse bestätigen</a>
                            </div>
                            
                            <p>Alternativ können Sie auch diesen Link verwenden:<br>
                            <a href="' . $verifyUrl . '">' . $verifyUrl . '</a></p>
                            
                            <p>Dieser Link ist 24 Stunden gültig.</p>
                            
                            <div class="footer">
                                <p>Falls Sie diesen Link nicht angefordert haben, können Sie diese E-Mail ignorieren.</p>
                                <p>Ihr Team der Grillhütte Waldems Reichenbach</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ';
            
            $emailResult = sendEmail($email, $subject, $body);
            
            if (!$emailResult['success']) {
                // Log failed verification email resend due to email sending failure
                $this->logSecurityEvent($email, $_SERVER['REMOTE_ADDR'], 'verification_resend', 'failure', 
                    ['reason' => 'email_send_failure']);
                    
                return [
                    'success' => false,
                    'message' => 'E-Mail konnte nicht gesendet werden. Bitte versuchen Sie es später erneut.'
                ];
            }
            
            // Log successful verification email resend
            $this->logSecurityEvent($email, $_SERVER['REMOTE_ADDR'], 'verification_resend', 'success');
            
            return [
                'success' => true,
                'message' => 'Ein neuer Bestätigungslink wurde an Ihre E-Mail-Adresse gesendet.'
            ];
            
        } catch (PDOException $e) {
            // Log database error during verification email resend
            $this->logSecurityEvent($email, $_SERVER['REMOTE_ADDR'], 'verification_resend', 'failure', 
                ['reason' => 'database_error']);
                
            return [
                'success' => false,
                'message' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }

    /**
     * Führt automatisch Wartungsarbeiten an den Sicherheitsprotokollen durch
     * 
     * Bereinigt veraltete Protokolleinträge nach folgenden Regeln:
     * - Anmeldeversuche: älter als 30 Tage
     * - Erfolgreiche Anmeldungen: älter als 90 Tage 
     * - Fehlgeschlagene Anmeldungen: älter als 180 Tage
     * - Passwort-Zurücksetzungen und Email-Änderungen: älter als 180 Tage
     * - Sicherheitswarnungen (nicht kritisch): älter als 365 Tage
     * 
     * @return array Informationen über die durchgeführte Wartung, mit Schlüsseln 'success' und 'cleaned_count'
     */
    public function performLogMaintenance() {
        try {
            $loginAttemptsCleaned = 0;
            $securityLogCleaned = 0;
            
            // Alte fehlgeschlagene Anmeldeversuche löschen (älter als 30 Tage)
            $stmt = $this->db->prepare("DELETE FROM gh_login_attempts WHERE attempt_time < (NOW() - INTERVAL 30 DAY)");
            $stmt->execute();
            $loginAttemptsCleaned = $stmt->rowCount();
            
            // Erfolgreiche Anmeldungen älter als 90 Tage löschen
            $stmt = $this->db->prepare("
                DELETE FROM gh_security_log 
                WHERE action_type = 'login' 
                AND status = 'success' 
                AND timestamp < (NOW() - INTERVAL 90 DAY)
            ");
            $stmt->execute();
            $securityLogCleaned += $stmt->rowCount();
            
            // Fehlgeschlagene Anmeldungen älter als 180 Tage löschen
            $stmt = $this->db->prepare("
                DELETE FROM gh_security_log 
                WHERE action_type = 'login' 
                AND status = 'failure' 
                AND timestamp < (NOW() - INTERVAL 180 DAY)
            ");
            $stmt->execute();
            $securityLogCleaned += $stmt->rowCount();
            
            // Nicht-kritische Sicherheitswarnungen älter als 365 Tage löschen
            $stmt = $this->db->prepare("
                DELETE FROM gh_security_log 
                WHERE action_type = 'security_alert' 
                AND status != 'critical' 
                AND timestamp < (NOW() - INTERVAL 365 DAY)
            ");
            $stmt->execute();
            $securityLogCleaned += $stmt->rowCount();
            
            // Alte Passwort-Zurücksetzungen und Email-Änderungen bereinigen (älter als 180 Tage)
            $stmt = $this->db->prepare("
                DELETE FROM gh_security_log 
                WHERE (action_type = 'password_reset' OR action_type = 'email_change')
                AND status IN ('success', 'failure') 
                AND timestamp < (NOW() - INTERVAL 180 DAY)
            ");
            $stmt->execute();
            $securityLogCleaned += $stmt->rowCount();
            
            $totalCleaned = $loginAttemptsCleaned + $securityLogCleaned;
            
            return [
                'success' => true,
                'cleaned_count' => $totalCleaned,
                'login_attempts_cleaned' => $loginAttemptsCleaned,
                'security_log_cleaned' => $securityLogCleaned
            ];
            
        } catch (PDOException $e) {
            error_log("Fehler bei der Protokollwartung: " . $e->getMessage());
            return [
                'success' => false,
                'cleaned_count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
}
?> 