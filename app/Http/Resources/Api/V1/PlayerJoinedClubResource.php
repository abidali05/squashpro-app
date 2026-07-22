<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PlayerJoinedClubResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $club = $this->club;

        return [
            'club_id' => $this->club_id,
            'club_name' => $club->club_name ?? $club->name,
            'club_logo' => $club->club_logo ? (str_starts_with($club->club_logo, 'http') ? $club->club_logo : Storage::disk('public')->url($club->club_logo)) : null,
            'location' => $club->address ?? ($club->cityRelation->name ?? $club->city),
            'address' => $club->address,
            'city' => $club->cityRelation->name ?? $club->city,
            'phone' => $club->phone,
            'email' => $club->email,
            'membership_number' => $this->membership_number,
            'membership_status' => $this->status,
            'joined_at' => ($this->approved_at ?? $this->created_at)?->toIso8601String(),
        ];
    }
}
