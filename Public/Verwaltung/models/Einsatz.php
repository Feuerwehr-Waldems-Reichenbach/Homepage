<?php
require_once dirname(__DIR__) . '/includes/Model.php';

/**
 * Einsatz Model
 * 
 * This class handles operations on the einsatz table.
 */
class Einsatz extends Model
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $table = 'einsatz';
        $primaryKey = 'ID';
        $fillable = ['EinsatzID', 'Anzeigen', 'Datum', 'Endzeit', 'Sachverhalt', 'Stichwort', 'Kategorie', 'Ort', 'Einheit'];
        
        parent::__construct($table, $primaryKey, $fillable);
    }
    
    /**
     * Get all operations with their details
     * 
     * @param string $orderBy The column to order by
     * @param string $order The order direction (ASC or DESC)
     * @return array The operations with their details
     */
    public function getAllWithDetails($orderBy = 'Datum', $order = 'DESC')
    {
        $sql = "
            SELECT e.*, d.einsatz_headline, d.einsatz_text, d.image_path, d.is_public 
            FROM {$this->table} e
            LEFT JOIN einsatz_Details d ON e.ID = d.einsatz_id
            ORDER BY e.{$orderBy} {$order}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get an operation with its details
     * 
     * @param int $id The operation ID
     * @return array|false The operation with its details or false if not found
     */
    public function getWithDetails($id)
    {
        $sql = "
            SELECT e.*, d.einsatz_headline, d.einsatz_text, d.image_path, d.is_public, d.ID as detailID
            FROM {$this->table} e
            LEFT JOIN einsatz_Details d ON e.ID = d.einsatz_id
            WHERE e.ID = :id
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create a new operation with details
     * 
     * @param array $data The operation data
     * @param array $details The details data
     * @return int|false The ID of the new operation or false on failure
     */
    public function createWithDetails($data, $details = null)
    {
        try {
            // Start transaction
            $this->beginTransaction();
            
            // Create operation
            $einsatzId = $this->create($data);
            
            if (!$einsatzId) {
                throw new Exception('Failed to create operation');
            }
            
            // Create details if provided
            if ($details) {
                $details['einsatz_id'] = $einsatzId;
                
                $sql = "
                    INSERT INTO einsatz_Details 
                    (einsatz_id, image_path, einsatz_headline, einsatz_text, is_public) 
                    VALUES (:einsatz_id, :image_path, :einsatz_headline, :einsatz_text, :is_public)
                ";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':einsatz_id', $details['einsatz_id']);
                $stmt->bindParam(':image_path', $details['image_path']);
                $stmt->bindParam(':einsatz_headline', $details['einsatz_headline']);
                $stmt->bindParam(':einsatz_text', $details['einsatz_text']);
                $stmt->bindParam(':is_public', $details['is_public']);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to create operation details');
                }
            }
            
            // Commit transaction
            $this->commit();
            
            return $einsatzId;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->rollback();
            error_log('Error creating operation with details: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an operation with details
     * 
     * @param int $id The operation ID
     * @param array $data The operation data
     * @param array $details The details data
     * @return bool Whether the update was successful
     */
    public function updateWithDetails($id, $data, $details = null)
    {
        try {
            // Start transaction
            $this->beginTransaction();
            
            // Update operation
            if (!$this->update($id, $data)) {
                throw new Exception('Failed to update operation');
            }
            
            // Update or create details if provided
            if ($details) {
                // Check if details exist
                $sql = "SELECT ID FROM einsatz_Details WHERE einsatz_id = :einsatz_id";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':einsatz_id', $id);
                $stmt->execute();
                $detailsExist = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($detailsExist) {
                    // Update existing details
                    $sql = "
                        UPDATE einsatz_Details 
                        SET image_path = :image_path, 
                            einsatz_headline = :einsatz_headline, 
                            einsatz_text = :einsatz_text, 
                            is_public = :is_public 
                        WHERE einsatz_id = :einsatz_id
                    ";
                } else {
                    // Create new details
                    $sql = "
                        INSERT INTO einsatz_Details 
                        (einsatz_id, image_path, einsatz_headline, einsatz_text, is_public) 
                        VALUES (:einsatz_id, :image_path, :einsatz_headline, :einsatz_text, :is_public)
                    ";
                }
                
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':einsatz_id', $id);
                $stmt->bindParam(':image_path', $details['image_path']);
                $stmt->bindParam(':einsatz_headline', $details['einsatz_headline']);
                $stmt->bindParam(':einsatz_text', $details['einsatz_text']);
                $stmt->bindParam(':is_public', $details['is_public']);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update operation details');
                }
            }
            
            // Commit transaction
            $this->commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->rollback();
            error_log('Error updating operation with details: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete an operation with its details
     * 
     * @param int $id The operation ID
     * @return bool Whether the deletion was successful
     */
    public function deleteWithDetails($id)
    {
        try {
            // Start transaction
            $this->beginTransaction();
            
            // Delete details first (foreign key constraint will handle this, but let's be explicit)
            $sql = "DELETE FROM einsatz_Details WHERE einsatz_id = :einsatz_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':einsatz_id', $id);
            $stmt->execute();
            
            // Delete operation
            if (!$this->delete($id)) {
                throw new Exception('Failed to delete operation');
            }
            
            // Commit transaction
            $this->commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->rollback();
            error_log('Error deleting operation with details: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get public operations
     * 
     * @param int $limit The maximum number of operations to return
     * @return array The public operations
     */
    public function getPublicEinsaetze($limit = null)
    {
        $sql = "
            SELECT e.*, d.einsatz_headline, d.einsatz_text, d.image_path
            FROM {$this->table} e
            LEFT JOIN einsatz_Details d ON e.ID = d.einsatz_id
            WHERE e.Anzeigen = 1
            ORDER BY e.Datum DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate a unique EinsatzID
     * 
     * @return int The generated ID
     */
    public function generateEinsatzID()
    {
        // Get the highest EinsatzID
        $sql = "SELECT MAX(EinsatzID) as max_id FROM {$this->table}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Generate a new ID (max + 1)
        return ($result['max_id'] ?? 0) + 1;
    }
    
    /**
     * Get operations by year
     * 
     * @param int $year The year
     * @return array The operations for the given year
     */
    public function getByYear($year)
    {
        $startDate = "{$year}-01-01 00:00:00";
        $endDate = "{$year}-12-31 23:59:59";
        
        $sql = "
            SELECT * FROM {$this->table}
            WHERE Datum BETWEEN :start_date AND :end_date
            ORDER BY Datum DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get available years
     * 
     * @return array The available years
     */
    public function getAvailableYears()
    {
        $sql = "
            SELECT DISTINCT YEAR(Datum) as year
            FROM {$this->table}
            ORDER BY year DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $years = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $years[] = $row['year'];
        }
        
        return $years;
    }
    
    /**
     * Get statistics
     * 
     * @param int|null $year The year to get statistics for (or null for all time)
     * @return array The statistics
     */
    public function getStatistics($year = null)
    {
        $whereClause = $year ? "WHERE YEAR(Datum) = {$year}" : "";
        
        // Total operations
        $sql = "SELECT COUNT(*) as count FROM {$this->table} {$whereClause}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Operations by category
        $sql = "
            SELECT Kategorie, COUNT(*) as count
            FROM {$this->table}
            {$whereClause}
            GROUP BY Kategorie
            ORDER BY count DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $byCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Operations by Stichwort
        $sql = "
            SELECT Stichwort, COUNT(*) as count
            FROM {$this->table}
            {$whereClause}
            GROUP BY Stichwort
            ORDER BY count DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $byStichwort = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Operations by month
        $sql = "
            SELECT MONTH(Datum) as month, COUNT(*) as count
            FROM {$this->table}
            {$whereClause}
            GROUP BY MONTH(Datum)
            ORDER BY month
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $byMonth = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total' => $totalCount,
            'by_category' => $byCategory,
            'by_stichwort' => $byStichwort,
            'by_month' => $byMonth
        ];
    }
} 