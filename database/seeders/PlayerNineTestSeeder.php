<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ClubMembership;
use App\Models\Tournament;

class PlayerNineTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Update player 9 details to match Tournament 3 criteria
        $player = User::find(9);
        if ($player) {
            $player->update([
                'gender' => 'male',
                'dob' => '2002-01-01', // Age 24, falls within 15-25 group
                'playing_level' => 'intermediate', // Matches INTERMEDIATE level
            ]);
        }

        // 2. Ensure player 9 has an active, approved membership at Club 7
        ClubMembership::updateOrCreate(
            ['player_id' => 9, 'club_id' => 7],
            [
                'membership_number' => 'CSC-10291',
                'verification_mode' => 'club_confirmed',
                'status' => 'approved',
                'approved_at' => now(),
                'removed_at' => null,
                'removal_reason' => null,
            ]
        );

        // 3. Update Tournament 3 to be open and set in the future
        $tournament = Tournament::find(3);
        if ($tournament) {
            $tournament->update([
                'status' => 'open',
                'start_date' => '2026-08-20',
                'end_date' => '2026-08-22',
                'registration_deadline' => '2026-08-15 18:00:00',
            ]);
        }
    }
}
