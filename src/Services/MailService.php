<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private array $config;

    public function __construct()
    {
        $this->config = [
            'host' => $_ENV['SMTP_HOST'] ?? '',
            'port' => $_ENV['SMTP_PORT'] ?? 587,
            'user' => $_ENV['SMTP_USER'] ?? '',
            'pass' => $_ENV['SMTP_PASS'] ?? '',
            'from' => $_ENV['SMTP_FROM'] ?? 'no-reply@ai-rag.local',
            'fromName' => $_ENV['SMTP_FROM_NAME'] ?? 'AI RAG Assistant'
        ];
    }

    public function sendPasswordReset(string $toEmail, string $token): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['user'];
            $mail->Password   = $this->config['pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->config['port'];

            // Recipients
            $mail->setFrom($this->config['from'], $this->config['fromName']);
            $mail->addAddress($toEmail);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Password Reset Token';
            
            $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                    <h2 style='color: #6366f1;'>AI RAG Assistant</h2>
                    <p>Hello,</p>
                    <p>You requested a password reset. Please use the following 6-digit token to complete the process:</p>
                    <div style='background: #f8fafc; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                        <span style='font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #1e293b;'>{$token}</span>
                    </div>
                    <p>This token will expire in 1 hour.</p>
                    <p>If you did not request this, please ignore this email.</p>
                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #94a3b8;'>This is an automated message, please do not reply.</p>
                </div>
            ";

            $mail->Body = $body;
            $mail->AltBody = "Your password reset token is: {$token}. It expires in 1 hour.";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail Error: " . $mail->ErrorInfo);
            return false;
        }
    }
}
