<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->club_name ?? $this->name,
            'address' => $this->address,
            'working_hours' => $this->working_hours,
            'facilities' => $this->facilities ?? [],
            'number_of_courts' => $this->number_of_courts ?? 0,
        ];
    }
}
