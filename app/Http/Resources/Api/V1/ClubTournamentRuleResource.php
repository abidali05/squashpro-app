<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubTournamentRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'tournament_id' => (int) $this->tournament_id,
            'tournament_format' => $this->tournament_format,
            'competition_setup' => $this->competition_setup,
            'pool_rules' => $this->pool_rules,
            'knockout_rounds' => $this->knockout_rounds,
            'match_equipment' => $this->match_equipment,
            'scoring_rules' => $this->scoring_rules,
            'note' => $this->note,
        ];
    }
}
