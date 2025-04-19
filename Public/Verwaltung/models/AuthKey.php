<?php
require_once dirname(__DIR__) . '/includes/Model.php';

/**
 * Authentication Key Model
 * 
 * This class handles operations on the authentifizierungsschluessel table.
 */
class AuthKey extends Model
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $table = 'authentifizierungsschluessel';
        $primaryKey = 'id';
        $fillable = ['Bezeichnung', 'auth_key', 'active'];
        
        parent::__construct($table, $primaryKey, $fillable);
    }
    
    /**
     * Generate a new authentication key
     * 
     * @param int $length The length of the key
     * @return string The generated key
     */
    public function generateKey($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Create a new authentication key
     * 
     * @param string $bezeichnung The key description
     * @param bool $active Whether the key is active
     * @param string|null $key The key to use (or null to generate one)
     * @return int|false The ID of the new key or false on failure
     */
    public function createKey($bezeichnung, $active = false, $key = null)
    {
        $key = $key ?? $this->generateKey();
        
        $data = [
            'Bezeichnung' => $bezeichnung,
            'auth_key' => $key,
            'active' => $active ? 1 : 0
        ];
        
        return $this->create($data);
    }
    
    /**
     * Activate an authentication key
     * 
     * @param int $id The key ID
     * @return bool Whether the activation was successful
     */
    public function activateKey($id)
    {
        return $this->update($id, ['active' => 1]);
    }
    
    /**
     * Deactivate an authentication key
     * 
     * @param int $id The key ID
     * @return bool Whether the deactivation was successful
     */
    public function deactivateKey($id)
    {
        return $this->update($id, ['active' => 0]);
    }
    
    /**
     * Check if a key is valid
     * 
     * @param string $key The key to check
     * @return bool Whether the key is valid
     */
    public function isValidKey($key)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE auth_key = :key AND active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':key', $key);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
    
    /**
     * Get all active keys
     * 
     * @return array The active keys
     */
    public function getActiveKeys()
    {
        return $this->getByColumn('active', 1);
    }
    
    /**
     * Get all inactive keys
     * 
     * @return array The inactive keys
     */
    public function getInactiveKeys()
    {
        return $this->getByColumn('active', 0);
    }
} 