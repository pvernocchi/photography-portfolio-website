<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

class Mailer
{
    public static function send(string $to, string $subject, string $body, string $replyTo = ''): bool
    {
        $smtpHost = trim((string) Setting::get('smtp_host', ''));
        $smtpPort = (int) trim((string) Setting::get('smtp_port', '587'));
        $smtpEncryption = trim((string) Setting::get('smtp_encryption', 'tls'));
        $smtpUsername = trim((string) Setting::get('smtp_username', ''));
        $smtpPassword = Encryption::decrypt((string) Setting::get('smtp_password', ''));

        $fromName = trim((string) Setting::get('smtp_from_name', ''));
        if ($fromName === '') {
            $fromName = (string) Setting::get('site_title', 'Photography Portfolio');
        }

        $fromEmail = trim((string) Setting::get('smtp_from_email', ''));
        if ($fromEmail === '') {
            $fromEmail = trim((string) Setting::get('contact_email', ''));
        }

        if ($smtpHost === '') {
            return self::sendWithMail($to, $subject, $body, $replyTo, $fromName, $fromEmail);
        }

        if ($smtpPort <= 0) {
            $smtpPort = 587;
        }

        return self::sendWithSmtp(
            $smtpHost,
            $smtpPort,
            $smtpEncryption,
            $smtpUsername,
            $smtpPassword,
            $fromName,
            $fromEmail,
            $to,
            $subject,
            $body,
            $replyTo
        );
    }

    private static function sendWithMail(
        string $to,
        string $subject,
        string $body,
        string $replyTo,
        string $fromName,
        string $fromEmail
    ): bool {
        $safeSubject = self::cleanHeader('' . $subject);
        $safeFromName = self::cleanHeader($fromName);
        $safeFromEmail = self::cleanHeader($fromEmail);
        $safeReplyTo = self::cleanHeader($replyTo);

        $headers = "From: {$safeFromName} <{$safeFromEmail}>\r\n";
        if ($safeReplyTo !== '') {
            $headers .= "Reply-To: {$safeReplyTo}\r\n";
        }
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8";

        $sent = mail($to, $safeSubject, $body, $headers);
        if (!$sent) {
            error_log('Mailer: native mail() failed.');
        }

        return $sent;
    }

    private static function sendWithSmtp(
        string $host,
        int $port,
        string $encryption,
        string $username,
        string $password,
        string $fromName,
        string $fromEmail,
        string $to,
        string $subject,
        string $body,
        string $replyTo
    ): bool {
        if ($username === '' || $password === '') {
            error_log('Mailer: SMTP host configured but username/password are missing.');
            return false;
        }

        $transport = strtolower($encryption) === 'ssl' ? 'ssl://' . $host : $host;
        $socket = @stream_socket_client(
            $transport . ':' . $port,
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT
        );

        if (!is_resource($socket)) {
            error_log(sprintf('Mailer: failed to connect to SMTP server (%s:%d): %s (%d)', $host, $port, $errstr, $errno));
            return false;
        }

        stream_set_timeout($socket, 10);

        if (!self::expectCode($socket, [220])) {
            fclose($socket);
            return false;
        }

        $ehloDomain = (string) parse_url((string) app_config('app.url', ''), PHP_URL_HOST);
        if ($ehloDomain === '') {
            $ehloDomain = 'localhost';
        }

        if (!self::sendCommand($socket, 'EHLO ' . $ehloDomain, [250])) {
            fclose($socket);
            return false;
        }

        if (strtolower($encryption) === 'tls') {
            if (!self::sendCommand($socket, 'STARTTLS', [220])) {
                fclose($socket);
                return false;
            }

            $tlsEnabled = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($tlsEnabled !== true) {
                error_log('Mailer: failed to enable STARTTLS.');
                fclose($socket);
                return false;
            }

            if (!self::sendCommand($socket, 'EHLO ' . $ehloDomain, [250])) {
                fclose($socket);
                return false;
            }
        }

        if (!self::sendCommand($socket, 'AUTH LOGIN', [334])) {
            fclose($socket);
            return false;
        }

        if (!self::sendCommand($socket, base64_encode($username), [334])) {
            fclose($socket);
            return false;
        }

        if (!self::sendCommand($socket, base64_encode($password), [235])) {
            fclose($socket);
            return false;
        }

        $safeFromEmail = self::cleanHeader($fromEmail);
        $safeTo = self::cleanHeader($to);
        if (!self::sendCommand($socket, 'MAIL FROM:<' . $safeFromEmail . '>', [250])) {
            fclose($socket);
            return false;
        }

        if (!self::sendCommand($socket, 'RCPT TO:<' . $safeTo . '>', [250, 251])) {
            fclose($socket);
            return false;
        }

        if (!self::sendCommand($socket, 'DATA', [354])) {
            fclose($socket);
            return false;
        }

        $headers = [
            'From: ' . self::cleanHeader($fromName) . ' <' . $safeFromEmail . '>',
            'To: <' . $safeTo . '>',
            'Subject: ' . self::cleanHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion(),
        ];
        $safeReplyTo = self::cleanHeader($replyTo);
        if ($safeReplyTo !== '') {
            $headers[] = 'Reply-To: ' . $safeReplyTo;
        }

        $message = implode("\r\n", $headers) . "\r\n\r\n" . self::prepareBody($body) . "\r\n.";
        fwrite($socket, $message . "\r\n");

        if (!self::expectCode($socket, [250])) {
            fclose($socket);
            return false;
        }

        self::sendCommand($socket, 'QUIT', [221]);
        fclose($socket);
        return true;
    }

    private static function sendCommand($socket, string $command, array $expectedCodes): bool
    {
        fwrite($socket, $command . "\r\n");
        return self::expectCode($socket, $expectedCodes, $command);
    }

    private static function expectCode($socket, array $expectedCodes, string $context = ''): bool
    {
        $response = self::readResponse($socket);
        if ($response === '') {
            error_log('Mailer: empty SMTP response' . ($context !== '' ? ' after ' . $context : '') . '.');
            return false;
        }

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            error_log(sprintf('Mailer: SMTP command failed%s. Response: %s', $context !== '' ? ' (' . $context . ')' : '', trim($response)));
            return false;
        }

        return true;
    }

    private static function readResponse($socket): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        return $response;
    }

    private static function cleanHeader(string $value): string
    {
        return str_replace(["\r", "\n"], '', trim($value));
    }

    private static function prepareBody(string $body): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $body);
        $dotStuffed = preg_replace('/^\./m', '..', $normalized);
        return str_replace("\n", "\r\n", (string) $dotStuffed);
    }
}
