<?php

namespace App\Notifications;

use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuizResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public QuizAttempt $attempt) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $attempt = $this->attempt->loadMissing('child', 'quiz.topic.subject.course');
        $child = $attempt->child;
        $course = $attempt->quiz->topic->subject->course;

        return (new MailMessage)
            ->subject("{$child->first_name} scored {$attempt->percentage}% on {$attempt->quiz->title}")
            ->markdown('mail.quiz-result', [
                'parentName' => $notifiable->name,
                'attempt' => $attempt,
                'child' => $child,
                'courseName' => $course->key_stage->label().' '.$course->title,
                'topicTitle' => $attempt->quiz->topic->title,
            ]);
    }
}
