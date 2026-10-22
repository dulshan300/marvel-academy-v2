<?php

namespace App\Listeners;

use App\Models\EmailLog;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;

class RecordSentEmail
{
    /**
     * Keep a log of every email the application sends.
     */
    public function handle(MessageSent $event): void
    {
        $email = $event->message;
        $recipients = collect([...$email->getTo(), ...$email->getCc(), ...$email->getBcc()])
            ->map(fn (Address $address) => $address->getAddress())
            ->unique()
            ->implode(', ');

        EmailLog::create([
            'mailable' => $event->data['__laravel_mailable'] ?? $event->data['__laravel_notification'] ?? null,
            'subject' => mb_substr((string) $email->getSubject(), 0, 255),
            'recipients' => $recipients,
            'message_id' => $event->sent->getMessageId(),
            'sent_at' => now(),
        ]);
    }
}
