<?php

namespace App\Http\Requests\Api\V1\Club;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'price_per_hour' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,maintenance'],
            'maintenance_note' => ['nullable', 'string'],
            
            // New Day-Wise price slots fields
            'slots' => ['nullable', 'array'],
            'slots.*.day' => ['required_with:slots', 'string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'slots.*.start_time' => ['required_with:slots', 'date_format:H:i'],
            'slots.*.end_time' => ['required_with:slots', 'date_format:H:i'],
            'slots.*.price' => ['required_with:slots', 'numeric', 'gt:0'],
            'slots.*.is_available' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $slots = $this->input('slots', []);

            if (empty($slots)) {
                return;
            }

            $slotsByDay = [];
            foreach ($slots as $index => $slot) {
                if (!isset($slot['day']) || !isset($slot['start_time']) || !isset($slot['end_time'])) {
                    continue;
                }

                $day = strtolower($slot['day']);
                $start = $slot['start_time'];
                $end = $slot['end_time'];

                // 1. Midnight / order check
                if (strcmp($start, $end) >= 0) {
                    $validator->errors()->add("slots.{$index}.end_time", "Slot end time must be after start time and cannot cross midnight.");
                    continue;
                }

                // 2. Working hours bounds check
                $workingHour = \App\Models\ClubWorkingHour::where('club_id', $user->id)
                    ->where('day', $day)
                    ->first();

                if (!$workingHour || !$workingHour->is_open) {
                    $validator->errors()->add("slots.{$index}.day", "The club is closed on {$day}. No slots can be configured.");
                    continue;
                }

                $opensAt = substr((string)$workingHour->opens_at, 0, 5);
                $closesAt = substr((string)$workingHour->closes_at, 0, 5);

                if (strcmp($start, $opensAt) < 0 || strcmp($end, $closesAt) > 0) {
                    $validator->errors()->add("slots.{$index}.start_time", "Slot times ({$start} - {$end}) must lie strictly within the club's working hours ({$opensAt} - {$closesAt}) on {$day}.");
                    continue;
                }

                $slotsByDay[$day][] = [
                    'index' => $index,
                    'start' => $start,
                    'end' => $end,
                ];
            }

            // 3. Day-Wise overlap check
            foreach ($slotsByDay as $day => $daySlots) {
                usort($daySlots, fn($a, $b) => strcmp($a['start'], $b['start']));

                for ($i = 1; $i < count($daySlots); $i++) {
                    $prev = $daySlots[$i - 1];
                    $curr = $daySlots[$i];

                    if (strcmp($curr['start'], $prev['end']) < 0) {
                        $validator->errors()->add("slots.{$curr['index']}.start_time", "Time slot overlap detected on {$day} with slot {$prev['start']} - {$prev['end']}.");
                    }
                }
            }
        });
    }
}
