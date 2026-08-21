<?php

namespace App\Services;

use App\Models\ClubMembership;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlayerMembershipService
{
    /**
     * Get the active approved clubs for the player.
     *
     * @param User $player
     * @param string|null $search
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPlayerClubs(User $player, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = ClubMembership::query()
            ->with(['club.cityRelation'])
            ->where('player_id', $player->id)
            ->where('status', ClubMembership::STATUS_APPROVED)
            ->whereHas('club', function ($q) {
                $q->where('status', 'active');
            });

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('membership_number', 'like', "%{$search}%")
                  ->orWhereHas('club', function ($sub) use ($search) {
                      $sub->where('club_name', 'like', "%{$search}%")
                          ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        // Return pagination
        return $query->paginate($perPage);
    }
}
