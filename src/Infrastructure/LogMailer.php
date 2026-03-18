<?php

declare(strict_types=1);

namespace App\Infrastructure\Mailer;

use App\Domain\Shared\MailerInterface;
use DateTimeImmutable;
use RuntimeException;

final class LogMailer implements MailerInterface
{
    public function __construct(private readonly string $logPath) {}

    public function send(string $to, string $subject, string $body): void
    {
        $entry = sprintf(
            "[%s] TO: %s | SUBJECT: %s\n%s\n%s\n",
            (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            $to,
            $subject,
            $body,
            str_repeat('-', 80),
        );

        $result = file_put_contents($this->logPath, $entry, FILE_APPEND | LOCK_EX);

        if ($result === false) {
            throw new RuntimeException(
                sprintf('LogMailer failed to write to "%s".', $this->logPath)
            );
        }
    }
}
