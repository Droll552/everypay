<?php

declare(strict_types=1);

namespace App\Domain\Shared;

interface MailerInterface
{
    /**
     * Send an email message.
     *
     * @param string   $to      Recipient email address.
     * @param string   $subject Email subject line.
     * @param string   $body    Plain-text body.
     */
    public function send(string $to, string $subject, string $body): void;
}
