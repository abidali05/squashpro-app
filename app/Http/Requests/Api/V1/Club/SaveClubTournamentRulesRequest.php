<?php

namespace App\Http\Requests\Api\V1\Club;

use Illuminate\Foundation\Http\FormRequest;

class SaveClubTournamentRulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tournament_format' => ['sometimes', 'nullable', 'string'],
            'competition_setup' => ['nullable', 'array'],
            'competition_setup.enable_pools' => ['nullable', 'boolean'],
            'competition_setup.knockout_after_pools' => ['nullable', 'boolean'],
            'pool_rules' => ['nullable', 'array'],
            'pool_rules.pool_count' => ['nullable', 'integer'],
            'pool_rules.qualifiers_per_pool' => ['nullable', 'integer'],
            'knockout_rounds' => ['nullable', 'array'],
            'knockout_rounds.quarter_final' => ['nullable', 'boolean'],
            'knockout_rounds.semi_final' => ['nullable', 'boolean'],
            'knockout_rounds.final' => ['nullable', 'boolean'],
            'match_equipment' => ['nullable', 'array'],
            'match_equipment.ball_type' => ['nullable', 'string'],
            'scoring_rules' => ['nullable', 'array'],
            'scoring_rules.best_of' => ['nullable', 'integer'],
            'scoring_rules.points_per_game' => ['nullable', 'integer'],
            'scoring_rules.win_by' => ['nullable', 'integer'],
            'note' => ['nullable', 'string'],
        ];
    }
}
