<?php
require_once 'db.php';
require_once 'auth.php';

class User {
    private $conn;
    private $auth;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auth = new Auth();
    }

    public function updateProfile($name, $email) {
        if (!$this->auth->isLoggedIn()) {
            return ["success" => false, "message" => "User must be logged in to update profile"];
        }

        try {
            // Check if email is already taken by another user
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                return ["success" => false, "message" => "Email already exists"];
            }

            $stmt = $this->conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $_SESSION['user_id']]);
            
            // Update session with new name
            $_SESSION['user_name'] = $name;
            
            return ["success" => true, "message" => "Profile updated successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Update failed: " . $e->getMessage()];
        }
    }

    public function changePassword($current_password, $new_password) {
        if (!$this->auth->isLoggedIn()) {
            return ["success" => false, "message" => "User must be logged in to change password"];
        }

        try {
            // Verify current password
            $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (!password_verify($current_password, $user['password'])) {
                return ["success" => false, "message" => "Current password is incorrect"];
            }

            // Update to new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            
            return ["success" => true, "message" => "Password changed successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Password change failed: " . $e->getMessage()];
        }
    }

    public function resetPassword($email) {
        try {
            // In a real application, this would:
            // 1. Generate a secure reset token
            // 2. Store it in the database with an expiration
            // 3. Send an email to the user with a reset link
            // For this example, we'll just return a success message
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                return ["success" => true, "message" => "If the email exists in our system, a password reset link will be sent."];
            }
            
            return ["success" => true, "message" => "If the email exists in our system, a password reset link will be sent."];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Password reset request failed: " . $e->getMessage()];
        }
    }

    public function getProfile() {
        if (!$this->auth->isLoggedIn()) {
            return ["success" => false, "message" => "User must be logged in to view profile"];
        }

        try {
            $stmt = $this->conn->prepare(
                "SELECT id, name, email, created_at FROM users WHERE id = ?"
            );
            $stmt->execute([$_SESSION['user_id']]);
            $profile = $stmt->fetch();
            
            if ($profile) {
                return ["success" => true, "profile" => $profile];
            } else {
                return ["success" => false, "message" => "Profile not found"];
            }
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Failed to fetch profile: " . $e->getMessage()];
        }
    }

    public function deleteAccount() {
        if (!$this->auth->isLoggedIn()) {
            return ["success" => false, "message" => "User must be logged in to delete account"];
        }

        try {
            // Start transaction
            $this->conn->beginTransaction();

            // Delete user's bookings first
            $stmt = $this->conn->prepare("DELETE FROM bookings WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);

            // Then delete the user
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);

            // Commit transaction
            $this->conn->commit();

            // Logout the user
            session_unset();
            session_destroy();

            return ["success" => true, "message" => "Account deleted successfully"];
        } catch (PDOException $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            return ["success" => false, "message" => "Account deletion failed: " . $e->getMessage()];
        }
    }

    public function createUser($name, $email, $password, $is_admin = 0) {
        try {
            // Check if email already exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                return ["success" => false, "message" => "Diese E-Mail-Adresse ist bereits registriert."];
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->conn->prepare(
                "INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$name, $email, $hashed_password, $is_admin]);
            
            return ["success" => true, "message" => "Benutzer erfolgreich erstellt"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Fehler beim Erstellen des Benutzers: " . $e->getMessage()];
        }
    }

    public function getAllUsers() {
        if (!$this->auth->isAdmin()) {
            return ["success" => false, "message" => "Nur Administratoren können alle Benutzer abrufen"];
        }

        try {
            $stmt = $this->conn->prepare(
                "SELECT id, name, email, is_admin, created_at FROM users ORDER BY name ASC"
            );
            $stmt->execute();
            
            return [
                "success" => true,
                "users" => $stmt->fetchAll()
            ];
        } catch (PDOException $e) {
            return [
                "success" => false,
                "message" => "Fehler beim Abrufen der Benutzer: " . $e->getMessage()
            ];
        }
    }

    public function updateUserByAdmin($user_id, $name, $email, $password = null, $is_admin = 0) {
        if (!$this->auth->isAdmin()) {
            return ["success" => false, "message" => "Nur Administratoren können Benutzer bearbeiten"];
        }

        try {
            // Check if email is already taken by another user
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                return ["success" => false, "message" => "Diese E-Mail-Adresse wird bereits verwendet"];
            }

            // Start building the update query
            $updateFields = ["name = ?", "email = ?", "is_admin = ?"];
            $params = [$name, $email, $is_admin];

            // Add password to update if provided
            if ($password !== null) {
                $updateFields[] = "password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }

            // Add user_id to params
            $params[] = $user_id;

            // Build and execute the update query
            $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() === 0) {
                return ["success" => false, "message" => "Benutzer nicht gefunden oder keine Änderungen vorgenommen"];
            }
            
            return ["success" => true, "message" => "Benutzer erfolgreich aktualisiert"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Fehler beim Aktualisieren des Benutzers: " . $e->getMessage()];
        }
    }
}
?> 