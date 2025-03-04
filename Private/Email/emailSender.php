<?php
// Stammverzeichnis definieren
$stepsBack = 2;
$basePath = __DIR__;
for ($i = 0; $i < $stepsBack; $i++) {
    $basePath = dirname($basePath);
}
define('BASE_PATH', $basePath);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require BASE_PATH . '/Private/Email/phpmailer/src/Exception.php';
require BASE_PATH . '/Private/Email/phpmailer/src/PHPMailer.php';
require BASE_PATH . '/Private/Email/phpmailer/src/SMTP.php';

function getSMTPConfig() {
    $config = parse_ini_file(BASE_PATH . '/Private/Initializations/smtpEmail_Config.ini');
    if ($config === false) {
        throw new Exception("Fehler beim Einlesen der Konfigurationsdatei.");
    }
    return $config;
}

function getEmailSignature() {
    $signature = file_get_contents(BASE_PATH . '/Private/Email/signatur/emailSignature.html');
    if ($signature === false) {
        throw new Exception("Fehler beim Einlesen der Signaturdatei.");
    }
    return $signature;
}

function sendEmail($to, $subject, $body) {
    try {
        $mail = new PHPMailer(true);
        $smtpConfig = getSMTPConfig();
        $signature = getEmailSignature();

        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtpConfig['Host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtpConfig['Email'];
        $mail->Password = $smtpConfig['Passwort'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $smtpConfig['Port'];

        // Charset settings
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Recipients
        $mail->setFrom($smtpConfig['Email'], 'Feuerwehr Waldems-Reichenbach');
        $mail->addAddress($to);

        // Attachments
        $mail->addEmbeddedImage(BASE_PATH . '/Private/Email/signatur/logo.png', 'logo');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body . $signature;

        $mail->send();
        return [
            'success' => true,
            'message' => 'E-Mail wurde erfolgreich versendet'
        ];
    } catch (Exception $e) {
        error_log("E-Mail-Versand fehlgeschlagen: " . $mail->ErrorInfo);
        return [
            'success' => false,
            'message' => 'E-Mail konnte nicht gesendet werden',
            'error' => $mail->ErrorInfo
        ];
    }
}
?>
