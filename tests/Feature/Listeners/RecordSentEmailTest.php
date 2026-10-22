<?php

namespace Tests\Feature\Listeners;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\EmailLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RecordSentEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_sent_emails_are_logged_with_their_mailable_and_recipients(): void
    {
        $message = ContactMessage::factory()->create(['subject' => 'Year 9 science']);

        Mail::to('office@marvelacademy.test')->cc('head@marvelacademy.test')->send(new ContactMessageReceived($message));

        $log = EmailLog::sole();
        $this->assertSame(ContactMessageReceived::class, $log->mailable);
        $this->assertSame('office@marvelacademy.test, head@marvelacademy.test', $log->recipients);
        $this->assertStringContainsString('Year 9 science', $log->subject);
        $this->assertNotNull($log->message_id);
    }
}
