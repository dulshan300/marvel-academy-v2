<?php

namespace App\Actions\Sessions;

use App\Models\Child;
use App\Models\LiveSession;
use App\Models\SessionAttendance;

class RecordAttendance
{
    /**
     * Log the child's first join of a live session; later joins are ignored.
     */
    public function handle(Child $child, LiveSession $session): SessionAttendance
    {
        return $session->attendances()->firstOrCreate(
            ['child_id' => $child->id],
            ['joined_at' => now()],
        );
    }
}
