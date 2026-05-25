<?php
// This script will not be accessed directly.
// It uses the PHPMailer library we installed.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// A function to send an email with a certificate attached.
function send_certificate_email($recipient_email, $recipient_name, $subject, $body, $attachment_path)
{

    $mail = new PHPMailer(true);

    try {
        // --- IMPORTANT SERVER SETTINGS ---
        // You MUST configure this section with your own email provider's details.
        // This example uses Gmail.

        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp.gmail.com';                 // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                             // Enable SMTP authentication
        $mail->Username = 'nandhanaveengasc41@gmail.com';   // SMTP username (your full Gmail address)
        $mail->Password = 'aydp fpdf cjna tigz';        // SMTP password (an App Password, NOT your regular password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;    // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587;                              // TCP port to connect to

        // Bypass local SSL issues in XAMPP
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // --- Recipients ---
        $mail->setFrom('nandhanaveengasc41@gmail.com', 'Gobi Arts & Science College Events');
        $mail->addAddress($recipient_email, $recipient_name); // Add a recipient

        // --- Attachments ---
        $mail->addAttachment($attachment_path);         // Add attachments

        // --- Content ---
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body); // Plain text version for non-HTML mail clients

        $mail->send();
        return true; // Email sent successfully
    } catch (Exception $e) {
        // You can log this error for debugging
        // error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false; // Email failed to send
    }
}
?>