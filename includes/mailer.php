
<?php
declare(strict_types=1);

function send_site_email(
    string $recipient,
    string $subject,
    string $message
): bool {
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $host = parse_url(SITE_URL, PHP_URL_HOST);

    if (!$host) {
        return false;
    }

    $sender = 'no-reply@' . $host;

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: EUROLINK V5 <' . $sender . '>',
        'Reply-To: ' . $sender,
        'X-Mailer: PHP/' . PHP_VERSION
    ];

    return mail(
        $recipient,
        '=?UTF-8?B?' . base64_encode($subject) . '?=',
        $message,
        implode("\r\n", $headers)
    );
}
