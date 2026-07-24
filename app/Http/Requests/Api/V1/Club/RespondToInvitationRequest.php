<?php

namespace App\Http\Requests\Api\V1\Club;

use Illuminate\Foundation\Http\FormRequest;

class RespondToInvitationRequest extends FormRequest
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
