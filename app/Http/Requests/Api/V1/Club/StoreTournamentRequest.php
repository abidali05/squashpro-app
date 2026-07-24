<?php

namespace App\Http\Requests\Api\V1\Club;

use Illuminate\Foundation\Http\FormRequest;

class StoreTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('player_level') && is_string($this->player_level)) {
            $decoded = json_decode($this->player_level, true);
            if (is_array($decoded)) {
                $this->merge([
                    'player_level' => $decoded,
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'tournament_image' => ['nullable'],
            'name' => ['required', 'string', 'max:255'],
            'format' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'registration_deadline' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'entry_fees' => ['required', 'numeric', 'min:0'],
            'prize_pool' => ['required', 'numeric', 'min:0'],
            'allowed_player' => ['nullable', 'integer', 'min:1'],
            'rules' => ['nullable', 'string'],
            
            // New Tournament Fields
            'tournament_type' => ['required', 'string', 'in:CLUB_TO_CLUB,CLUB_MEMBERS_ONLY'],
            'opponent_club_id' => [
                'required_if:tournament_type,CLUB_TO_CLUB',
                'nullable',
                'integer',
                \Illuminate\Validation\Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'club')->where('status', 'active');
                }),
            ],
            'gender' => ['required', 'string', 'in:MALE,FEMALE,MIXED,OPEN'],
            'player_level' => ['required', 'array', 'min:1'],
            'player_level.*' => ['required', 'string'],
            'age_group' => ['required', 'string', 'regex:/^\d+-\d+$/'],
            'maximum_players' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        if ($this->hasFile('tournament_image')) {
            $validator->sometimes('tournament_image', ['file', 'image', 'mimes:jpg,jpeg,png,webp'], fn () => true);
        } elseif ($this->filled('tournament_image') && is_string($this->input('tournament_image'))) {
            $validator->sometimes('tournament_image', ['url'], fn () => true);
        }

        $validator->after(function ($validator) {
            $user = $this->user();

            if ($this->filled('start_date') && $this->filled('registration_deadline')) {
                $start = \Illuminate\Support\Carbon::parse($this->input('start_date'));
                $deadline = \Illuminate\Support\Carbon::parse($this->input('registration_deadline'));
                if ($deadline->gt($start)) {
                    $validator->errors()->add('registration_deadline', 'Registration deadline must be on or before the start date.');
                }
            }

            if ($this->filled('end_date') && $this->filled('start_date')) {
                $start = \Illuminate\Support\Carbon::parse($this->input('start_date'));
                $end = \Illuminate\Support\Carbon::parse($this->input('end_date'));
                if ($end->lt($start)) {
                    $validator->errors()->add('end_date', 'End date must be on or after the start date.');
                }
            }

            if ($this->input('tournament_type') === 'CLUB_TO_CLUB') {
                if ($this->filled('opponent_club_id') && (int) $this->input('opponent_club_id') === $user->id) {
                    $validator->errors()->add('opponent_club_id', 'Opponent club cannot be the organizing club itself.');
                }
            }
        });
    }
}
