<?php

namespace App\Actions\Sessions;

use App\Enums\EnrolmentStatus;
use App\Mail\SessionJoiningReminder;
use App\Mail\SessionPreparationReminder;
use App\Models\Child;
use App\Models\LiveSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class SendSessionReminder
{
    public const SEVEN_DAY = 'seven_day';

    public const TWO_DAY = 'two_day';

    /**
     * Claim the reminder for the session and queue one email per eligible
     * child. Returns the number of emails queued, or null when another run
     * already claimed this reminder.
     */
    public function handle(LiveSession $session, string $reminder): ?int
    {
        $column = match ($reminder) {
            self::SEVEN_DAY => 'seven_day_reminder_sent_at',
            self::TWO_DAY => 'two_day_reminder_sent_at',
            default => throw new InvalidArgumentException("Unknown reminder [{$reminder}]."),
        };

        $claimed = LiveSession::whereKey($session->id)->whereNull($column)->update([$column => now()]);

        if ($claimed === 0) {
            return null;
        }

        $session->loadMissing('course', 'teacher', 'topic');
        $recipients = $this->recipients($session);

        foreach ($recipients as $child) {
            $mailable = $reminder === self::SEVEN_DAY
                ? new SessionPreparationReminder($session, $child)
                : new SessionJoiningReminder($session, $child);

            Mail::to($child->parent)->queue($mailable);
        }

        return $recipients->count();
    }

    /**
     * Children actively enrolled on the course whose parent's plan is valid.
     *
     * @return Collection<int, Child>
     */
    public function recipients(LiveSession $session): Collection
    {
        return Child::query()
            ->whereHas('enrolments', fn ($query) => $query
                ->where('course_id', $session->course_id)
                ->where('status', EnrolmentStatus::Active))
            ->whereHas('parent', fn ($query) => $query->where('is_active', true))
            ->with(['parent.subscriptions', 'student'])
            ->get()
            ->filter(fn (Child $child) => $child->hasActiveSubscription())
            ->values();
    }
}
