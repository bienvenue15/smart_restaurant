<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../PHPMailer/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../PHPMailer/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/PHPMailer/src/SMTP.php';

/**
 * Centralized email helper powered by PHPMailer.
 */
class MailService
{
    /**
     * @param array{
     *     to: string|array,
     *     subject: string,
     *     body: string,
     *     alt_body?: string,
     *     is_html?: bool,
     *     cc?: string|array,
     *     bcc?: string|array,
     *     reply_to?: string|array,
     *     attachments?: array<array{path:string,name?:string}>
     * } $message
     */
    public static function send(array $message): array
    {
        if (MAIL_DISABLE_DELIVERY) {
            error_log('[MailService] Delivery disabled. Skipping email: ' . ($message['subject'] ?? 'No subject'));
            return ['status' => true, 'skipped' => true];
        }

        $recipients = self::normalizeRecipients($message['to'] ?? []);
        if (empty($recipients)) {
            return ['status' => false, 'error' => 'No recipients provided'];
        }

        $mailer = new PHPMailer(true);

        try {
            $mailer->CharSet = 'UTF-8';
            $mailer->setFrom(
                MAIL_FROM_ADDRESS,
                MAIL_FROM_NAME ?: 'Smart Restaurant Cloud'
            );

            if (!empty(MAIL_SMTP_HOST)) {
                $mailer->isSMTP();
                $mailer->Host = MAIL_SMTP_HOST;
                $mailer->Port = (int) MAIL_SMTP_PORT;
                $mailer->SMTPAuth = !empty(MAIL_SMTP_USERNAME) || !empty(MAIL_SMTP_PASSWORD);
                if ($mailer->SMTPAuth) {
                    $mailer->Username = MAIL_SMTP_USERNAME;
                    $mailer->Password = MAIL_SMTP_PASSWORD;
                }
                if (!empty(MAIL_SMTP_ENCRYPTION)) {
                    $mailer->SMTPSecure = MAIL_SMTP_ENCRYPTION;
                }
            } else {
                $mailer->isMail();
            }

            foreach ($recipients as $recipient) {
                $mailer->addAddress($recipient['email'], $recipient['name']);
            }

            foreach (self::normalizeRecipients($message['cc'] ?? []) as $cc) {
                $mailer->addCC($cc['email'], $cc['name']);
            }

            foreach (self::normalizeRecipients($message['bcc'] ?? []) as $bcc) {
                $mailer->addBCC($bcc['email'], $bcc['name']);
            }

            foreach (self::normalizeRecipients($message['reply_to'] ?? []) as $reply) {
                $mailer->addReplyTo($reply['email'], $reply['name']);
            }

            if (!empty($message['attachments']) && is_array($message['attachments'])) {
                foreach ($message['attachments'] as $attachment) {
                    if (!empty($attachment['path'])) {
                        $mailer->addAttachment(
                            $attachment['path'],
                            $attachment['name'] ?? basename($attachment['path'])
                        );
                    }
                }
            }

            $mailer->Subject = $message['subject'] ?? 'Smart Restaurant Notification';
            $isHtml = array_key_exists('is_html', $message) ? (bool) $message['is_html'] : true;
            if ($isHtml) {
                $mailer->isHTML(true);
            }
            $mailer->Body = $message['body'] ?? '';
            $mailer->AltBody = $message['alt_body'] ?? strip_tags($mailer->Body);

            $mailer->send();
            return ['status' => true];
        } catch (Exception $e) {
            error_log('[MailService] Error sending email: ' . $mailer->ErrorInfo);
            return ['status' => false, 'error' => $mailer->ErrorInfo];
        }
    }

    private static function normalizeRecipients($value): array
    {
        if (empty($value)) {
            return [];
        }

        $recipients = [];

        if (is_string($value)) {
            $recipients[] = ['email' => trim($value), 'name' => ''];
        } elseif (isset($value['email'])) {
            $recipients[] = [
                'email' => trim($value['email']),
                'name' => $value['name'] ?? ''
            ];
        } elseif (is_array($value)) {
            foreach ($value as $entry) {
                if (is_string($entry)) {
                    $recipients[] = ['email' => trim($entry), 'name' => ''];
                } elseif (isset($entry['email'])) {
                    $recipients[] = [
                        'email' => trim($entry['email']),
                        'name' => $entry['name'] ?? ''
                    ];
                }
            }
        }

        return array_filter($recipients, function ($recipient) {
            return filter_var($recipient['email'], FILTER_VALIDATE_EMAIL);
        });
    }
}

