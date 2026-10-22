<?php

namespace App\Console\Commands;

use App\Actions\Sessions\SendSessionReminder;
use App\Enums\LiveSessionStatus;
use App\Models\LiveSession;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sessions:send-reminders')]
#[Description('Email parents 7 days (with preparation materials) and 2 days (with joining details) before live sessions')]
class SendSessionReminders extends Command
{
    public function handle(SendSessionReminder $sendReminder): int
    {
        $now = now();

        // 7-day reminder: sessions starting in 2 to 7 days. Sessions booked at
        // short notice skip straight to the 2-day reminder.
        $prepSessions = LiveSession::query()
            ->where('status', LiveSessionStatus::Scheduled)
            ->whereNull('seven_day_reminder_sent_at')
            ->where('starts_at', '>', $now->copy()->addDays(2))
            ->where('starts_at', '<=', $now->copy()->addDays(7))
            ->get();

        $joinSessions = LiveSession::query()
            ->where('status', LiveSessionStatus::Scheduled)
            ->whereNull('two_day_reminder_sent_at')
            ->where('starts_at', '>', $now)
            ->where('starts_at', '<=', $now->copy()->addDays(2))
            ->get();

        $queued = 0;

        foreach ($prepSessions as $session) {
            $queued += (int) $sendReminder->handle($session, SendSessionReminder::SEVEN_DAY);
        }

        foreach ($joinSessions as $session) {
            $queued += (int) $sendReminder->handle($session, SendSessionReminder::TWO_DAY);
        }

        $this->components->info("Queued {$queued} reminder ".str('email')->plural($queued)." for {$prepSessions->count()} 7-day and {$joinSessions->count()} 2-day sessions.");

        return self::SUCCESS;
    }
}
