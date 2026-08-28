<?php

namespace App\Http\Requests\Api\V1\Club;

use Illuminate\Foundation\Http\FormRequest;

class SaveClubTournamentPoolsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', 'string'],
            'has_pools' => ['required', 'boolean'],
            'pools' => ['required', 'array', 'min:1'],
            'pools.*.pool_name' => ['required', 'string'],
            'pools.*.pool_index' => ['required', 'integer'],
            'pools.*.club_ids' => ['nullable', 'array'],
            'pools.*.club_ids.*' => ['integer'],
            'pools.*.player_ids' => ['nullable', 'array'],
            'pools.*.player_ids.*' => ['integer'],
        ];
    }
}
