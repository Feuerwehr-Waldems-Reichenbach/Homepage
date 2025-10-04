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

    try {
        $db = Database::getInstance()->getConnection();
        
        // Get all users from both tables
        // gh_users: Grillhütten-Benutzer with email notification setting
        // fw_users: Feuerwehr-Benutzer (all verified users)
        $stmt = $db->prepare("
            SELECT email, first_name, last_name, 'gh_users' as source
            FROM gh_users 
            WHERE erhaelt_emails = 1 AND is_verified = 1
            UNION
            SELECT email, first_name, last_name, 'fw_users' as source
            FROM fw_users 
            WHERE is_verified = 1
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $successCount = 0;
        $failCount = 0;
        $failedEmails = [];

        // Build the website URL
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $domain = $_SERVER['HTTP_HOST'];
        $datenschutzUrl = $protocol . $domain . '/Datenschutz/';

        foreach ($users as $user) {
            $emailBody = getPrivacyUpdateEmailTemplate(
                $user['first_name'],
                $user['last_name'],
                $datenschutzUrl
            );

            $result = sendEmail(
                $user['email'],
                'Aktualisierung unserer Datenschutzerklärung',
                $emailBody
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

        if ($successCount === 0 && $failCount === 0) {
            $_SESSION['error'] = 'Keine Benutzer mit aktivierten E-Mail-Benachrichtigungen gefunden.';
        }

    } catch (PDOException $e) {
        error_log('Datenschutz-Benachrichtigung error: ' . $e->getMessage());
        $_SESSION['error'] = 'Ein Fehler ist aufgetreten beim Versenden der Benachrichtigungen.';
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get count of users who will receive the email
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT COUNT(*) as count FROM (
            SELECT email FROM gh_users WHERE erhaelt_emails = 1 AND is_verified = 1
            UNION
            SELECT email FROM fw_users WHERE is_verified = 1
        ) as combined_users
    ");
    $recipientCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
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
                <span class="count"><?php echo $recipientCount; ?></span>
                <span class="label">Benutzer werden benachrichtigt</span>
            </div>
            
            <div class="warning-box">
                <h5><i class="fas fa-exclamation-triangle me-2"></i> Wichtige Hinweise:</h5>
                <ul class="mb-0">
                    <li>Die E-Mail wird an alle <strong>verifizierten</strong> Benutzer aus beiden Systemen versendet:
                        <ul>
                            <li>Grillhütten-Benutzer (gh_users) mit aktivierten E-Mail-Benachrichtigungen</li>
                            <li>Feuerwehr-Benutzer (fw_users)</li>
                        </ul>
                    </li>
                    <li>Die E-Mail enthält einen direkten Link zur aktualisierten Datenschutzerklärung.</li>
                    <li>Bitte stellen Sie sicher, dass die Datenschutzerklärung bereits aktualisiert wurde, bevor Sie die Benachrichtigung versenden.</li>
                    <li>Doppelte E-Mail-Adressen werden automatisch herausgefiltert.</li>
                </ul>
            </div>
            
            <div class="email-preview">
                <h5><i class="fas fa-envelope me-2"></i> Vorschau der E-Mail:</h5>
                <p><strong>Betreff:</strong> Aktualisierung unserer Datenschutzerklärung</p>
                <div style="border: 1px solid #ccc; padding: 20px; background: white; margin-top: 10px;">
                    <p style="font-size: 16px; color: #333;">Sehr geehrte/r [Vorname] [Nachname],</p>
                    <p style="font-size: 16px; color: #333;">wir möchten Sie darüber informieren, dass wir unsere Datenschutzerklärung aktualisiert haben.</p>
                    <p style="font-size: 16px; color: #333;">Die Änderungen dienen dazu, die Transparenz zu erhöhen und sicherzustellen, dass Sie umfassend über die Verarbeitung Ihrer personenbezogenen Daten informiert sind...</p>
                    <p><em>[...] Vollständige E-Mail-Vorlage wird versendet</em></p>
                </div>
            </div>
            
            <form method="POST" onsubmit="return confirm('Möchten Sie wirklich die Datenschutz-Benachrichtigung an <?php echo $recipientCount; ?> Benutzer versenden?');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-send">
                        <i class="fas fa-paper-plane me-2"></i> Benachrichtigung jetzt versenden
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

<?php
/**
 * Generate the privacy update email template
 */
function getPrivacyUpdateEmailTemplate($firstName, $lastName, $privacyUrl) {
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
        
        <p style="font-size: 16px; color: #333333; line-height: 1.6; margin-top: 20px;">
            wir möchten Sie darüber informieren, dass wir unsere <strong>Datenschutzerklärung</strong> aktualisiert haben.
        </p>
        
        <p style="font-size: 16px; color: #333333; line-height: 1.6; margin-top: 15px;">
            Die Änderungen dienen dazu, die Transparenz zu erhöhen und sicherzustellen, dass Sie umfassend über die Verarbeitung Ihrer personenbezogenen Daten informiert sind.
        </p>
        
        <div style="background-color: #f8f9fa; border-left: 4px solid #A72920; padding: 20px; margin: 30px 0; border-radius: 5px;">
            <h3 style="color: #A72920; margin: 0 0 10px 0; font-size: 18px;">
                <i class="fas fa-info-circle"></i> Was hat sich geändert?
            </h3>
            <p style="font-size: 15px; color: #555555; line-height: 1.6; margin: 0;">
                Die aktualisierte Datenschutzerklärung enthält detaillierte Informationen über die Art und Weise, wie wir Ihre Daten erheben, verarbeiten und schützen. Wir empfehlen Ihnen, die vollständige Datenschutzerklärung sorgfältig durchzulesen.
            </p>
        </div>
        
        <!-- Call to Action Button -->
        <div style="text-align: center; margin: 35px 0;">
            <a href="{$privacyUrl}" style="display: inline-block; background: linear-gradient(135deg, rgba(167, 41, 32, 0.85), rgba(167, 41, 32, 0.95)); color: #ffffff; text-decoration: none; padding: 15px 40px; border-radius: 5px; font-size: 16px; font-weight: bold; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                <i class="fas fa-file-alt"></i> Datenschutzerklärung lesen
            </a>
        </div>
        
        <p style="font-size: 16px; color: #333333; line-height: 1.6; margin-top: 30px;">
            Durch die weitere Nutzung unserer Website und Dienste stimmen Sie der aktualisierten Datenschutzerklärung zu.
        </p>
        
        <p style="font-size: 16px; color: #333333; line-height: 1.6; margin-top: 20px;">
            Bei Fragen zum Datenschutz stehen wir Ihnen gerne zur Verfügung. Sie können uns jederzeit unter 
            <a href="mailto:info@feuerwehr-waldems-reichenbach.de" style="color: #A72920; text-decoration: none;">info@feuerwehr-waldems-reichenbach.de</a> erreichen.
        </p>
        
        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;">
        
        <p style="font-size: 16px; color: #333333; line-height: 1.6;">
            Mit freundlichen Grüßen<br>
            <strong style="color: #A72920;">Feuerwehr Waldems Reichenbach</strong>
        </p>
    </div>
    
    <!-- Footer Note -->
    <div style="background-color: #f8f9fa; padding: 20px 30px; border-radius: 0 0 10px 10px; text-align: center;">
        <p style="font-size: 13px; color: #666666; margin: 0; line-height: 1.5;">
            <i class="fas fa-info-circle"></i> Diese E-Mail wurde automatisch versendet, da Sie sich für E-Mail-Benachrichtigungen registriert haben.<br>
            Sie können Ihre E-Mail-Einstellungen jederzeit in Ihrem Benutzerprofil ändern.
        </p>
    </div>
</div>
HTML;
}

// Footer einbinden
include __DIR__ . '/templates/footer.php';
?>

