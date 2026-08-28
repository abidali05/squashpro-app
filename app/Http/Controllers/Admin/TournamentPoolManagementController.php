<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClubTournamentPool;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TournamentPoolManagementController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = in_array((int) $request->integer('per_page', 10), [10, 25, 50, 100], true)
            ? (int) $request->integer('per_page', 10) : 10;

        $sort = in_array($request->string('sort', 'created_at')->toString(), ['format', 'created_at'], true)
            ? $request->string('sort', 'created_at')->toString() : 'created_at';

        $direction = in_array($request->string('direction', 'desc')->toString(), ['asc', 'desc'], true)
            ? $request->string('direction', 'desc')->toString() : 'desc';

        $search = trim($request->string('search')->toString());
        $format = trim($request->string('format')->toString());
        $tournamentId = (int) $request->integer('tournament_id');
        $clubId = (int) $request->integer('club_id');

        $tournaments = Tournament::orderBy('name')->get(['id', 'name']);
        $clubs = User::where('role', 'club')->orderBy('club_name')->get(['id', 'club_name', 'name']);

        $pools = ClubTournamentPool::query()
            ->with([
                'tournament:id,name,format',
                'club:id,club_name,name,club_logo',
            ])
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('format', 'like', "%{$search}%")
                        ->orWhereHas('tournament', fn (Builder $t) => $t->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('club', fn (Builder $c) => $c->where('club_name', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
                });
            })
            ->when($format !== '', fn (Builder $q) => $q->where('format', $format))
            ->when($tournamentId > 0, fn (Builder $q) => $q->where('tournament_id', $tournamentId))
            ->when($clubId > 0, fn (Builder $q) => $q->where('club_id', $clubId))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('content.admin.tournaments.pools', compact(
            'pools',
            'tournaments',
            'clubs',
            'search',
            'format',
            'tournamentId',
            'clubId',
            'sort',
            'direction',
            'perPage'
        ));
    }

    public function show(ClubTournamentPool $tournamentPool): View
    {
        $tournamentPool->load(['tournament', 'club']);

        // Collect all club IDs referenced inside the pools array to display club names nicely
        $allClubIds = [];
        if (is_array($tournamentPool->pools)) {
            foreach ($tournamentPool->pools as $p) {
                if (isset($p['club_ids']) && is_array($p['club_ids'])) {
                    foreach ($p['club_ids'] as $cid) {
                        $allClubIds[] = (int) $cid;
                    }
                }
            }
        }

        $referencedClubs = User::whereIn('id', array_unique($allClubIds))
            ->get(['id', 'club_name', 'name', 'club_logo'])
            ->keyBy('id');

        return view('content.admin.tournaments.pool-show', compact('tournamentPool', 'referencedClubs'));
    }

    public function destroy(ClubTournamentPool $tournamentPool): RedirectResponse
    {
        $tournamentPool->delete();

        return redirect()->route('admin.tournament-pools.index')->with('success', 'Tournament pool record deleted successfully.');
    }
}
