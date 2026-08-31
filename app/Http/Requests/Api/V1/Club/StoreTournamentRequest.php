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
        if ($this->has('player_level')) {
            $val = $this->player_level;
            if (is_string($val)) {
                $decoded = json_decode($val, true);
                if (is_array($decoded)) {
                    $this->merge([
                        'player_level' => array_values(array_filter(array_map('trim', $decoded))),
                    ]);
                } elseif (str_contains($val, ',')) {
                    $parts = array_values(array_filter(array_map('trim', explode(',', $val))));
                    $this->merge([
                        'player_level' => $parts,
                    ]);
                } else {
                    $trimmed = trim($val);
                    $this->merge([
                        'player_level' => $trimmed !== '' ? [$trimmed] : [],
                    ]);
                }
            }
        }

        // Support backward compatibility for opponent_club_id
        if ($this->filled('opponent_club_id') && !$this->has('invited_club_ids')) {
            $this->merge([
                'invited_club_ids' => [(int) $this->opponent_club_id],
            ]);
        }
    }

    public function rules(): array
    {
        $isLegacy = $this->filled('opponent_club_id') && !$this->has('host_team_player_ids');

        $rules = [
            'tournament_image' => ['required'],
            'name' => ['required', 'string', 'max:255'],
            'format' => ['required', 'string', 'in:knockout,league'],
            'start_date' => ['required', 'date'],
            'registration_deadline' => ['required', 'date', 'before:start_date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'entry_fees' => ['required', 'numeric', 'min:0'],
            'prize_pool' => ['required', 'numeric', 'min:0'],
            'allowed_player' => ['nullable', 'integer', 'min:1'],
            'rules' => ['nullable', 'string'],
            
            // New Tournament Fields
            'tournament_type' => ['required', 'string', 'in:CLUB_TO_CLUB,CLUB_MEMBERS_ONLY,OPEN'],
            'gender' => ['required', 'string', 'in:MALE,FEMALE,OPEN'],
            'player_level' => ['required', 'array', 'min:1'],
            'player_level.*' => ['required', 'string', 'in:BEGINNER,INTERMEDIATE,PROFESSIONAL'],
            'age_group' => ['required', 'string', 'regex:/^\d+-\d+$/'],
            'maximum_players' => ['required', 'integer', 'min:1'],
            'scorer_ids' => ['nullable', 'array'],
            'scorer_ids.*' => [
                'integer',
                \Illuminate\Validation\Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'player');
                }),
            ],
            'umpire_ids' => ['nullable', 'array'],
            'umpire_ids.*' => [
                'integer',
                \Illuminate\Validation\Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'player');
                }),
            ],
        ];

        if ($isLegacy) {
            $rules['opponent_club_id'] = [
                'required_if:tournament_type,CLUB_TO_CLUB',
                'nullable',
                'integer',
                \Illuminate\Validation\Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'club')->where('status', 'active');
                }),
            ];
            $rules['invited_club_ids'] = ['nullable', 'array'];
            $rules['invited_club_ids.*'] = ['integer'];
        } else {
            $rules['invited_club_ids'] = [
                'required_if:tournament_type,CLUB_TO_CLUB',
                'array',
                'min:1'
            ];
            $rules['invited_club_ids.*'] = [
                'required_with:invited_club_ids',
                'integer',
                'distinct',
                \Illuminate\Validation\Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'club')->where('status', 'active');
                }),
            ];

            $rules['host_team_player_ids'] = [
                'required_if:tournament_type,CLUB_TO_CLUB',
                'array',
                'min:1'
            ];
            $rules['host_team_player_ids.*'] = [
                'required_with:host_team_player_ids',
                'integer',
                'distinct',
                \Illuminate\Validation\Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'player');
                }),
            ];
        }

        return $rules;
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
            $isLegacy = $this->filled('opponent_club_id') && !$this->has('host_team_player_ids');

            if ($this->filled('start_date') && $this->filled('registration_deadline')) {
                $start = \Illuminate\Support\Carbon::parse($this->input('start_date'));
                $deadline = \Illuminate\Support\Carbon::parse($this->input('registration_deadline'));
                if ($deadline->gte($start)) {
                    $validator->errors()->add('registration_deadline', 'Registration deadline must be before the start date.');
                }
            }

            if ($this->filled('end_date') && $this->filled('start_date')) {
                $start = \Illuminate\Support\Carbon::parse($this->input('start_date'));
                $end = \Illuminate\Support\Carbon::parse($this->input('end_date'));
                if ($end->lte($start)) {
                    $validator->errors()->add('end_date', 'End date must be after the start date.');
                }
            }

            if ($this->input('tournament_type') === 'CLUB_TO_CLUB') {
                if ($isLegacy) {
                    if ($this->filled('opponent_club_id') && (int) $this->input('opponent_club_id') === $user->id) {
                        $validator->errors()->add('opponent_club_id', 'Opponent club cannot be the organizing club itself.');
                    }
                } else {
                    $invitedIds = $this->input('invited_club_ids', []);
                    if (in_array((int)$user->id, array_map('intval', $invitedIds), true)) {
                        $validator->errors()->add('invited_club_ids', 'Organizing club cannot be invited as an opponent.');
                    }
                }
            }
        });
    }
}
