<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ClubDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->club_name ?? $this->name,
            'club_logo' => $this->logoUrl($this->club_logo),
            'address' => $this->address,
            'working_hours' => (function() {
                $dbWorkingHours = \App\Models\ClubWorkingHour::where('club_id', $this->id)->get();
                if ($dbWorkingHours->isNotEmpty()) {
                    return $dbWorkingHours->map(function ($wh) {
                        return [
                            'day' => $wh->day,
                            'is_open' => (bool) $wh->is_open,
                            'opens_at' => $wh->opens_at ? substr((string)$wh->opens_at, 0, 5) : null,
                            'closes_at' => $wh->closes_at ? substr((string)$wh->closes_at, 0, 5) : null,
                        ];
                    })->all();
                }
                
                $start = null;
                $end = null;
                if ($this->working_hours && preg_match('/(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/', $this->working_hours, $matches)) {
                    $start = $matches[1];
                    $end = $matches[2];
                }
                $workingHoursList = [];
                $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                foreach ($daysOfWeek as $d) {
                    $workingHoursList[] = [
                        'day' => $d,
                        'is_open' => $start && $end ? true : false,
                        'opens_at' => $start,
                        'closes_at' => $end,
                    ];
                }
                return $workingHoursList;
            })(),
            'facilities' => $this->facilities ?? [],
            'number_of_courts' => $this->number_of_courts ?? 0,
            
            // MaxSquash non-member booking updates
            'allow_non_member_booking' => (bool) $this->non_member_booking_allowed,
            'non_member_booking_start_time' => $this->non_member_booking_allowed && $this->non_member_booking_start_time ? substr((string)$this->non_member_booking_start_time, 0, 5) : null,
            'non_member_booking_end_time' => $this->non_member_booking_allowed && $this->non_member_booking_end_time ? substr((string)$this->non_member_booking_end_time, 0, 5) : null,
            'non_member_booking_schedule' => (function() {
                if (!$this->non_member_booking_allowed) {
                    return null;
                }
                $dbWindows = \App\Models\ClubNonMemberWindow::where('club_id', $this->id)->get();
                if ($dbWindows->isEmpty()) {
                    return null;
                }
                return $dbWindows->map(function ($win) {
                    return [
                        'day' => $win->day,
                        'is_available' => (bool) $win->is_available,
                        'from_time' => $win->from_time ? substr((string)$win->from_time, 0, 5) : null,
                        'to_time' => $win->to_time ? substr((string)$win->to_time, 0, 5) : null,
                    ];
                })->all();
            })(),
        ];
    }

    private function logoUrl(?string $path): ?string
    {
        return app_image_url($path);
    }
}
