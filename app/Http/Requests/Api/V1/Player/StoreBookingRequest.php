<?php

namespace App\Http\Requests\Api\V1\Player;

use App\Http\Requests\Api\BaseApiRequest;
use App\Support\ApiErrorCode;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $player = $this->user();
        $clubId = $this->input('club_id');

        $isMember = \App\Models\ClubMembership::where('player_id', $player?->id)
            ->where('club_id', $clubId)
            ->where('status', 'approved')
            ->exists();

        return [
            'club_id' => ['required', 'integer', 'min:1'],
            'court_id' => ['required', 'integer', 'min:1'],
            'slot_id' => ['required', 'integer', 'min:1'],
            'booking_date' => ['required', 'date_format:Y-m-d'],
            'payment_method' => [$isMember ? 'nullable' : 'required', 'in:card,wallet,cash,jazzcash,easypaisa'],
            'payment_transaction_id' => [$isMember ? 'nullable' : 'required', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('booking_date') && $this->date('booking_date')->startOfDay()->lt(now()->startOfDay())) {
                $validator->errors()->add('booking_date', 'The booking date must not be in the past.');
            }
        });
    }
}
