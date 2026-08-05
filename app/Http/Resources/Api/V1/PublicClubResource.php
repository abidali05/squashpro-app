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
            'logo_url' => app_image_url($this->club_logo),
            'city' => $this->city,
            'address' => $this->address,
            'status' => $this->status,
        ];
    }
}
