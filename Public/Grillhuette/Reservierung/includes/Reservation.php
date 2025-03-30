<?php
class Reservation {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Retrieves pricing information from the gh_informations table
     * 
     * @param int $userId ID of the user to check for special pricing
     * @return array Pricing information including base price, deposit amount, and calculated rate for the user
     */
    public function getPriceInformation($userId = null) {
        try {
            // Default values in case the database query fails
            $priceInfo = [
                'base_price' => 100.00,
                'deposit_amount' => 100.00,
                'user_rate' => 100.00,
                'rate_type' => 'normal'
            ];
            
            // Get pricing information from gh_informations table
            $infoStmt = $this->db->prepare("
                SELECT title, content 
                FROM gh_informations 
                WHERE title IN ('MietpreisNormal', 'MietpreisAktivesMitglied', 'MietpreisFeuerwehr', 'Kautionspreis')
            ");
            $infoStmt->execute();
            $pricingData = $infoStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // If we have data from the database, update our default values
            if (!empty($pricingData)) {
                // Remove the € symbol and replace comma with dot for proper decimal parsing
                $basePrice = floatval(str_replace(['€', ','], ['', '.'], $pricingData['MietpreisNormal'] ?? '100'));
                $aktivesPrice = floatval(str_replace(['€', ','], ['', '.'], $pricingData['MietpreisAktivesMitglied'] ?? '50'));
                
                // Always set Feuerwehr price to 0, regardless of what's in the database
                // This ensures the price is always free for Feuerwehr members
                $feuerwehrPrice = 0.00;
                
                $depositAmount = floatval(str_replace(['€', ','], ['', '.'], $pricingData['Kautionspreis'] ?? '100'));
                
                $priceInfo['base_price'] = $basePrice;
                $priceInfo['deposit_amount'] = $depositAmount;
                $priceInfo['user_rate'] = $basePrice; // Default to normal rate
            }
            
            // If userId is provided, check for special pricing
            if ($userId) {
                // Special case for test mode
                if ($userId === 'test' && isset($_SESSION['is_Feuerwehr']) && $_SESSION['is_Feuerwehr']) {
                    $priceInfo['user_rate'] = 0.00; // Hard-coded to 0 for Feuerwehr
                    $priceInfo['rate_type'] = 'feuerwehr';
                    return $priceInfo;
                }
                
                $userStmt = $this->db->prepare("
                    SELECT is_AktivesMitglied, is_Feuerwehr 
                    FROM gh_users 
                    WHERE id = ?
                ");
                $userStmt->execute([$userId]);
                $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($userData) {
                    // Force conversion to boolean to ensure proper comparison
                    $isFeuerwehr = (bool)$userData['is_Feuerwehr'];
                    $isAktivesMitglied = (bool)$userData['is_AktivesMitglied'];
                    
                    if ($isFeuerwehr) {
                        // Feuerwehr has highest priority pricing - always set to 0
                        $priceInfo['user_rate'] = 0.00;
                        $priceInfo['rate_type'] = 'feuerwehr';
                    } elseif ($isAktivesMitglied) {
                        // Aktives Mitglied has second priority
                        $priceInfo['user_rate'] = $aktivesPrice;
                        $priceInfo['rate_type'] = 'aktives_mitglied';
                    }                   
                }
            }
            
            return $priceInfo;
        } catch (PDOException $e) {
            return [
                'base_price' => 100.00,
                'deposit_amount' => 100.00,
                'user_rate' => 100.00,
                'rate_type' => 'normal'
            ];
        }
    }
    
    public function create($userId, $startDatetime, $endDatetime, $userMessage = null) {
        // Zuweisung der URL-Variablen
        $myReservationsUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Benutzer/Meine-Reservierungen');
        $adminReservationsUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Admin/Reservierungsverwaltung');

        try {
            // Überprüfen, ob der Zeitraum verfügbar ist
            if (!$this->isTimeSlotAvailable($startDatetime, $endDatetime)) {
                return [
                    'success' => false,
                    'message' => 'Der gewählte Zeitraum ist nicht verfügbar. Bitte wählen Sie einen anderen Zeitraum.'
                ];
            }
            
            // Kostenberechnung
            $startDate = new DateTime($startDatetime);
            $endDate = new DateTime($endDatetime);
            $diffSeconds = $endDate->getTimestamp() - $startDate->getTimestamp();
            $diffDays = $diffSeconds / (24 * 60 * 60);
            $days = max(1, ceil($diffDays));
            
            // Preisdaten abrufen
            $priceInfo = $this->getPriceInformation($userId);
            $userRate = $priceInfo['user_rate'];
            $basePrice = $priceInfo['base_price'];
            $depositAmount = $priceInfo['deposit_amount'];
            $totalCost = $days * $userRate;
            
            // Reservierung erstellen mit Preisdaten
            $stmt = $this->db->prepare("
                INSERT INTO gh_reservations (
                    user_id, start_datetime, end_datetime, user_message, 
                    days_count, base_price, total_price, deposit_amount
                ) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId, $startDatetime, $endDatetime, $userMessage, 
                $days, $userRate, $totalCost, $depositAmount
            ]);
            
            // Formatierte Werte für E-Mail
            $formattedUserRate = number_format($userRate, 2, ',', '.');
            $formattedTotalCost = number_format($totalCost, 2, ',', '.');
            $formattedDepositAmount = number_format($depositAmount, 2, ',', '.');
            
            // Benutzer per E-Mail benachrichtigen
            $userStmt = $this->db->prepare("SELECT email, first_name, last_name FROM gh_users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            $subject = 'Reservierungsanfrage für die Grillhütte Waldems Reichenbach';
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
                        .cost-box { background-color: #f0f8ff; border: 1px solid #b8daff; padding: 15px; border-radius: 5px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>Reservierungsanfrage</h2>
                        </div>
                        <div class="content">
                            <h3>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h3>
                            <p>vielen Dank für Ihre Reservierungsanfrage für die Grillhütte Waldems Reichenbach. Wir haben Ihre Anfrage erhalten und werden sie so schnell wie möglich bearbeiten.</p>
                            
                            <div class="info-box">
                                <strong>Ihre Reservierungsdetails:</strong><br>
                                Von: ' . date('d.m.Y H:i', strtotime($startDatetime)) . '<br>
                                Bis: ' . date('d.m.Y H:i', strtotime($endDatetime)) . '<br>
                                Status: <span style="color: #ffc107; font-weight: bold;">Ausstehend</span>
                            </div>
                            
                            <div class="cost-box">
                                <strong>Kostenübersicht:</strong><br>
                                Grundpreis pro Tag: ' . $formattedUserRate . '€<br>
                                Anzahl der Tage: ' . $days . '<br>
                                <strong>Gesamtpreis: ' . $formattedTotalCost . '€</strong><br>
                                <small>Hinweis: Die Kaution (' . $formattedDepositAmount . '€) ist in diesem Preis nicht enthalten.</small>
                            </div>
                            
                            <p>Wir werden Sie benachrichtigen, sobald Ihre Anfrage bearbeitet wurde. Sie können den Status Ihrer Reservierung auch jederzeit über den folgenden Link überprüfen.</p>
                            
                            <a href="' . $myReservationsUrl . '" class="button">Meine Reservierungen ansehen</a>
                            
                            <div class="footer">
                                <p>Bei Fragen stehen wir Ihnen gerne zur Verfügung.</p>
                                <p>Ihr Team der Grillhütte Waldems Reichenbach</p>
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
                            .cost-box { background-color: #f0f8ff; border: 1px solid #b8daff; padding: 15px; border-radius: 5px; margin: 20px 0; }
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
                                
                                <div class="cost-box">
                                    <strong>Kostenübersicht:</strong><br>
                                    Grundpreis pro Tag: ' . $formattedUserRate . '€<br>
                                    Anzahl der Tage: ' . $days . '<br>
                                    <strong>Gesamtpreis: ' . $formattedTotalCost . '€</strong><br>';
                
                // Spezielle Preishinweise für Administratoren
                if ($priceInfo['rate_type'] !== 'normal') {
                    $rateTypeText = $priceInfo['rate_type'] === 'feuerwehr' ? 'Feuerwehr' : 'Aktives Mitglied';
                    $normalRate = number_format($basePrice * $days, 2, ',', '.');
                    $adminBody .= '
                                    <span style="color: #A72920;"><strong>Hinweis:</strong> Spezialpreis für ' . $rateTypeText . ' angewendet!</span><br>
                                    <small>(Regulärer Preis wäre: ' . $normalRate . '€)</small>';
                }
                
                $adminBody .= '
                                </div>
                                
                                <a href="' . $adminReservationsUrl . '" class="button">Reservierung verwalten</a>
                                
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
                'message' => 'Ein technischer Fehler ist aufgetreten. Bitte versuchen Sie es später erneut oder kontaktieren Sie den Support.'
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
        // Zuweisung der URL-Variablen
        $myReservationsUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Benutzer/Meine-Reservierungen');
        $adminReservationsUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Admin/Reservierungsverwaltung');

        try {
            // Reservierungsdaten abrufen
            $reservationData = $this->getById($id);
            if (!$reservationData) {
                return [
                    'success' => false,
                    'message' => 'Reservierung nicht gefunden.'
                ];
            }
            
            // Status aktualisieren
            $stmt = $this->db->prepare("
                UPDATE gh_reservations 
                SET status = ?, admin_message = ? 
                WHERE id = ?
            ");
            $stmt->execute([$status, $adminMessage, $id]);
            
            // Preisdaten abrufen
            $daysCount = $reservationData['days_count'] ?? 1;
            $basePrice = $reservationData['base_price'] ?? 100.00;
            $totalPrice = $reservationData['total_price'] ?? ($daysCount * $basePrice);
            $depositAmount = $reservationData['deposit_amount'] ?? 100.00;
            
            // Formatierte Werte für die E-Mail
            $formattedBasePrice = number_format($basePrice, 2, ',', '.');
            $formattedTotalPrice = number_format($totalPrice, 2, ',', '.');
            $formattedDepositAmount = number_format($depositAmount, 2, ',', '.');
            
            $statusText = $status == 'confirmed' ? 'bestätigt' : 'abgelehnt';
            $statusColor = $status == 'confirmed' ? '#28a745' : '#dc3545';
            $subject = 'Status Ihrer Reservierung für die Grillhütte Waldems Reichenbach';
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
                        .cost-box { background-color: #f0f8ff; border: 1px solid #b8daff; padding: 15px; border-radius: 5px; margin: 20px 0; }
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
                            <h3>Hallo ' . $reservationData['first_name'] . ' ' . $reservationData['last_name'] . ',</h3>
                            
                            <div class="info-box">
                                <strong>Ihre Reservierungsdetails:</strong><br>
                                Von: ' . date('d.m.Y H:i', strtotime($reservationData['start_datetime'])) . '<br>
                                Bis: ' . date('d.m.Y H:i', strtotime($reservationData['end_datetime'])) . '<br>
                                Status: <span class="status-badge">' . ucfirst($statusText) . '</span>
                            </div>
                            
                            <div class="cost-box">
                                <strong>Kostenübersicht:</strong><br>
                                Grundpreis pro Tag: ' . $formattedBasePrice . '€<br>
                                Anzahl der Tage: ' . $daysCount . '<br>
                                <strong>Gesamtpreis: ' . $formattedTotalPrice . '€</strong><br>
                                <small>Hinweis: Die Kaution (' . $formattedDepositAmount . '€) ist in diesem Preis nicht enthalten.</small>
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
                            <a href="' . $myReservationsUrl . '" class="button">Meine Reservierungen ansehen</a>
                            
                            <div class="footer">
                                <p>Bei Fragen können Sie auf diese E-Mail antworten.</p>
                                <p>Ihr Team der Grillhütte Waldems Reichenbach</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ';
            
            // Benutzer per E-Mail benachrichtigen
            $userStmt = $this->db->prepare("SELECT email, first_name, last_name FROM gh_users WHERE id = ?");
            $userStmt->execute([$reservationData['user_id']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            sendEmail($user['email'], $subject, $body);
            
            return [
                'success' => true,
                'message' => 'Status der Reservierung erfolgreich aktualisiert und Benutzer benachrichtigt.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Ein Fehler ist aufgetreten. Die Statusänderung konnte nicht gespeichert werden.'
            ];
        }
    }
    
    public function addUserMessage($id, $message) {
        // Zuweisung der URL-Variablen
        $myReservationsUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Benutzer/Meine-Reservierungen');
        $adminReservationsUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Admin/Reservierungsverwaltung');

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
                                
                                <a href="' . $adminReservationsUrl . '" class="button">Reservierung verwalten</a>
                                
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
                'message' => 'Die Nachricht konnte nicht gespeichert werden. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function addAdminMessage($id, $message) {
        // Zuweisung der URL-Variablen
        $myReservationsUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Benutzer/Meine-Reservierungen');
        $adminReservationsUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Admin/Reservierungsverwaltung');

        try {
            $stmt = $this->db->prepare("UPDATE gh_reservations SET admin_message = ? WHERE id = ?");
            $stmt->execute([$message, $id]);
            
            // Benutzer benachrichtigen
            $reservation = $this->getById($id);
            
            $subject = 'Neue Nachricht vom Administrator';
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
                            <p>Der Administrator hat eine Nachricht zu Ihrer Reservierung hinzugefügt.</p>
                            
                            <div class="info-box">
                                <strong>Reservierungsdetails:</strong><br>
                                Von: ' . date('d.m.Y H:i', strtotime($reservation['start_datetime'])) . '<br>
                                Bis: ' . date('d.m.Y H:i', strtotime($reservation['end_datetime'])) . '
                            </div>
                            
                            <div class="message-box">
                                <strong>Nachricht vom Administrator:</strong><br>
                                ' . nl2br($message) . '
                            </div>
                            
                            <a href="' . $myReservationsUrl . '" class="button">Meine Reservierungen ansehen</a>
                            
                            <div class="footer">
                                <p>Bei Fragen können Sie auf diese E-Mail antworten.</p>
                                <p>Ihr Team der Grillhütte Waldems Reichenbach</p>
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
                'message' => 'Die Admin-Nachricht konnte nicht gespeichert werden. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function createByAdmin($userId, $startDatetime, $endDatetime, $adminMessage = null) {
        // Zuweisung der URL-Variablen
        $myReservationsUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Benutzer/Meine-Reservierungen');
        $adminReservationsUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Admin/Reservierungsverwaltung');

        try {
            // Überprüfen, ob der Zeitraum verfügbar ist
            if (!$this->isTimeSlotAvailable($startDatetime, $endDatetime)) {
                return [
                    'success' => false,
                    'message' => 'Der gewählte Zeitraum ist nicht verfügbar. Bitte wählen Sie einen anderen Zeitraum.'
                ];
            }
            
            // Preisberechnung
            $startDate = new DateTime($startDatetime);
            $endDate = new DateTime($endDatetime);
            $diffSeconds = $endDate->getTimestamp() - $startDate->getTimestamp();
            $diffDays = $diffSeconds / (24 * 60 * 60);
            $days = max(1, ceil($diffDays));
            
            // Preisdaten abrufen
            $priceInfo = $this->getPriceInformation($userId);
            $userRate = $priceInfo['user_rate'];
            $basePrice = $priceInfo['base_price'];
            $depositAmount = $priceInfo['deposit_amount'];
            $totalCost = $days * $userRate;
            
            // Reservierung erstellen (direkt bestätigt) mit Preisdaten
            $stmt = $this->db->prepare("
                INSERT INTO gh_reservations (
                    user_id, start_datetime, end_datetime, admin_message, status,
                    days_count, base_price, total_price, deposit_amount
                ) 
                VALUES (?, ?, ?, ?, 'confirmed', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId, $startDatetime, $endDatetime, $adminMessage,
                $days, $userRate, $totalCost, $depositAmount
            ]);
            
            // Benutzer per E-Mail benachrichtigen
            $userStmt = $this->db->prepare("SELECT email, first_name, last_name FROM gh_users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            // Formatierte Werte für die E-Mail
            $formattedUserRate = number_format($userRate, 2, ',', '.');
            $formattedTotalCost = number_format($totalCost, 2, ',', '.');
            $formattedDepositAmount = number_format($depositAmount, 2, ',', '.');
            
            $subject = 'Neue Reservierung für die Grillhütte Waldems Reichenbach';
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
                        .cost-box { background-color: #f0f8ff; border: 1px solid #b8daff; padding: 15px; border-radius: 5px; margin: 20px 0; }
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
                            
                            <div class="cost-box">
                                <strong>Kostenübersicht:</strong><br>
                                Grundpreis pro Tag: ' . $formattedUserRate . '€<br>
                                Anzahl der Tage: ' . $days . '<br>
                                <strong>Gesamtpreis: ' . $formattedTotalCost . '€</strong><br>
                                <small>Hinweis: Die Kaution (' . $formattedDepositAmount . '€) ist in diesem Preis nicht enthalten.</small>
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
                            <a href="' . $myReservationsUrl . '" class="button">Meine Reservierungen ansehen</a>
                            
                            <div class="footer">
                                <p>Bei Fragen können Sie auf diese E-Mail antworten.</p>
                                <p>Ihr Team der Grillhütte Waldems Reichenbach</p>
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
                'message' => 'Die Reservierung konnte nicht erstellt werden. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    
    public function cancel($id) {
        // Zuweisung der URL-Variablen
        $myReservationsUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Benutzer/Meine-Reservierungen');
        $adminReservationsUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Admin/Reservierungsverwaltung');

        try {
            // Reservierungsdaten vor der Stornierung abrufen
            $reservationData = $this->getById($id);
            if (!$reservationData) {
                return [
                    'success' => false,
                    'message' => 'Reservierung nicht gefunden.'
                ];
            }
            
            // Reservierung stornieren
            $stmt = $this->db->prepare("UPDATE gh_reservations SET status = 'canceled' WHERE id = ?");
            $stmt->execute([$id]);
            
            // Preisdaten abrufen
            $daysCount = $reservationData['days_count'];
            $basePrice = $reservationData['base_price'];
            $totalPrice = $reservationData['total_price'];
            $depositAmount = $reservationData['deposit_amount'];
            
            // Formatierte Werte für die E-Mail
            $formattedBasePrice = number_format($basePrice, 2, ',', '.');
            $formattedTotalPrice = number_format($totalPrice, 2, ',', '.');
            $formattedDepositAmount = number_format($depositAmount, 2, ',', '.');
            
            // Benutzer per E-Mail benachrichtigen, falls vom Admin storniert
            if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && $reservationData['user_id'] != $_SESSION['user_id']) {
                $userStmt = $this->db->prepare("SELECT email, first_name, last_name FROM gh_users WHERE id = ?");
                $userStmt->execute([$reservationData['user_id']]);
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                $subject = 'Stornierung Ihrer Reservierung für die Grillhütte Waldems Reichenbach';
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
                            .cost-box { background-color: #f0f8ff; border: 1px solid #b8daff; padding: 15px; border-radius: 5px; margin: 20px 0; text-decoration: line-through; }
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
                                    Von: ' . date('d.m.Y H:i', strtotime($reservationData['start_datetime'])) . '<br>
                                    Bis: ' . date('d.m.Y H:i', strtotime($reservationData['end_datetime'])) . '<br>
                                    Status: <span class="status-badge">Storniert</span>
                                </div>
                                
                                <div class="cost-box">
                                    <strong>Ursprüngliche Kostenübersicht:</strong><br>
                                    Grundpreis pro Tag: ' . $formattedBasePrice . '€<br>
                                    Anzahl der Tage: ' . $daysCount . '<br>
                                    <strong>Gesamtpreis: ' . $formattedTotalPrice . '€</strong>
                                </div>
                                
                                <p>Bei Fragen wenden Sie sich bitte an den Administrator.</p>
                                
                                <a href="' . $myReservationsUrl . '" class="button">Meine Reservierungen ansehen</a>
                                
                                <div class="footer">
                                    <p>Ihr Team der Grillhütte Waldems Reichenbach</p>
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
                    $userInfo = $reservationData['first_name'] . ' ' . $reservationData['last_name'];
                    
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
                                .cost-box { background-color: #f0f8ff; border: 1px solid #b8daff; padding: 15px; border-radius: 5px; margin: 20px 0; }
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
                                        Von: ' . date('d.m.Y H:i', strtotime($reservationData['start_datetime'])) . '<br>
                                        Bis: ' . date('d.m.Y H:i', strtotime($reservationData['end_datetime'])) . '<br>
                                        Status: <span class="status-badge">Storniert</span>
                                    </div>
                                    
                                    <div class="cost-box">
                                        <strong>Ursprüngliche Kostenübersicht:</strong><br>
                                        Grundpreis pro Tag: ' . $formattedBasePrice . '€<br>
                                        Anzahl der Tage: ' . $daysCount . '<br>
                                        <strong>Gesamtpreis: ' . $formattedTotalPrice . '€</strong>
                                    </div>
                                    
                                    <a href="' . $adminReservationsUrl . '" class="button">Reservierungen verwalten</a>
                                    
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
                'message' => 'Die Reservierung konnte nicht storniert werden. Bitte versuchen Sie es später erneut.'
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
                'message' => 'Die Reservierungen konnten nicht gelöscht werden. Bitte versuchen Sie es später erneut.'
            ];
        }
    }

    public function updateReservation($id, $userId, $startDatetime, $endDatetime, $adminMessage = null, $status = 'pending') {
        try {
            // Überprüfen, ob die Reservierung existiert
            $stmt = $this->db->prepare("SELECT id, status FROM gh_reservations WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Reservierung nicht gefunden.'
                ];
            }

            // Wenn der Status auf "confirmed" gesetzt werden soll, prüfen ob der Zeitraum verfügbar ist
            if ($status === 'confirmed') {
                // Prüfen ob es andere bestätigte oder ausstehende Reservierungen im gleichen Zeitraum gibt
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) AS count
                    FROM gh_reservations 
                    WHERE 
                        id != ? AND
                        status = 'confirmed' AND
                        (
                            (start_datetime <= ? AND end_datetime > ?) OR
                            (start_datetime < ? AND end_datetime >= ?) OR
                            (start_datetime >= ? AND end_datetime <= ?)
                        )
                ");
                $stmt->execute([$id, $endDatetime, $startDatetime, $endDatetime, $startDatetime, $startDatetime, $endDatetime]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    return [
                        'success' => false,
                        'message' => 'Der Zeitraum ist bereits durch eine andere Reservierung belegt. Die Reservierung kann nicht bestätigt werden.'
                    ];
                }
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
                $myReservationsUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Benutzer/Meine-Reservierungen');
                
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
                            .cost-box { background-color: #f0f8ff; border: 1px solid #b8daff; padding: 15px; border-radius: 5px; margin: 20px 0; }
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
                                <p>Ihre Reservierung für die Grillhütte Waldems Reichenbach wurde aktualisiert.</p>
                                <p><strong>Zeitraum:</strong> ' . date('d.m.Y H:i', strtotime($startDatetime)) . ' bis ' . date('d.m.Y H:i', strtotime($endDatetime)) . '</p>
                                <p><strong>Status:</strong> ' . ucfirst($statusText) . '</p>
                                ';
                                if (!empty($adminMessage)) {
                                    $body .= '
                                    <div class="message-box">
                                    <strong>Nachricht vom Administrator:</strong><br>
                                    ' . nl2br($adminMessage) . '
                                    </div>
                                    ';
                                }
                                
                                $body .= '
                                <a href="' . $myReservationsUrl . '" class="button">Meine Reservierungen ansehen</a>
                                <div class="footer">
                                <p>Bei Fragen können Sie auf diese E-Mail antworten.</p>
                                <p>Ihr Team der Grillhütte Waldems Reichenbach</p>
                                </div>
                                </div>
                        </div>
                    </body>
                    </html>
                ';
                

                
                sendEmail($user['email'], $subject, $body);
            }
            
            return [
                'success' => true,
                'message' => 'Reservierung erfolgreich aktualisiert.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Die Reservierung konnte nicht aktualisiert werden. Bitte versuchen Sie es später erneut.'
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
            
            $myReservationsUrl = 'https://' . $_SERVER['HTTP_HOST'] . getRelativePath('Benutzer/Meine-Reservierungen');
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
                                <p>Ihre Reservierung für die Grillhütte Waldems Reichenbach für den Zeitraum ' . date('d.m.Y H:i', strtotime($reservation['start_datetime'])) . ' bis ' . date('d.m.Y H:i', strtotime($reservation['end_datetime'])) . ' wurde gelöscht.</p>
                                <a href="' . $myReservationsUrl . '" class="button">Meine Reservierungen ansehen</a>
                                <div class="footer">
                                    <p>Bei Fragen wenden Sie sich bitte an den Administrator.</p>
                                    <p>Ihr Team der Grillhütte Waldems Reichenbach</p>
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
                'message' => 'Die Reservierung konnte nicht gelöscht werden. Bitte versuchen Sie es später erneut.'
            ];
        }
    }

    /**
     * Systeminformationen aus der Datenbank abrufen
     * 
     * @param array $keys Array mit Schlüsseln, die abgerufen werden sollen
     * @param string $category Optional: Nur Einträge einer bestimmten Kategorie abrufen
     * @return array Array mit den abgerufenen Informationen
     */
    public function getSystemInformation($keys = [], $category = null) {
        try {
            if (empty($keys) && empty($category)) {
                // Wenn keine Schlüssel und keine Kategorie angegeben, alle Einträge holen
                $stmt = $this->db->prepare("SELECT title, content FROM gh_informations ORDER BY sort_order ASC");
                $stmt->execute();
            } else if (!empty($category) && empty($keys)) {
                // Wenn nur Kategorie angegeben, alle Einträge der Kategorie holen
                $stmt = $this->db->prepare("SELECT title, content FROM gh_informations WHERE category = ? ORDER BY sort_order ASC");
                $stmt->execute([$category]);
            } else if (!empty($keys) && !empty($category)) {
                // Wenn Schlüssel und Kategorie angegeben, beide Kriterien anwenden
                $placeholders = str_repeat('?,', count($keys) - 1) . '?';
                $stmt = $this->db->prepare("SELECT title, content FROM gh_informations WHERE title IN ($placeholders) AND category = ? ORDER BY sort_order ASC");
                $params = array_merge($keys, [$category]);
                $stmt->execute($params);
            } else {
                // Nur Schlüssel angegeben
                $placeholders = str_repeat('?,', count($keys) - 1) . '?';
                $stmt = $this->db->prepare("SELECT title, content FROM gh_informations WHERE title IN ($placeholders) ORDER BY sort_order ASC");
                $stmt->execute($keys);
            }
            
            // Daten als Key-Value-Paare abrufen
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Abrufen aller Systeminformationen mit vollständigen Datensätzen (nicht als Key-Value-Paare)
     * 
     * @param string $category Optional: Filter für eine bestimmte Kategorie
     * @return array Array mit vollständigen Datensätzen
     */
    public function getAllSystemInformationRecords($category = null) {
        try {
            if (empty($category)) {
                $sql = "SELECT id, title, content, category, sort_order 
                       FROM gh_informations 
                       ORDER BY category, sort_order ASC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
            } else {
                $sql = "SELECT id, title, content, category, sort_order 
                       FROM gh_informations 
                       WHERE category = ? 
                       ORDER BY sort_order ASC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$category]);
            }
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);            
            // Leeres Array als Fallback, falls keine Ergebnisse
            return $results ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Hinzufügen einer neuen Information zur gh_informations-Tabelle
     * 
     * @param string $title Titel/Schlüssel der Information
     * @param string $content Inhalt der Information
     * @param string $category Kategorie der Information
     * @param int $sortOrder Sortierreihenfolge
     * @return array Erfolg/Fehlermeldung
     */
    public function addInformation($title, $content, $category, $sortOrder = 10) {
        try {
            // Prüfen, ob der Titel bereits existiert
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM gh_informations WHERE title = ?");
            $checkStmt->execute([$title]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'Eine Information mit diesem Titel existiert bereits.'
                ];
            }
            
            // Verwende MySQL-Syntax die mit der Tabellendefinition kompatibel ist
            $stmt = $this->db->prepare("
                INSERT INTO gh_informations (title, content, category, sort_order) 
                VALUES (?, ?, ?, ?)
            ");
            $success = $stmt->execute([$title, $content, $category, $sortOrder]);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Information erfolgreich hinzugefügt.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Fehler beim Hinzufügen der Information.'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Datenbankfehler beim Hinzufügen der Information: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Aktualisieren einer Information in der gh_informations-Tabelle
     * 
     * @param int $id ID der zu aktualisierenden Information
     * @param string $content Neuer Inhalt
     * @param string $category Neue Kategorie (optional)
     * @param int $sortOrder Neue Sortierreihenfolge (optional)
     * @return array Erfolg/Fehlermeldung
     */
    public function updateInformation($id, $content, $category = null, $sortOrder = null) {
        try {
            // Prüfen Sie, ob es sich um einen System-Eintrag handelt
            $checkStmt = $this->db->prepare("SELECT category FROM gh_informations WHERE id = ?");
            $checkStmt->execute([$id]);
            $entryCategory = $checkStmt->fetchColumn();
            
            // Dynamisches SQL basierend auf den übergebenen Parametern
            $sql = "UPDATE gh_informations SET content = ?";
            $params = [$content];
            
            if ($category !== null) {
                $sql .= ", category = ?";
                $params[] = $category;
            }
            
            // Sort order nur aktualisieren, wenn es kein System-Eintrag ist und ein Wert gegeben ist
            if ($sortOrder !== null && $entryCategory !== 'system') {
                $sql .= ", sort_order = ?";
                $params[] = $sortOrder;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($params);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Information erfolgreich aktualisiert.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Fehler beim Aktualisieren der Information.'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Datenbankfehler beim Aktualisieren der Information: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Löschen einer Information aus der gh_informations-Tabelle
     * 
     * @param int $id ID der zu löschenden Information
     * @return array Erfolg/Fehlermeldung
     */
    public function deleteInformation($id) {
        try {
            // Prüfen, ob es sich um eine System-Information handelt
            $checkStmt = $this->db->prepare("SELECT category FROM gh_informations WHERE id = ?");
            $checkStmt->execute([$id]);
            $category = $checkStmt->fetchColumn();
            
            if ($category === 'system') {
                return [
                    'success' => false,
                    'message' => 'System-Informationen können nicht gelöscht werden.'
                ];
            }
            
            $stmt = $this->db->prepare("DELETE FROM gh_informations WHERE id = ?");
            $success = $stmt->execute([$id]);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Information erfolgreich gelöscht.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Fehler beim Löschen der Information.'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Datenbankfehler beim Löschen der Information.'
            ];
        }
    }
    
    /**
     * Abrufen aller Systeminformationen sortiert nach Erstellungszeit (ID)
     * Speziell für die Admin-Oberfläche, wo Einträge in Erstellungsreihenfolge angezeigt werden sollen
     * 
     * @param string $category Optional: Filter für eine bestimmte Kategorie
     * @return array Array mit vollständigen Datensätzen, sortiert nach ID (älteste zuerst)
     */
    public function getSystemInformationByCreationTime($category = null) {
        try {
            if (empty($category)) {
                $sql = "SELECT id, title, content, category, sort_order 
                       FROM gh_informations 
                       ORDER BY category, id ASC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
            } else {
                $sql = "SELECT id, title, content, category, sort_order 
                       FROM gh_informations 
                       WHERE category = ? 
                       ORDER BY id ASC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$category]);
            }
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Leeres Array als Fallback, falls keine Ergebnisse
            return $results ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
}
?> 