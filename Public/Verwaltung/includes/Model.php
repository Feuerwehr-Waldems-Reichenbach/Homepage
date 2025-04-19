<?php

/**
 * Base Model class for database operations
 * 
 * This class provides common database operations that can be extended by specific models.
 */
class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * Constructor
     * 
     * @param string $table The table name
     * @param string $primaryKey The primary key column name
     * @param array $fillable The fillable columns
     */
    public function __construct($table, $primaryKey = 'id', $fillable = [])
    {
        $this->db = Database::getInstance()->getConnection();
        $this->table = $table;
        $this->primaryKey = $primaryKey;
        $this->fillable = $fillable;
    }

    /**
     * Get all records from the table
     * 
     * @param string $orderBy The column to order by
     * @param string $order The order direction (ASC or DESC)
     * @param int $limit The maximum number of records to return
     * @param int $offset The offset to start from
     * @return array The records
     */
    public function getAll($orderBy = null, $order = 'ASC', $limit = null, $offset = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy} {$order}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a record by its primary key
     * 
     * @param int $id The primary key value
     * @return array|false The record or false if not found
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get records by a specific column value
     * 
     * @param string $column The column name
     * @param mixed $value The value to search for
     * @return array The matching records
     */
    public function getByColumn($column, $value)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :value";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':value', $value);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new record
     * 
     * @param array $data The data to insert
     * @return int|false The ID of the new record or false on failure
     */
    public function create($data)
    {
        // Filter data to only include fillable columns
        $filteredData = array_intersect_key($data, array_flip($this->fillable));
        
        if (empty($filteredData)) {
            return false;
        }
        
        $columns = implode(', ', array_keys($filteredData));
        $placeholders = ':' . implode(', :', array_keys($filteredData));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        
        foreach ($filteredData as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        $result = $stmt->execute();
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Update a record
     * 
     * @param int $id The primary key value
     * @param array $data The data to update
     * @return bool Whether the update was successful
     */
    public function update($id, $data)
    {
        // Filter data to only include fillable columns
        $filteredData = array_intersect_key($data, array_flip($this->fillable));
        
        if (empty($filteredData)) {
            return false;
        }
        
        $setClause = '';
        foreach ($filteredData as $key => $value) {
            $setClause .= "{$key} = :{$key}, ";
        }
        $setClause = rtrim($setClause, ', ');
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':id', $id);
        
        foreach ($filteredData as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        return $stmt->execute();
    }

    /**
     * Delete a record
     * 
     * @param int $id The primary key value
     * @return bool Whether the deletion was successful
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Count records
     * 
     * @param string|null $whereClause The WHERE clause
     * @param array $params Parameters for the WHERE clause
     * @return int The count
     */
    public function count($whereClause = null, $params = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if ($whereClause) {
            $sql .= " WHERE {$whereClause}";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'];
    }

    /**
     * Execute a custom query
     * 
     * @param string $sql The SQL query
     * @param array $params The query parameters
     * @param bool $fetchAll Whether to fetch all records or just one
     * @return array|object The query result
     */
    public function query($sql, $params = [], $fetchAll = true)
    {
        $stmt = $this->db->prepare($sql);
        
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
        }
        
        $stmt->execute();
        
        if ($fetchAll) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Begin a transaction
     * 
     * @return bool Whether the transaction was started
     */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit a transaction
     * 
     * @return bool Whether the transaction was committed
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     * Rollback a transaction
     * 
     * @return bool Whether the transaction was rolled back
     */
    public function rollback()
    {
        return $this->db->rollBack();
    }

    /**
     * Format date for database
     * 
     * @param string $date The date to format
     * @return string The formatted date
     */
    public function formatDate($date)
    {
        return date($this->dateFormat, strtotime($date));
    }
} 