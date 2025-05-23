<?php
// Include required files
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/models/News.php';
require_once dirname(__DIR__) . '/includes/Security.php';
require_once dirname(__DIR__) . '/includes/FileUpload.php';

// Define title for the page
$pageTitle = "Neue Neuigkeit";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Instantiate the model
$newsModel = new News();


// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        header('Location: ' . BASE_URL . '/neuigkeiten/create.php');
        exit;
    }
    
    // Sanitize input
    $ueberschrift = Security::sanitizeInput($_POST['ueberschrift'] ?? '');
    $datum = Security::sanitizeInput($_POST['datum'] ?? '');
    $ort = Security::sanitizeInput($_POST['ort'] ?? '');
    $information = Security::sanitizeInput($_POST['information'] ?? '', true); // Allow some HTML tags
    $aktiv = isset($_POST['aktiv']) ? 1 : 0;
    $isPopup = isset($_POST['is_popup']) ? 1 : 0;
    $popupStart = Security::sanitizeInput($_POST['popup_start'] ?? '');
    $popupEnd = Security::sanitizeInput($_POST['popup_end'] ?? '');
    
    // Validate input
    if (empty($ueberschrift) || empty($datum) || empty($ort) || empty($information)) {
        $_SESSION['error'] = 'Bitte füllen Sie alle Pflichtfelder aus.';
        header('Location: ' . BASE_URL . '/neuigkeiten/create.php');
        exit;
    }
    
    // Prepare data
    $data = [
        'Ueberschrift' => $ueberschrift,
        'Datum' => $datum,
        'Ort' => $ort,
        'Information' => $information,
        'aktiv' => $aktiv,
        'is_popup' => $isPopup
    ];
    
    // Add popup dates if necessary
    if ($isPopup) {
        if (empty($popupStart) || empty($popupEnd)) {
            $_SESSION['error'] = 'Bitte geben Sie Start- und Enddatum für das Popup an.';
            header('Location: ' . BASE_URL . '/neuigkeiten/create.php');
            exit;
        }
        
        $data['popup_start'] = $popupStart;
        $data['popup_end'] = $popupEnd;
    }
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
        $relativePath = '/assets/Flyer/';
        $uploadDir = dirname(__DIR__, 3) . '/Public' . $relativePath;
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        // Dateiendung holen
        $fileInfo = pathinfo($_FILES['image']['name']);
        $extension = strtolower($fileInfo['extension']);

        if (!in_array($extension, $allowedExtensions)) {
            $_SESSION['error'] = 'Ungültiges Dateiformat.';
            header('Location: ' . BASE_URL . '/neuigkeiten/create.php');
            exit;
        }

        // Überschrift bereinigen
        $cleanTitle = preg_replace('/[^a-z0-9\-]/i', '', str_replace(' ', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $ueberschrift)));

        // Jahr aus dem Datum extrahieren
        $jahr = date('Y', strtotime($datum));

        // Benutzerdefinierter Dateiname
        $customName = "Flyer-{$cleanTitle}-{$jahr}";

        // Upload starten
        $uploader = new FileUpload($uploadDir, $allowedExtensions);
        $upload = $uploader
            ->file($_FILES['image'])
            ->setName($customName)
            ->upload();

        if ($upload) {
            $data['path_to_image'] = $relativePath . $uploader->getFileName();
        } else {
            $_SESSION['error'] = 'Fehler beim Hochladen des Bildes: ' . $uploader->getError();
            header('Location: ' . BASE_URL . '/neuigkeiten/create.php');
            exit;
        }
    }

    
    
    // Create the news item
    $result = $newsModel->createNews($data);
    
    if ($result) {
        $_SESSION['success'] = 'Neuigkeit wurde erfolgreich erstellt.';
        header('Location: ' . BASE_URL . '/neuigkeiten/list.php');
        exit;
    } else {
        $_SESSION['error'] = 'Fehler beim Erstellen der Neuigkeit.';
        header('Location: ' . BASE_URL . '/neuigkeiten/create.php');
        exit;
    }
}

