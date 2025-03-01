<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '/mnt/rid/08/69/543220869/htdocs/Private/Email/phpmailer/src/Exception.php';
require '/mnt/rid/08/69/543220869/htdocs/Private/Email/phpmailer/src/PHPMailer.php';
require '/mnt/rid/08/69/543220869/htdocs/Private/Email/phpmailer/src/SMTP.php';

function getSMTPConfig() {
    $config = parse_ini_file('/mnt/rid/08/69/543220869/htdocs/Private/Initializations/smtpEmail_Config.ini');
    if ($config === false) {
        throw new Exception("Fehler beim Einlesen der Konfigurationsdatei.");
    }
    return $config;
}

function getEmailSignature() {
    $signature = file_get_contents('/mnt/rid/08/69/543220869/htdocs/Private/Email/signatur/emailSignature.html');
    if ($signature === false) {
        throw new Exception("Fehler beim Einlesen der Signaturdatei.");
    }
    return $signature;
}

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    $smtpConfig = getSMTPConfig();
    $signature = getEmailSignature();
    
    try {
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
        $mail->addEmbeddedImage('/mnt/rid/08/69/543220869/htdocs/Private/Email/signatur/logo.png', 'logo');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body . $signature;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Die E-Mail konnte nicht gesendet werden. Fehler: {$mail->ErrorInfo}";
    }
}
?>
