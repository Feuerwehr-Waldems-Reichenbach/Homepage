<?php
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function register($email, $password, $firstName, $lastName, $phone = null) {
        try {
            // Prüfen, ob E-Mail bereits existiert
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
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
                INSERT INTO users (email, password, first_name, last_name, phone, verification_token) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$email, $hashedPassword, $firstName, $lastName, $phone, $verificationToken]);
            
            // E-Mail zur Verifikation senden
            $subject = 'Bestätigen Sie Ihre E-Mail-Adresse';
            $verifyUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/Grillhuette/verify.php?token=' . $verificationToken;
            
            $body = '
                <h2>Hallo ' . $firstName . ' ' . $lastName . ',</h2>
                <p>Vielen Dank für Ihre Registrierung im Reservierungssystem für die Grillhütte.</p>
                <p>Bitte bestätigen Sie Ihre E-Mail-Adresse, indem Sie auf den folgenden Link klicken:</p>
                <p><a href="' . $verifyUrl . '">' . $verifyUrl . '</a></p>
                <p>Dieser Link ist 24 Stunden gültig.</p>
                <p>Falls Sie sich nicht für diesen Dienst registriert haben, können Sie diese E-Mail ignorieren.</p>
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
                'message' => 'Fehler bei der Registrierung: ' . $e->getMessage()
            ];
        }
    }
    
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
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
            
            return [
                'success' => true,
                'message' => 'Login erfolgreich.',
                'is_admin' => $user['is_admin']
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Login: ' . $e->getMessage()
            ];
        }
    }
    
    public function verify($token) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Ungültiger oder bereits verwendeter Bestätigungslink.'
                ];
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Benutzer verifizieren
            $stmt = $this->db->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            return [
                'success' => true,
                'message' => 'Ihre E-Mail-Adresse wurde erfolgreich bestätigt. Sie können sich jetzt anmelden.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler bei der Verifikation: ' . $e->getMessage()
            ];
        }
    }
    
    public function requestPasswordReset($email) {
        try {
            $stmt = $this->db->prepare("SELECT id, first_name, last_name FROM users WHERE email = ? AND is_verified = 1");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Kein aktiver Benutzer mit dieser E-Mail-Adresse gefunden.'
                ];
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Altes Token löschen falls vorhanden
            $stmt = $this->db->prepare("DELETE FROM password_reset WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            
            // Neues Token generieren
            $token = generate_token();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Token speichern
            $stmt = $this->db->prepare("INSERT INTO password_reset (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, $expiresAt]);
            
            // E-Mail senden
            $subject = 'Zurücksetzen Ihres Passworts';
            $resetUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/Grillhuette/reset_password.php?token=' . $token;
            
            $body = '
                <h2>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h2>
                <p>Sie haben eine Anfrage zum Zurücksetzen Ihres Passworts gestellt.</p>
                <p>Bitte klicken Sie auf den folgenden Link, um Ihr Passwort zurückzusetzen:</p>
                <p><a href="' . $resetUrl . '">' . $resetUrl . '</a></p>
                <p>Dieser Link ist 1 Stunde gültig.</p>
                <p>Falls Sie keine Anfrage zum Zurücksetzen Ihres Passworts gestellt haben, können Sie diese E-Mail ignorieren.</p>
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
            return [
                'success' => false,
                'message' => 'Fehler beim Zurücksetzen des Passworts: ' . $e->getMessage()
            ];
        }
    }
    
    public function resetPassword($token, $newPassword) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.user_id 
                FROM password_reset r 
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
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            // Token löschen
            $stmt = $this->db->prepare("DELETE FROM password_reset WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            return [
                'success' => true,
                'message' => 'Ihr Passwort wurde erfolgreich zurückgesetzt. Sie können sich jetzt mit Ihrem neuen Passwort anmelden.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Zurücksetzen des Passworts: ' . $e->getMessage()
            ];
        }
    }
    
    public function updateProfile($userId, $firstName, $lastName, $phone, $currentPassword = null, $newPassword = null) {
        try {
            // Prüfen, ob der Benutzer existiert
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
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
                $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);
            }
            
            // Profilinformationen aktualisieren
            $stmt = $this->db->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
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
                'message' => 'Fehler beim Aktualisieren des Profils: ' . $e->getMessage()
            ];
        }
    }
    
    public function updateEmail($userId, $newEmail, $password) {
        try {
            // Prüfen, ob der Benutzer existiert und das Passwort korrekt ist
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Ungültiges Passwort.'
                ];
            }
            
            // Prüfen, ob die neue E-Mail-Adresse bereits existiert
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
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
                UPDATE users 
                SET email = ?, is_verified = 0, verification_token = ? 
                WHERE id = ?
            ");
            $stmt->execute([$newEmail, $verificationToken, $userId]);
            
            // E-Mail zur Verifikation senden
            $subject = 'Bestätigen Sie Ihre neue E-Mail-Adresse';
            $verifyUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/Grillhuette/verify.php?token=' . $verificationToken;
            
            $body = '
                <h2>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h2>
                <p>Sie haben eine Änderung Ihrer E-Mail-Adresse im Reservierungssystem für die Grillhütte angefordert.</p>
                <p>Bitte bestätigen Sie Ihre neue E-Mail-Adresse, indem Sie auf den folgenden Link klicken:</p>
                <p><a href="' . $verifyUrl . '">' . $verifyUrl . '</a></p>
                <p>Dieser Link ist 24 Stunden gültig.</p>
                <p>Falls Sie diese Änderung nicht angefordert haben, setzen Sie sich bitte umgehend mit dem Administrator in Verbindung.</p>
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
            return [
                'success' => false,
                'message' => 'Fehler beim Aktualisieren der E-Mail-Adresse: ' . $e->getMessage()
            ];
        }
    }

    public function getUserById($userId) {
        try {
            $stmt = $this->db->prepare("SELECT id, email, first_name, last_name, phone, is_admin, is_verified, created_at FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getAllUsers() {
        try {
            $stmt = $this->db->prepare("SELECT id, email, first_name, last_name, phone, is_admin, is_verified, created_at FROM users ORDER BY id");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function toggleAdmin($userId) {
        try {
            // Aktuellen Status abrufen
            $stmt = $this->db->prepare("SELECT is_admin FROM users WHERE id = ?");
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
            
            $stmt = $this->db->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            
            return [
                'success' => true,
                'message' => 'Administrator-Status geändert.',
                'is_admin' => $newStatus
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Ändern des Administrator-Status: ' . $e->getMessage()
            ];
        }
    }

    public function createUserByAdmin($email, $password, $firstName, $lastName, $phone = null, $isAdmin = 0) {
        try {
            // Prüfen, ob E-Mail bereits existiert
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'Diese E-Mail-Adresse ist bereits registriert.'
                ];
            }
            
            // Passwort hashen
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Benutzer anlegen (direkt verifiziert)
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password, first_name, last_name, phone, is_verified, is_admin) 
                VALUES (?, ?, ?, ?, ?, 1, ?)
            ");
            $stmt->execute([$email, $hashedPassword, $firstName, $lastName, $phone, $isAdmin]);
            
            return [
                'success' => true,
                'message' => 'Benutzer erfolgreich erstellt.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Erstellen des Benutzers: ' . $e->getMessage()
            ];
        }
    }
    
    public function deleteUser($userId) {
        try {
            // Prüfen, ob der Benutzer existiert
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Benutzer nicht gefunden.'
                ];
            }
            
            // Passwort-Reset-Tokens löschen
            $stmt = $this->db->prepare("DELETE FROM password_reset WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Benutzer löschen
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            return [
                'success' => true,
                'message' => 'Benutzer erfolgreich gelöscht.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Löschen des Benutzers: ' . $e->getMessage()
            ];
        }
    }
    
    public function authenticate($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
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
                'message' => 'Fehler bei der Authentifizierung: ' . $e->getMessage()
            ];
        }
    }
    
    public function updateUser($userId, $email, $firstName, $lastName, $phone = null, $isAdmin = 0, $newPassword = null) {
        try {
            // Prüfen, ob der Benutzer existiert
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
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
                $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->rowCount() > 0) {
                    return [
                        'success' => false,
                        'message' => 'Diese E-Mail-Adresse wird bereits verwendet.'
                    ];
                }
            }
            
            // SQL-Statement vorbereiten
            $sql = "UPDATE users SET email = ?, first_name = ?, last_name = ?, phone = ?, is_admin = ?";
            $params = [$email, $firstName, $lastName, $phone, $isAdmin];
            
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
            return [
                'success' => false,
                'message' => 'Fehler beim Aktualisieren des Benutzers: ' . $e->getMessage()
            ];
        }
    }
}
?> 