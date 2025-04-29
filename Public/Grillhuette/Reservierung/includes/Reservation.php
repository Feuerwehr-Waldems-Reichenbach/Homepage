<?php
class Reservation
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    /**
     * Retrieves pricing information from the gh_informations table
     * 
     * @param int $userId ID of the user to check for special pricing
     * @return array Pricing information including base price, deposit amount, and calculated rate for the user
     */
    public function getPriceInformation($userId = null)
    {
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
                    $isFeuerwehr = (bool) $userData['is_Feuerwehr'];
                    $isAktivesMitglied = (bool) $userData['is_AktivesMitglied'];
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
    /**
     * Creates a new reservation
     * 
     * @param int $userId User ID
     * @param string $startDatetime Start date and time
     * @param string $endDatetime End date and time
     * @param string|null $userMessage Optional user message
     * @param int $receiptRequested Whether a receipt is requested (1) or not (0)
     * @return array Result with success flag and message
     */
    public function create(
        $userId,
        $startDatetime,
        $endDatetime,
        $userMessage = null,
        $receiptRequested = 0,
        $isPublic = 0,
        $eventName = null,
        $displayStartDate = null,
        $displayEndDate = null
    ) {
        // Zuweisung der URL-Variablen
        $myReservationsUrl = buildUrl(getRelativePath('Benutzer/Meine-Reservierungen'));
        $adminReservationsUrl = buildUrl(getRelativePath('Admin/Reservierungsverwaltung'));
        try {
            // Überprüfen, ob der Zeitraum verfügbar ist
            if (!$this->isTimeSlotAvailable($startDatetime, $endDatetime)) {
                return [
                    'success' => false,
                    'message' => 'Der gewählte Zeitraum ist nicht verfügbar. Bitte wählen Sie einen anderen Zeitraum.'
                ];
            }
            // Standard-Schlüsselübergabezeiten berechnen
            $keyTimes = $this->calculateDefaultKeyHandoverTimes($startDatetime, $endDatetime);
            $keyHandoverDatetime = $keyTimes['key_handover'];
            $keyReturnDatetime = $keyTimes['key_return'];
            // Anzahl der Tage berechnen
            $daysCount = $this->calculateReservationDays($startDatetime, $endDatetime);
            // Standardpreise berechnen
            $prices = $this->calculateDefaultCosts($daysCount, $userId);
            // SQL-Query für die Erstellung einer neuen Reservierung
            $sql = "INSERT INTO gh_reservations 
                    (user_id, start_datetime, end_datetime, key_handover_datetime, key_return_datetime, 
                     user_message, days_count, base_price, total_price, deposit_amount, receipt_requested, 
                     is_public, event_name, display_event_name_on_calendar_start_date, display_event_name_on_calendar_end_date) 
                    VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $userId,
                $startDatetime,
                $endDatetime,
                $keyHandoverDatetime,
                $keyReturnDatetime,
                $userMessage,
                $daysCount,
                $prices['base_price'],
                $prices['total_price'],
                $prices['deposit_amount'],
                $receiptRequested,
                $isPublic,
                $eventName,
                $displayStartDate,
                $displayEndDate
            ]);
            $reservationId = $this->db->lastInsertId();
            // Überprüfung, ob die Reservierung erfolgreich erstellt wurde
            if ($reservationId) {
                // E-Mail an den Benutzer senden
                $user = $this->getUserById($userId);
                if ($user) {
                    // E-Mail an den Benutzer senden
                    $this->sendUserReservationConfirmation($user, $startDatetime, $endDatetime, $myReservationsUrl);
                    // E-Mail an die Admins senden
                    $this->sendAdminReservationNotification($user, $startDatetime, $endDatetime, $adminReservationsUrl, $receiptRequested);
                }
                return [
                    'success' => true,
                    'message' => 'Ihre Reservierung wurde erfolgreich erstellt und muss nun vom Administrator bestätigt werden.',
                    'reservation_id' => $reservationId
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Es ist ein Fehler bei der Erstellung der Reservierung aufgetreten. Bitte versuchen Sie es später erneut.'
                ];
            }
        } catch (Exception $e) {
            error_log('Fehler beim Erstellen der Reservierung: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Es ist ein Datenbankfehler aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    public function getById($id)
    {
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
    public function getByUserId($userId)
    {
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
    public function getAll()
    {
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
    public function getAllByStatus($status)
    {
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
    public function updateStatus($id, $status, $adminMessage = null)
    {
        // Zuweisung der URL-Variablen
        $myReservationsUrl = buildUrl(getRelativePath('Benutzer/Meine-Reservierungen'));
        $adminReservationsUrl = buildUrl(getRelativePath('Admin/Reservierungsverwaltung'));
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
                                Status: <span class="status-badge">' . ucfirst($statusText) . '</span>';
            // Quittung-Information hinzufügen
            if (isset($reservationData['receipt_requested']) && $reservationData['receipt_requested'] == 1) {
                $body .= '<br>Quittung: Ja';
            } else {
                $body .= '<br>Quittung: Nein';
            }
            $body .= '
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
    public function addUserMessage($id, $message)
    {
        // Zuweisung der URL-Variablen
        $myReservationsUrl = buildUrl(getRelativePath('Benutzer/Meine-Reservierungen'));
        $adminReservationsUrl = buildUrl(getRelativePath('Admin/Reservierungsverwaltung'));
        try {
            $stmt = $this->db->prepare("UPDATE gh_reservations SET user_message = ? WHERE id = ?");
            $stmt->execute([$message, $id]);
            // Admin benachrichtigen
            $reservation = $this->getById($id);
            $adminStmt = $this->db->prepare("SELECT email, erhaelt_emails FROM gh_users WHERE is_admin = 1");
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
                    if ($admin['erhaelt_emails'] == 1) {
                        sendEmail($admin['email'], $subject, $body);
                    }
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
    public function addAdminMessage($id, $message)
    {
        // Zuweisung der URL-Variablen
        $myReservationsUrl = buildUrl(getRelativePath('Benutzer/Meine-Reservierungen'));
        $adminReservationsUrl = buildUrl(getRelativePath('Admin/Reservierungsverwaltung'));
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
    /**
     * Creates a reservation directly by an admin (already confirmed)
     * 
     * @param int $userId User ID
     * @param string $startDatetime Start date and time
     * @param string $endDatetime End date and time
     * @param string|null $adminMessage Optional admin message
     * @param int $receiptRequested Whether a receipt is requested (1) or not (0)
     * @return array Result with success flag and message
     */
    public function createByAdmin(
        $userId,
        $startDatetime,
        $endDatetime,
        $adminMessage = null,
        $receiptRequested = 0,
        $isPublic = 0,
        $eventName = null,
        $displayStartDate = null,
        $displayEndDate = null,
        $keyHandoverDatetime = null,
        $keyReturnDatetime = null
    ) {
        // Zuweisung der URL-Variablen
        $myReservationsUrl = buildUrl(getRelativePath('Benutzer/Meine-Reservierungen'));
        $adminReservationsUrl = buildUrl(getRelativePath('Admin/Reservierungsverwaltung'));
        try {
            // Überprüfen, ob der Zeitraum verfügbar ist
            if (!$this->isTimeSlotAvailable($startDatetime, $endDatetime)) {
                return [
                    'success' => false,
                    'message' => 'Der gewählte Zeitraum ist nicht verfügbar. Bitte wählen Sie einen anderen Zeitraum.'
                ];
            }
            // Wenn keine Schlüsselübergabezeiten angegeben wurden, Standard-Zeiten berechnen
            if ($keyHandoverDatetime === null || $keyReturnDatetime === null) {
                $keyTimes = $this->calculateDefaultKeyHandoverTimes($startDatetime, $endDatetime);
                if ($keyHandoverDatetime === null) {
                    $keyHandoverDatetime = $keyTimes['key_handover'];
                }
                if ($keyReturnDatetime === null) {
                    $keyReturnDatetime = $keyTimes['key_return'];
                }
            }
            // Anzahl der Tage und Kosten berechnen
            $daysCount = $this->calculateReservationDays($startDatetime, $endDatetime);
            $prices = $this->calculateDefaultCosts($daysCount, $userId);
            // SQL-Query für die Erstellung einer neuen Reservierung
            $sql = "INSERT INTO gh_reservations 
                    (user_id, start_datetime, end_datetime, key_handover_datetime, key_return_datetime, 
                     admin_message, days_count, base_price, total_price, deposit_amount, receipt_requested, 
                     status, is_public, event_name, display_event_name_on_calendar_start_date, display_event_name_on_calendar_end_date) 
                    VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $userId,
                $startDatetime,
                $endDatetime,
                $keyHandoverDatetime,
                $keyReturnDatetime,
                $adminMessage,
                $daysCount,
                $prices['base_price'],
                $prices['total_price'],
                $prices['deposit_amount'],
                $receiptRequested,
                $isPublic,
                $eventName,
                $displayStartDate,
                $displayEndDate
            ]);
            $reservationId = $this->db->lastInsertId();
            // Überprüfung, ob die Reservierung erfolgreich erstellt wurde
            if ($reservationId) {
                // E-Mail an den Benutzer senden
                $user = $this->getUserById($userId);
                if ($user) {
                    // E-Mail an den Benutzer senden mit Status "Bestätigt"
                    $this->sendUserReservationConfirmation($user, $startDatetime, $endDatetime, $myReservationsUrl, 'confirmed', $keyHandoverDatetime, $keyReturnDatetime);
                }
                return [
                    'success' => true,
                    'message' => 'Die Reservierung wurde erfolgreich erstellt.',
                    'reservation_id' => $reservationId
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Es ist ein Fehler bei der Erstellung der Reservierung aufgetreten. Bitte versuchen Sie es später erneut.'
                ];
            }
        } catch (Exception $e) {
            error_log('Fehler beim Erstellen der Admin-Reservierung: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Es ist ein Datenbankfehler aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    public function cancel($id)
    {
        // Zuweisung der URL-Variablen
        $myReservationsUrl = buildUrl(getRelativePath('Benutzer/Meine-Reservierungen'));
        $adminReservationsUrl = buildUrl(getRelativePath('Admin/Reservierungsverwaltung'));
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
                $adminStmt = $this->db->prepare("SELECT email, erhaelt_emails FROM gh_users WHERE is_admin = 1");
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
                        if ($admin['erhaelt_emails'] == 1) {
                            sendEmail($admin['email'], $subject, $body);
                        }
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
    public function getReservationsForCalendar($monthYear)
    {
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
    /**
     * Überprüft, ob ein Zeitraum für eine Reservierung verfügbar ist
     * 
     * @param string $startDatetime Beginn des zu prüfenden Zeitraums
     * @param string $endDatetime Ende des zu prüfenden Zeitraums
     * @param int|null $excludeReservationId Optionale ID einer Reservierung, die bei der Prüfung ausgeschlossen werden soll
     * @return bool True wenn der Zeitraum verfügbar ist, sonst False
     */
    public function isTimeSlotAvailable($startDatetime, $endDatetime, $excludeReservationId = null)
    {
        try {
            // Basisabfrage
            $sql = "
                SELECT COUNT(*) AS count
                FROM gh_reservations 
                WHERE 
                    status IN ('confirmed', 'pending') AND
                    (
                        (start_datetime <= ? AND end_datetime > ?) OR
                        (start_datetime < ? AND end_datetime >= ?) OR
                        (start_datetime >= ? AND end_datetime <= ?)
                    )
            ";
            $params = [$endDatetime, $startDatetime, $endDatetime, $startDatetime, $startDatetime, $endDatetime];
            // Wenn eine Reservierungs-ID zum Ausschließen angegeben wurde, ergänze die Abfrage
            if ($excludeReservationId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeReservationId;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] == 0;
        } catch (PDOException $e) {
            // Im Fehlerfall lieber vorsichtig sein und "nicht verfügbar" zurückgeben
            return false;
        }
    }
    public function getReservationDayStatus($date)
    {
        // Datum für den ganzen Tag (0:00 bis 23:59)
        $startDate = $date . ' 00:00:00';
        $endDate = $date . ' 23:59:59';
        try {
            // Debug: Log the date being checked
            error_log("Checking reservation status for date: " . $date);
            // Reservierungen für diesen Tag abrufen
            $stmt = $this->db->prepare("
                SELECT id, start_datetime, end_datetime, status, is_public, event_name,
                       display_event_name_on_calendar_start_date, display_event_name_on_calendar_end_date,
                       key_handover_datetime, key_return_datetime
                FROM gh_reservations 
                WHERE 
                    status IN ('confirmed', 'pending') AND
                    (
                        (start_datetime <= ? AND end_datetime > ?) OR
                        (start_datetime >= ? AND start_datetime <= ?) OR
                        (key_handover_datetime IS NOT NULL AND DATE(key_handover_datetime) = ?) OR
                        (key_return_datetime IS NOT NULL AND DATE(key_return_datetime) = ?)
                    )
                ORDER BY start_datetime
            ");
            // Debug: Output the SQL with values
            error_log("SQL Query: " . str_replace(
                ['?', '?', '?', '?', '?', '?'],
                [$endDate, $startDate, $startDate, $endDate, $date, $date],
                $stmt->queryString
            ));
            $stmt->execute([$endDate, $startDate, $startDate, $endDate, $date, $date]);
            $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Debug: Log the number of reservations found
            error_log("Found " . count($reservations) . " reservations for date: " . $date);
            if (empty($reservations)) {
                return 'free'; // Frei
            }
            $hasConfirmed = false;
            $hasPending = false;
            $hasPublicEvent = false;
            $hasKeyHandover = false;
            $hasKeyReturn = false;
            $eventName = null;
            $keyInfo = [];
            $timeRestrictions = [];
            foreach ($reservations as $reservation) {
                // Prüfen auf Schlüsselübergabe/-rückgabe
                if (!empty($reservation['key_handover_datetime']) && $reservation['key_handover_datetime'] !== null) {
                    $keyHandoverDate = date('Y-m-d', strtotime($reservation['key_handover_datetime']));
                    if ($keyHandoverDate == $date) {
                        $hasKeyHandover = true;
                        $keyInfo['handover'] = date('H:i', strtotime($reservation['key_handover_datetime']));
                        // Wenn es eine Schlüsselübergabe gibt, ist der Tag nur bis zur Übergabe verfügbar
                        $timeRestrictions['available_until'] = $keyInfo['handover'];
                    }
                }
                if (!empty($reservation['key_return_datetime']) && $reservation['key_return_datetime'] !== null) {
                    $keyReturnDate = date('Y-m-d', strtotime($reservation['key_return_datetime']));
                    if ($keyReturnDate == $date) {
                        $hasKeyReturn = true;
                        $keyInfo['return'] = date('H:i', strtotime($reservation['key_return_datetime']));
                        // Wenn es eine Schlüsselrückgabe gibt, ist der Tag ab der Rückgabe verfügbar
                        $timeRestrictions['available_from'] = $keyInfo['return'];
                    }
                }
                // Prüfen ob der Tag tatsächlich belegt ist
                $reservationStart = strtotime($reservation['start_datetime']);
                $reservationEnd = strtotime($reservation['end_datetime']);
                $dayStart = strtotime($date . ' 00:00:00');
                $dayEnd = strtotime($date . ' 23:59:59');
                if ($reservationStart <= $dayEnd && $reservationEnd > $dayStart) {
                    if ($reservation['status'] == 'confirmed') {
                        $hasConfirmed = true;
                        // Prüfen, ob es eine öffentliche Reservierung mit Eventanzeige für diesen Tag ist
                        if ($reservation['is_public'] == 1 && !empty($reservation['event_name'])) {
                            $displayStartDate = !empty($reservation['display_event_name_on_calendar_start_date'])
                                ? date('Y-m-d', strtotime($reservation['display_event_name_on_calendar_start_date']))
                                : date('Y-m-d', strtotime($reservation['start_datetime']));
                            $displayEndDate = !empty($reservation['display_event_name_on_calendar_end_date'])
                                ? date('Y-m-d', strtotime($reservation['display_event_name_on_calendar_end_date']))
                                : date('Y-m-d', strtotime($reservation['end_datetime']));
                            // Prüfen, ob das aktuelle Datum im Anzeigebereich liegt
                            if ($date >= $displayStartDate && $date <= $displayEndDate) {
                                $hasPublicEvent = true;
                                $eventName = $reservation['event_name'];
                            }
                        }
                    } else if ($reservation['status'] == 'pending') {
                        $hasPending = true;
                    }
                }
            }
            // Rückgabe mit Event-Informationen, Schlüsselübergabeinformationen und Zeitbeschränkungen
            if ($hasConfirmed) {
                if ($hasPublicEvent) {
                    $response = [
                        'status' => 'public_event',
                        'event_name' => $eventName
                    ];
                    if ($hasKeyHandover || $hasKeyReturn) {
                        $response['key_info'] = $keyInfo;
                    }
                    return $response;
                }
                if ($hasKeyHandover || $hasKeyReturn) {
                    return [
                        'status' => 'booked',
                        'key_info' => $keyInfo
                    ];
                }
                return 'booked'; // Belegt (bestätigt)
            } else if ($hasPending) {
                if ($hasKeyHandover || $hasKeyReturn) {
                    return [
                        'status' => 'pending',
                        'key_info' => $keyInfo
                    ];
                }
                return 'pending'; // Ausstehend (noch nicht bestätigt)
            } else if ($hasKeyHandover || $hasKeyReturn) {
                // Nur Schlüsselübergabe/Rückgabe an diesem Tag - Tag ist reservierbar mit Zeitbeschränkungen
                return [
                    'status' => 'key_handover',
                    'key_info' => $keyInfo,
                    'time_restrictions' => $timeRestrictions
                ];
            }
            return 'free'; // Sollte nicht erreicht werden, aber als Fallback
        } catch (PDOException $e) {
            error_log('Error in getReservationDayStatus: ' . $e->getMessage());
            return 'error'; // Fehlerfall
        }
    }
    public function deleteByUserId($userId)
    {
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
    /**
     * Updates an existing reservation
     * 
     * @param int $id Reservation ID
     * @param int $userId User ID
     * @param string $startDatetime Start date and time
     * @param string $endDatetime End date and time
     * @param string|null $adminMessage Optional admin message
     * @param string $status Status of the reservation
     * @param int $receiptRequested Whether a receipt is requested (1) or not (0)
     * @return array Result with success flag and message
     */
    public function updateReservation(
        $id,
        $userId,
        $startDatetime,
        $endDatetime,
        $adminMessage = null,
        $status = 'pending',
        $receiptRequested = null,
        $isPublic = null,
        $eventName = null,
        $displayStartDate = null,
        $displayEndDate = null,
        $keyHandoverDatetime = null,
        $keyReturnDatetime = null
    ) {
        try {
            // Überprüfen, ob die Reservierung existiert
            $stmt = $this->db->prepare("SELECT id, status, receipt_requested, is_public, event_name, 
                                       display_event_name_on_calendar_start_date, display_event_name_on_calendar_end_date,
                                       key_handover_datetime, key_return_datetime
                                       FROM gh_reservations WHERE id = ?");
            $stmt->execute([$id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$reservation) {
                return [
                    'success' => false,
                    'message' => 'Die angegebene Reservierung wurde nicht gefunden.'
                ];
            }
            // Überprüfen, ob der Zeitraum verfügbar ist (außer für die eigene Reservierung)
            if (!$this->isTimeSlotAvailable($startDatetime, $endDatetime, $id)) {
                return [
                    'success' => false,
                    'message' => 'Der gewählte Zeitraum ist nicht verfügbar. Bitte wählen Sie einen anderen Zeitraum.'
                ];
            }
            // Wenn keine neuen Schlüsselübergabezeiten angegeben wurden, bestehende beibehalten oder Standardwerte berechnen
            if ($keyHandoverDatetime === null) {
                if (!empty($reservation['key_handover_datetime'])) {
                    $keyHandoverDatetime = $reservation['key_handover_datetime'];
                } else {
                    $keyTimes = $this->calculateDefaultKeyHandoverTimes($startDatetime, $endDatetime);
                    $keyHandoverDatetime = $keyTimes['key_handover'];
                }
            }
            if ($keyReturnDatetime === null) {
                if (!empty($reservation['key_return_datetime'])) {
                    $keyReturnDatetime = $reservation['key_return_datetime'];
                } else {
                    $keyTimes = $this->calculateDefaultKeyHandoverTimes($startDatetime, $endDatetime);
                    $keyReturnDatetime = $keyTimes['key_return'];
                }
            }
            // Reservierung aktualisieren
            $sql = "UPDATE gh_reservations SET 
                    user_id = ?, 
                    start_datetime = ?, 
                    end_datetime = ?,
                    key_handover_datetime = ?,
                    key_return_datetime = ?,
                    admin_message = ?, 
                    status = ?";
            $params = [
                $userId,
                $startDatetime,
                $endDatetime,
                $keyHandoverDatetime,
                $keyReturnDatetime,
                $adminMessage,
                $status
            ];
            // Optionale Felder nur hinzufügen, wenn sie nicht null sind
            if ($receiptRequested !== null) {
                $sql .= ", receipt_requested = ?";
                $params[] = $receiptRequested;
            }
            if ($isPublic !== null) {
                $sql .= ", is_public = ?";
                $params[] = $isPublic;
                // Wenn öffentlich ist, auch die Veranstaltungsdaten aktualisieren
                if ($isPublic) {
                    $sql .= ", event_name = ?, 
                              display_event_name_on_calendar_start_date = ?, 
                              display_event_name_on_calendar_end_date = ?";
                    $params[] = $eventName;
                    $params[] = $displayStartDate;
                    $params[] = $displayEndDate;
                } else {
                    // Wenn nicht öffentlich, die Veranstaltungsdaten auf null setzen
                    $sql .= ", event_name = NULL, 
                              display_event_name_on_calendar_start_date = NULL, 
                              display_event_name_on_calendar_end_date = NULL";
                }
            }
            // Aktualisiere die Anzahl der Tage und die Kosten
            $daysCount = $this->calculateReservationDays($startDatetime, $endDatetime);
            $prices = $this->calculateDefaultCosts($daysCount, $userId);
            $sql .= ", days_count = ?, base_price = ?, total_price = ?, deposit_amount = ?";
            $params[] = $daysCount;
            $params[] = $prices['base_price'];
            $params[] = $prices['total_price'];
            $params[] = $prices['deposit_amount'];
            $sql .= " WHERE id = ?";
            $params[] = $id;
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            if ($result) {
                // Benutzer per E-Mail benachrichtigen
                $user = $this->getUserById($userId);
                if ($user) {
                    // URL für die Benutzers Reservierungen
                    $myReservationsUrl = buildUrl(getRelativePath('Benutzer/Meine-Reservierungen'));
                    // Prüfen, ob es eine Statusänderung gab
                    $statusChanged = $reservation['status'] !== $status;
                    // Bei einer Statusänderung zu confirmed oder canceled die spezielle Update-E-Mail senden
                    if ($statusChanged && ($status === 'confirmed' || $status === 'canceled')) {
                        // E-Mail mit aktualisiertem Status senden
                        $this->sendUserReservationUpdate($user, $startDatetime, $endDatetime, $myReservationsUrl, $status, $adminMessage, $keyHandoverDatetime, $keyReturnDatetime);
                    }
                    // Bei allen anderen Änderungen (wenn durch Admin ausgeführt und nicht der Benutzer selbst)
                    else if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && $userId != $_SESSION['user_id']) {
                        // E-Mail über allgemeine Änderungen senden
                        $this->sendReservationModifiedEmail($user, $id, $startDatetime, $endDatetime, $myReservationsUrl, $status, $adminMessage, $keyHandoverDatetime, $keyReturnDatetime);
                    }
                }
                return [
                    'success' => true,
                    'message' => 'Die Reservierung wurde erfolgreich aktualisiert.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Die Reservierung konnte nicht aktualisiert werden. Bitte versuchen Sie es später erneut.'
                ];
            }
        } catch (Exception $e) {
            error_log('Fehler beim Aktualisieren der Reservierung: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Es ist ein Datenbankfehler aufgetreten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    public function deleteReservation($id)
    {
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
            $myReservationsUrl = buildUrl(getRelativePath('Benutzer/Meine-Reservierungen'));
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
    public function getSystemInformation($keys = [], $category = null)
    {
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
    public function getAllSystemInformationRecords($category = null)
    {
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
    public function addInformation($title, $content, $category, $sortOrder = 10)
    {
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
    public function updateInformation($id, $content, $category = null, $sortOrder = null)
    {
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
    public function deleteInformation($id)
    {
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
    public function getSystemInformationByCreationTime($category = null)
    {
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
    /**
     * Updates only the public event details of a reservation
     * This allows users to modify their public event name and display dates without
     * changing the core reservation details
     * 
     * @param int $id Reservation ID
     * @param string $eventName Name of the public event
     * @param string $displayStartDate Start date for displaying the event on the calendar
     * @param string $displayEndDate End date for displaying the event on the calendar
     * @return array Result with success flag and message
     */
    public function updatePublicEvent($id, $eventName, $displayStartDate, $displayEndDate)
    {
        try {
            // Überprüfen, ob die Reservierung existiert und dem aktuellen Benutzer gehört
            $stmt = $this->db->prepare("SELECT id, user_id, start_datetime, end_datetime, status, is_public 
                                      FROM gh_reservations WHERE id = ?");
            $stmt->execute([$id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$reservation) {
                return [
                    'success' => false,
                    'message' => 'Reservierung nicht gefunden.'
                ];
            }
            // Sicherstellen, dass die Reservierung dem aktuellen Benutzer gehört
            if ($reservation['user_id'] != $_SESSION['user_id']) {
                return [
                    'success' => false,
                    'message' => 'Sie sind nicht berechtigt, diese Reservierung zu bearbeiten.'
                ];
            }
            // Sicherstellen, dass es sich um eine öffentliche Reservierung handelt
            if (!$reservation['is_public']) {
                return [
                    'success' => false,
                    'message' => 'Nur öffentliche Reservierungen können auf diese Weise bearbeitet werden.'
                ];
            }
            // Prüfen ob die Anzeigedaten innerhalb des Reservierungszeitraums liegen
            $resStartDate = date('Y-m-d', strtotime($reservation['start_datetime']));
            $resEndDate = date('Y-m-d', strtotime($reservation['end_datetime']));
            if (
                strtotime($displayStartDate) < strtotime($resStartDate) ||
                strtotime($displayEndDate) > strtotime($resEndDate)
            ) {
                return [
                    'success' => false,
                    'message' => 'Die Anzeigedaten müssen innerhalb des Reservierungszeitraums liegen.'
                ];
            }
            // Nur die Veranstaltungsdaten aktualisieren
            $stmt = $this->db->prepare("
                UPDATE gh_reservations 
                SET event_name = ?,
                    display_event_name_on_calendar_start_date = ?, 
                    display_event_name_on_calendar_end_date = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $eventName,
                $displayStartDate,
                $displayEndDate,
                $id
            ]);
            return [
                'success' => true,
                'message' => 'Die Veranstaltungsdaten wurden erfolgreich aktualisiert.'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim Aktualisieren der Veranstaltungsdaten. Bitte versuchen Sie es später erneut.'
            ];
        }
    }
    /**
     * Berechnet die Standard-Zeiten für die Schlüsselübergabe und -rückgabe
     * Berücksichtigt dabei bestehende Reservierungen
     * 
     * @param string $startDatetime Beginn der Reservierung
     * @param string $endDatetime Ende der Reservierung
     * @return array Schlüsselübergabe- und Rückgabezeiten
     */
    private function calculateDefaultKeyHandoverTimes($startDatetime, $endDatetime)
    {
        try {
            // Startdatum in DateTime umwandeln
            $startDate = new DateTime($startDatetime);
            $endDate = new DateTime($endDatetime);
            // Standard-Zeiten setzen
            $keyHandover = clone $startDate;
            $keyHandover->modify('-1 day');
            $keyHandover->setTime(16, 0, 0);
            $keyReturn = clone $endDate;
            $keyReturn->modify('+1 day');
            $keyReturn->setTime(12, 0, 0);
            // Prüfe auf nachfolgende Reservierungen
            $stmt = $this->db->prepare("
                SELECT start_datetime, key_handover_datetime
                FROM gh_reservations 
                WHERE status IN ('confirmed', 'pending')
                AND start_datetime > ?
                ORDER BY start_datetime ASC
                LIMIT 1
            ");
            $stmt->execute([$endDatetime]);
            $nextReservation = $stmt->fetch(PDO::FETCH_ASSOC);
            // Prüfe auf vorherige Reservierungen
            $stmt = $this->db->prepare("
                SELECT end_datetime, key_return_datetime
                FROM gh_reservations 
                WHERE status IN ('confirmed', 'pending')
                AND end_datetime < ?
                ORDER BY end_datetime DESC
                LIMIT 1
            ");
            $stmt->execute([$startDatetime]);
            $prevReservation = $stmt->fetch(PDO::FETCH_ASSOC);
            // Wenn es eine nachfolgende Reservierung gibt
            if ($nextReservation) {
                $nextKeyHandover = new DateTime($nextReservation['key_handover_datetime']);
                // Wenn die Standard-Rückgabezeit nach der Schlüsselübergabe der nächsten Reservierung liegt
                if ($keyReturn > $nextKeyHandover) {
                    // Setze die Rückgabezeit auf die exakte Zeit der nächsten Schlüsselübergabe
                    $keyReturn = clone $nextKeyHandover;
                }
            }
            // Wenn es eine vorherige Reservierung gibt
            if ($prevReservation) {
                $prevKeyReturn = new DateTime($prevReservation['key_return_datetime']);
                // Wenn die Standard-Übergabezeit vor der Schlüsselrückgabe der vorherigen Reservierung liegt
                if ($keyHandover < $prevKeyReturn) {
                    // Setze die Übergabezeit auf die exakte Zeit der vorherigen Schlüsselrückgabe
                    $keyHandover = clone $prevKeyReturn;
                }
            }
            return [
                'key_handover' => $keyHandover->format('Y-m-d H:i:s'),
                'key_return' => $keyReturn->format('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log('Fehler bei der Berechnung der Schlüsselübergabezeiten: ' . $e->getMessage());
            // Fallback zu Standard-Zeiten im Fehlerfall
            $keyHandover = clone $startDate;
            $keyHandover->modify('-1 day');
            $keyHandover->setTime(16, 0, 0);
            $keyReturn = clone $endDate;
            $keyReturn->modify('+1 day');
            $keyReturn->setTime(12, 0, 0);
            return [
                'key_handover' => $keyHandover->format('Y-m-d H:i:s'),
                'key_return' => $keyReturn->format('Y-m-d H:i:s')
            ];
        }
    }
    /**
     * Berechnet die Anzahl der Tage einer Reservierung
     * 
     * @param string $startDatetime Beginn der Reservierung
     * @param string $endDatetime Ende der Reservierung
     * @return int Anzahl der Tage
     */
    private function calculateReservationDays($startDatetime, $endDatetime)
    {
        $startDate = new DateTime($startDatetime);
        $endDate = new DateTime($endDatetime);
        // Extrahiere nur die Datumsteile, ignoriere die Uhrzeiten
        $startDate->setTime(0, 0, 0);
        $endDate->setTime(0, 0, 0);
        // Berechne die Differenz in Tagen
        $interval = $startDate->diff($endDate);
        $diffDays = $interval->days;
        // Füge einen Tag hinzu, da der Enddatum Teil der Buchung ist
        $diffDays += 1;
        return max(1, $diffDays);
    }
    /**
     * Berechnet die Standardkosten basierend auf der Anzahl der Tage
     * 
     * @param int $daysCount Anzahl der Tage
     * @param int|null $userId ID des Benutzers für spezifische Preise
     * @return array Array mit base_price, total_price und deposit_amount
     */
    private function calculateDefaultCosts($daysCount, $userId = null)
    {
        // Holen der Preisinfos unter Berücksichtigung des Benutzers (falls vorhanden)
        $priceInfo = $this->getPriceInformation($userId);
        
        // Benutze den spezifischen Preis des Benutzers oder den Standardpreis
        $basePrice = $priceInfo['user_rate'];
        $totalPrice = $basePrice * $daysCount;
        $depositAmount = $priceInfo['deposit_amount'];
        
        return [
            'base_price' => $basePrice,
            'total_price' => $totalPrice,
            'deposit_amount' => $depositAmount
        ];
    }
    /**
     * Hilfsfunktion zum Abrufen eines Benutzers nach ID
     * 
     * @param int $userId Benutzer-ID
     * @return array|false Benutzerdaten oder false, wenn nicht gefunden
     */
    private function getUserById($userId)
    {
        $stmt = $this->db->prepare("SELECT id, email, first_name, last_name FROM gh_users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    /**
     * Hilfsfunktion zum Abrufen des spezifischen Tarifs für einen Benutzer
     * 
     * @param int $userId Benutzer-ID
     * @return float Der Tagestarif für den Benutzer
     */
    private function getUserRate($userId)
    {
        $priceInfo = $this->getPriceInformation($userId);
        return $priceInfo['user_rate'];
    }
    /**
     * Sendet eine E-Mail-Bestätigung an den Benutzer
     * 
     * @param array $user Benutzerdaten (mit email, first_name, last_name)
     * @param string $startDatetime Beginn der Reservierung
     * @param string $endDatetime Ende der Reservierung
     * @param string $myReservationsUrl URL zur Reservierungsübersicht des Benutzers
     * @param string $status Status der Reservierung (default: 'pending')
     * @param string $keyHandoverDatetime Zeitpunkt der Schlüsselübergabe
     * @param string $keyReturnDatetime Zeitpunkt der Schlüsselrückgabe
     * @return void
     */
    private function sendUserReservationConfirmation($user, $startDatetime, $endDatetime, $myReservationsUrl, $status = 'pending', $keyHandoverDatetime = null, $keyReturnDatetime = null)
    {
        // Formatierte Werte für die E-Mail
        $formattedStartDate = date('d.m.Y H:i', strtotime($startDatetime));
        $formattedEndDate = date('d.m.Y H:i', strtotime($endDatetime));
        // Formatierung der Schlüsselübergabezeiten, falls vorhanden
        $keyHandoverText = '';
        $keyReturnText = '';
        if ($keyHandoverDatetime) {
            $keyHandoverText = date('d.m.Y H:i', strtotime($keyHandoverDatetime));
        }
        if ($keyReturnDatetime) {
            $keyReturnText = date('d.m.Y H:i', strtotime($keyReturnDatetime));
        }
        // Berechnung der Kosten
        $startDate = new DateTime($startDatetime);
        $endDate = new DateTime($endDatetime);
        $interval = $startDate->diff($endDate);
        $days = $interval->days + 1; // +1 weil der erste Tag mitgezählt wird
        // Abrufen des Benutzertarifs
        $userRatePerDay = $this->getUserRate($user['id']);
        $totalCost = $days * $userRatePerDay;
        // Formatierung der Kosten mit deutschem Format
        $formattedRate = number_format($userRatePerDay, 2, ',', '.');
        $formattedTotalCost = number_format($totalCost, 2, ',', '.');
        // Status-abhängige Werte
        $statusText = $status == 'confirmed' ? 'bestätigt' : ($status == 'pending' ? 'ausstehend' : 'abgelehnt');
        $statusColor = $status == 'confirmed' ? '#28a745' : ($status == 'pending' ? '#ffc107' : '#dc3545');
        $headerText = $status == 'confirmed' ? 'Reservierung bestätigt' : 'Neue Reservierung';
        $subject = 'Ihre Reservierung für die Grillhütte Waldems Reichenbach';
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
                    .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                    .cost-info { margin-top: 15px; border-top: 1px solid #ddd; padding-top: 15px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>' . $headerText . '</h2>
                    </div>
                    <div class="content">
                        <h3>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h3>
                        <p>vielen Dank für Ihre Reservierung der Grillhütte Waldems Reichenbach.</p>
                        <div class="info-box">
                            <strong>Ihre Reservierungsdetails:</strong><br>
                            Status: <span class="status-badge">' . $statusText . '</span><br>
                            Von: ' . $formattedStartDate . '<br>
                            Bis: ' . $formattedEndDate . '<br>';
        if ($keyHandoverText && $keyReturnText) {
            $body .= '
                            <br><strong>Schlüsselübergabe:</strong><br>
                            Abholung: ' . $keyHandoverText . '<br>
                            Rückgabe: ' . $keyReturnText . '<br>';
        }
        // Quittung-Information hinzufügen
        if (isset($user['receipt_requested']) && $user['receipt_requested'] == 1) {
            $body .= 'Quittung: Ja<br>';
        } else {
            $body .= 'Quittung: Nein<br>';
        }
        // Kosteninformationen hinzufügen
        $body .= '
                            <div class="cost-info">
                                <strong>Kosteninformationen:</strong><br>
                                Anzahl Tage: ' . $days . '<br>
                                Preis pro Tag: ' . $formattedRate . ' €<br>
                                <strong>Gesamtkosten: ' . $formattedTotalCost . ' €</strong>
                            </div>';
        $body .= '
                        </div>';
        if ($status == 'pending') {
            $body .= '
                        <p>Ihre Reservierung wurde erfolgreich erstellt und wird nun vom Administrator geprüft. 
                        Sie erhalten eine Benachrichtigung, sobald Ihre Reservierung bestätigt wurde.</p>';
        } else if ($status == 'confirmed') {
            $body .= '
                        <p>Ihre Reservierung wurde bestätigt. Wir freuen uns auf Ihren Besuch!</p>';
        }
        $body .= '
                        <p>Sie können den Status Ihrer Reservierung jederzeit in Ihrem Konto einsehen:</p>
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
    private function sendAdminReservationNotification($user, $startDatetime, $endDatetime, $adminReservationsUrl, $receiptRequested)
    {
        // Admin-E-Mails abrufen
        $stmt = $this->db->prepare("SELECT email, erhaelt_emails FROM gh_users WHERE is_admin = 1");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($admins)) {
            return; // Keine Admins gefunden
        }
        // Formatierte Werte für die E-Mail
        $formattedStartDate = date('d.m.Y H:i', strtotime($startDatetime));
        $formattedEndDate = date('d.m.Y H:i', strtotime($endDatetime));
        $subject = 'Neue Reservierungsanfrage für die Grillhütte';
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
                        <h2>Neue Reservierungsanfrage</h2>
                    </div>
                    <div class="content">
                        <p>Es liegt eine neue Reservierungsanfrage für die Grillhütte vor.</p>
                        <div class="info-box">
                            <strong>Reservierungsdetails:</strong><br>
                            Benutzer: ' . $user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')';
        // Telefon hinzufügen, falls vorhanden
        if (isset($user['phone']) && !empty($user['phone'])) {
            $body .= ' | Telefon: ' . $user['phone'];
        }
        $body .= '<br>
                            Von: ' . $formattedStartDate . '<br>
                            Bis: ' . $formattedEndDate . '<br>';
        // Quittung-Information hinzufügen
        if (isset($receiptRequested) && $receiptRequested == 1) {
            $body .= 'Quittung: Ja<br>';
        } else {
            $body .= 'Quittung: Nein<br>';
        }
        $body .= '
                        </div>
                        <p>Bitte bestätigen oder lehnen Sie diese Anfrage ab:</p>
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
            if ($admin['erhaelt_emails'] == 1) {
                sendEmail($admin['email'], $subject, $body);
            }
        }
    }
    private function sendUserReservationUpdate($user, $startDatetime, $endDatetime, $myReservationsUrl, $status, $adminMessage = null, $keyHandoverDatetime = null, $keyReturnDatetime = null)
    {
        // Formatierte Werte für die E-Mail
        $formattedStartDate = date('d.m.Y H:i', strtotime($startDatetime));
        $formattedEndDate = date('d.m.Y H:i', strtotime($endDatetime));
        // Formatierung der Schlüsselübergabezeiten, falls vorhanden
        $keyHandoverText = '';
        $keyReturnText = '';
        if ($keyHandoverDatetime) {
            $keyHandoverText = date('d.m.Y H:i', strtotime($keyHandoverDatetime));
        }
        if ($keyReturnDatetime) {
            $keyReturnText = date('d.m.Y H:i', strtotime($keyReturnDatetime));
        }
        // Berechnung der Kosten
        $startDate = new DateTime($startDatetime);
        $endDate = new DateTime($endDatetime);
        $interval = $startDate->diff($endDate);
        $days = $interval->days + 1; // +1 weil der erste Tag mitgezählt wird
        // Abrufen des Benutzertarifs
        $userRatePerDay = $this->getUserRate($user['id']);
        $totalCost = $days * $userRatePerDay;
        // Formatierung der Kosten mit deutschem Format
        $formattedRate = number_format($userRatePerDay, 2, ',', '.');
        $formattedTotalCost = number_format($totalCost, 2, ',', '.');
        // Status-abhängige Werte
        $statusText = $status == 'confirmed' ? 'bestätigt' : ($status == 'pending' ? 'ausstehend' : 'abgelehnt');
        $statusColor = $status == 'confirmed' ? '#28a745' : ($status == 'pending' ? '#ffc107' : '#dc3545');
        $headerText = 'Status Ihrer Reservierung | ' . $statusText;
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
                    .status-badge { display: inline-block; padding: 5px 15px; background-color: ' . $statusColor . '; color: white; border-radius: 15px; }
                    .message-box { background-color: #f8f9fa; border-left: 4px solid ' . $statusColor . '; padding: 15px; margin: 20px 0; }
                    .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                    .cost-info { margin-top: 15px; border-top: 1px solid #ddd; padding-top: 15px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>' . $headerText . '</h2>
                    </div>
                    <div class="content">
                        <h3>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h3>
                        <p>der Status Ihrer Reservierung wurde aktualisiert.</p>
                        <div class="info-box">
                            <strong>Ihre Reservierungsdetails:</strong><br>
                            Status: <span class="status-badge">' . $statusText . '</span><br>
                            Von: ' . $formattedStartDate . '<br>
                            Bis: ' . $formattedEndDate . '<br>';
        if ($keyHandoverText && $keyReturnText) {
            $body .= '
                            <br><strong>Schlüsselübergabe:</strong><br>
                            Abholung: ' . $keyHandoverText . '<br>
                            Rückgabe: ' . $keyReturnText . '<br>';
        }
        // Kosteninformationen hinzufügen
        $body .= '
                            <div class="cost-info">
                                <strong>Kosteninformationen:</strong><br>
                                Anzahl Tage: ' . $days . '<br>
                                Preis pro Tag: ' . $formattedRate . ' €<br>
                                <strong>Gesamtkosten: ' . $formattedTotalCost . ' €</strong>
                            </div>';
        $body .= '
                        </div>';
        if ($adminMessage) {
            $body .= '
                        <div class="message-box">
                            <strong>Nachricht vom Administrator:</strong><br>
                            ' . nl2br(htmlspecialchars($adminMessage)) . '
                        </div>';
        }
        if ($status == 'confirmed') {
            $body .= '
                        <p>Ihre Reservierung wurde bestätigt. Wir freuen uns auf Ihren Besuch!</p>';
        } else if ($status == 'rejected') {
            $body .= '
                        <p>Ihre Reservierung wurde abgelehnt. Bitte beachten Sie die Nachricht des Administrators für weitere Informationen.</p>';
        }
        $body .= '
                        <p>Sie können den Status Ihrer Reservierung jederzeit in Ihrem Konto einsehen:</p>
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
    /**
     * Sendet eine E-Mail an den Benutzer, wenn seine Reservierung vom Administrator bearbeitet wurde
     * 
     * @param array $user Benutzerdaten
     * @param int $reservationId ID der Reservierung
     * @param string $startDatetime Beginn der Reservierung
     * @param string $endDatetime Ende der Reservierung
     * @param string $myReservationsUrl URL zur Reservierungsübersicht des Benutzers
     * @param string $status Status der Reservierung
     * @param string $adminMessage Nachricht vom Administrator
     * @param string $keyHandoverDatetime Zeitpunkt der Schlüsselübergabe
     * @param string $keyReturnDatetime Zeitpunkt der Schlüsselrückgabe
     * @return void
     */
    private function sendReservationModifiedEmail($user, $reservationId, $startDatetime, $endDatetime, $myReservationsUrl, $status, $adminMessage = null, $keyHandoverDatetime = null, $keyReturnDatetime = null)
    {
        // Formatierte Werte für die E-Mail
        $formattedStartDate = date('d.m.Y H:i', strtotime($startDatetime));
        $formattedEndDate = date('d.m.Y H:i', strtotime($endDatetime));
        // Formatierung der Schlüsselübergabezeiten, falls vorhanden
        $keyHandoverText = '';
        $keyReturnText = '';
        if ($keyHandoverDatetime) {
            $keyHandoverText = date('d.m.Y H:i', strtotime($keyHandoverDatetime));
        }
        if ($keyReturnDatetime) {
            $keyReturnText = date('d.m.Y H:i', strtotime($keyReturnDatetime));
        }
        // Status-abhängige Werte
        $statusText = $status == 'confirmed' ? 'bestätigt' : ($status == 'pending' ? 'ausstehend' : 'abgelehnt');
        $statusColor = $status == 'confirmed' ? '#28a745' : ($status == 'pending' ? '#ffc107' : '#dc3545');
        $subject = 'Änderungen an Ihrer Reservierung für die Grillhütte';
        $body = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #0275d8; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background-color: #ffffff; padding: 20px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; }
                    .button { display: inline-block; padding: 10px 20px; background-color: #A72920; color: white !important; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                    .info-box { background-color: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    .status-badge { display: inline-block; padding: 5px 15px; background-color: ' . $statusColor . '; color: white; border-radius: 15px; }
                    .message-box { background-color: #f8f9fa; border-left: 4px solid #0275d8; padding: 15px; margin: 20px 0; }
                    .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>Änderungen an Ihrer Reservierung</h2>
                    </div>
                    <div class="content">
                        <h3>Hallo ' . $user['first_name'] . ' ' . $user['last_name'] . ',</h3>
                        <p>der Administrator hat Änderungen an Ihrer Reservierung für die Grillhütte Waldems Reichenbach vorgenommen.</p>
                        <div class="info-box">
                            <strong>Ihre aktualisierte Reservierung:</strong><br>
                            Status: <span class="status-badge">' . $statusText . '</span><br>
                            Von: ' . $formattedStartDate . '<br>
                            Bis: ' . $formattedEndDate . '<br>';
        if ($keyHandoverText && $keyReturnText) {
            $body .= '
                            <br><strong>Schlüsselübergabe:</strong><br>
                            Abholung: ' . $keyHandoverText . '<br>
                            Rückgabe: ' . $keyReturnText . '<br>';
        }
        // Quittung-Information hinzufügen
        if (isset($user['receipt_requested']) && $user['receipt_requested'] == 1) {
            $body .= 'Quittung: Ja<br>';
        } else {
            $body .= 'Quittung: Nein<br>';
        }
        $body .= '
                        </div>';
        if ($adminMessage) {
            $body .= '
                        <div class="message-box">
                            <strong>Nachricht vom Administrator:</strong><br>
                            ' . nl2br(htmlspecialchars($adminMessage)) . '
                        </div>';
        }
        $body .= '
                        <p>Sie können alle Details Ihrer Reservierung in Ihrem Konto einsehen:</p>
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
        sendEmail($user['email'], $subject, $body);
    }
}
