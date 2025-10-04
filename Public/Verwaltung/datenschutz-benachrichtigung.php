<?php
// Include required files
require_once dirname(__DIR__, 2) . '/Private/Database/Database.php';
require_once dirname(__DIR__, 2) . '/Private/Email/emailSender.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Security.php';

// Überprüfen, ob der Benutzer angemeldet ist
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['error'] = 'Bitte melden Sie sich an, um auf diese Seite zuzugreifen.';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Define title for the page
$pageTitle = "Datenschutz-Benachrichtigung";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Ungültiger CSRF-Token. Bitte versuchen Sie es erneut.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Check if this is a send action
    if (isset($_POST['action']) && $_POST['action'] === 'send') {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get selected users from form
            $selectedEmails = $_POST['selected_emails'] ?? [];
            $emailSubject = $_POST['email_subject'] ?? 'Aktualisierung unserer Datenschutzerklärung';
            $emailBody = $_POST['email_body'] ?? '';
            
            if (empty($selectedEmails)) {
                $_SESSION['error'] = 'Bitte wählen Sie mindestens einen Benutzer aus.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
            if (empty($emailBody)) {
                $_SESSION['error'] = 'Bitte geben Sie einen E-Mail-Text ein.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

            // Get user details for selected emails (merge duplicates)
            $placeholders = str_repeat('?,', count($selectedEmails) - 1) . '?';
            $stmt = $db->prepare("
                SELECT email, first_name, last_name, 'gh_users' as source
                FROM gh_users 
                WHERE email IN ($placeholders) AND is_verified = 1
                UNION ALL
                SELECT email, first_name, last_name, 'fw_users' as source
                FROM fw_users 
                WHERE email IN ($placeholders) AND is_verified = 1
            ");
            $stmt->execute(array_merge($selectedEmails, $selectedEmails));
            $rawUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Merge users with same email (only need one entry per email for sending)
            $usersByEmail = [];
            foreach ($rawUsers as $user) {
                $email = $user['email'];
                if (!isset($usersByEmail[$email])) {
                    $usersByEmail[$email] = [
                        'email' => $user['email'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name']
                    ];
                }
            }
            
            $users = array_values($usersByEmail);

            $successCount = 0;
            $failCount = 0;
            $failedEmails = [];

            // Build the website URL
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $domain = $_SERVER['HTTP_HOST'];
            $datenschutzUrl = $protocol . $domain . '/Datenschutz/';

            foreach ($users as $user) {
                // Replace placeholders in custom email body
                $personalizedBody = str_replace(
                    ['[Vorname]', '[Nachname]'],
                    [$user['first_name'], $user['last_name']],
                    $emailBody
                );
                
                $emailHtml = getPrivacyUpdateEmailTemplate(
                    $user['first_name'],
                    $user['last_name'],
                    $datenschutzUrl,
                    $personalizedBody
                );

                $result = sendEmail(
                    $user['email'],
                    $emailSubject,
                    $emailHtml
                );

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failCount++;
                    $failedEmails[] = $user['email'];
                }
            }

            // Set success/error messages
            if ($successCount > 0) {
                $_SESSION['success'] = "Datenschutz-Benachrichtigung wurde erfolgreich an {$successCount} Benutzer versendet.";
            }
            
            if ($failCount > 0) {
                $_SESSION['error'] = "Fehler beim Versenden an {$failCount} Benutzer: " . implode(', ', $failedEmails);
            }

        } catch (PDOException $e) {
            error_log('Datenschutz-Benachrichtigung error: ' . $e->getMessage());
            $_SESSION['error'] = 'Ein Fehler ist aufgetreten beim Versenden der Benachrichtigungen.';
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get all available users for selection and merge duplicates
try {
    $db = Database::getInstance()->getConnection();
    
    // Get all users from both tables
    $stmt = $db->query("
        SELECT email, first_name, last_name, 'gh_users' as source, 
               CASE WHEN erhaelt_emails = 1 THEN 1 ELSE 0 END as email_enabled
        FROM gh_users 
        WHERE is_verified = 1
        UNION ALL
        SELECT email, first_name, last_name, 'fw_users' as source, 1 as email_enabled
        FROM fw_users 
        WHERE is_verified = 1
    ");
    $rawUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Merge users with same email address
    $usersByEmail = [];
    foreach ($rawUsers as $user) {
        $email = $user['email'];
        if (!isset($usersByEmail[$email])) {
            $usersByEmail[$email] = [
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'sources' => [],
                'email_enabled' => 0
            ];
        }
        
        // Add source if not already added
        if (!in_array($user['source'], $usersByEmail[$email]['sources'])) {
            $usersByEmail[$email]['sources'][] = $user['source'];
        }
        
        // If any account has email enabled, mark as email enabled
        if ($user['email_enabled'] == 1) {
            $usersByEmail[$email]['email_enabled'] = 1;
        }
    }
    
    // Convert to array and sort by name
    $allUsers = array_values($usersByEmail);
    usort($allUsers, function($a, $b) {
        $nameA = $a['first_name'] . ' ' . $a['last_name'];
        $nameB = $b['first_name'] . ' ' . $b['last_name'];
        return strcasecmp($nameA, $nameB);
    });
    
    // Count users who have email notifications enabled (for default selection)
    $recipientCount = count(array_filter($allUsers, function($user) {
        return $user['email_enabled'] == 1;
    }));
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    $allUsers = [];
    $recipientCount = 0;
}

// Standard header einbinden
include __DIR__ . '/templates/header.php';
?>

<style>
    .info-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .warning-box {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin: 20px 0;
        border-radius: 5px;
    }
    
    .recipient-count {
        background: linear-gradient(135deg, rgba(167, 41, 32, 0.85), rgba(167, 41, 32, 0.95));
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        margin: 20px 0;
    }
    
    .recipient-count .count {
        font-size: 3rem;
        font-weight: bold;
        display: block;
    }
    
    .recipient-count .label {
        font-size: 1.1rem;
        opacity: 0.9;
    }
    
    .email-preview {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
    }
    
    .btn-send {
        background: linear-gradient(135deg, rgba(167, 41, 32, 0.85), rgba(167, 41, 32, 0.95));
        border: none;
        color: white;
        padding: 15px 40px;
        font-size: 1.1rem;
        font-weight: bold;
        border-radius: 5px;
        transition: all 0.3s ease;
    }
    
    .btn-send:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(167, 41, 32, 0.3);
        background: linear-gradient(135deg, rgba(167, 41, 32, 0.95), rgba(167, 41, 32, 1));
        color: white;
    }
    
    .btn-send:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .user-selection {
        margin: 20px 0;
    }
    
    .user-list {
        max-height: 500px;
        overflow-y: auto;
        overflow-x: hidden;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 8px;
        border: 1px solid #dee2e6;
        padding: 15px;
    }
    
    .user-item {
        padding: 12px 10px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        transition: background-color 0.2s ease;
        margin-bottom: 8px;
        border-radius: 5px;
    }
    
    .user-item:hover {
        background-color: rgba(167, 41, 32, 0.05);
    }
    
    .user-item:last-child {
        border-bottom: none;
    }
    
    .user-item.email-disabled {
        background-color: rgba(255, 243, 205, 0.3);
    }
    
    .user-item.email-disabled:hover {
        background-color: rgba(255, 243, 205, 0.5);
    }
    
    .form-check-label {
        cursor: pointer;
        width: 100%;
        display: block;
    }
    
    .user-info {
        display: flex;
        flex-direction: column;
        gap: 5px;
        padding-left: 5px;
    }
    
    .user-name {
        font-size: 1rem;
        color: #212529;
    }
    
    .user-email {
        color: #6c757d;
        font-size: 0.875rem;
    }
    
    .user-badges {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
        margin-top: 5px;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.3rem 0.6rem;
        border-radius: 4px;
        font-weight: 500;
    }
    
    .badge-info {
        background-color: #17a2b8;
        color: white;
    }
    
    .badge-primary {
        background-color: #a72920;
        color: white;
    }
    
    .badge-warning {
        background-color: #ffc107;
        color: #212529;
    }
    
    .btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .btn-group .btn {
        margin: 0;
    }
    
    .form-check-input {
        width: 1.2em;
        height: 1.2em;
        margin-top: 0.3em;
        cursor: pointer;
    }
    
    /* Scrollbar Styling */
    .user-list::-webkit-scrollbar {
        width: 8px;
    }
    
    .user-list::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.05);
        border-radius: 10px;
    }
    
    .user-list::-webkit-scrollbar-thumb {
        background: rgba(167, 41, 32, 0.5);
        border-radius: 10px;
    }
    
    .user-list::-webkit-scrollbar-thumb:hover {
        background: rgba(167, 41, 32, 0.7);
    }
    
    .email-content {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        padding: 20px;
        border: 1px solid rgba(167, 41, 32, 0.2);
    }
    
    .email-content .form-control {
        background-color: rgba(255, 255, 255, 0.9);
        border: 1px solid #dee2e6;
        color: #212529;
        font-size: 0.95rem;
    }
    
    .email-content .form-control:focus {
        background-color: white;
        border-color: rgba(167, 41, 32, 0.5);
        box-shadow: 0 0 0 0.25rem rgba(167, 41, 32, 0.15);
    }
    
    .email-content textarea {
        font-family: 'Segoe UI', Arial, sans-serif;
        line-height: 1.6;
    }
    
    .email-content .form-label {
        color: #333;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="info-card">
            <h2 class="mb-4">
                <i class="fas fa-shield-alt me-2"></i> Datenschutz-Benachrichtigung versenden
            </h2>
            
            <p class="lead">
                Mit dieser Funktion können Sie alle registrierten Benutzer über eine Aktualisierung der Datenschutzerklärung informieren.
            </p>
            
            <div class="recipient-count">
                <span class="count" id="selected-count">0</span>
                <span class="label">Benutzer ausgewählt</span>
            </div>
            
            <div class="warning-box">
                <h5><i class="fas fa-exclamation-triangle me-2"></i> Wichtige Hinweise:</h5>
                <ul class="mb-0">
                    <li>Wählen Sie die Benutzer aus, die über die Datenschutz-Aktualisierung informiert werden sollen.</li>
                    <li>Sie können auch an Benutzer mit deaktivierten E-Mail-Benachrichtigungen senden (gelb markiert).</li>
                    <li>Benutzer mit mehreren Accounts (Grillhütte & Feuerwehr) werden zusammengefasst angezeigt.</li>
                    <li>Sie können den E-Mail-Text nach Bedarf anpassen.</li>
                    <li>Die E-Mail enthält einen direkten Link zur aktualisierten Datenschutzerklärung.</li>
                    <li>Bitte stellen Sie sicher, dass die Datenschutzerklärung bereits aktualisiert wurde.</li>
                </ul>
            </div>
            
            <form method="POST" id="sendForm" onsubmit="return validateSelection();">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="send">
            
            <div class="user-selection">
                <h5><i class="fas fa-users me-2"></i> Benutzer auswählen:</h5>
                
                <div class="mb-3">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAll()">
                            <i class="fas fa-check-square me-1"></i> Alle auswählen
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectNone()">
                            <i class="fas fa-square me-1"></i> Alle abwählen
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="selectEmailEnabled()">
                            <i class="fas fa-envelope me-1"></i> Nur E-Mail-aktivierte
                        </button>
                    </div>
                </div>
                
                <div class="user-list">
                    <?php if (empty($allUsers)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Keine verifizierten Benutzer gefunden.
                        </div>
                    <?php else: ?>
                        <?php foreach ($allUsers as $user): ?>
                            <div class="form-check user-item <?php echo $user['email_enabled'] == 0 ? 'email-disabled' : ''; ?>">
                                <input class="form-check-input user-checkbox" 
                                       type="checkbox" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       name="selected_emails[]" 
                                       id="user_<?php echo htmlspecialchars(str_replace(['@', '.'], ['_', '_'], $user['email'])); ?>"
                                       <?php echo $user['email_enabled'] == 1 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="user_<?php echo htmlspecialchars(str_replace(['@', '.'], ['_', '_'], $user['email'])); ?>">
                                    <div class="user-info">
                                        <strong class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                        <small class="user-email">(<?php echo htmlspecialchars($user['email']); ?>)</small>
                                        <div class="user-badges">
                                            <?php foreach ($user['sources'] as $source): ?>
                                                <span class="badge badge-<?php echo $source === 'gh_users' ? 'info' : 'primary'; ?>">
                                                    <?php echo $source === 'gh_users' ? 'Grillhütte' : 'Feuerwehr'; ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if ($user['email_enabled'] == 0): ?>
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-envelope-slash"></i> E-Mail deaktiviert
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="email-content mt-4">
                <h5><i class="fas fa-edit me-2"></i> E-Mail-Inhalt anpassen:</h5>
                <p class="text-muted mb-2">
                    <small>Sie können den Text nach Bedarf anpassen. Die Platzhalter [Vorname] und [Nachname] werden automatisch ersetzt.</small>
                </p>
                
                <div class="mb-3">
                    <label for="email_subject" class="form-label"><strong>Betreff:</strong></label>
                    <input type="text" 
                           class="form-control" 
                           id="email_subject" 
                           name="email_subject" 
                           value="Aktualisierung unserer Datenschutzerklärung"
                           required>
                </div>
                
                <div class="mb-3">
                    <label for="email_body" class="form-label"><strong>Nachricht:</strong></label>
                    <textarea class="form-control" 
                              id="email_body" 
                              name="email_body" 
                              rows="10" 
                              required>wir möchten Sie darüber informieren, dass wir unsere Datenschutzerklärung aktualisiert haben.

Die Änderungen dienen dazu, die Transparenz zu erhöhen und sicherzustellen, dass Sie umfassend über die Verarbeitung Ihrer personenbezogenen Daten informiert sind.

Wir empfehlen Ihnen, die vollständige Datenschutzerklärung sorgfältig durchzulesen.

Durch die weitere Nutzung unserer Website und Dienste stimmen Sie der aktualisierten Datenschutzerklärung zu.

Bei Fragen zum Datenschutz stehen wir Ihnen gerne zur Verfügung. Sie können uns jederzeit unter info@feuerwehr-waldems-reichenbach.de erreichen.</textarea>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-send" id="sendButton" disabled>
                    <i class="fas fa-paper-plane me-2"></i> Benachrichtigung versenden
                </button>
            </div>
            
            </form>
            
            <div class="mt-4 text-muted">
                <small>
                    <i class="fas fa-info-circle me-1"></i> 
                    Der Versand kann je nach Anzahl der Empfänger einige Minuten dauern.
                </small>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript für Benutzerauswahl
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selected-count').textContent = count;
    
    const sendButton = document.getElementById('sendButton');
    if (count > 0) {
        sendButton.disabled = false;
        sendButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Benachrichtigung an ' + count + ' Benutzer versenden';
    } else {
        sendButton.disabled = true;
        sendButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Benachrichtigung versenden';
    }
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = true);
    updateSelectedCount();
}

function selectNone() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    updateSelectedCount();
}

function selectEmailEnabled() {
    selectNone();
    // Select only users who are not in email-disabled items
    const items = document.querySelectorAll('.user-item:not(.email-disabled)');
    items.forEach(item => {
        const checkbox = item.querySelector('.user-checkbox');
        if (checkbox) {
            checkbox.checked = true;
        }
    });
    updateSelectedCount();
}

function validateSelection() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Bitte wählen Sie mindestens einen Benutzer aus.');
        return false;
    }
    return confirm('Möchten Sie wirklich die Datenschutz-Benachrichtigung an ' + checkboxes.length + ' Benutzer versenden?');
}

