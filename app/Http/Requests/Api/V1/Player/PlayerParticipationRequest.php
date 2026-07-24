<?php

namespace App\Http\Requests\Api\V1\Player;

use Illuminate\Foundation\Http\FormRequest;

class PlayerParticipationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'in:ACCEPT,REJECT'],
        ];
    }
}
