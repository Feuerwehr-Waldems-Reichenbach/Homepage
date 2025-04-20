<?php
// Include required files
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/models/Einsatz.php';
require_once dirname(__DIR__) . '/includes/Security.php';
require_once dirname(__DIR__) . '/includes/FileUpload.php';
require_once dirname(__DIR__, 3) . '/Private/AI/generateEinsatzbericht.php';

// Define title for the page
$pageTitle = "Einsatz bearbeiten";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'Keine Einsatz-ID angegeben.';
    header('Location: ' . BASE_URL . '/einsatz/list.php');
    exit;
}

$id = intval($_GET['id']);

// Instantiate the model
$einsatzModel = new Einsatz();

// Get the operation with details
$einsatz = $einsatzModel->getWithDetails($id);

if (!$einsatz) {
    $_SESSION['error'] = 'Einsatz nicht gefunden.';
    header('Location: ' . BASE_URL . '/einsatz/list.php');
    exit;
}

// Create upload directory if it doesn't exist
$uploadDir = dirname(__DIR__, 3) . '/Public/assets/images';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        header('Location: ' . BASE_URL . '/einsatz/edit.php?id=' . $id);
        exit;
    }
    
    // Sanitize input
    $sachverhalt = Security::sanitizeInput($_POST['sachverhalt'] ?? '');
    $stichwort = Security::sanitizeInput($_POST['stichwort'] ?? '');
    $kategorie = Security::sanitizeInput($_POST['kategorie'] ?? '');
    $ort = Security::sanitizeInput($_POST['ort'] ?? '');
    $einheit = Security::sanitizeInput($_POST['einheit'] ?? '');
    $datum = Security::sanitizeInput($_POST['datum'] ?? '');
    $endzeit = Security::sanitizeInput($_POST['endzeit'] ?? '');
    $anzeigen = isset($_POST['anzeigen']) ? 1 : 0;
    
    // Details
    $einsatzHeadline = Security::sanitizeInput($_POST['einsatz_headline'] ?? '');
    $einsatzText = Security::sanitizeInput($_POST['einsatz_text'] ?? '', true); // Allow some HTML tags
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    $removeImage = isset($_POST['remove_image']) ? true : false;
    
    // Validate input
    if (empty($sachverhalt) || empty($stichwort) || empty($ort) || empty($einheit) || empty($datum)) {
        $_SESSION['error'] = 'Bitte füllen Sie alle Pflichtfelder aus.';
        header('Location: ' . BASE_URL . '/einsatz/edit.php?id=' . $id);
        exit;
    }
    
    // Validate dates
    if (!strtotime($datum)) {
        $_SESSION['error'] = 'Bitte geben Sie ein gültiges Datum ein.';
        header('Location: ' . BASE_URL . '/einsatz/edit.php?id=' . $id);
        exit;
    }
    
    if (!empty($endzeit) && !strtotime($endzeit)) {
        $_SESSION['error'] = 'Bitte geben Sie eine gültige Endzeit ein.';
        header('Location: ' . BASE_URL . '/einsatz/edit.php?id=' . $id);
        exit;
    }
    
    // If endzeit is empty, set it to the start time
    if (empty($endzeit)) {
        $endzeit = $datum;
    }
    
    // Prepare data
    $data = [
        'Anzeigen' => $anzeigen,
        'Datum' => $datum,
        'Endzeit' => $endzeit,
        'Sachverhalt' => $sachverhalt,
        'Stichwort' => $stichwort,
        'Kategorie' => $kategorie,
        'Ort' => $ort,
        'Einheit' => $einheit
    ];
    
    // Prepare details data if needed
    $details = null;
    if (!empty($einsatzHeadline) || !empty($einsatzText)) {
        $details = [
            'einsatz_headline' => $einsatzHeadline,
            'einsatz_text' => $einsatzText,
            'is_public' => $isPublic,
            'image_path' => $einsatz['image_path'] ?? null
        ];
        
        // Handle image upload or removal
        if ($removeImage) {
            // Delete the existing image if it exists
            if (!empty($einsatz['image_path']) && file_exists(ADMIN_PATH . $einsatz['image_path'])) {
                @unlink(ADMIN_PATH . $einsatz['image_path']);
            }
            $details['image_path'] = null;
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
            $fileUpload = new FileUpload($uploadDir, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            $upload = $fileUpload->file($_FILES['image'])->generateUniqueName('einsatz')->upload();
            
            if ($upload) {
                // Delete the old image if it exists
                if (!empty($einsatz['image_path']) && file_exists(ADMIN_PATH . $einsatz['image_path'])) {
                    @unlink(ADMIN_PATH . $einsatz['image_path']);
                }
                
                $details['image_path'] = str_replace(ADMIN_PATH, '', $upload);
            } else {
                $_SESSION['error'] = 'Fehler beim Hochladen des Bildes: ' . $fileUpload->getError();
                header('Location: ' . BASE_URL . '/einsatz/edit.php?id=' . $id);
                exit;
            }
        }
    }
    
    // Update the operation
    $result = $einsatzModel->updateWithDetails($id, $data, $details);
    
    if ($result) {
        $_SESSION['success'] = 'Einsatz wurde erfolgreich aktualisiert.';
        header('Location: ' . BASE_URL . '/einsatz/list.php');
        exit;
    } else {
        $_SESSION['error'] = 'Fehler beim Aktualisieren des Einsatzes.';
        header('Location: ' . BASE_URL . '/einsatz/edit.php?id=' . $id);
        exit;
    }
}

