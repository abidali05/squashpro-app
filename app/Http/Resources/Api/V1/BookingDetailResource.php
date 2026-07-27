<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class BookingDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $courtPrice = (float) ($this->court_price ?? 0);

        $membership = \App\Models\ClubMembership::where('player_id', $this->player_id)
            ->where('club_id', $this->club_id)
            ->first();

        $isMember = $membership && $membership->status === 'approved';
        $membershipNumber = $membership?->membership_number;
        $membershipStatus = $membership?->status;

        $club = $this->club;
        $allowNonMemberBooking = $club ? (bool) $club->non_member_booking_allowed : false;
        $canPay = !$isMember && $allowNonMemberBooking;

        $player = $this->player;
        $dob = $player?->dob;
        $age = null;
        if ($dob instanceof \Carbon\Carbon) {
            $age = $dob->age;
        } elseif (is_string($dob)) {
            try {
                $age = \Carbon\Carbon::parse($dob)->age;
            } catch (\Throwable $e) {
                $age = null;
            }
        }

        return [
            'booking_id' => $this->id,
            'status' => $this->booking_status,
            'rejection_reason' => $this->rejection_reason,
            'player_detail' => [
                'player_id' => $player?->id,
                'name' => $player?->name,
                'email' => $player?->email,
                'phone' => $player?->phone,
                'profile_image' => $player?->profile_image ? asset('storage/' . $player->profile_image) : null,
                'gender' => $player?->gender,
                'playing_level' => $player?->playing_level,
                'dob' => $dob instanceof \Carbon\Carbon ? $dob->toDateString() : (is_string($dob) ? $dob : null),
                'age' => $age,
                'is_member' => $isMember,
                'membership_status' => $membershipStatus,
                'membership_number' => $membershipNumber,
                'can_pay' => $canPay,
            ],
            'court_detail' => [
                'court_id' => $this->court?->id,
                'name' => $this->court?->name,
                'type' => $this->court ? Str::headline((string) $this->court->type) : null,
                'price_per_hour' => $courtPrice == (int) $courtPrice ? (int) $courtPrice : $courtPrice,
            ],
            'booking_date' => $this->booking_date?->toDateString(),
            'booking_time' => substr((string) $this->start_time, 0, 5).' - '.substr((string) $this->end_time, 0, 5),
            'price' => $courtPrice == (int) $courtPrice ? (int) $courtPrice : $courtPrice,
            'payment_status' => $this->payment_status,
        ];
    }
}
