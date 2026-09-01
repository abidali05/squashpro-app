<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Tournament;
use App\Models\TournamentFixture;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FixtureManagementController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = in_array((int) $request->integer('per_page', 10), [10, 25, 50, 100], true)
            ? (int) $request->integer('per_page', 10) : 10;

        $sort = in_array($request->string('sort', 'created_at')->toString(), ['round', 'status', 'created_at'], true)
            ? $request->string('sort', 'created_at')->toString() : 'created_at';

        $direction = in_array($request->string('direction', 'desc')->toString(), ['asc', 'desc'], true)
            ? $request->string('direction', 'desc')->toString() : 'desc';

        $search = trim($request->string('search')->toString());
        $status = trim($request->string('status')->toString());
        $tournamentId = (int) $request->integer('tournament_id');
        $courtId = (int) $request->integer('court_id');

        $tournaments = Tournament::orderBy('name')->get(['id', 'name']);
        $courts = Court::orderBy('name')->get(['id', 'name']);

        $fixtures = TournamentFixture::query()
            ->with([
                'tournament:id,name,format',
                'group:id,name',
                'homeClub:id,club_name,name',
                'awayClub:id,club_name,name',
                'byeClub:id,club_name,name',
                'restClub:id,club_name,name',
                'court:id,name,type',
                'matches.court:id,name,type',
            ])
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('round', 'like', "%{$search}%")
                        ->orWhereHas('tournament', fn (Builder $t) => $t->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('homeClub', fn (Builder $c) => $c->where('club_name', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                        ->orWhereHas('awayClub', fn (Builder $c) => $c->where('club_name', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                        ->orWhereHas('court', fn (Builder $ct) => $ct->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn (Builder $q) => $q->where('status', $status))
            ->when($tournamentId > 0, fn (Builder $q) => $q->where('tournament_id', $tournamentId))
            ->when($courtId > 0, function (Builder $q) use ($courtId) {
                $q->where('court_id', $courtId)
                  ->orWhereHas('matches', fn (Builder $m) => $m->where('court_id', $courtId));
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('content.admin.tournaments.fixtures', compact(
            'fixtures',
            'tournaments',
            'courts',
            'search',
            'status',
            'tournamentId',
            'courtId',
            'sort',
            'direction',
            'perPage'
        ));
    }

    public function show(TournamentFixture $fixture): View
    {
        $fixture->load([
            'tournament',
            'group',
            'homeClub',
            'awayClub',
            'byeClub',
            'restClub',
            'winnerClub',
            'court',
            'matches.homePlayer',
            'matches.awayPlayer',
            'matches.winnerPlayer',
            'matches.court',
            'matches.venue',
            'matches.scorers',
            'matches.umpires',
        ]);

        $tournament = $fixture->tournament;
        $fixtures = collect([$fixture]);

        return view('content.admin.tournaments.fixture-show', compact('fixture', 'tournament', 'fixtures'));
    }

    public function showByTournament(Tournament $tournament): View
    {
        $tournament->load('club:id,club_name,name,club_logo');

        $fixtures = TournamentFixture::query()
            ->where('tournament_id', $tournament->id)
            ->with([
                'group',
                'homeClub',
                'awayClub',
                'byeClub',
                'restClub',
                'winnerClub',
                'court',
                'matches.homePlayer',
                'matches.awayPlayer',
                'matches.winnerPlayer',
                'matches.court',
                'matches.venue',
                'matches.scorers',
                'matches.umpires',
            ])
            ->get();

        $fixture = $fixtures->first();

        return view('content.admin.tournaments.fixture-show', compact('tournament', 'fixtures', 'fixture'));
    }

    public function destroy(TournamentFixture $fixture): RedirectResponse
    {
        $fixture->delete();

        return redirect()->route('admin.fixtures.index')->with('success', 'Tournament fixture deleted successfully.');
    }
}
