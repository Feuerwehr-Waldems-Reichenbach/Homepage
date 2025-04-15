<?php
// Basisverzeichnis auf zwei Ebenen über diesem Script
define('BASE_PATH_DB', dirname(__DIR__, 2));

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require BASE_PATH_DB . '/Private/Email/phpmailer/src/Exception.php';
require BASE_PATH_DB . '/Private/Email/phpmailer/src/PHPMailer.php';
require BASE_PATH_DB . '/Private/Email/phpmailer/src/SMTP.php';

// Aktiver E-Mail-Client (Standard: default)
$GLOBALS['ACTIVE_EMAIL_CLIENT'] = 'default';

function setActiveEmailClient(string $client): void {
    $GLOBALS['ACTIVE_EMAIL_CLIENT'] = $client;
}

function getActiveEmailClient(): string {
    return $GLOBALS['ACTIVE_EMAIL_CLIENT'] ?? 'default';
}

function getSMTPConfig(): array {
    $client = getActiveEmailClient();
    $configPath = BASE_PATH_DB . "/Private/Initializations/smtpEmail_{$client}.ini";
    if (!file_exists($configPath)) {
        throw new Exception("Die Konfigurationsdatei für den Client '{$client}' wurde nicht gefunden.");
    }
    $config = parse_ini_file($configPath);
    if ($config === false) {
        throw new Exception("Fehler beim Einlesen der Konfigurationsdatei für '{$client}'.");
    }
    return $config;
}

function getEmailSignature(): string {
    $signaturePath = BASE_PATH_DB . '/Private/Email/signatur/emailSignature.html';
    $signature = file_get_contents($signaturePath);
    if ($signature === false) {
        throw new Exception("Fehler beim Einlesen der Signaturdatei.");
    }
    return $signature;
}

function sendEmail(string $to, string $subject, string $body): array {
    try {
        $mail = new PHPMailer(true);
        $smtpConfig = getSMTPConfig();
        $signature = getEmailSignature();

        $mail->isSMTP();
        $mail->Host = $smtpConfig['Host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtpConfig['Email'];
        $mail->Password = $smtpConfig['Passwort'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $smtpConfig['Port'];

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->setFrom($smtpConfig['Email'], $smtpConfig['Absendername'] ?? 'Feuerwehr Waldems-Reichenbach');
        $mail->addAddress($to);
        $mail->addEmbeddedImage(BASE_PATH_DB . '/Private/Email/signatur/logo.png', 'logo');

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body . $signature;

        $mail->send();
        return [
            'success' => true,
            'message' => 'E-Mail wurde erfolgreich versendet'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'E-Mail konnte nicht gesendet werden: ' . $e->getMessage()
        ];
    }
}
?>
