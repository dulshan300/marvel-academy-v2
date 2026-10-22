<?php

namespace App\Support;

use App\Mail\ContactMessageReceived;
use App\Mail\SessionJoiningReminder;
use App\Mail\SessionPreparationReminder;
use App\Models\Child;
use App\Models\ContactMessage;
use App\Models\LiveSession;
use App\Models\QuizAttempt;
use App\Notifications\QuizResultNotification;

/**
 * Renders the application's email templates against the latest real records
 * so staff can check how they look without sending anything.
 */
class EmailPreviews
{
    /**
     * @return array<string, string>
     */
    public static function templates(): array
    {
        return [
            'session-preparation' => '7-day session reminder (preparation)',
            'session-joining' => '2-day session reminder (joining details)',
            'quiz-result' => 'Quiz result for parents',
            'contact-message' => 'Contact form message (to staff)',
        ];
    }

    /**
     * Render a template, or return null when there is no data to preview it with.
     */
    public function render(string $template): ?string
    {
        return match ($template) {
            'session-preparation' => $this->sessionReminder(SessionPreparationReminder::class),
            'session-joining' => $this->sessionReminder(SessionJoiningReminder::class),
            'quiz-result' => $this->quizResult(),
            'contact-message' => $this->contactMessage(),
            default => null,
        };
    }

    /**
     * @param  class-string<SessionPreparationReminder|SessionJoiningReminder>  $mailable
     */
    private function sessionReminder(string $mailable): ?string
    {
        $session = LiveSession::with('course', 'teacher', 'topic')->latest('starts_at')->first();

        if ($session === null) {
            return null;
        }

        $child = Child::with('parent', 'student')
            ->whereHas('enrolments', fn ($query) => $query->where('course_id', $session->course_id))
            ->first()
            ?? Child::with('parent', 'student')->first();

        return $child ? (new $mailable($session, $child))->render() : null;
    }

    private function quizResult(): ?string
    {
        $attempt = QuizAttempt::with('child.parent', 'quiz.topic.subject.course')
            ->whereNotNull('completed_at')
            ->latest()
            ->first();

        if ($attempt === null) {
            return null;
        }

        return (string) (new QuizResultNotification($attempt))->toMail($attempt->child->parent)->render();
    }

    private function contactMessage(): ?string
    {
        $message = ContactMessage::latest()->first() ?? new ContactMessage([
            'name' => 'Sample Parent',
            'email' => 'parent@example.com',
            'phone' => '07700 900000',
            'subject' => 'Question about GCSE Maths',
            'message' => 'Hello, could you tell me more about the Year 10 course?',
        ]);

        return (new ContactMessageReceived($message))->render();
    }
}
