<?php

namespace App\Mail;

use App\Models\Child;
use App\Models\LiveSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent two days before a live session with joining instructions and the stream link.
 */
class SessionJoiningReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public LiveSession $session, public Child $child) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "In 2 days: {$this->child->first_name}'s {$this->session->course->title} live lesson",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.session-joining',
        );
    }
}
