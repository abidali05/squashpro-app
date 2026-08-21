<?php

namespace App\Http\Requests\Api\V1\Club;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClubDetailsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('facilities')) {
            return;
        }

        $facilities = $this->input('facilities');

        if (is_string($facilities)) {
            $decoded = json_decode($facilities, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $facilities = $decoded;
            } else {
                $facilities = array_values(array_filter(array_map('trim', explode(',', $facilities))));
            }
        }

        $this->merge([
            'facilities' => $facilities,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isLegacy = is_string($this->input('working_hours'));

        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'facilities' => ['sometimes', 'required', 'array'],
            'facilities.*' => ['string', 'max:255'],
            'number_of_courts' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'allow_non_member_booking' => ['sometimes', 'boolean'],
            'non_member_booking_schedule' => ['required_if:allow_non_member_booking,true,1', 'nullable', 'array', 'min:7', 'max:7'],
            'non_member_booking_schedule.*.day' => ['required_with:non_member_booking_schedule', 'string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'non_member_booking_schedule.*.is_available' => ['required_with:non_member_booking_schedule', 'boolean'],
            'non_member_booking_schedule.*.time_ranges' => ['required_if:non_member_booking_schedule.*.is_available,true,1', 'nullable', 'array'],
            'non_member_booking_schedule.*.time_ranges.*.from' => ['required_with:non_member_booking_schedule.*.time_ranges', 'date_format:H:i'],
            'non_member_booking_schedule.*.time_ranges.*.to' => ['required_with:non_member_booking_schedule.*.time_ranges', 'date_format:H:i'],
        ];

        if ($isLegacy) {
            $rules['working_hours'] = ['sometimes', 'required', 'string', 'max:100'];
        } else {
            $rules['working_hours'] = ['sometimes', 'required', 'array', 'min:7', 'max:7'];
            $rules['working_hours.*.day'] = ['required_with:working_hours', 'string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'];
            $rules['working_hours.*.is_open'] = ['required_with:working_hours', 'boolean'];
            $rules['working_hours.*.opens_at'] = ['required_if:working_hours.*.is_open,true,1', 'nullable', 'date_format:H:i'];
            $rules['working_hours.*.closes_at'] = ['required_if:working_hours.*.is_open,true,1', 'nullable', 'date_format:H:i'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $isLegacy = is_string($this->input('working_hours'));
            if ($isLegacy) {
                return;
            }

            $user = $this->user();
            $workingHours = $this->input('working_hours');
            $workingHoursMap = [];

            if ($this->has('working_hours') && is_array($workingHours)) {
                $daysSeen = [];
                foreach ($workingHours as $index => $wh) {
                    if (!isset($wh['day']) || !isset($wh['is_open'])) {
                        continue;
                    }

                    $day = strtolower($wh['day']);

                    if (in_array($day, $daysSeen, true)) {
                        $validator->errors()->add("working_hours.{$index}.day", "Duplicate working hours entry for day: {$day}.");
                    }
                    $daysSeen[] = $day;

                    if ($wh['is_open']) {
                        $opens = $wh['opens_at'] ?? null;
                        $closes = $wh['closes_at'] ?? null;

                        if ($opens && $closes) {
                            if (strcmp($opens, $closes) >= 0) {
                                $validator->errors()->add("working_hours.{$index}.opens_at", "The opening time must be earlier than closing time.");
                            }
                        }
                    }
                    $workingHoursMap[$day] = $wh;
                }
            } else {
                // Fetch existing working hours from database
                $dbWH = \App\Models\ClubWorkingHour::where('club_id', $user->id)->get();
                foreach ($dbWH as $wh) {
                    $workingHoursMap[$wh->day] = [
                        'day' => $wh->day,
                        'is_open' => (bool) $wh->is_open,
                        'opens_at' => $wh->opens_at ? substr((string)$wh->opens_at, 0, 5) : null,
                        'closes_at' => $wh->closes_at ? substr((string)$wh->closes_at, 0, 5) : null,
                    ];
                }
            }

            // Validate non-member booking schedule if enabled
            $allowNonMember = $this->has('allow_non_member_booking') 
                ? filter_var($this->input('allow_non_member_booking'), FILTER_VALIDATE_BOOLEAN)
                : (bool)$user->non_member_booking_allowed;

            $nonMemberSchedule = $this->input('non_member_booking_schedule');

            if ($this->has('non_member_booking_schedule')) {
                if ($allowNonMember) {
                    if (!is_array($nonMemberSchedule)) {
                        $validator->errors()->add('non_member_booking_schedule', 'The non-member booking schedule is required when non-member booking is allowed.');
                        return;
                    }

                    $nonMemberDays = [];
                    foreach ($nonMemberSchedule as $index => $nms) {
                        if (!isset($nms['day'])) continue;
                        $day = strtolower($nms['day']);
                        if (in_array($day, $nonMemberDays, true)) {
                            $validator->errors()->add("non_member_booking_schedule.{$index}.day", "Duplicate non-member booking schedule entry for day: {$day}.");
                        }
                        $nonMemberDays[] = $day;

                        $isAvailable = filter_var($nms['is_available'] ?? false, FILTER_VALIDATE_BOOLEAN);
                        $timeRanges = $nms['time_ranges'] ?? [];

                        $clubDayConfig = $workingHoursMap[$day] ?? null;
                        $isClubOpen = $clubDayConfig ? filter_var($clubDayConfig['is_open'] ?? false, FILTER_VALIDATE_BOOLEAN) : false;

                        if (!$isClubOpen) {
                            if ($isAvailable || !empty($timeRanges)) {
                                $validator->errors()->add("non_member_booking_schedule.{$index}.is_available", "A closed club day ({$day}) cannot contain non-member booking ranges.");
                            }
                            continue;
                        }

                        if ($isAvailable && is_array($timeRanges)) {
                            $sortedRanges = [];
                            foreach ($timeRanges as $rIdx => $range) {
                                $from = $range['from'] ?? null;
                                $to = $range['to'] ?? null;
                                if (!$from || !$to) continue;

                                try {
                                    $fromTime = \Illuminate\Support\Carbon::createFromFormat('H:i', $from);
                                    $toTime = \Illuminate\Support\Carbon::createFromFormat('H:i', $to);

                                    if ($fromTime->greaterThanOrEqualTo($toTime)) {
                                        $validator->errors()->add("non_member_booking_schedule.{$index}.time_ranges.{$rIdx}.from", "The from time must be earlier than the to time.");
                                        continue;
                                    }

                                    if (isset($clubDayConfig['opens_at']) && isset($clubDayConfig['closes_at'])) {
                                        $clubOpensAt = \Illuminate\Support\Carbon::createFromFormat('H:i', $clubDayConfig['opens_at']);
                                        $clubClosesAt = \Illuminate\Support\Carbon::createFromFormat('H:i', $clubDayConfig['closes_at']);

                                        if ($fromTime->lessThan($clubOpensAt) || $toTime->greaterThan($clubClosesAt)) {
                                            $validator->errors()->add("non_member_booking_schedule.{$index}.time_ranges.{$rIdx}.from", "The non-member booking window must fall completely within club working hours ({$clubDayConfig['opens_at']} - {$clubDayConfig['closes_at']}).");
                                        }
                                    }

                                    $sortedRanges[] = [
                                        'from' => $fromTime,
                                        'to' => $toTime,
                                        'rIdx' => $rIdx
                                    ];
                                } catch (\Exception $e) {
                                    // format rules handle this
                                }
                            }

                            usort($sortedRanges, function ($a, $b) {
                                return $a['from'] <=> $b['from'];
                            });

                            for ($i = 0; $i < count($sortedRanges) - 1; $i++) {
                                $current = $sortedRanges[$i];
                                $next = $sortedRanges[$i + 1];
                                if ($current['to']->greaterThan($next['from'])) {
                                    $validator->errors()->add("non_member_booking_schedule.{$index}.time_ranges.{$next['rIdx']}.from", "Non-member booking time ranges must not overlap.");
                                }
                            }
                        }
                    }
                }
            }
        });
    }
}
