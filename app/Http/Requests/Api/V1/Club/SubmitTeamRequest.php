<?php

namespace App\Http\Requests\Api\V1\Club;

use Illuminate\Foundation\Http\FormRequest;

class SubmitTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('player_ids') && is_string($this->player_ids)) {
            $decoded = json_decode($this->player_ids, true);
            if (is_array($decoded)) {
                $this->merge([
                    'player_ids' => $decoded,
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'player_ids' => ['required', 'array', 'min:1'],
            'player_ids.*' => [
                'required',
                'integer',
                'distinct',
                \Illuminate\Validation\Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'player');
                }),
            ],
        ];
    }
}
