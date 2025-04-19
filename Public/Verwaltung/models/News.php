<?php
require_once dirname(__DIR__) . '/includes/Model.php';

/**
 * News Model
 * 
 * This class handles operations on the neuigkeiten table.
 */
class News extends Model
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $table = 'neuigkeiten';
        $primaryKey = 'ID';
        $fillable = ['Ueberschrift', 'Datum', 'Ort', 'Information', 'path_to_image', 'aktiv', 'is_popup', 'popup_start', 'popup_end'];
        
        parent::__construct($table, $primaryKey, $fillable);
    }
    
    /**
     * Get active news
     * 
     * @param int $limit The maximum number of news to return
     * @return array The active news
     */
    public function getActiveNews($limit = null)
    {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE aktiv = 1
            ORDER BY Datum DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get active popups
     * 
     * @return array The active popups
     */
    public function getActivePopups()
    {
        $now = date('Y-m-d H:i:s');
        
        $sql = "
            SELECT * FROM {$this->table}
            WHERE is_popup = 1
            AND aktiv = 1
            AND popup_start <= :now
            AND popup_end >= :now
            ORDER BY Datum DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':now', $now);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create a new news item
     * 
     * @param array $data The news data
     * @return int|false The ID of the new news item or false on failure
     */
    public function createNews($data)
    {
        // Make sure date is in the correct format
        if (isset($data['Datum']) && !empty($data['Datum'])) {
            $data['Datum'] = $this->formatDate($data['Datum']);
        } else {
            $data['Datum'] = date('Y-m-d H:i:s');
        }
        
        // Format popup dates if set
        if (isset($data['popup_start']) && !empty($data['popup_start'])) {
            $data['popup_start'] = $this->formatDate($data['popup_start']);
        }
        
        if (isset($data['popup_end']) && !empty($data['popup_end'])) {
            $data['popup_end'] = $this->formatDate($data['popup_end']);
        }
        
        // Convert checkbox values to boolean
        $data['aktiv'] = isset($data['aktiv']) && $data['aktiv'] ? 1 : 0;
        $data['is_popup'] = isset($data['is_popup']) && $data['is_popup'] ? 1 : 0;
        
        return $this->create($data);
    }
    
    /**
     * Update a news item
     * 
     * @param int $id The news ID
     * @param array $data The news data
     * @return bool Whether the update was successful
     */
    public function updateNews($id, $data)
    {
        // Make sure date is in the correct format
        if (isset($data['Datum']) && !empty($data['Datum'])) {
            $data['Datum'] = $this->formatDate($data['Datum']);
        }
        
        // Format popup dates if set
        if (isset($data['popup_start']) && !empty($data['popup_start'])) {
            $data['popup_start'] = $this->formatDate($data['popup_start']);
        }
        
        if (isset($data['popup_end']) && !empty($data['popup_end'])) {
            $data['popup_end'] = $this->formatDate($data['popup_end']);
        }
        
        // Convert checkbox values to boolean
        $data['aktiv'] = isset($data['aktiv']) && $data['aktiv'] ? 1 : 0;
        $data['is_popup'] = isset($data['is_popup']) && $data['is_popup'] ? 1 : 0;
        
        return $this->update($id, $data);
    }
    
    /**
     * Activate a news item
     * 
     * @param int $id The news ID
     * @return bool Whether the activation was successful
     */
    public function activateNews($id)
    {
        return $this->update($id, ['aktiv' => 1]);
    }
    
    /**
     * Deactivate a news item
     * 
     * @param int $id The news ID
     * @return bool Whether the deactivation was successful
     */
    public function deactivateNews($id)
    {
        return $this->update($id, ['aktiv' => 0]);
    }
    
    /**
     * Make a news item a popup
     * 
     * @param int $id The news ID
     * @param string $startDate The popup start date
     * @param string $endDate The popup end date
     * @return bool Whether the update was successful
     */
    public function makePopup($id, $startDate, $endDate)
    {
        $data = [
            'is_popup' => 1,
            'popup_start' => $this->formatDate($startDate),
            'popup_end' => $this->formatDate($endDate)
        ];
        
        return $this->update($id, $data);
    }
    
    /**
     * Remove popup status from a news item
     * 
     * @param int $id The news ID
     * @return bool Whether the update was successful
     */
    public function removePopup($id)
    {
        $data = [
            'is_popup' => 0,
            'popup_start' => null,
            'popup_end' => null
        ];
        
        return $this->update($id, $data);
    }
    
    /**
     * Get news by year
     * 
     * @param int $year The year
     * @return array The news for the given year
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
     * Search news
     * 
     * @param string $query The search query
     * @return array The matching news
     */
    public function search($query)
    {
        $searchQuery = '%' . $query . '%';
        
        $sql = "
            SELECT * FROM {$this->table}
            WHERE Ueberschrift LIKE :query
            OR Information LIKE :query
            OR Ort LIKE :query
            ORDER BY Datum DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':query', $searchQuery);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 