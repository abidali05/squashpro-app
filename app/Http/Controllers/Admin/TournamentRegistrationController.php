<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TournamentRegistration;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TournamentRegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = in_array((int) $request->integer('per_page', 10), [10, 25, 50, 100], true)
            ? (int) $request->integer('per_page', 10) : 10;

        $sort = in_array($request->string('sort', 'created_at')->toString(), ['registration_status', 'payment_status', 'created_at'], true)
            ? $request->string('sort', 'created_at')->toString() : 'created_at';

        $direction = in_array($request->string('direction', 'desc')->toString(), ['asc', 'desc'], true)
            ? $request->string('direction', 'desc')->toString() : 'desc';

        $search = trim($request->string('search')->toString());
        $regStatus = trim($request->string('registration_status')->toString());
        $payStatus = trim($request->string('payment_status')->toString());
        $tournamentId = (int) $request->integer('tournament_id');

        $tournaments = Tournament::orderBy('name')->get(['id', 'name']);

        $registrations = TournamentRegistration::query()
            ->with(['tournament:id,name', 'player:id,name,email'])
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->whereHas('player', function (Builder $playerQuery) use ($search) {
                        $playerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('tournament', function (Builder $tournamentQuery) use ($search) {
                        $tournamentQuery->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->when($regStatus !== '', fn (Builder $q) => $q->where('registration_status', $regStatus))
            ->when($payStatus !== '', fn (Builder $q) => $q->where('payment_status', $payStatus))
            ->when($tournamentId > 0, fn (Builder $q) => $q->where('tournament_id', $tournamentId))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('content.admin.tournaments.registrations', compact(
            'registrations',
            'tournaments',
            'search',
            'regStatus',
            'payStatus',
            'tournamentId',
            'sort',
            'direction',
            'perPage'
        ));
    }

    public function approve(TournamentRegistration $registration): RedirectResponse
    {
        if ($registration->registration_status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending registration requests can be accepted.');
        }

        $registration->update([
            'registration_status' => 'accepted',
        ]);

        return redirect()->route('admin.tournament-registrations.index')->with('success', 'Tournament registration request accepted. Player can now proceed with payment.');
    }

    public function destroy(TournamentRegistration $registration): RedirectResponse
    {
        $oldStatus = $registration->registration_status;

        // Cancel the registration
        $registration->update([
            'registration_status' => 'cancelled',
        ]);

        // Decrement the tournament count only if it was fully registered
        if ($oldStatus === 'registered') {
            $tournament = $registration->tournament;
            if ($tournament) {
                $tournament->registered_players_count = max(0, ((int) $tournament->registered_players_count) - 1);
                if ($tournament->status === 'full' && (int) $tournament->registered_players_count < (int) $tournament->allowed_player) {
                    $tournament->status = 'open';
                }
                $tournament->save();
            }
        }

        return redirect()->route('admin.tournament-registrations.index')->with('success', 'Tournament registration cancelled successfully.');
    }
}