// Format dates for datetime-local input
$formattedDatum = date('Y-m-d\TH:i', strtotime($einsatz['Datum']));
$formattedEndzeit = date('Y-m-d\TH:i', strtotime($einsatz['Endzeit']));

// Include header
include dirname(__DIR__) . '/templates/header.php';
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Einsatz bearbeiten</h6>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $id); ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="mb-4">
                <h5 class="border-bottom pb-2">Grunddaten</h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="sachverhalt" class="form-label">Sachverhalt</label>
                        <input type="text" class="form-control" id="sachverhalt" name="sachverhalt" value="<?php echo htmlspecialchars($einsatz['Sachverhalt']); ?>" required>
                        <div class="invalid-feedback">
                            Bitte geben Sie einen Sachverhalt ein.
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="stichwort" class="form-label">Stichwort</label>
                        <input type="text" class="form-control" id="stichwort" name="stichwort" value="<?php echo htmlspecialchars($einsatz['Stichwort']); ?>" required>
                        <div class="invalid-feedback">
                            Bitte geben Sie ein Stichwort ein.
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="kategorie" class="form-label">Kategorie</label>
                        <input type="text" class="form-control" id="kategorie" name="kategorie" value="<?php echo htmlspecialchars($einsatz['Kategorie'] ?? ''); ?>">
                        <div class="form-text">Optional</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="ort" class="form-label">Ort</label>
                        <input type="text" class="form-control" id="ort" name="ort" value="<?php echo htmlspecialchars($einsatz['Ort']); ?>" required>
                        <div class="invalid-feedback">
                            Bitte geben Sie einen Ort ein.
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="einheit" class="form-label">Einheit</label>
                        <input type="text" class="form-control" id="einheit" name="einheit" value="<?php echo htmlspecialchars($einsatz['Einheit']); ?>" required>
                        <div class="invalid-feedback">
                            Bitte geben Sie eine Einheit ein.
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="datum" class="form-label">Datum</label>
                        <input type="datetime-local" class="form-control" id="datum" name="datum" value="<?php echo $formattedDatum; ?>" required>
                        <div class="invalid-feedback">
                            Bitte geben Sie ein Datum ein.
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="endzeit" class="form-label">Endzeit</label>
                        <input type="datetime-local" class="form-control" id="endzeit" name="endzeit" value="<?php echo $formattedEndzeit; ?>">
                        <div class="form-text">Optional. Wenn leer, wird das Startdatum verwendet.</div>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="anzeigen" name="anzeigen" <?php echo $einsatz['Anzeigen'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="anzeigen">Öffentlich anzeigen</label>
                    <div class="form-text">Wenn aktiviert, wird der Einsatz auf der Website angezeigt.</div>
                </div>
            </div>
            
            <div class="mb-4">
                <h5 class="border-bottom pb-2">Details (optional)</h5>
                <div class="form-text mb-3">Fügen Sie optionale Details hinzu, die auf der Website angezeigt werden.</div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="einsatz_headline" class="form-label">Überschrift</label>
                        <input type="text" class="form-control" id="einsatz_headline" name="einsatz_headline" value="<?php echo htmlspecialchars($einsatz['einsatz_headline'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="einsatz_text" class="form-label">Beschreibung</label>
                    <div class="input-group">
                        <textarea class="form-control" id="einsatz_text" name="einsatz_text" rows="6"><?php echo $einsatz['einsatz_text'] ?? ''; ?></textarea>
                        <button type="button" id="generateReportBtn" class="btn btn-outline-secondary">Bericht mit Künstlicher Intelligenz generieren lassen</button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="image" class="form-label">Bild</label>
                    
                    <?php if (!empty($einsatz['image_path'])): ?>
                        <div class="mb-2">
                            <p>Aktuelles Bild:</p>
                            <img src="<?php echo htmlspecialchars($einsatz['image_path']); ?>" alt="Aktuelles Bild" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="remove_image" name="remove_image">
                                <label class="form-check-label" for="remove_image">Bild entfernen</label>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    <div class="form-text">Optional. Maximale Größe: 20 MB. Erlaubte Formate: JPG, JPEG, PNG, GIF, WEBP.</div>
                    <div class="mt-2">
                        <img id="imagePreview" src="#" alt="Vorschau" style="max-width: 200px; max-height: 200px; display: none;">
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_public" name="is_public" <?php echo (isset($einsatz['is_public']) && $einsatz['is_public']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_public">Details öffentlich anzeigen</label>
                    <div class="form-text">Wenn aktiviert, werden die Details auf der Website angezeigt.</div>
                </div>
            </div>
            
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="<?php echo BASE_URL; ?>/einsatz/list.php" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Initialize CKEditor
    document.addEventListener('DOMContentLoaded', function() {
        // Add custom CSS to fix CKEditor in input-group
        const style = document.createElement('style');
        style.textContent = `
            .ck.ck-editor {
                width: 100%;
            }
            .ck.ck-editor__main .ck-editor__editable {
                min-height: 200px;
            }
            .input-group:has(.ck-editor) {
                flex-wrap: wrap;
            }
            .input-group:has(.ck-editor) .ck-editor {
                flex: 0 0 100%;
                width: 100%;
            }
            .input-group:has(.ck-editor) .btn {
                margin-top: 10px;
            }
        `;
        document.head.appendChild(style);
        
        let editor;
        
        ClassicEditor
            .create(document.getElementById('einsatz_text'), {
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
            .then(editorInstance => {
                editor = editorInstance;
                
                // Add event listener for the generate report button
                document.getElementById('generateReportBtn').addEventListener('click', function() {
                    generateEinsatzbericht(editor);
                });
            })
            .catch(error => {
                console.error(error);
            });
            
        // Function to generate Einsatzbericht
        function generateEinsatzbericht(editor) {
            // Get form values
            const sachverhalt = document.getElementById('sachverhalt').value;
            const stichwort = document.getElementById('stichwort').value;
            const kategorie = document.getElementById('kategorie').value;
            const ort = document.getElementById('ort').value;
            const einheit = document.getElementById('einheit').value;
            const datum = document.getElementById('datum').value;
            const endzeit = document.getElementById('endzeit').value || datum;
            const einsatzId = <?php echo $id; ?>;
            
            // Validate required fields
            if (!sachverhalt || !stichwort || !ort || !einheit || !datum) {
                alert('Bitte füllen Sie alle Pflichtfelder aus, bevor Sie einen Bericht generieren.');
                return;
            }
            
            // Show loading state
            const btn = document.getElementById('generateReportBtn');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Wird generiert...';
            
            // Make AJAX request
            fetch('<?php echo BASE_URL; ?>/einsatz/generate-report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    einsatz_id: einsatzId,
                    start: datum,
                    end: endzeit,
                    stichwort: stichwort,
                    kategorie: kategorie,
                    einsatzgruppe: einheit,
                    sachverhalt: sachverhalt,
                    ort: ort,
                    csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Netzwerkfehler oder Serverfehler');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Set the generated text in the editor
                    editor.setData(data.text);
                } else {
                    alert('Fehler: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Fehler beim Generieren des Berichts: ' + error.message);
            })
            .finally(() => {
                // Restore button state
                btn.disabled = false;
                btn.textContent = originalText;
            });
        }
        
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
        
        // Remove image checkbox
        const removeImageCheckbox = document.getElementById('remove_image');
        if (removeImageCheckbox) {
            removeImageCheckbox.addEventListener('change', function() {
                const imageInput = document.getElementById('image');
                if (this.checked) {
                    imageInput.disabled = true;
                } else {
                    imageInput.disabled = false;
                }
            });
        }
        
        // Form validation
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
    });
</script>

<?php
// Include footer
include dirname(__DIR__) . '/templates/footer.php';
?> 