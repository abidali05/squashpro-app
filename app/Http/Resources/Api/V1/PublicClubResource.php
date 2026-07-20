<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PublicClubResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->club_name ?? $this->name,
            'logo_url' => $this->club_logo ? (str_starts_with($this->club_logo, 'http') ? $this->club_logo : Storage::disk('public')->url($this->club_logo)) : null,
            'city' => $this->city,
            'address' => $this->address,
            'status' => $this->status,
        ];
    }
}
