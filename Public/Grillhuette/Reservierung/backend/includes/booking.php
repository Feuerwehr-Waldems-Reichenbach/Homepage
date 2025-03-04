<?php
require_once 'db.php';
require_once 'auth.php';

class Booking {
    private $conn;
    private $auth;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auth = new Auth();
        
        // Clean up old unconfirmed bookings
        $this->cleanupUnconfirmedBookings();
    }

    private function cleanupUnconfirmedBookings() {
        try {
            $stmt = $this->conn->prepare(
                "DELETE FROM bookings 
                 WHERE status = 'pending' 
                 AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );
            $stmt->execute();
        } catch (PDOException $e) {
            // Log error but don't stop execution
            error_log("Failed to cleanup unconfirmed bookings: " . $e->getMessage());
        }
    }

    public function createBooking($start_date, $end_date, $start_time, $end_time, $message = '') {
        if (!$this->auth->isLoggedIn()) {
            return ["success" => false, "message" => "User must be logged in to create a booking"];
        }

        try {
            // Check for overlapping bookings
            if ($this->hasOverlappingBookings($start_date, $end_date, $start_time, $end_time)) {
                return ["success" => false, "message" => "This time slot is already booked"];
            }

            $stmt = $this->conn->prepare(
                "INSERT INTO bookings (user_id, start_date, end_date, start_time, end_time, message) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            
            $stmt->execute([
                $_SESSION['user_id'],
                $start_date,
                $end_date,
                $start_time,
                $end_time,
                $message
            ]);
            
            return ["success" => true, "message" => "Booking created successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Booking failed: " . $e->getMessage()];
        }
    }

    private function hasOverlappingBookings($start_date, $end_date, $start_time, $end_time) {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) as count FROM bookings 
             WHERE status != 'cancelled'
             AND (
                 (start_date BETWEEN :start_date AND :end_date)
                 OR (end_date BETWEEN :start_date AND :end_date)
                 OR (:start_date BETWEEN start_date AND end_date)
             )
             AND (
                 (TIME(start_time) <= TIME(:end_time) AND TIME(end_time) > TIME(:start_time))
             )"
        );
        
        $stmt->execute([
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':start_time' => $start_time,
            ':end_time' => $end_time
        ]);
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    public function getUserBookings() {
        if (!$this->auth->isLoggedIn()) {
            return ["success" => false, "message" => "User must be logged in to view bookings"];
        }

        try {
            $stmt = $this->conn->prepare(
                "SELECT * FROM bookings WHERE user_id = ? ORDER BY start_date DESC, start_time DESC"
            );
            $stmt->execute([$_SESSION['user_id']]);
            return ["success" => true, "bookings" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Failed to fetch bookings: " . $e->getMessage()];
        }
    }

    public function updateBooking($booking_id, $start_date, $end_date, $start_time, $end_time, $message = '') {
        if (!$this->auth->isLoggedIn()) {
            return ["success" => false, "message" => "User must be logged in to update booking"];
        }

        try {
            // Verify booking belongs to user
            $stmt = $this->conn->prepare("SELECT user_id FROM bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch();

            if (!$booking || $booking['user_id'] != $_SESSION['user_id']) {
                return ["success" => false, "message" => "Unauthorized to update this booking"];
            }

            $stmt = $this->conn->prepare(
                "UPDATE bookings 
                 SET start_date = ?, end_date = ?, start_time = ?, end_time = ?, message = ? 
                 WHERE id = ? AND user_id = ?"
            );
            
            $stmt->execute([
                $start_date,
                $end_date,
                $start_time,
                $end_time,
                $message,
                $booking_id,
                $_SESSION['user_id']
            ]);
            
            return ["success" => true, "message" => "Booking updated successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Update failed: " . $e->getMessage()];
        }
    }

    public function cancelBooking($booking_id) {
        if (!$this->auth->isLoggedIn()) {
            return ["success" => false, "message" => "User must be logged in to cancel booking"];
        }

        try {
            $stmt = $this->conn->prepare(
                "UPDATE bookings SET status = 'cancelled' 
                 WHERE id = ? AND user_id = ? AND status != 'cancelled'"
            );
            
            $stmt->execute([$booking_id, $_SESSION['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                return ["success" => true, "message" => "Booking cancelled successfully"];
            } else {
                return ["success" => false, "message" => "Booking not found or already cancelled"];
            }
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Cancellation failed: " . $e->getMessage()];
        }
    }

    public function getAllBookings() {
        try {
            $stmt = $this->conn->prepare(
                "SELECT b.*, u.name as user_name 
                 FROM bookings b 
                 JOIN users u ON b.user_id = u.id 
                 WHERE b.status != 'cancelled'
                 ORDER BY b.start_date ASC, b.start_time ASC"
            );
            $stmt->execute();
            return ["success" => true, "bookings" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Failed to fetch bookings: " . $e->getMessage()];
        }
    }

    public function getBookingsByDateRange($start_date, $end_date) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT b.*, u.name as user_name 
                 FROM bookings b 
                 JOIN users u ON b.user_id = u.id 
                 WHERE b.status != 'cancelled'
                 AND (
                     (b.start_date BETWEEN :start_date AND :end_date)
                     OR (b.end_date BETWEEN :start_date AND :end_date)
                     OR (:start_date BETWEEN b.start_date AND b.end_date)
                 )
                 ORDER BY b.start_date ASC, b.start_time ASC"
            );
            
            $stmt->execute([
                ':start_date' => $start_date,
                ':end_date' => $end_date
            ]);
            
            return ["success" => true, "bookings" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Failed to fetch bookings: " . $e->getMessage()];
        }
    }

    public function createAdminBooking($user_id, $start_date, $end_date, $start_time, $end_time, $message = '') {
        if (!$this->auth->isAdmin()) {
            return ["success" => false, "message" => "Nur Administratoren können Buchungen für andere Benutzer erstellen"];
        }

        try {
            // Check if user exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            if ($stmt->rowCount() === 0) {
                return ["success" => false, "message" => "Der ausgewählte Benutzer existiert nicht"];
            }

            // Check for overlapping bookings
            if ($this->hasOverlappingBookings($start_date, $end_date, $start_time, $end_time)) {
                return ["success" => false, "message" => "Dieser Zeitraum ist bereits gebucht"];
            }

            $stmt = $this->conn->prepare(
                "INSERT INTO bookings (user_id, start_date, end_date, start_time, end_time, message, status) 
                 VALUES (?, ?, ?, ?, ?, ?, 'confirmed')"
            );
            
            $stmt->execute([
                $user_id,
                $start_date,
                $end_date,
                $start_time,
                $end_time,
                $message
            ]);
            
            return ["success" => true, "message" => "Buchung erfolgreich erstellt"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Buchung fehlgeschlagen: " . $e->getMessage()];
        }
    }
}
?> 