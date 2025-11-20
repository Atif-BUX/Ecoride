<?php
// Simple email stub that logs notifications to logs/mail.log for demo/testing.
class Email
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $dir = __DIR__ . '/../logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $line = sprintf(
            "[%s] TO:%s | SUBJECT:%s | %s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            str_replace(["\r", "\n"], [' ', ' '], $body)
        );
        @file_put_contents($dir . '/mail.log', $line, FILE_APPEND);
        // Also mirror to PHP error_log for convenience
        error_log('[MAIL] ' . trim($line));
        return true;
    }
}