// Include header
include dirname(__DIR__) . '/templates/header.php';
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Neue Neuigkeit erstellen</h6>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="ueberschrift" class="form-label">Überschrift</label>
                    <input type="text" class="form-control" id="ueberschrift" name="ueberschrift" required>
                    <div class="invalid-feedback">
                        Bitte geben Sie eine Überschrift ein.
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="datum" class="form-label">Datum</label>
                    <input type="datetime-local" class="form-control" id="datum" name="datum" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    <div class="invalid-feedback">
                        Bitte geben Sie ein Datum ein.
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="ort" class="form-label">Ort</label>
                    <input type="text" class="form-control" id="ort" name="ort" required>
                    <div class="invalid-feedback">
                        Bitte geben Sie einen Ort ein.
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="information" class="form-label">Information</label>
                <textarea class="form-control" id="information" name="information" rows="6" required></textarea>
                <div class="invalid-feedback">
                    Bitte geben Sie Informationen ein.
                </div>
            </div>
            
            <div class="mb-3">
                <label for="image" class="form-label">Bild</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                <small class="form-text text-muted">Optional. Maximale Größe: 30 MB. Erlaubte Formate: JPG, JPEG, PNG, GIF, WEBP.</small>
                <div class="mt-2">
                    <img id="imagePreview" src="#" alt="Vorschau" style="max-width: 200px; max-height: 200px; display: none;">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="aktiv" name="aktiv" checked>
                        <label class="form-check-label" for="aktiv">Aktiv</label>
                        <small class="form-text text-muted d-block">Wenn aktiviert, wird die Neuigkeit auf der Website angezeigt.</small>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="is_popup" name="is_popup" onchange="togglePopupOptions()">
                        <label class="form-check-label" for="is_popup">Als Popup anzeigen</label>
                        <small class="form-text text-muted d-block">Wenn aktiviert, wird die Neuigkeit als Popup auf der Website angezeigt.</small>
                    </div>
                </div>
            </div>
            
            <div id="popup_options" class="row" style="display: none;">
                <div class="col-md-6 mb-3">
                    <label for="popup_start" class="form-label">Popup Start</label>
                    <input type="date" class="form-control" id="popup_start" name="popup_start">
                    <div class="invalid-feedback">
                        Bitte geben Sie ein Startdatum ein.
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="popup_end" class="form-label">Popup Ende</label>
                    <input type="date" class="form-control" id="popup_end" name="popup_end">
                    <div class="invalid-feedback">
                        Bitte geben Sie ein Enddatum ein.
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="<?php echo BASE_URL; ?>/neuigkeiten/list.php" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Initialize CKEditor
    document.addEventListener('DOMContentLoaded', function() {
        ClassicEditor
            .create(document.getElementById('information'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo'],
                heading: {
                    options: [
                        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                    ]
                }
            })
            .catch(error => {
                console.error(error);
            });
        
        // Image preview
        document.getElementById('image').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    });
    
    // Toggle popup options
    function togglePopupOptions() {
        const isPopup = document.getElementById('is_popup').checked;
        const popupOptions = document.getElementById('popup_options');
        
        if (isPopup) {
            popupOptions.style.display = 'flex';
            
            // Set default dates if empty
            if (!document.getElementById('popup_start').value) {
                document.getElementById('popup_start').value = new Date().toISOString().split('T')[0];
            }
            
            if (!document.getElementById('popup_end').value) {
                const endDate = new Date();
                endDate.setDate(endDate.getDate() + 14); // Default to 2 weeks
                document.getElementById('popup_end').value = endDate.toISOString().split('T')[0];
            }
        } else {
            popupOptions.style.display = 'none';
        }
    }
    
    // Form validation
    (function() {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

<?php
// Include footer
include dirname(__DIR__) . '/templates/footer.php';
?> 