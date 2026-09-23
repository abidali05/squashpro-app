<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PublicCourtResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $price = (float) $this->price_per_hour;

        return [
            'id' => $this->id,
            'court_id' => $this->id,
            'club_id' => (int) $this->club_id,
            'name' => $this->name,
            'type' => Str::headline((string) $this->type),
            'price_per_hour' => $price == (int) $price ? (int) $price : $price,
            'capacity' => (int) $this->capacity,
            'status' => $this->status,
            'description' => $this->description,
            'maintenance_note' => $this->maintenance_note,
            'slots' => $this->slots ? $this->slots->map(fn ($slot) => [
                'id' => $slot->id,
                'day' => $slot->day,
                'start_time' => substr((string)$slot->start_time, 0, 5),
                'end_time' => substr((string)$slot->end_time, 0, 5),
                'price' => (float) $slot->price,
                'is_available' => (bool) $slot->is_available,
            ]) : [],
        ];
    }
}