// Event Listener für Checkboxen
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    
    // Initial count update
    updateSelectedCount();
});
</script>

<?php
/**
 * Generate the privacy update email template
 */
function getPrivacyUpdateEmailTemplate($firstName, $lastName, $privacyUrl, $customMessage) {
    // Convert line breaks to HTML paragraphs
    $messageParagraphs = '';
    $lines = explode("\n", $customMessage);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            $messageParagraphs .= '<p style="font-size: 16px; color: #333333; line-height: 1.6; margin-top: 15px;">' . htmlspecialchars($line) . '</p>';
        }
    }
    
    return <<<HTML
<div style="font-family: 'Segoe UI', Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 0;">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, rgba(167, 41, 32, 0.85), rgba(167, 41, 32, 0.95)); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;">
            <i class="fas fa-shield-alt"></i> Datenschutzerklärung aktualisiert
        </h1>
    </div>
    
    <!-- Content -->
    <div style="padding: 40px 30px; background-color: #ffffff;">
        <p style="font-size: 16px; color: #333333; line-height: 1.6;">
            Sehr geehrte/r {$firstName} {$lastName},
        </p>
        
        {$messageParagraphs}
        
        <!-- Call to Action Button -->
        <div style="text-align: center; margin: 35px 0;">
            <a href="{$privacyUrl}" style="display: inline-block; background: linear-gradient(135deg, rgba(167, 41, 32, 0.85), rgba(167, 41, 32, 0.95)); color: #ffffff; text-decoration: none; padding: 15px 40px; border-radius: 5px; font-size: 16px; font-weight: bold; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                <i class="fas fa-file-alt"></i> Datenschutzerklärung lesen
            </a>
        </div>
        
        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;">
        
        <p style="font-size: 16px; color: #333333; line-height: 1.6;">
            Mit freundlichen Grüßen<br>
            <strong style="color: #A72920;">Feuerwehr Waldems Reichenbach</strong>
        </p>
    </div>
    
    <!-- Footer Note -->
    <div style="background-color: #f8f9fa; padding: 20px 30px; border-radius: 0 0 10px 10px; text-align: center;">
        <p style="font-size: 13px; color: #666666; margin: 0; line-height: 1.5;">
            <i class="fas fa-info-circle"></i> Diese E-Mail wurde automatisch versendet.<br>
            Bei Fragen erreichen Sie uns unter info@feuerwehr-waldems-reichenbach.de
        </p>
    </div>
</div>
HTML;
}

// Footer einbinden
include __DIR__ . '/templates/footer.php';
?>

