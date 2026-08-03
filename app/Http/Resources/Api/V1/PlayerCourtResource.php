<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerCourtResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'court_id' => $this->resource['court_id'],
            'club_id' => $this->resource['club_id'],
            'court_name' => $this->resource['court_name'],
            'court_type' => $this->resource['court_type'],
            'price_per_slot' => $this->resource['price_per_slot'],
            'status' => $this->resource['status'],
            'status_label' => $this->resource['status_label'],
            'allow_non_member_booking' => $this->resource['allow_non_member_booking'] ?? false,
            'non_member_booking_start_time' => $this->resource['non_member_booking_start_time'] ?? null,
            'non_member_booking_end_time' => $this->resource['non_member_booking_end_time'] ?? null,
        ];
    }
}
