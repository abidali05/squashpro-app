<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ClubMembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $player = $this->player;
        $nameParts = explode(' ', trim($player->name ?? ''), 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        return [
            'membership_id' => $this->id,
            'membership_number' => $this->membership_number,
            'status' => $this->status,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'membership_type' => $this->membership_type ?? 'temporary',
            'membership_expiry_date' => $this->membership_expiry_date?->toIso8601String(),
            'player' => [
                'id' => $player->id ?? null,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'full_name' => $player->name ?? '',
                'email' => $player->email ?? '',
                'phone' => $player->phone ?? '',
                'profile_image_url' => $player && $player->profile_image ? (str_starts_with($player->profile_image, 'http') ? $player->profile_image : Storage::disk('public')->url($player->profile_image)) : null,
            ],
        ];
    }
}
