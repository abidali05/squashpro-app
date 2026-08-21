<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Storage;

class ClubProfileCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        $club = $this->collection->first();

        if (! $club) {
            return [];
        }

        return [
            'id' => $club->id,
            'name' => $club->club_name ?? $club->name,
            'club_logo' => $this->logoUrl($club->club_logo),
            'email' => $club->email,
            'phone' => $club->phone,
            'status' => $club->status,
            'role' => $club->role,
            'otp_verified' => (bool) $club->otp_verified,
            'address' => $club->address,
            'working_hours' => (function() use ($club) {
                $dbWorkingHours = \App\Models\ClubWorkingHour::where('club_id', $club->id)->get();
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
                if ($club->working_hours && preg_match('/(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/', $club->working_hours, $matches)) {
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
            'facilities' => $club->facilities ?? [],
            'number_of_courts' => $club->number_of_courts ?? 0,
            
            // MaxSquash non-member booking updates
            'allow_non_member_booking' => (bool) $club->non_member_booking_allowed,
            'non_member_booking_start_time' => $club->non_member_booking_allowed && $club->non_member_booking_start_time ? substr((string)$club->non_member_booking_start_time, 0, 5) : null,
            'non_member_booking_end_time' => $club->non_member_booking_allowed && $club->non_member_booking_end_time ? substr((string)$club->non_member_booking_end_time, 0, 5) : null,
            'non_member_booking_schedule' => (function() use ($club) {
                if (!$club->non_member_booking_allowed) {
                    return null;
                }
                $dbWindows = \App\Models\ClubNonMemberWindow::where('club_id', $club->id)->get();
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
