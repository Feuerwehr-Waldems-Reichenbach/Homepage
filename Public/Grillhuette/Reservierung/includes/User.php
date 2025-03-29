<?php
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function register($email, $password, $firstName, $lastName, $phone = null) {
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
            error_log('Fehler bei der Registrierung: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ein Fehler ist bei der Registrierung aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM gh_users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Ungültige E-Mail-Adresse oder Passwort.'
                ];
            }
            
            if (!password_verify($password, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Ungültige E-Mail-Adresse oder Passwort.'
                ];
            }
            
            if ($user['is_verified'] == 0) {
                return [
                    'success' => false,
                    'message' => 'Bitte bestätigen Sie zuerst Ihre E-Mail-Adresse.'
                ];
            }
            
            // Benutzerinformationen in der Session speichern
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['is_verified'] = $user['is_verified'];
            $_SESSION['is_AktivesMitglied'] = $user['is_AktivesMitglied'] ?? 0;
            $_SESSION['is_Feuerwehr'] = $user['is_Feuerwehr'] ?? 0;
            
            return [
                'success' => true,
                'message' => 'Login erfolgreich.',
                'is_admin' => $user['is_admin']
            ];
            
        } catch (PDOException $e) {
            error_log('Fehler beim Login: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ein Fehler ist beim Login aufgetreten. Bitte versuchen Sie es später erneut.'
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
            error_log('Fehler bei der Verifikation: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ein Fehler ist bei der Verifikation aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function requestPasswordReset($email) {
        try {
            $stmt = $this->db->prepare("SELECT id, first_name, last_name FROM gh_users WHERE email = ? AND is_verified = 1");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Kein aktiver Benutzer mit dieser E-Mail-Adresse gefunden.'
                ];
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Altes Token löschen falls vorhanden
            $stmt = $this->db->prepare("DELETE FROM gh_password_reset WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            
            // Neues Token generieren
            $token = generate_token();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
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
                            
                            <p>Dieser Link ist 1 Stunde gültig.</p>
                            
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
                return [
                    'success' => false,
                    'message' => 'E-Mail konnte nicht gesendet werden: ' . $emailResult['message']
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Eine E-Mail mit Anweisungen zum Zurücksetzen Ihres Passworts wurde an Ihre E-Mail-Adresse gesendet.'
            ];
            
        } catch (PDOException $e) {
            error_log('Fehler beim Zurücksetzen des Passworts: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ein Fehler ist beim Zurücksetzen des Passworts aufgetreten. Bitte versuchen Sie es später erneut.'
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
                return [
                    'success' => false,
                    'message' => 'Ungültiger oder abgelaufener Token.'
                ];
            }
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $userId = $row['user_id'];
            
            // Passwort aktualisieren
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE gh_users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            // Token löschen
            $stmt = $this->db->prepare("DELETE FROM gh_password_reset WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            return [
                'success' => true,
                'message' => 'Ihr Passwort wurde erfolgreich zurückgesetzt. Sie können sich jetzt mit Ihrem neuen Passwort anmelden.'
            ];
            
        } catch (PDOException $e) {
            error_log('Fehler beim Zurücksetzen des Passworts: ' . $e->getMessage());
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
            error_log('Fehler beim Aktualisieren des Profils: ' . $e->getMessage());
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
                return [
                    'success' => false,
                    'message' => 'Ungültiges Passwort.'
                ];
            }
            
            // Prüfen, ob die neue E-Mail-Adresse bereits existiert
            $stmt = $this->db->prepare("SELECT id FROM gh_users WHERE email = ? AND id != ?");
            $stmt->execute([$newEmail, $userId]);
            if ($stmt->rowCount() > 0) {
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
                return [
                    'success' => false,
                    'message' => 'E-Mail konnte nicht gesendet werden: ' . $emailResult['message']
                ];
            }
            
            // Benutzer ausloggen, um erneute Verifikation zu erzwingen
            session_destroy();
            
            return [
                'success' => true,
                'message' => 'Ihre E-Mail-Adresse wurde aktualisiert. Bitte überprüfen Sie Ihre neue E-Mail, um sie zu bestätigen, und melden Sie sich dann erneut an.'
            ];
            
        } catch (PDOException $e) {
            error_log('Fehler beim Aktualisieren der E-Mail-Adresse: ' . $e->getMessage());
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
            error_log('Fehler beim Abrufen des Benutzers: ' . $e->getMessage());
            return null;
        }
    }

    public function getAllUsers() {
        try {
            $stmt = $this->db->prepare("SELECT id, email, first_name, last_name, phone, is_admin, is_verified, is_AktivesMitglied, is_Feuerwehr, created_at FROM gh_users ORDER BY id");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Fehler beim Abrufen aller Benutzer: ' . $e->getMessage());
            return [];
        }
    }

    public function toggleAdmin($userId) {
        try {
            // Aktuellen Status abrufen
            $stmt = $this->db->prepare("SELECT is_admin FROM gh_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Benutzer nicht gefunden.'
                ];
            }
            
            // Status umkehren
            $newStatus = $user['is_admin'] ? 0 : 1;
            
            $stmt = $this->db->prepare("UPDATE gh_users SET is_admin = ? WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            
            return [
                'success' => true,
                'message' => 'Administrator-Status geändert.',
                'is_admin' => $newStatus
            ];
        } catch (PDOException $e) {
            error_log('Fehler beim Umschalten des Admin-Status: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ein Fehler ist beim Ändern des Admin-Status aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }

    public function createUserByAdmin($email, $password, $firstName, $lastName, $phone = null, $isAdmin = 0, $isVerified = 1, $isAktivesMitglied = 0, $isFeuerwehr = 0) {
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
            
            // Benutzer anlegen (mit dem angegebenen Verifikationsstatus)
            $stmt = $this->db->prepare("
                INSERT INTO gh_users (email, password, first_name, last_name, phone, is_verified, is_admin, is_AktivesMitglied, is_Feuerwehr) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$email, $hashedPassword, $firstName, $lastName, $phone, $isVerified, $isAdmin, $isAktivesMitglied, $isFeuerwehr]);
            
            return [
                'success' => true,
                'message' => 'Benutzer erfolgreich erstellt.'
            ];
            
        } catch (PDOException $e) {
            error_log('Fehler beim Erstellen des Benutzers: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ein Fehler ist beim Erstellen des Benutzers aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function deleteUser($userId) {
        try {
            // Prüfen, ob der Benutzer existiert
            $stmt = $this->db->prepare("SELECT * FROM gh_users WHERE id = ?");
            $stmt->execute([$userId]);
            
            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Benutzer nicht gefunden.'
                ];
            }
            
            // Passwort-Reset-Tokens löschen
            $stmt = $this->db->prepare("DELETE FROM gh_password_reset WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Benutzer löschen
            $stmt = $this->db->prepare("DELETE FROM gh_users WHERE id = ?");
            $stmt->execute([$userId]);
            
            return [
                'success' => true,
                'message' => 'Benutzer erfolgreich gelöscht.'
            ];
            
        } catch (PDOException $e) {
            error_log('Fehler beim Löschen des Benutzers: ' . $e->getMessage());
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
            error_log('Fehler bei der Authentifizierung: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ein Fehler ist bei der Authentifizierung aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function updateUser($userId, $email, $firstName, $lastName, $phone = null, $isAdmin = 0, $newPassword = null, $isVerified = 1, $isAktivesMitglied = 0, $isFeuerwehr = 0) {
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
            
            // Prüfen, ob die E-Mail bereits von einem anderen Benutzer verwendet wird
            if ($email !== $user['email']) {
                $stmt = $this->db->prepare("SELECT id FROM gh_users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->rowCount() > 0) {
                    return [
                        'success' => false,
                        'message' => 'Diese E-Mail-Adresse wird bereits verwendet.'
                    ];
                }
            }
            
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
            
            return [
                'success' => true,
                'message' => 'Benutzer erfolgreich aktualisiert.'
            ];
            
        } catch (PDOException $e) {
            error_log('Fehler beim Aktualisieren des Benutzers: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ein Fehler ist beim Aktualisieren des Benutzers aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }

    public function toggleVerification($userId) {
        try {
            // Benutzer-ID prüfen
            $stmt = $this->db->prepare("SELECT id, is_verified, email, first_name, last_name FROM gh_users WHERE id = ?");
            $stmt->execute([$userId]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Benutzer nicht gefunden.'
                ];
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $newStatus = $user['is_verified'] ? 0 : 1;
            $statusText = $newStatus ? 'verifiziert' : 'unverifiziert';
            
            // Status ändern
            $stmt = $this->db->prepare("UPDATE gh_users SET is_verified = ?, verification_token = NULL WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            
            return [
                'success' => true,
                'message' => 'Der Benutzer ' . $user['first_name'] . ' ' . $user['last_name'] . ' wurde erfolgreich als ' . $statusText . ' markiert.'
            ];
            
        } catch (PDOException $e) {
            error_log('Fehler beim Umschalten des Verifikations-Status: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ein Fehler ist beim Ändern des Verifikations-Status aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
}
?> 