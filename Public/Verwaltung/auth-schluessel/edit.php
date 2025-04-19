<?php
// Include required files
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/models/AuthKey.php';
require_once dirname(__DIR__) . '/includes/Security.php';

// Define title for the page
$pageTitle = "Authentifizierungsschlüssel bearbeiten";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'Keine Schlüssel-ID angegeben.';
    header('Location: ' . BASE_URL . '/auth-schluessel/list.php');
    exit;
}

$id = intval($_GET['id']);

// Instantiate the model
$authKeyModel = new AuthKey();

// Get the key
$key = $authKeyModel->getById($id);

if (!$key) {
    $_SESSION['error'] = 'Schlüssel nicht gefunden.';
    header('Location: ' . BASE_URL . '/auth-schluessel/list.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        header('Location: ' . BASE_URL . '/auth-schluessel/edit.php?id=' . $id);
        exit;
    }
    
    // Sanitize input
    $bezeichnung = Security::sanitizeInput($_POST['bezeichnung'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Validate input
    if (empty($bezeichnung)) {
        $_SESSION['error'] = 'Bitte geben Sie eine Bezeichnung für den Schlüssel ein.';
        header('Location: ' . BASE_URL . '/auth-schluessel/edit.php?id=' . $id);
        exit;
    }
    
    // Prepare data for update
    $data = [
        'Bezeichnung' => $bezeichnung,
        'active' => $active
    ];
    
    // Check if key should be changed
    if (isset($_POST['key_option']) && $_POST['key_option'] === 'custom' && isset($_POST['custom_key']) && !empty($_POST['custom_key'])) {
        $data['auth_key'] = Security::sanitizeInput($_POST['custom_key']);
    }
    
    // Update the key
    $result = $authKeyModel->update($id, $data);
    
    if ($result) {
        $_SESSION['success'] = 'Authentifizierungsschlüssel wurde erfolgreich aktualisiert.';
        header('Location: ' . BASE_URL . '/auth-schluessel/list.php');
        exit;
    } else {
        $_SESSION['error'] = 'Fehler beim Aktualisieren des Authentifizierungsschlüssels.';
        header('Location: ' . BASE_URL . '/auth-schluessel/edit.php?id=' . $id);
        exit;
    }
}

// Include header
include dirname(__DIR__) . '/templates/header.php';
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Authentifizierungsschlüssel bearbeiten</h6>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $id); ?>" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="mb-3">
                <label for="bezeichnung" class="form-label">Bezeichnung</label>
                <input type="text" class="form-control" id="bezeichnung" name="bezeichnung" value="<?php echo htmlspecialchars($key['Bezeichnung']); ?>" required>
                <div class="invalid-feedback">
                    Bitte geben Sie eine Bezeichnung ein.
                </div>
            </div>
            
            <div class="mb-3">
                <label for="key_options" class="form-label">Schlüssel</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="key_option" id="keep_key" value="keep" checked onchange="toggleKeyOptions()">
                    <label class="form-check-label" for="keep_key">
                        Bestehenden Schlüssel beibehalten
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="key_option" id="custom_key_option" value="custom" onchange="toggleKeyOptions()">
                    <label class="form-check-label" for="custom_key_option">
                        Neuen Schlüssel angeben
                    </label>
                </div>
            </div>
            
            <div id="current_key_container" class="mb-3">
                <label for="current_key" class="form-label">Aktueller Schlüssel</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="current_key" value="<?php echo htmlspecialchars($key['auth_key']); ?>" readonly>
                    <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-target="#current_key">
                        <i class="fas fa-copy"></i> Kopieren
                    </button>
                </div>
            </div>
            
            <div id="custom_key_container" class="mb-3" style="display: none;">
                <label for="custom_key" class="form-label">Neuer Schlüssel</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="custom_key" name="custom_key" placeholder="Geben Sie einen neuen Schlüssel ein">
                    <button class="btn btn-outline-secondary" type="button" id="generate_key">
                        <i class="fas fa-sync-alt"></i> Generieren
                    </button>
                </div>
                <small class="form-text text-muted">Der Schlüssel sollte mindestens 16 Zeichen lang sein und schwer zu erraten.</small>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="active" name="active" <?php echo $key['active'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="active">Schlüssel aktivieren</label>
                <small class="form-text text-muted d-block">Wenn aktiviert, kann der Schlüssel verwendet werden.</small>
            </div>
            
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="<?php echo BASE_URL; ?>/auth-schluessel/list.php" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>

<!-- Clipboard.js for copy functionality -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
<script>
    // Initialize clipboard.js
    document.addEventListener('DOMContentLoaded', function() {
        var clipboard = new ClipboardJS('.copy-btn');
        
        clipboard.on('success', function(e) {
            // Show tooltip or feedback
            const btn = e.trigger;
            const icon = btn.querySelector('i');
            
            // Change icon temporarily
            icon.textContent = ' Kopiert!';
            
            setTimeout(function() {
                icon.textContent = ' Kopieren';
            }, 1500);
            
            e.clearSelection();
        });
        
        // Generate key
        document.getElementById('generate_key').addEventListener('click', function() {
            // Make an AJAX request to get a new key
            fetch('<?php echo BASE_URL; ?>/auth-schluessel/generate_key.php')
                .then(response => response.json())
                .then(data => {
                    if (data.key) {
                        document.getElementById('custom_key').value = data.key;
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    });
    
    // Toggle key options
    function toggleKeyOptions() {
        const keepOption = document.getElementById('keep_key');
        const currentContainer = document.getElementById('current_key_container');
        const customContainer = document.getElementById('custom_key_container');
        
        if (keepOption.checked) {
            currentContainer.style.display = 'block';
            customContainer.style.display = 'none';
            document.getElementById('custom_key').value = '';
        } else {
            currentContainer.style.display = 'block';
            customContainer.style.display = 'block';
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