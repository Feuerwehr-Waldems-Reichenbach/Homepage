<?php
require_once dirname(__DIR__) . '/includes/Model.php';
require_once dirname(__DIR__) . '/includes/Security.php';

/**
 * User Model
 * 
 * This class handles operations on the fw_users table.
 */
class User extends Model
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $table = 'fw_users';
        $primaryKey = 'id';
        $fillable = [
            'email', 'password', 'first_name', 'last_name', 'phone',
            'is_verified', 'verification_token', 'is_admin', 'is_AktivesMitglied', 'is_Feuerwehr'
        ];
        
        parent::__construct($table, $primaryKey, $fillable);
    }
    
    /**
     * Get a user by email
     * 
     * @param string $email The email to look up
     * @return array|false The user or false if not found
     */
    public function getByEmail($email)
    {
        $users = $this->getByColumn('email', $email);
        
        if (empty($users)) {
            return false;
        }
        
        return $users[0];
    }
    
    /**
     * Create a new user
     * 
     * @param array $data The user data
     * @param bool $verifyEmail Whether to require email verification
     * @return int|false The ID of the new user or false on failure
     */
    public function createUser($data, $verifyEmail = true)
    {
        // Check if email already exists
        if ($this->getByEmail($data['email'])) {
            return false;
        }
        
        // Hash password
        $data['password'] = Security::hashPassword($data['password']);
        
        // Set default values
        $data['is_verified'] = $verifyEmail ? 0 : 1;
        $data['verification_token'] = $verifyEmail ? Security::generateToken() : null;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Convert checkbox values to boolean
        $data['is_admin'] = isset($data['is_admin']) && $data['is_admin'] ? 1 : 0;
        $data['is_AktivesMitglied'] = isset($data['is_AktivesMitglied']) && $data['is_AktivesMitglied'] ? 1 : 0;
        $data['is_Feuerwehr'] = isset($data['is_Feuerwehr']) && $data['is_Feuerwehr'] ? 1 : 0;
        
        return $this->create($data);
    }
    
    /**
     * Update a user
     * 
     * @param int $id The user ID
     * @param array $data The user data
     * @return bool Whether the update was successful
     */
    public function updateUser($id, $data)
    {
        // Check if email is already taken by another user
        if (isset($data['email'])) {
            $existingUser = $this->getByEmail($data['email']);
            
            if ($existingUser && $existingUser['id'] != $id) {
                return false;
            }
        }
        
        // Hash password if set
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Security::hashPassword($data['password']);
        } else {
            // Remove password from data if empty
            unset($data['password']);
        }
        
        // Update timestamp
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Convert checkbox values to boolean
        if (isset($data['is_admin'])) {
            $data['is_admin'] = $data['is_admin'] ? 1 : 0;
        }
        
        if (isset($data['is_AktivesMitglied'])) {
            $data['is_AktivesMitglied'] = $data['is_AktivesMitglied'] ? 1 : 0;
        }
        
        if (isset($data['is_Feuerwehr'])) {
            $data['is_Feuerwehr'] = $data['is_Feuerwehr'] ? 1 : 0;
        }
        
        if (isset($data['is_verified'])) {
            $data['is_verified'] = $data['is_verified'] ? 1 : 0;
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Change a user's password
     * 
     * @param int $id The user ID
     * @param string $password The new password
     * @return bool Whether the change was successful
     */
    public function changePassword($id, $password)
    {
        $data = [
            'password' => Security::hashPassword($password),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($id, $data);
    }
    
    /**
     * Verify a user's email
     * 
     * @param string $token The verification token
     * @return bool Whether the verification was successful
     */
    public function verifyEmail($token)
    {
        // Find user by token
        $sql = "SELECT id FROM {$this->table} WHERE verification_token = :token";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        // Update user
        $data = [
            'is_verified' => 1,
            'verification_token' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($user['id'], $data);
    }
    
    /**
     * Create a password reset token
     * 
     * @param string $email The user's email
     * @return string|false The reset token or false on failure
     */
    public function createPasswordResetToken($email)
    {
        $user = $this->getByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        // Generate token
        $token = Security::generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Delete any existing tokens
        $sql = "DELETE FROM fw_password_reset WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user['id']);
        $stmt->execute();
        
        // Create new token
        $sql = "
            INSERT INTO fw_password_reset 
            (user_id, token, expires_at, created_at) 
            VALUES (:user_id, :token, :expires_at, NOW())
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user['id']);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expiresAt);
        
        if (!$stmt->execute()) {
            return false;
        }
        
        return $token;
    }
    
    /**
     * Check if a password reset token is valid
     * 
     * @param string $token The token to check
     * @return array|false The user ID or false if the token is invalid
     */
    public function checkPasswordResetToken($token)
    {
        $sql = "
            SELECT user_id 
            FROM fw_password_reset 
            WHERE token = :token 
            AND expires_at > NOW()
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Reset a user's password
     * 
     * @param string $token The reset token
     * @param string $password The new password
     * @return bool Whether the reset was successful
     */
    public function resetPassword($token, $password)
    {
        $tokenData = $this->checkPasswordResetToken($token);
        
        if (!$tokenData) {
            return false;
        }
        
        // Change password
        $result = $this->changePassword($tokenData['user_id'], $password);
        
        if ($result) {
            // Delete token
            $sql = "DELETE FROM fw_password_reset WHERE token = :token";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
        }
        
        return $result;
    }
    
    /**
     * Get all admins
     * 
     * @return array The admin users
     */
    public function getAdmins()
    {
        return $this->getByColumn('is_admin', 1);
    }
    
    /**
     * Clean up expired password reset tokens
     * 
     * @return bool Whether the cleanup was successful
     */
    public function cleanupExpiredTokens()
    {
        $sql = "DELETE FROM fw_password_reset WHERE expires_at < NOW()";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute();
    }
    
    /**
     * Attempt to log in a user
     * 
     * @param string $email The email
     * @param string $password The password
     * @return array|false The user data if login is successful, false otherwise
     */
    public function login($email, $password)
    {
        // Get user by email
        $user = $this->getByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        // Check password
        if (!Security::verifyPassword($password, $user['password'])) {
            return false;
        }
        
        // Check if user is verified
        if (!$user['is_verified']) {
            return false;
        }
        
        return $user;
    }

    /**
     * Verify user password
     *
     * @param int $id User ID
     * @param string $password Plain text password
     * @return bool
     */
    public function verifyPassword($id, $password) {
        $sql = "SELECT password FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return false;
        }
        
        return password_verify($password, $result['password']);
    }
} 