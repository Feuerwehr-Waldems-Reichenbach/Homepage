<?php
class Reservation {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($userId, $startDatetime, $endDatetime, $userMessage = null) {
        try {
            // Überprüfen, ob der Zeitraum verfügbar ist
            if (!$this->isTimeSlotAvailable($startDatetime, $endDatetime)) {
                return [
                    'success' => false,
                    'message' => 'Der gewählte Zeitraum ist nicht verfügbar. Bitte wählen Sie einen anderen Zeitraum.'
                ];
            }
            
            // Reservierung erstellen
            $stmt = $this->db->prepare("
                INSERT INTO gh_reservations (user_id, start_datetime, end_datetime, user_message, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$userId, $startDatetime, $endDatetime, $userMessage]);
            
            // Benutzerinformationen für E-Mail abrufen
            $userStmt = $this->db->prepare("SELECT email, first_name, last_name FROM gh_users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            // Bestätigungs-E-Mail senden
            $subject = 'Ihre Reservierungsanfrage für die Grillhütte';
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
                            <h2>Reservierungsanfrage eingegangen</h2>
                        </div>
                        <div class="content">
                            <h3>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h3>
                            <p>vielen Dank für Ihre Reservierungsanfrage für die Grillhütte.</p>
                            
                            <div class="info-box">
                                <strong>Ihre Reservierungsdetails:</strong><br>
                                Von: ' . date('d.m.Y H:i', strtotime($startDatetime)) . '<br>
                                Bis: ' . date('d.m.Y H:i', strtotime($endDatetime)) . '<br>
                                Status: <strong>Ausstehend</strong>
                            </div>
                            
                            <p>Ihre Anfrage wird nun von unserem Team geprüft. Sie erhalten eine weitere E-Mail, sobald Ihre Reservierung bestätigt oder abgelehnt wurde.</p>
                            
                            <a href="https://' . $_SERVER['HTTP_HOST'] . '/Grillhuette/my_reservations.php" class="button">Meine Reservierungen ansehen</a>
                            
                            <div class="footer">
                                <p>Bei Fragen stehen wir Ihnen gerne zur Verfügung.</p>
                                <p>Ihr Team der Grillhütte Reichenbach</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ';
            
            sendEmail($user['email'], $subject, $body);
            
            // Admin-E-Mail abrufen und Benachrichtigung senden
            $adminStmt = $this->db->prepare("SELECT email FROM gh_users WHERE is_admin = 1");
            $adminStmt->execute();
            $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($admins)) {
                $adminSubject = 'Neue Reservierungsanfrage';
                $adminBody = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                            .content { background-color: #ffffff; padding: 20px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; }
                            .button { display: inline-block; padding: 10px 20px; background-color: #A72920; color: white !important; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                            .info-box { background-color: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                            .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h2>Neue Reservierungsanfrage</h2>
                            </div>
                            <div class="content">
                                <p>Es liegt eine neue Reservierungsanfrage vor, die Ihre Aufmerksamkeit erfordert.</p>
                                
                                <div class="info-box">
                                    <strong>Reservierungsdetails:</strong><br>
                                    Benutzer: ' . $user['first_name'] . ' ' . $user['last_name'] . '<br>
                                    E-Mail: ' . $user['email'] . '<br>
                                    Von: ' . date('d.m.Y H:i', strtotime($startDatetime)) . '<br>
                                    Bis: ' . date('d.m.Y H:i', strtotime($endDatetime)) . '
                                </div>
                                
                                <a href="https://' . $_SERVER['HTTP_HOST'] . '/Grillhuette/admin_reservations.php" class="button">Reservierung verwalten</a>
                                
                                <div class="footer">
                                    <p>Dies ist eine automatische Benachrichtigung des Grillhütten-Reservierungssystems.</p>
                                </div>
                            </div>
                        </div>
                    </body>
                    </html>
                ';
                
                foreach ($admins as $admin) {
                    sendEmail($admin['email'], $adminSubject, $adminBody);
                }
            }
            
            return [
                'success' => true,
                'message' => 'Reservierung erfolgreich erstellt. Wir werden Sie benachrichtigen, sobald Ihre Anfrage bearbeitet wurde.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Erstellen der Reservierung: ' . $e->getMessage()
            ];
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, u.first_name, u.last_name, u.email 
                FROM gh_reservations r
                JOIN gh_users u ON r.user_id = u.id
                WHERE r.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function getByUserId($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM gh_reservations 
                WHERE user_id = ? 
                ORDER BY start_datetime DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getAll() {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, u.first_name, u.last_name, u.email 
                FROM gh_reservations r
                JOIN gh_users u ON r.user_id = u.id
                ORDER BY r.start_datetime DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getAllByStatus($status) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, u.first_name, u.last_name, u.email 
                FROM gh_reservations r
                JOIN gh_users u ON r.user_id = u.id
                WHERE r.status = ?
                ORDER BY r.start_datetime DESC
            ");
            $stmt->execute([$status]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function updateStatus($id, $status, $adminMessage = null) {
        try {
            $reservation = $this->getById($id);
            if (!$reservation) {
                return [
                    'success' => false,
                    'message' => 'Reservierung nicht gefunden.'
                ];
            }
            
            $stmt = $this->db->prepare("
                UPDATE gh_reservations 
                SET status = ?, admin_message = ? 
                WHERE id = ?
            ");
            $stmt->execute([$status, $adminMessage, $id]);
            
            // Benutzer per E-Mail benachrichtigen
            $userStmt = $this->db->prepare("SELECT email, first_name, last_name FROM gh_users WHERE id = ?");
            $userStmt->execute([$reservation['user_id']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            $statusText = $status == 'confirmed' ? 'bestätigt' : 'abgelehnt';
            $statusColor = $status == 'confirmed' ? '#28a745' : '#dc3545';
            $subject = 'Status Ihrer Reservierung für die Grillhütte';
            $body = '
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: ' . $statusColor . '; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { background-color: #ffffff; padding: 20px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; }
                        .button { display: inline-block; padding: 10px 20px; background-color: #A72920; color: white !important; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                        .info-box { background-color: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                        .status-badge { display: inline-block; padding: 5px 15px; background-color: ' . $statusColor . '; color: white; border-radius: 15px; }
                        .message-box { background-color: #f8f9fa; border-left: 4px solid ' . $statusColor . '; padding: 15px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>Reservierungsstatus aktualisiert</h2>
                        </div>
                        <div class="content">
                            <h3>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h3>
                            
                            <div class="info-box">
                                <strong>Ihre Reservierungsdetails:</strong><br>
                                Von: ' . date('d.m.Y H:i', strtotime($reservation['start_datetime'])) . '<br>
                                Bis: ' . date('d.m.Y H:i', strtotime($reservation['end_datetime'])) . '<br>
                                Status: <span class="status-badge">' . ucfirst($statusText) . '</span>
                            </div>
            ';
            
            if ($adminMessage) {
                $body .= '
                    <div class="message-box">
                        <strong>Nachricht vom Administrator:</strong><br>
                        ' . nl2br($adminMessage) . '
                    </div>
                ';
            }
            
            $body .= '
                            <a href="https://' . $_SERVER['HTTP_HOST'] . '/Grillhuette/my_reservations.php" class="button">Meine Reservierungen ansehen</a>
                            
                            <div class="footer">
                                <p>Bei Fragen können Sie auf diese E-Mail antworten.</p>
                                <p>Ihr Team der Grillhütte Reichenbach</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ';
            
            sendEmail($user['email'], $subject, $body);
            
            return [
                'success' => true,
                'message' => 'Status der Reservierung erfolgreich aktualisiert und Benutzer benachrichtigt.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Aktualisieren des Status: ' . $e->getMessage()
            ];
        }
    }
    
    public function addUserMessage($id, $message) {
        try {
            $stmt = $this->db->prepare("UPDATE gh_reservations SET user_message = ? WHERE id = ?");
            $stmt->execute([$message, $id]);
            
            // Admin benachrichtigen
            $reservation = $this->getById($id);
            
            $adminStmt = $this->db->prepare("SELECT email FROM gh_users WHERE is_admin = 1");
            $adminStmt->execute();
            $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($admins)) {
                $subject = 'Neue Nachricht zu einer Reservierung';
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
                            .message-box { background-color: #f8f9fa; border-left: 4px solid #A72920; padding: 15px; margin: 20px 0; }
                            .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h2>Neue Nachricht zu einer Reservierung</h2>
                            </div>
                            <div class="content">
                                <p>Ein Benutzer hat eine Nachricht zu seiner Reservierung hinzugefügt.</p>
                                
                                <div class="info-box">
                                    <strong>Reservierungsdetails:</strong><br>
                                    Benutzer: ' . $reservation['first_name'] . ' ' . $reservation['last_name'] . '<br>
                                    Von: ' . date('d.m.Y H:i', strtotime($reservation['start_datetime'])) . '<br>
                                    Bis: ' . date('d.m.Y H:i', strtotime($reservation['end_datetime'])) . '
                                </div>
                                
                                <div class="message-box">
                                    <strong>Neue Nachricht:</strong><br>
                                    ' . nl2br($message) . '
                                </div>
                                
                                <a href="https://' . $_SERVER['HTTP_HOST'] . '/Grillhuette/admin_reservations.php" class="button">Reservierung verwalten</a>
                                
                                <div class="footer">
                                    <p>Dies ist eine automatische Benachrichtigung des Grillhütten-Reservierungssystems.</p>
                                </div>
                            </div>
                        </div>
                    </body>
                    </html>
                ';
                
                foreach ($admins as $admin) {
                    sendEmail($admin['email'], $subject, $body);
                }
            }
            
            return [
                'success' => true,
                'message' => 'Nachricht erfolgreich hinzugefügt.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Hinzufügen der Nachricht: ' . $e->getMessage()
            ];
        }
    }
    
    public function addAdminMessage($id, $message) {
        try {
            $stmt = $this->db->prepare("UPDATE gh_reservations SET admin_message = ? WHERE id = ?");
            $stmt->execute([$message, $id]);
            
            // Benutzer benachrichtigen
            $reservation = $this->getById($id);
            
            $subject = 'Neue Nachricht zu Ihrer Reservierung';
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
                        .message-box { background-color: #f8f9fa; border-left: 4px solid #A72920; padding: 15px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>Neue Nachricht vom Administrator</h2>
                        </div>
                        <div class="content">
                            <h3>Hallo ' . $reservation['first_name'] . ' ' . $reservation['last_name'] . ',</h3>
                            
                            <div class="info-box">
                                <strong>Ihre Reservierung:</strong><br>
                                Von: ' . date('d.m.Y H:i', strtotime($reservation['start_datetime'])) . '<br>
                                Bis: ' . date('d.m.Y H:i', strtotime($reservation['end_datetime'])) . '
                            </div>
                            
                            <div class="message-box">
                                <strong>Nachricht vom Administrator:</strong><br>
                                ' . nl2br($message) . '
                            </div>
                            
                            <a href="https://' . $_SERVER['HTTP_HOST'] . '/Grillhuette/my_reservations.php" class="button">Meine Reservierungen ansehen</a>
                            
                            <div class="footer">
                                <p>Bei Fragen können Sie auf diese E-Mail antworten.</p>
                                <p>Ihr Team der Grillhütte Reichenbach</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ';
            
            sendEmail($reservation['email'], $subject, $body);
            
            return [
                'success' => true,
                'message' => 'Nachricht erfolgreich hinzugefügt und Benutzer benachrichtigt.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Hinzufügen der Nachricht: ' . $e->getMessage()
            ];
        }
    }
    
    public function createByAdmin($userId, $startDatetime, $endDatetime, $adminMessage = null) {
        try {
            // Überprüfen, ob der Zeitraum verfügbar ist
            if (!$this->isTimeSlotAvailable($startDatetime, $endDatetime)) {
                return [
                    'success' => false,
                    'message' => 'Der gewählte Zeitraum ist nicht verfügbar. Bitte wählen Sie einen anderen Zeitraum.'
                ];
            }
            
            // Reservierung erstellen (direkt bestätigt)
            $stmt = $this->db->prepare("
                INSERT INTO gh_reservations (user_id, start_datetime, end_datetime, admin_message, status) 
                VALUES (?, ?, ?, ?, 'confirmed')
            ");
            $stmt->execute([$userId, $startDatetime, $endDatetime, $adminMessage]);
            
            // Benutzer per E-Mail benachrichtigen
            $userStmt = $this->db->prepare("SELECT email, first_name, last_name FROM gh_users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            $subject = 'Neue Reservierung für die Grillhütte';
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
                        .message-box { background-color: #f8f9fa; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>Neue Reservierung bestätigt</h2>
                        </div>
                        <div class="content">
                            <h3>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h3>
                            <p>der Administrator hat eine Reservierung für Sie erstellt.</p>
                            
                            <div class="info-box">
                                <strong>Ihre Reservierungsdetails:</strong><br>
                                Von: ' . date('d.m.Y H:i', strtotime($startDatetime)) . '<br>
                                Bis: ' . date('d.m.Y H:i', strtotime($endDatetime)) . '<br>
                                Status: <span style="color: #28a745; font-weight: bold;">Bestätigt</span>
                            </div>
            ';
            
            if ($adminMessage) {
                $body .= '
                    <div class="message-box">
                        <strong>Nachricht vom Administrator:</strong><br>
                        ' . nl2br($adminMessage) . '
                    </div>
                ';
            }
            
            $body .= '
                            <a href="https://' . $_SERVER['HTTP_HOST'] . '/Grillhuette/my_reservations.php" class="button">Meine Reservierungen ansehen</a>
                            
                            <div class="footer">
                                <p>Bei Fragen können Sie auf diese E-Mail antworten.</p>
                                <p>Ihr Team der Grillhütte Reichenbach</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ';
            
            sendEmail($user['email'], $subject, $body);
            
            return [
                'success' => true,
                'message' => 'Reservierung erfolgreich erstellt und Benutzer benachrichtigt.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Erstellen der Reservierung: ' . $e->getMessage()
            ];
        }
    }
    
    public function cancel($id) {
        try {
            $reservation = $this->getById($id);
            if (!$reservation) {
                return [
                    'success' => false,
                    'message' => 'Reservierung nicht gefunden.'
                ];
            }
            
            $stmt = $this->db->prepare("UPDATE gh_reservations SET status = 'canceled' WHERE id = ?");
            $stmt->execute([$id]);
            
            // Benutzer per E-Mail benachrichtigen, falls vom Admin storniert
            if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && $reservation['user_id'] != $_SESSION['user_id']) {
                $userStmt = $this->db->prepare("SELECT email, first_name, last_name FROM gh_users WHERE id = ?");
                $userStmt->execute([$reservation['user_id']]);
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                $subject = 'Stornierung Ihrer Reservierung für die Grillhütte';
                $body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                            .content { background-color: #ffffff; padding: 20px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; }
                            .button { display: inline-block; padding: 10px 20px; background-color: #A72920; color: white !important; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                            .info-box { background-color: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                            .status-badge { display: inline-block; padding: 5px 15px; background-color: #dc3545; color: white; border-radius: 15px; }
                            .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h2>Reservierung storniert</h2>
                            </div>
                            <div class="content">
                                <h3>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h3>
                                
                                <div class="info-box">
                                    <strong>Stornierte Reservierung:</strong><br>
                                    Von: ' . date('d.m.Y H:i', strtotime($reservation['start_datetime'])) . '<br>
                                    Bis: ' . date('d.m.Y H:i', strtotime($reservation['end_datetime'])) . '<br>
                                    Status: <span class="status-badge">Storniert</span>
                                </div>
                                
                                <p>Bei Fragen wenden Sie sich bitte an den Administrator.</p>
                                
                                <a href="https://' . $_SERVER['HTTP_HOST'] . '/Grillhuette/my_reservations.php" class="button">Meine Reservierungen ansehen</a>
                                
                                <div class="footer">
                                    <p>Ihr Team der Grillhütte Reichenbach</p>
                                </div>
                            </div>
                        </div>
                    </body>
                    </html>
                ';
                
                sendEmail($user['email'], $subject, $body);
            }
            
            // Admin benachrichtigen, falls vom Benutzer storniert
            if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
                $adminStmt = $this->db->prepare("SELECT email FROM gh_users WHERE is_admin = 1");
                $adminStmt->execute();
                $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($admins)) {
                    $userInfo = $reservation['first_name'] . ' ' . $reservation['last_name'];
                    
                    $subject = 'Stornierung einer Reservierung';
                    $body = '
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                                .content { background-color: #ffffff; padding: 20px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; }
                                .button { display: inline-block; padding: 10px 20px; background-color: #A72920; color: white !important; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                                .info-box { background-color: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                                .status-badge { display: inline-block; padding: 5px 15px; background-color: #dc3545; color: white; border-radius: 15px; }
                                .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                            </style>
                        </head>
                        <body>
                            <div class="container">
                                <div class="header">
                                    <h2>Reservierung wurde storniert</h2>
                                </div>
                                <div class="content">
                                    <p>Eine Reservierung wurde vom Benutzer storniert.</p>
                                    
                                    <div class="info-box">
                                        <strong>Stornierte Reservierung:</strong><br>
                                        Benutzer: ' . $userInfo . '<br>
                                        Von: ' . date('d.m.Y H:i', strtotime($reservation['start_datetime'])) . '<br>
                                        Bis: ' . date('d.m.Y H:i', strtotime($reservation['end_datetime'])) . '<br>
                                        Status: <span class="status-badge">Storniert</span>
                                    </div>
                                    
                                    <a href="https://' . $_SERVER['HTTP_HOST'] . '/Grillhuette/admin_reservations.php" class="button">Reservierungen verwalten</a>
                                    
                                    <div class="footer">
                                        <p>Dies ist eine automatische Benachrichtigung des Grillhütten-Reservierungssystems.</p>
                                    </div>
                                </div>
                            </div>
                        </body>
                        </html>
                    ';
                    
                    foreach ($admins as $admin) {
                        sendEmail($admin['email'], $subject, $body);
                    }
                }
            }
            
            return [
                'success' => true,
                'message' => 'Reservierung erfolgreich storniert.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Stornieren der Reservierung: ' . $e->getMessage()
            ];
        }
    }
    
    public function getReservationsForCalendar($monthYear) {
        // Format: YYYY-MM
        list($year, $month) = explode('-', $monthYear);
        $startDate = $year . '-' . $month . '-01 00:00:00';
        $lastDay = date('t', strtotime($startDate));
        $endDate = $year . '-' . $month . '-' . $lastDay . ' 23:59:59';
        
        try {
            $stmt = $this->db->prepare("
                SELECT id, start_datetime, end_datetime, status 
                FROM gh_reservations 
                WHERE 
                (start_datetime BETWEEN ? AND ?) OR 
                (end_datetime BETWEEN ? AND ?) OR 
                (start_datetime <= ? AND end_datetime >= ?)
            ");
            $stmt->execute([$startDate, $endDate, $startDate, $endDate, $startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function isTimeSlotAvailable($startDatetime, $endDatetime) {
        try {
            // Überprüfen, ob für den Zeitraum bereits eine bestätigte oder ausstehende Reservierung existiert
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS count
                FROM gh_reservations 
                WHERE 
                    status IN ('confirmed', 'pending') AND
                    (
                        (start_datetime <= ? AND end_datetime > ?) OR
                        (start_datetime < ? AND end_datetime >= ?) OR
                        (start_datetime >= ? AND end_datetime <= ?)
                    )
            ");
            $stmt->execute([$endDatetime, $startDatetime, $endDatetime, $startDatetime, $startDatetime, $endDatetime]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] == 0;
        } catch (PDOException $e) {
            // Im Fehlerfall lieber vorsichtig sein und "nicht verfügbar" zurückgeben
            return false;
        }
    }
    
    public function getReservationDayStatus($date) {
        // Datum für den ganzen Tag (0:00 bis 23:59)
        $startDate = $date . ' 00:00:00';
        $endDate = $date . ' 23:59:59';
        
        try {
            // Reservierungen für diesen Tag abrufen
            $stmt = $this->db->prepare("
                SELECT id, start_datetime, end_datetime, status 
                FROM gh_reservations 
                WHERE 
                    status IN ('confirmed', 'pending') AND
                    (
                        (start_datetime <= ? AND end_datetime > ?) OR
                        (start_datetime >= ? AND start_datetime <= ?)
                    )
                ORDER BY start_datetime
            ");
            $stmt->execute([$endDate, $startDate, $startDate, $endDate]);
            $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($reservations)) {
                return 'free'; // Frei
            }
            
            $hasConfirmed = false;
            $hasPending = false;
            
            foreach ($reservations as $reservation) {
                if ($reservation['status'] == 'confirmed') {
                    $hasConfirmed = true;
                } else if ($reservation['status'] == 'pending') {
                    $hasPending = true;
                }
            }
            
            if ($hasConfirmed) {
                return 'booked'; // Belegt (bestätigt)
            } else if ($hasPending) {
                return 'pending'; // Ausstehend (noch nicht bestätigt)
            }
            
            return 'free'; // Sollte nicht erreicht werden, aber als Fallback
            
        } catch (PDOException $e) {
            return 'error'; // Fehlerfall
        }
    }
    
    public function deleteByUserId($userId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM gh_reservations WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            return [
                'success' => true,
                'message' => 'Alle Reservierungen des Benutzers wurden gelöscht.'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Löschen der Reservierungen: ' . $e->getMessage()
            ];
        }
    }

    public function updateReservation($id, $userId, $startDatetime, $endDatetime, $adminMessage = null, $status = 'pending') {
        try {
            // Überprüfen, ob die Reservierung existiert
            $stmt = $this->db->prepare("SELECT id FROM gh_reservations WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Reservierung nicht gefunden.'
                ];
            }
            
            // Reservierung aktualisieren
            $stmt = $this->db->prepare("
                UPDATE gh_reservations 
                SET user_id = ?, start_datetime = ?, end_datetime = ?, admin_message = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->execute([$userId, $startDatetime, $endDatetime, $adminMessage, $status, $id]);
            
            // Benutzer-Informationen abrufen
            $userStmt = $this->db->prepare("SELECT email, first_name, last_name FROM gh_users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            // E-Mail an den Benutzer senden
            if ($user) {
                $statusText = '';
                switch ($status) {
                    case 'pending':
                        $statusText = 'ausstehend';
                        break;
                    case 'confirmed':
                        $statusText = 'bestätigt';
                        break;
                    case 'canceled':
                        $statusText = 'storniert';
                        break;
                    default:
                        $statusText = $status;
                }
                
                $statusColor = $status == 'confirmed' ? '#28a745' : '#dc3545';
                $subject = 'Ihre Reservierungsdetails wurden aktualisiert';
                $body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background-color: ' . $statusColor . '; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                            .content { background-color: #ffffff; padding: 20px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; }
                            .button { display: inline-block; padding: 10px 20px; background-color: #A72920; color: white !important; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                            .info-box { background-color: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                            .status-badge { display: inline-block; padding: 5px 15px; background-color: ' . $statusColor . '; color: white; border-radius: 15px; }
                            .message-box { background-color: #f8f9fa; border-left: 4px solid ' . $statusColor . '; padding: 15px; margin: 20px 0; }
                            .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h2>Reservierungsdetails aktualisiert</h2>
                            </div>
                            <div class="content">
                                <h3>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h3>
                                <p>Ihre Reservierung für die Grillhütte wurde aktualisiert.</p>
                                <p><strong>Neuer Zeitraum:</strong> ' . date('d.m.Y H:i', strtotime($startDatetime)) . ' bis ' . date('d.m.Y H:i', strtotime($endDatetime)) . '</p>
                                <p><strong>Status:</strong> ' . ucfirst($statusText) . '</p>
                            </div>
                            <a href="https://' . $_SERVER['HTTP_HOST'] . '/Grillhuette/my_reservations.php" class="button">Meine Reservierungen ansehen</a>
                            <div class="footer">
                                <p>Bei Fragen können Sie auf diese E-Mail antworten.</p>
                                <p>Ihr Team der Grillhütte Reichenbach</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ';
                
                if (!empty($adminMessage)) {
                    $body .= '
                        <div class="message-box">
                            <strong>Nachricht vom Administrator:</strong><br>
                            ' . nl2br($adminMessage) . '
                        </div>
                    ';
                }
                
                sendEmail($user['email'], $subject, $body);
            }
            
            return [
                'success' => true,
                'message' => 'Reservierung erfolgreich aktualisiert.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Aktualisieren der Reservierung: ' . $e->getMessage()
            ];
        }
    }
    
    public function deleteReservation($id) {
        try {
            // Überprüfen, ob die Reservierung existiert
            $stmt = $this->db->prepare("SELECT r.*, u.email, u.first_name, u.last_name FROM gh_reservations r LEFT JOIN gh_users u ON r.user_id = u.id WHERE r.id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Reservierung nicht gefunden.'
                ];
            }
            
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Reservierung löschen
            $stmt = $this->db->prepare("DELETE FROM gh_reservations WHERE id = ?");
            $stmt->execute([$id]);
            
            // E-Mail an den Benutzer senden
            if ($reservation['email']) {
                $subject = 'Ihre Reservierung wurde gelöscht';
                $body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                            .content { background-color: #ffffff; padding: 20px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; }
                            .button { display: inline-block; padding: 10px 20px; background-color: #A72920; color: white !important; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                            .info-box { background-color: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                            .message-box { background-color: #f8f9fa; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; }
                            .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h2>Ihre Reservierung wurde gelöscht</h2>
                            </div>
                            <div class="content">
                                <p>Ihre Reservierung für die Grillhütte für den Zeitraum ' . date('d.m.Y H:i', strtotime($reservation['start_datetime'])) . ' bis ' . date('d.m.Y H:i', strtotime($reservation['end_datetime'])) . ' wurde gelöscht.</p>
                                <a href="https://' . $_SERVER['HTTP_HOST'] . '/Grillhuette/my_reservations.php" class="button">Meine Reservierungen ansehen</a>
                                <div class="footer">
                                    <p>Bei Fragen wenden Sie sich bitte an den Administrator.</p>
                                    <p>Ihr Team der Grillhütte Reichenbach</p>
                                </div>
                            </div>
                        </div>
                    </body>
                    </html>
                ';
                
                sendEmail($reservation['email'], $subject, $body);
            }
            
            return [
                'success' => true,
                'message' => 'Reservierung erfolgreich gelöscht.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Löschen der Reservierung: ' . $e->getMessage()
            ];
        }
    }
}
?> 