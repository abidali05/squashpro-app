<?php

namespace App\Http\Requests\Api\V1\Club;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClubDetailsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('facilities')) {
            return;
        }

        $facilities = $this->input('facilities');

        if (is_string($facilities)) {
            $decoded = json_decode($facilities, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $facilities = $decoded;
            } else {
                $facilities = array_values(array_filter(array_map('trim', explode(',', $facilities))));
            }
        }

        $this->merge([
            'facilities' => $facilities,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isLegacy = is_string($this->input('working_hours'));

        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'facilities' => ['sometimes', 'required', 'array'],
            'facilities.*' => ['string', 'max:255'],
            'number_of_courts' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];

        if ($isLegacy) {
            $rules['working_hours'] = ['sometimes', 'required', 'string', 'max:100'];
        } else {
            $rules['working_hours'] = ['sometimes', 'required', 'array', 'min:7', 'max:7'];
            $rules['working_hours.*.day'] = ['required_with:working_hours', 'string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'];
            $rules['working_hours.*.is_open'] = ['required_with:working_hours', 'boolean'];
            $rules['working_hours.*.opens_at'] = ['required_if:working_hours.*.is_open,true,1', 'nullable', 'date_format:H:i'];
            $rules['working_hours.*.closes_at'] = ['required_if:working_hours.*.is_open,true,1', 'nullable', 'date_format:H:i'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $isLegacy = is_string($this->input('working_hours'));
            if ($isLegacy || !$this->has('working_hours')) {
                return;
            }

            $workingHours = $this->input('working_hours', []);
            $daysSeen = [];

            foreach ($workingHours as $index => $wh) {
                if (!isset($wh['day']) || !isset($wh['is_open'])) {
                    continue;
                }

                $day = strtolower($wh['day']);

                if (in_array($day, $daysSeen, true)) {
                    $validator->errors()->add("working_hours.{$index}.day", "Duplicate working hours entry for day: {$day}.");
                }
                $daysSeen[] = $day;

                if ($wh['is_open']) {
                    $opens = $wh['opens_at'] ?? null;
                    $closes = $wh['closes_at'] ?? null;

                    if ($opens && $closes) {
                        if (strcmp($opens, $closes) >= 0) {
                            $validator->errors()->add("working_hours.{$index}.opens_at", "The opening time must be earlier than closing time.");
                        }
                    }
                }
            }
        });
    }
}
