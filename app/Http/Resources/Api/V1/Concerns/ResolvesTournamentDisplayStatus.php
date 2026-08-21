<?php

namespace App\Http\Resources\Api\V1\Concerns;

use App\Models\Tournament;
use Carbon\Carbon;

/**
 * @mixin \Illuminate\Http\Resources\Json\JsonResource
 * @property Tournament $resource
 */
trait ResolvesTournamentDisplayStatus
{
    private function resolveDisplayStatus(): string
    {
        /** @var Tournament $tournament */
        $tournament = $this->resource;

        // cancelled is always terminal — DB is authoritative
        if ($tournament->status === 'cancelled') {
            return 'cancelled';
        }

        $today = Carbon::today('Asia/Karachi');

        // Past end_date → completed (overrides any DB status including 'open', 'full', etc.)
        if ($tournament->end_date && $today->gt($tournament->end_date)) {
            return 'completed';
        }

        // Currently running: started but not yet ended
        if (
            $tournament->start_date && $tournament->end_date
            && $today->gte($tournament->start_date)
            && $today->lte($tournament->end_date)
        ) {
            return 'ongoing';
        }

        // Registration still open: deadline is today or in the future
        if ($tournament->registration_deadline && $today->lte($tournament->registration_deadline)) {
            return 'open';
        }

        // Deadline passed but tournament hasn't started yet
        if ($tournament->start_date && $today->lt($tournament->start_date)) {
            return 'upcoming';
        }

        // Fallback to raw DB status
        return $tournament->status;
    }

    private function normalizeNumber(mixed $value): int|float
    {
        $numeric = (float) ($value ?? 0);

        return $numeric == (int) $numeric ? (int) $numeric : $numeric;
    }
}
