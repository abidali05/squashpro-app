<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PlayerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'player_image' => $this->imageUrl($this->profile_image),
            'full_name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone,
            'city_id' => $this->city_id ?? null,
            'city_name' => $this->cityRelation?->name ?? $this->city,
            'total_matches_played' => 0,
            'win_rate' => 0,
            'total_points' => 0,
        ];
    }

    private function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
