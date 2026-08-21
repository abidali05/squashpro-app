<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class CourtResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $price = (float) $this->price_per_hour;

        return [
            'court_id' => $this->id,
            'name' => $this->name,
            'type' => Str::headline((string) $this->type),
            'price_per_hour' => $price == (int) $price ? (int) $price : $price,
            'status' => in_array($this->status, ['maintenance', 'inactive'], true) ? 'maintenance' : 'available',
            'slots' => $this->slots ? $this->slots->map(fn ($slot) => [
                'day' => $slot->day,
                'start_time' => substr((string)$slot->start_time, 0, 5),
                'end_time' => substr((string)$slot->end_time, 0, 5),
                'price' => (float) $slot->price,
                'is_available' => (bool) $slot->is_available,
            ]) : [],
        ];
    }
}
