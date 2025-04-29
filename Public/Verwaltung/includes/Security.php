<?php
/**
 * Security class for handling authentication, validation, and security-related functionality
 */
class Security {
    private static $csrfToken;
    
    /**
     * Set security headers to protect against common web vulnerabilities
     */
    public static function setSecurityHeaders() {
        // Set Content Security Policy
        header("Content-Security-Policy: default-src 'self'; connect-src 'self' http://cdn.datatables.net https://cdn.datatables.net; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://code.jquery.com https://cdn.datatables.net https://cdn.ckeditor.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:;");
        
        // Prevent MIME type sniffing
        header("X-Content-Type-Options: nosniff");
        
        // Enable XSS protection in browsers
        header("X-XSS-Protection: 1; mode=block");
        
        // Prevent clickjacking
        header("X-Frame-Options: SAMEORIGIN");
        
        // Strict Transport Security
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        
        // Referrer Policy
        header("Referrer-Policy: same-origin");
    }
    
    /**
     * Initialize and return a CSRF token
     * 
     * @return string The CSRF token
     */
    public static function getCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        self::$csrfToken = $_SESSION['csrf_token'];
        return self::$csrfToken;
    }
    
    /**
     * Validate a CSRF token
     * 
     * @param string $token The token to validate
     * @return bool Whether the token is valid
     */
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitize input data to prevent XSS and other injection attacks
     * 
     * @param string $input The input to sanitize
     * @param bool $allowHtml Whether to allow HTML tags (useful for rich text editors like CKEditor)
     * @return string The sanitized input
     */
    public static function sanitizeInput($input, $allowHtml = false) {
        // Remove whitespace from both ends
        $input = trim($input);
        
        if ($allowHtml) {
            // Remove <p>-Tags if they are not desired
            $input = preg_replace('#</?p[^>]*>#', '', $input);
        
            $allowedTags = '<br><strong><em><ul><ol><li><a><blockquote><h1><h2><h3><h4><h5><h6><img><table><tr><td><th><thead><tbody><caption><div><span><hr>';
            return strip_tags($input, $allowedTags);
        }
        else {
            // Convert special characters to HTML entities
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            
            return $input;
        }
    }
    
    /**
     * Validate email address format
     * 
     * @param string $email The email to validate
     * @return bool Whether the email is valid
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Hash a password using bcrypt
     * 
     * @param string $password The password to hash
     * @return string The hashed password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify a password against a hash
     * 
     * @param string $password The password to verify
     * @param string $hash The hash to verify against
     * @return bool Whether the password is correct
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if a password needs rehashing
     * 
     * @param string $hash The hash to check
     * @return bool Whether the password needs rehashing
     */
    public static function passwordNeedsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Check if a user is locked out due to too many failed login attempts
     * 
     * @param string $email The user email to check
     * @return bool Whether the user is locked out
     */
    public static function isLockedOut($email) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get the number of failed login attempts within the last 15 minutes
            $stmt = $db->prepare("
                SELECT COUNT(*) as attempt_count 
                FROM fw_login_attempts 
                WHERE email = ? 
                AND success = 0 
                AND timestamp > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If there are 5 or more failed attempts, the user is locked out
            return ($result['attempt_count'] >= 5);
        } catch (PDOException $e) {
            // Log the error
            error_log('Error checking lockout status: ' . $e->getMessage());
            
            // Default to not locked out in case of error
            return false;
        }
    }
    
    /**
     * Record a login attempt
     * 
     * @param string $email The email used for the login attempt
     * @param bool $success Whether the attempt was successful
     */
    public static function recordLoginAttempt($email, $success = false) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Insert the login attempt
            $stmt = $db->prepare("
                INSERT INTO fw_login_attempts (email, ip_address, user_agent, success) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $email,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $success ? 1 : 0
            ]);
        } catch (PDOException $e) {
            // Log the error
            error_log('Error recording login attempt: ' . $e->getMessage());
        }
    }
    
    /**
     * Log a security event
     * 
     * @param string $user The user identifier (email or username)
     * @param string $event_type The type of security event
     * @param string $severity The severity of the event (info, warning, error)
     * @param string $description A description of the event
     */
    public static function logSecurityEvent($user, $event_type, $severity, $description) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Insert the security event
            $stmt = $db->prepare("
                INSERT INTO fw_security_log (user_identifier, event_type, severity, ip_address, user_agent, description) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user,
                $event_type,
                $severity,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $description
            ]);
        } catch (PDOException $e) {
            // Log the error
            error_log('Error logging security event: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate a random token for password reset, verification, etc.
     * 
     * @param int $length The length of the token
     * @return string The generated token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Create a password reset token and store it in the database
     * 
     * @param string $email The user's email
     * @return string|false The reset token or false on failure
     */
    public static function createPasswordResetToken($email) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Verify that the user exists
            $userStmt = $db->prepare("SELECT id FROM fw_users WHERE email = ?");
            $userStmt->execute([$email]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false;
            }
            
            // Generate a token
            $token = self::generateToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Remove any existing reset tokens for this user
            $deleteStmt = $db->prepare("DELETE FROM fw_password_reset WHERE user_id = ?");
            $deleteStmt->execute([$user['id']]);
            
            // Store the new reset token
            $stmt = $db->prepare("
                INSERT INTO fw_password_reset (user_id, token, expires_at) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user['id'], $token, $expiresAt]);
            
            return $token;
        } catch (PDOException $e) {
            // Log the error
            error_log('Error creating password reset token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify a password reset token
     * 
     * @param string $token The token to verify
     * @return array|false User data if valid, false otherwise
     */
    public static function verifyPasswordResetToken($token) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get the reset entry and join with user data
            $stmt = $db->prepare("
                SELECT u.id, u.email, u.first_name, u.last_name, pr.expires_at
                FROM fw_password_reset pr
                JOIN fw_users u ON pr.user_id = u.id
                WHERE pr.token = ?
            ");
            $stmt->execute([$token]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if token exists and is not expired
            if (!$result || strtotime($result['expires_at']) < time()) {
                return false;
            }
            
            return $result;
        } catch (PDOException $e) {
            // Log the error
            error_log('Error verifying password reset token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Invalidate a password reset token
     * 
     * @param string $token The token to invalidate
     * @return bool Whether the operation was successful
     */
    public static function invalidatePasswordResetToken($token) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Delete the reset entry
            $stmt = $db->prepare("DELETE FROM fw_password_reset WHERE token = ?");
            $stmt->execute([$token]);
            
            return true;
        } catch (PDOException $e) {
            // Log the error
            error_log('Error invalidating password reset token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get a setting value from the database
     * 
     * @param string $key The setting key
     * @param mixed $default The default value to return if setting not found
     * @return mixed The setting value or default value
     */
    public static function getSetting($key, $default = null) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get the setting value
            $stmt = $db->prepare("SELECT value FROM fw_settings WHERE `key` = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ($result) ? $result['value'] : $default;
        } catch (PDOException $e) {
            // Log the error
            error_log('Error getting setting: ' . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Update a setting value in the database
     * 
     * @param string $key The setting key
     * @param string $value The setting value
     * @return bool Whether the operation was successful
     */
    public static function updateSetting($key, $value) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Check if the setting exists
            $checkStmt = $db->prepare("SELECT id FROM fw_settings WHERE `key` = ?");
            $checkStmt->execute([$key]);
            $setting = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($setting) {
                // Update existing setting
                $stmt = $db->prepare("UPDATE fw_settings SET value = ? WHERE `key` = ?");
                $stmt->execute([$value, $key]);
            } else {
                // Insert new setting
                $stmt = $db->prepare("INSERT INTO fw_settings (`key`, value) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }
            
            return true;
        } catch (PDOException $e) {
            // Log the error
            error_log('Error updating setting: ' . $e->getMessage());
            return false;
        }
    }
} 