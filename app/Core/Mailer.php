<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

class Mailer
{
    private const SOCKET_TIMEOUT = 10;
    private const SMTP_LINE_BUFFER = 515; // 512 + CRLF + continuation safety

    public static function send(string $to, string $subject, string $body, string $replyTo = ''): bool
    {
        $mailDriver = trim((string) Setting::get('mail_driver', 'mail'));

        $fromName = trim((string) Setting::get('smtp_from_name', ''));
        if ($fromName === '') {
            $fromName = (string) Setting::get('site_title', 'Photography Portfolio');
        }

        $fromEmail = trim((string) Setting::get('smtp_from_email', ''));
        if ($fromEmail === '') {
            $fromEmail = trim((string) Setting::get('contact_email', ''));
        }

        if ($mailDriver !== 'smtp') {
            return self::sendWithMail($to, $subject, $body, $replyTo, $fromName, $fromEmail);
        }

        $smtpHost = trim((string) Setting::get('smtp_host', ''));
        $smtpPort = (int) trim((string) Setting::get('smtp_port', '587'));
        $smtpEncryption = trim((string) Setting::get('smtp_encryption', 'tls'));
        $smtpUsername = trim((string) Setting::get('smtp_username', ''));
        $smtpPasswordEncrypted = (string) Setting::get('smtp_password', '');
        $smtpPassword = Encryption::decrypt($smtpPasswordEncrypted);

        if ($smtpPasswordEncrypted !== '' && $smtpPassword === '') {
            error_log('Mailer: SMTP password decryption failed.');
            return false;
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
        $safeSubject = self::cleanHeader($subject);
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
        if ($host === '') {
            error_log('Mailer: SMTP host is not configured.');
            return false;
        }

        if ($fromEmail === '') {
            error_log('Mailer: SMTP from email address is not configured.');
            return false;
        }

        if ($username === '' || $password === '') {
            error_log('Mailer: SMTP host configured but username/password are missing.');
            return false;
        }

        $transport = strtolower($encryption) === 'ssl' ? 'ssl://' . $host : $host;
        $socket = @stream_socket_client(
            $transport . ':' . $port,
            $errno,
            $errstr,
            self::SOCKET_TIMEOUT,
            STREAM_CLIENT_CONNECT
        );

        if (!is_resource($socket)) {
            error_log(sprintf('Mailer: failed to connect to SMTP server (%s:%d): %s (%d)', $host, $port, $errstr, $errno));
            return false;
        }

        stream_set_timeout($socket, self::SOCKET_TIMEOUT);

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
        $payload = $message . "\r\n";
        $written = fwrite($socket, $payload);
        if ($written === false || $written !== strlen($payload)) {
            error_log('Mailer: failed to write message body to SMTP socket.');
            fclose($socket);
            return false;
        }

        if (!self::expectCode($socket, [250], 'DATA body')) {
            fclose($socket);
            return false;
        }

        self::sendCommand($socket, 'QUIT', [221]);
        fclose($socket);
        return true;
    }

    /**
     * @param resource $socket
     */
    private static function sendCommand($socket, string $command, array $expectedCodes): bool
    {
        fwrite($socket, $command . "\r\n");
        return self::expectCode($socket, $expectedCodes, $command);
    }

    /**
     * @param resource $socket
     */
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

    /**
     * @param resource $socket
     */
    private static function readResponse($socket): string
    {
        $response = '';
        while (($line = fgets($socket, self::SMTP_LINE_BUFFER)) !== false) {
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
        if ($dotStuffed === null) {
            $dotStuffed = $normalized;
        }

        return str_replace("\n", "\r\n", $dotStuffed);
    }
}
