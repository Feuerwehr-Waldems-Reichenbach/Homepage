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
                INSERT INTO reservations (user_id, start_datetime, end_datetime, user_message, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$userId, $startDatetime, $endDatetime, $userMessage]);
            
            // Benutzerinformationen für E-Mail abrufen
            $userStmt = $this->db->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            // Bestätigungs-E-Mail senden
            $subject = 'Ihre Reservierungsanfrage für die Grillhütte';
            $body = '
                <h2>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h2>
                <p>Vielen Dank für Ihre Reservierungsanfrage für die Grillhütte.</p>
                <p>Ihre Reservierung für den Zeitraum vom ' . date('d.m.Y H:i', strtotime($startDatetime)) . ' bis ' . date('d.m.Y H:i', strtotime($endDatetime)) . ' wurde erfolgreich gespeichert und wartet auf Bestätigung.</p>
                <p>Sie werden benachrichtigt, sobald Ihre Reservierung bestätigt oder abgelehnt wurde.</p>
            ';
            
            sendEmail($user['email'], $subject, $body);
            
            // Admin-E-Mail abrufen und Benachrichtigung senden
            $adminStmt = $this->db->prepare("SELECT email FROM users WHERE is_admin = 1 LIMIT 1");
            $adminStmt->execute();
            $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                $adminSubject = 'Neue Reservierungsanfrage';
                $adminBody = '
                    <h2>Neue Reservierungsanfrage</h2>
                    <p>Es liegt eine neue Reservierungsanfrage vor:</p>
                    <p>
                        <strong>Benutzer:</strong> ' . $user['first_name'] . ' ' . $user['last_name'] . '<br>
                        <strong>E-Mail:</strong> ' . $user['email'] . '<br>
                        <strong>Zeitraum:</strong> ' . date('d.m.Y H:i', strtotime($startDatetime)) . ' bis ' . date('d.m.Y H:i', strtotime($endDatetime)) . '
                    </p>
                    <p>Bitte loggen Sie sich in das Administrationssystem ein, um die Anfrage zu bearbeiten.</p>
                ';
                
                sendEmail($admin['email'], $adminSubject, $adminBody);
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
                FROM reservations r
                JOIN users u ON r.user_id = u.id
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
                SELECT * FROM reservations 
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
                FROM reservations r
                JOIN users u ON r.user_id = u.id
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
                FROM reservations r
                JOIN users u ON r.user_id = u.id
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
                UPDATE reservations 
                SET status = ?, admin_message = ? 
                WHERE id = ?
            ");
            $stmt->execute([$status, $adminMessage, $id]);
            
            // Benutzer per E-Mail benachrichtigen
            $userStmt = $this->db->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
            $userStmt->execute([$reservation['user_id']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            $statusText = $status == 'confirmed' ? 'bestätigt' : 'abgelehnt';
            $subject = 'Status Ihrer Reservierung für die Grillhütte';
            $body = '
                <h2>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h2>
                <p>Der Status Ihrer Reservierung für den Zeitraum vom ' . date('d.m.Y H:i', strtotime($reservation['start_datetime'])) . ' bis ' . date('d.m.Y H:i', strtotime($reservation['end_datetime'])) . ' wurde geändert.</p>
                <p>Ihre Reservierung wurde <strong>' . $statusText . '</strong>.</p>
            ';
            
            if ($adminMessage) {
                $body .= '<p><strong>Nachricht vom Administrator:</strong> ' . $adminMessage . '</p>';
            }
            
            $body .= '<p>Bei Fragen können Sie auf diese E-Mail antworten.</p>';
            
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
            $stmt = $this->db->prepare("UPDATE reservations SET user_message = ? WHERE id = ?");
            $stmt->execute([$message, $id]);
            
            // Admin benachrichtigen
            $reservation = $this->getById($id);
            
            $adminStmt = $this->db->prepare("SELECT email FROM users WHERE is_admin = 1 LIMIT 1");
            $adminStmt->execute();
            $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                $subject = 'Neue Nachricht zu einer Reservierung';
                $body = '
                    <h2>Neue Nachricht zu einer Reservierung</h2>
                    <p>Ein Benutzer hat eine Nachricht zu seiner Reservierung hinzugefügt:</p>
                    <p>
                        <strong>Benutzer:</strong> ' . $reservation['first_name'] . ' ' . $reservation['last_name'] . '<br>
                        <strong>Zeitraum:</strong> ' . date('d.m.Y H:i', strtotime($reservation['start_datetime'])) . ' bis ' . date('d.m.Y H:i', strtotime($reservation['end_datetime'])) . '<br>
                        <strong>Nachricht:</strong> ' . $message . '
                    </p>
                    <p>Bitte loggen Sie sich in das Administrationssystem ein, um die Reservierung zu bearbeiten.</p>
                ';
                
                sendEmail($admin['email'], $subject, $body);
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
            $stmt = $this->db->prepare("UPDATE reservations SET admin_message = ? WHERE id = ?");
            $stmt->execute([$message, $id]);
            
            // Benutzer benachrichtigen
            $reservation = $this->getById($id);
            
            $subject = 'Neue Nachricht zu Ihrer Reservierung';
            $body = '
                <h2>Hallo ' . $reservation['first_name'] . ' ' . $reservation['last_name'] . ',</h2>
                <p>Der Administrator hat eine Nachricht zu Ihrer Reservierung vom ' . date('d.m.Y H:i', strtotime($reservation['start_datetime'])) . ' bis ' . date('d.m.Y H:i', strtotime($reservation['end_datetime'])) . ' hinzugefügt:</p>
                <p><strong>Nachricht:</strong> ' . $message . '</p>
                <p>Bei Fragen können Sie auf diese E-Mail antworten.</p>
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
                INSERT INTO reservations (user_id, start_datetime, end_datetime, admin_message, status) 
                VALUES (?, ?, ?, ?, 'confirmed')
            ");
            $stmt->execute([$userId, $startDatetime, $endDatetime, $adminMessage]);
            
            // Benutzer per E-Mail benachrichtigen
            $userStmt = $this->db->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            $subject = 'Neue Reservierung für die Grillhütte';
            $body = '
                <h2>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h2>
                <p>Der Administrator hat eine Reservierung für Sie erstellt.</p>
                <p>Ihre Reservierung für den Zeitraum vom ' . date('d.m.Y H:i', strtotime($startDatetime)) . ' bis ' . date('d.m.Y H:i', strtotime($endDatetime)) . ' wurde bestätigt.</p>
            ';
            
            if ($adminMessage) {
                $body .= '<p><strong>Nachricht vom Administrator:</strong> ' . $adminMessage . '</p>';
            }
            
            $body .= '<p>Bei Fragen können Sie auf diese E-Mail antworten.</p>';
            
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
            
            $stmt = $this->db->prepare("UPDATE reservations SET status = 'canceled' WHERE id = ?");
            $stmt->execute([$id]);
            
            // Benutzer per E-Mail benachrichtigen, falls vom Admin storniert
            if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && $reservation['user_id'] != $_SESSION['user_id']) {
                $userStmt = $this->db->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
                $userStmt->execute([$reservation['user_id']]);
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                $subject = 'Stornierung Ihrer Reservierung für die Grillhütte';
                $body = '
                    <h2>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h2>
                    <p>Ihre Reservierung für den Zeitraum vom ' . date('d.m.Y H:i', strtotime($reservation['start_datetime'])) . ' bis ' . date('d.m.Y H:i', strtotime($reservation['end_datetime'])) . ' wurde storniert.</p>
                    <p>Bei Fragen wenden Sie sich bitte an den Administrator.</p>
                ';
                
                sendEmail($user['email'], $subject, $body);
            }
            
            // Admin benachrichtigen, falls vom Benutzer storniert
            if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
                $adminStmt = $this->db->prepare("SELECT email FROM users WHERE is_admin = 1 LIMIT 1");
                $adminStmt->execute();
                $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin) {
                    $userInfo = $reservation['first_name'] . ' ' . $reservation['last_name'];
                    
                    $subject = 'Stornierung einer Reservierung';
                    $body = '
                        <h2>Stornierung einer Reservierung</h2>
                        <p>Eine Reservierung wurde vom Benutzer storniert:</p>
                        <p>
                            <strong>Benutzer:</strong> ' . $userInfo . '<br>
                            <strong>Zeitraum:</strong> ' . date('d.m.Y H:i', strtotime($reservation['start_datetime'])) . ' bis ' . date('d.m.Y H:i', strtotime($reservation['end_datetime'])) . '
                        </p>
                    ';
                    
                    sendEmail($admin['email'], $subject, $body);
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
                FROM reservations 
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
                FROM reservations 
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
                FROM reservations 
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
            $stmt = $this->db->prepare("DELETE FROM reservations WHERE user_id = ?");
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
            $stmt = $this->db->prepare("SELECT id FROM reservations WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Reservierung nicht gefunden.'
                ];
            }
            
            // Reservierung aktualisieren
            $stmt = $this->db->prepare("
                UPDATE reservations 
                SET user_id = ?, start_datetime = ?, end_datetime = ?, admin_message = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->execute([$userId, $startDatetime, $endDatetime, $adminMessage, $status, $id]);
            
            // Benutzer-Informationen abrufen
            $userStmt = $this->db->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
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
                
                $subject = 'Ihre Reservierungsdetails wurden aktualisiert';
                $body = '
                    <h2>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h2>
                    <p>Ihre Reservierung für die Grillhütte wurde aktualisiert.</p>
                    <p><strong>Neuer Zeitraum:</strong> ' . date('d.m.Y H:i', strtotime($startDatetime)) . ' bis ' . date('d.m.Y H:i', strtotime($endDatetime)) . '</p>
                    <p><strong>Status:</strong> ' . $statusText . '</p>
                ';
                
                if (!empty($adminMessage)) {
                    $body .= '<p><strong>Nachricht vom Administrator:</strong><br>' . nl2br($adminMessage) . '</p>';
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
            $stmt = $this->db->prepare("SELECT r.*, u.email, u.first_name, u.last_name FROM reservations r LEFT JOIN users u ON r.user_id = u.id WHERE r.id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Reservierung nicht gefunden.'
                ];
            }
            
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Reservierung löschen
            $stmt = $this->db->prepare("DELETE FROM reservations WHERE id = ?");
            $stmt->execute([$id]);
            
            // E-Mail an den Benutzer senden
            if ($reservation['email']) {
                $subject = 'Ihre Reservierung wurde gelöscht';
                $body = '
                    <h2>Hallo ' . $reservation['first_name'] . ' ' . $reservation['last_name'] . ',</h2>
                    <p>Ihre Reservierung für die Grillhütte für den Zeitraum ' . date('d.m.Y H:i', strtotime($reservation['start_datetime'])) . ' bis ' . date('d.m.Y H:i', strtotime($reservation['end_datetime'])) . ' wurde gelöscht.</p>
                    <p>Bei Fragen wenden Sie sich bitte an den Administrator.</p>
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