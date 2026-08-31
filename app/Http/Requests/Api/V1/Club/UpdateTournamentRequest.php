<?php

namespace App\Http\Requests\Api\V1\Club;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTournamentRequest extends FormRequest
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
    }

    public function rules(): array
    {
        return [
            'tournament_image' => ['nullable'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'format' => ['sometimes', 'required', 'string', 'in:knockout,league'],
            'start_date' => ['sometimes', 'required', 'date'],
            'registration_deadline' => ['sometimes', 'required', 'date', 'before:start_date'],
            'end_date' => ['sometimes', 'required', 'date', 'after:start_date'],
            'entry_fees' => ['sometimes', 'required', 'numeric', 'min:0'],
            'prize_pool' => ['sometimes', 'required', 'numeric', 'min:0'],
            'allowed_player' => ['sometimes', 'required', 'integer', 'min:1'],
            'maximum_players' => ['sometimes', 'required', 'integer', 'min:1'],
            'rules' => ['sometimes', 'nullable', 'string'],
            'gender' => ['sometimes', 'required', 'string', 'in:MALE,FEMALE,OPEN'],
            'player_level' => ['sometimes', 'required', 'array', 'min:1'],
            'player_level.*' => ['required', 'string', 'in:BEGINNER,INTERMEDIATE,PROFESSIONAL'],
            'age_group' => ['sometimes', 'required', 'string', 'regex:/^\d+-\d+$/'],
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
            if ($this->filled('start_date') && $this->filled('registration_deadline')
                && $this->date('registration_deadline')->gt($this->date('start_date'))) {
                $validator->errors()->add('registration_deadline', 'Registration deadline must be on or before the start date.');
            }

            if ($this->filled('end_date') && $this->filled('start_date')
                && $this->date('end_date')->lt($this->date('start_date'))) {
                $validator->errors()->add('end_date', 'End date must be on or after the start date.');
            }
        });
    }
}
