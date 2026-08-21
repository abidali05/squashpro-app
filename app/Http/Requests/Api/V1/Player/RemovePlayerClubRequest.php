<?php

namespace App\Http\Requests\Api\V1\Player;

use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;

class RemovePlayerClubRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'club_id' => [
                'required',
                'integer',
                Rule::exists('club_memberships', 'club_id')->where(function ($q) {
                    $q->where('player_id', $this->user()->id)
                      ->where('status', 'approved');
                }),
            ],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'club_id.exists' => 'Active membership not found for this club.',
        ];
    }
}
