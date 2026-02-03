<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $pdo;
    private $mailer;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->setupMailer();
    }

    private function setupMailer()
    {
        $this->mailer = new PHPMailer(true);
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = defined('SMTP_HOST') ? SMTP_HOST : (getenv('SMTP_HOST') ?: 'smtp.example.com');
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = defined('SMTP_USER') ? SMTP_USER : (getenv('SMTP_USER') ?: 'user@example.com');
            $this->mailer->Password = defined('SMTP_PASS') ? SMTP_PASS : (getenv('SMTP_PASS') ?: 'secret');
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = defined('SMTP_PORT') ? (int) SMTP_PORT : (int) (getenv('SMTP_PORT') ?: 587);
            $this->mailer->CharSet = 'UTF-8';

            // Bypass SSL certificate verification issues (common on shared hostings)
            $this->mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Sender
            $from = defined('SMTP_FROM') ? SMTP_FROM : (getenv('SMTP_FROM') ?: 'noreply@practicalia.es');
            $name = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : (getenv('SMTP_FROM_NAME') ?: 'Practicalia');
            $this->mailer->setFrom($from, $name);
        } catch (Exception $e) {
            // Handle setup errors if needed
        }
    }

    public function sendEmail($to, $subject, $body, $attachments = [], $replyTo = null, $replyToName = '')
    {
        try {
            $this->mailer->addAddress($to);
            if ($replyTo) {
                $this->mailer->addReplyTo($replyTo, $replyToName);
            }
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            // Plain text version
            $this->mailer->AltBody = strip_tags($body);

            foreach ($attachments as $att) {
                // $this->mailer->addAttachment($att);
            }

            $this->mailer->send();
            $this->mailer->clearAddresses();
            $this->mailer->clearReplyTos();
            return ['success' => true, 'error' => ''];
        } catch (Exception $e) {
            $this->mailer->clearAddresses();
            $this->mailer->clearReplyTos();
            $error = "Mailer Error: {$this->mailer->ErrorInfo}";
            error_log($error);
            return ['success' => false, 'error' => $error];
        }
    }

    public function logEmailContact($empresaId, $profesorId, $subject, $body, $success)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO contactos_empresa 
            (empresa_id, usuario_id, canal, asunto, resumen, resultado, created_at) 
            VALUES (?, ?, 'email', ?, ?, ?, NOW())
        ");

        $resumen = "Enviado masivo: " . substr(strip_tags($body), 0, 100) . "...";
        $resultado = $success ? "Enviado correctamente" : "Error al enviar";

        $stmt->execute([$empresaId, $profesorId, $subject, $resumen, $resultado]);
    }
}
