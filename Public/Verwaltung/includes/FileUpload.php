<?php

/**
 * File Upload Handler
 * 
 * This class handles file uploads with security checks.
 */
class FileUpload
{
    private $uploadDirectory;
    private $allowedTypes;
    private $maxSize;
    private $fileName;
    private $fileType;
    private $fileTmpName;
    private $fileError;
    private $fileSize;
    private $newFileName;
    private $uploadPath;
    private $errorMessage;

    /**
     * Constructor
     * 
     * @param string $uploadDirectory The directory to upload to
     * @param array $allowedTypes The allowed file types
     * @param int $maxSize The maximum file size in bytes
     */
    public function __construct($uploadDirectory = UPLOAD_DIR, $allowedTypes = ALLOWED_FILE_TYPES, $maxSize = MAX_UPLOAD_SIZE)
    {
        $this->uploadDirectory = rtrim($uploadDirectory, '/') . '/';
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;
        $this->errorMessage = '';
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0755, true);
        }
    }

    /**
     * Set the file to upload
     * 
     * @param array $file The file from $_FILES
     * @return FileUpload The file upload object for method chaining
     */
    public function file($file)
    {
        if (!isset($file['name']) || empty($file['name'])) {
            $this->errorMessage = 'Keine Datei ausgewählt.';
            return $this;
        }
        
        $this->fileName = basename($file['name']);
        $this->fileType = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));
        $this->fileTmpName = $file['tmp_name'];
        $this->fileError = $file['error'];
        $this->fileSize = $file['size'];
        
        return $this;
    }

    /**
     * Set a new file name
     * 
     * @param string $name The new file name (without extension)
     * @return FileUpload The file upload object for method chaining
     */
    public function setName($name)
    {
        $safeName = preg_replace('/[^a-z0-9_-]/i', '_', $name);
        $this->newFileName = $safeName . '.' . $this->fileType;
        
        return $this;
    }

    /**
     * Generate a unique file name
     * 
     * @param string $prefix An optional prefix for the file name
     * @return FileUpload The file upload object for method chaining
     */
    public function generateUniqueName($prefix = '')
    {
        $prefix = empty($prefix) ? '' : preg_replace('/[^a-z0-9_-]/i', '_', $prefix) . '_';
        $this->newFileName = $prefix . uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $this->fileType;
        
        return $this;
    }

    /**
     * Validate the file
     * 
     * @return bool Whether the file is valid
     */
    public function validate()
    {
        // Check for upload errors
        if ($this->fileError !== UPLOAD_ERR_OK) {
            switch ($this->fileError) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $this->errorMessage = 'Die Datei ist zu groß.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $this->errorMessage = 'Die Datei wurde nur teilweise hochgeladen.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $this->errorMessage = 'Keine Datei hochgeladen.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $this->errorMessage = 'Kein temporäres Verzeichnis.';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $this->errorMessage = 'Fehler beim Schreiben der Datei.';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $this->errorMessage = 'Upload durch eine Erweiterung gestoppt.';
                    break;
                default:
                    $this->errorMessage = 'Unbekannter Fehler.';
            }
            
            return false;
        }
        
        // Check file size
        if ($this->fileSize > $this->maxSize) {
            $this->errorMessage = 'Die Datei ist zu groß. Maximale Größe: ' . ($this->maxSize / (1024 * 1024)) . ' MB.';
            return false;
        }
        
        // Check file type
        if (!in_array($this->fileType, $this->allowedTypes)) {
            $this->errorMessage = 'Ungültiger Dateityp. Erlaubte Typen: ' . implode(', ', $this->allowedTypes);
            return false;
        }
        
        // Set default file name if not set
        if (empty($this->newFileName)) {
            $this->generateUniqueName();
        }
        
        $this->uploadPath = $this->uploadDirectory . $this->newFileName;
        
        return true;
    }

    /**
     * Upload the file
     * 
     * @return string|false The path to the uploaded file or false on failure
     */
    public function upload()
    {
        if (!$this->validate()) {
            return false;
        }
        
        // Ensure the file is from a POST upload
        if (!is_uploaded_file($this->fileTmpName)) {
            $this->errorMessage = 'Möglicher Angriff durch Dateiupload.';
            return false;
        }
        
        // Move the file to the upload directory
        if (!move_uploaded_file($this->fileTmpName, $this->uploadPath)) {
            $this->errorMessage = 'Fehler beim Speichern der Datei.';
            return false;
        }
        
        // Additional security: Verify that the file exists and is readable
        if (!file_exists($this->uploadPath) || !is_readable($this->uploadPath)) {
            $this->errorMessage = 'Fehler beim Überprüfen der hochgeladenen Datei.';
            return false;
        }
        
        // For images, verify that it's a valid image
        if (in_array($this->fileType, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $imageInfo = getimagesize($this->uploadPath);
            if ($imageInfo === false) {
                // Not a valid image, delete it
                unlink($this->uploadPath);
                $this->errorMessage = 'Die hochgeladene Datei ist kein gültiges Bild.';
                return false;
            }
        }
        
        return $this->uploadPath;
    }

    /**
     * Get the error message
     * 
     * @return string The error message
     */
    public function getError()
    {
        return $this->errorMessage;
    }

    /**
     * Get the new file name
     * 
     * @return string The new file name
     */
    public function getFileName()
    {
        return $this->newFileName;
    }

    /**
     * Get the upload path
     * 
     * @return string The upload path
     */
    public function getUploadPath()
    {
        return $this->uploadPath;
    }

    /**
     * Get the web path (relative to the document root)
     * 
     * @return string The web path
     */
    public function getWebPath()
    {
        $relPath = str_replace(ADMIN_PATH, '', $this->uploadPath);
        return $relPath;
    }

    /**
     * Delete a file
     * 
     * @param string $filePath The path to the file
     * @return bool Whether the deletion was successful
     */
    public static function delete($filePath)
    {
        if (empty($filePath) || !file_exists($filePath)) {
            return false;
        }
        
        return unlink($filePath);
    }
} 