<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TournamentManagementController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = in_array((int) $request->integer('per_page', 10), [10, 25, 50, 100], true)
            ? (int) $request->integer('per_page', 10) : 10;

        $sort = in_array($request->string('sort', 'start_date')->toString(), ['id', 'name', 'start_date', 'registration_deadline', 'entry_fees', 'prize_pool', 'status', 'created_at'], true)
            ? $request->string('sort', 'start_date')->toString() : 'start_date';

        $direction = in_array($request->string('direction', 'desc')->toString(), ['asc', 'desc'], true)
            ? $request->string('direction', 'desc')->toString() : 'desc';

        $search = trim($request->string('search')->toString());
        $status = trim($request->string('status')->toString());
        $format = trim($request->string('format')->toString());
        $startDate = trim($request->string('start_date')->toString());
        $clubId = (int) $request->integer('club_id');

        $clubs = User::query()
            ->where('role', 'club')
            ->orderBy('club_name')
            ->get(['id', 'club_name', 'name']);

        $query = Tournament::query()
            ->with([
                'club:id,club_name,name,city,club_logo',
                'clubTournamentRule:id,tournament_id',
                'clubTournamentPool:id,tournament_id',
                'fixtures:id,tournament_id',
            ])
            ->when($search !== '', function (Builder $builder) use ($search) {
                $builder->where(function (Builder $sub) use ($search) {
                    $sub->where('id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('format', 'like', "%{$search}%")
                        ->orWhereHas('club', function (Builder $clubQuery) use ($search) {
                            $clubQuery->where('club_name', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn (Builder $builder) => $builder->where('status', $status))
            ->when($format !== '', fn (Builder $builder) => $builder->where('format', $format))
            ->when($startDate !== '', fn (Builder $builder) => $builder->whereDate('start_date', $startDate))
            ->when($clubId > 0, fn (Builder $builder) => $builder->where('club_id', $clubId))
            ->orderBy($sort, $direction);

        $tournaments = $query->paginate($perPage)->withQueryString();

        $stats = [
            'total_tournaments' => Tournament::count(),
            'open_tournaments' => Tournament::where('status', 'open')->count(),
            'full_tournaments' => Tournament::where('status', 'full')->count(),
            'completed_tournaments' => Tournament::where('status', 'completed')->count(),
            'cancelled_tournaments' => Tournament::where('status', 'cancelled')->count(),
            'total_registered_players' => (int) Tournament::sum('registered_players_count'),
            'total_prize_pool' => (float) Tournament::sum('prize_pool'),
        ];

        return view('content.admin.tournaments.index', compact(
            'tournaments',
            'clubs',
            'search',
            'status',
            'format',
            'startDate',
            'clubId',
            'sort',
            'direction',
            'perPage',
            'stats'
        ));
    }

    public function show(Tournament $tournament): View
    {
        $tournament->load([
            'club:id,club_name,name,email,phone,address,city,club_logo,working_hours',
            'registrations.player:id,name,email,gender,playing_level',
            'invitations.invitedClub:id,club_name,name,email,phone,city,club_logo',
            'scorers:id,name,email,profile_image',
            'umpires:id,name,email,profile_image',
            'clubTournamentRule:id,tournament_id',
            'clubTournamentPool:id,tournament_id',
            'fixtures:id,tournament_id',
        ]);

        return view('content.admin.tournaments.show', compact('tournament'));
    }

    public function create(): View
    {
        $clubs = User::query()
            ->where('role', 'club')
            ->where('status', 'active')
            ->orderBy('club_name')
            ->get(['id', 'club_name', 'name', 'city']);

        $scorers = User::query()
            ->where('role', 'player')
            ->where('status', 'active')
            ->where('are_you_scorer', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $umpires = User::query()
            ->where('role', 'player')
            ->where('status', 'active')
            ->where('are_you_umpire', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('content.admin.tournaments.create', compact('clubs', 'scorers', 'umpires'));
    }

    public function store(Request $request): RedirectResponse
    {
        $opponents = $request->input('opponent_club_id');
        if ($opponents !== null) {
            $opponents = is_array($opponents) ? $opponents : [$opponents];
            $opponents = array_values(array_filter(array_map('intval', $opponents)));
            $request->merge(['opponent_club_id' => $opponents]);
        }

        $validated = $request->validate([
            'club_id' => ['required', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'format' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'registration_deadline' => ['required', 'date', 'before_or_equal:start_date'],
            'entry_fees' => ['required', 'numeric', 'min:0'],
            'prize_pool' => ['required', 'numeric', 'min:0'],
            'maximum_players' => ['required', 'integer', 'min:1'],
            'rules' => ['nullable', 'string'],
            'tournament_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'tournament_type' => ['required', 'string', 'in:CLUB_TO_CLUB,CLUB_MEMBERS_ONLY,OPEN'],
            'opponent_club_id' => [
                'required_if:tournament_type,CLUB_TO_CLUB',
                'nullable',
                'array',
                function ($attribute, $value, $fail) use ($request) {
                    if (in_array((int)$request->input('club_id'), array_map('intval', $value), true)) {
                        $fail('The selected opponent club must be different from the hosting club.');
                    }
                }
            ],
            'opponent_club_id.*' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'gender' => ['required', 'string', 'in:MALE,FEMALE,MIXED,OPEN'],
            'player_level' => ['required', 'array', 'min:1'],
            'player_level.*' => ['required', 'string', 'in:BEGINNER,INTERMEDIATE,ADVANCED,PROFESSIONAL,OPEN'],
            'age_group' => ['required', 'string', 'regex:/^\d+-\d+$/'],
            'scorer_ids' => ['nullable', 'array'],
            'scorer_ids.*' => ['required', 'integer', 'exists:users,id'],
            'umpire_ids' => ['nullable', 'array'],
            'umpire_ids.*' => ['required', 'integer', 'exists:users,id'],
        ]);

        $imagePath = null;
        if ($request->hasFile('tournament_image')) {
            try {
                $imagePath = $request->file('tournament_image')->store('tournament-images', 'public');
            } catch (\Throwable $e) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['tournament_image' => 'File upload failed. This usually indicates that your local PHP temporary upload directory is not configured or not writable. Please set "upload_tmp_dir" in your php.ini file. Details: ' . $e->getMessage()]);
            }
        }

        $opponentArray = $validated['opponent_club_id'] ?? null;

        $tournament = Tournament::create(array_merge($validated, [
            'opponent_club_id' => $opponentArray,
            'tournament_image' => $imagePath,
            'allowed_player' => $validated['maximum_players'],
            'status' => 'open',
            'created_by_admin' => true,
        ]));

        if ($tournament->tournament_type === 'CLUB_TO_CLUB') {
            if (isset($validated['scorer_ids'])) {
                $tournament->scorers()->sync($validated['scorer_ids']);
            }
            if (isset($validated['umpire_ids'])) {
                $tournament->umpires()->sync($validated['umpire_ids']);
            }

            if (!empty($opponentArray)) {
                foreach ($opponentArray as $invitedId) {
                    \App\Models\TournamentInvitation::create([
                        'tournament_id' => $tournament->id,
                        'invited_club_id' => $invitedId,
                        'status' => 'pending',
                        'invited_at' => now(),
                    ]);
                }
            }
        }

        return redirect()->route('admin.tournaments.index')->with('success', 'Tournament created successfully.');
    }

    public function edit(Tournament $tournament): View
    {
        $clubs = User::query()
            ->where('role', 'club')
            ->where('status', 'active')
            ->orderBy('club_name')
            ->get(['id', 'club_name', 'name', 'city']);

        $scorers = User::query()
            ->where('role', 'player')
            ->where('status', 'active')
            ->where('are_you_scorer', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $umpires = User::query()
            ->where('role', 'player')
            ->where('status', 'active')
            ->where('are_you_umpire', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $tournament->load(['scorers:id', 'umpires:id']);

        return view('content.admin.tournaments.edit', compact('tournament', 'clubs', 'scorers', 'umpires'));
    }

    public function update(Request $request, Tournament $tournament): RedirectResponse
    {
        $opponents = $request->input('opponent_club_id');
        if ($opponents !== null) {
            $opponents = is_array($opponents) ? $opponents : [$opponents];
            $opponents = array_values(array_filter(array_map('intval', $opponents)));
            $request->merge(['opponent_club_id' => $opponents]);
        }

        $validated = $request->validate([
            'club_id' => ['required', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'format' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'registration_deadline' => ['required', 'date', 'before_or_equal:start_date'],
            'entry_fees' => ['required', 'numeric', 'min:0'],
            'prize_pool' => ['required', 'numeric', 'min:0'],
            'maximum_players' => ['required', 'integer', 'min:1'],
            'rules' => ['nullable', 'string'],
            'tournament_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'tournament_type' => ['required', 'string', 'in:CLUB_TO_CLUB,CLUB_MEMBERS_ONLY,OPEN'],
            'opponent_club_id' => [
                'required_if:tournament_type,CLUB_TO_CLUB',
                'nullable',
                'array',
                function ($attribute, $value, $fail) use ($request) {
                    if (in_array((int)$request->input('club_id'), array_map('intval', $value), true)) {
                        $fail('The selected opponent club must be different from the hosting club.');
                    }
                }
            ],
            'opponent_club_id.*' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'gender' => ['required', 'string', 'in:MALE,FEMALE,MIXED,OPEN'],
            'player_level' => ['required', 'array', 'min:1'],
            'player_level.*' => ['required', 'string', 'in:BEGINNER,INTERMEDIATE,ADVANCED,PROFESSIONAL,OPEN'],
            'age_group' => ['required', 'string', 'regex:/^\d+-\d+$/'],
            'status' => ['required', 'string', 'in:pending,soft_accepted,confirmed,rejected,open,full,closed,completed,cancelled'],
            'scorer_ids' => ['nullable', 'array'],
            'scorer_ids.*' => ['required', 'integer', 'exists:users,id'],
            'umpire_ids' => ['nullable', 'array'],
            'umpire_ids.*' => ['required', 'integer', 'exists:users,id'],
        ]);

        $imagePath = $tournament->tournament_image;
        if ($request->hasFile('tournament_image')) {
            try {
                $imagePath = $request->file('tournament_image')->store('tournament-images', 'public');
            } catch (\Throwable $e) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['tournament_image' => 'File upload failed. This usually indicates that your local PHP temporary upload directory is not configured or not writable. Please set "upload_tmp_dir" in your php.ini file. Details: ' . $e->getMessage()]);
            }
        }

        $opponentArray = $validated['opponent_club_id'] ?? null;

        $tournament->update(array_merge($validated, [
            'opponent_club_id' => $opponentArray,
            'tournament_image' => $imagePath,
            'allowed_player' => $validated['maximum_players'],
        ]));

        if ($tournament->tournament_type === 'CLUB_TO_CLUB') {
            $tournament->scorers()->sync($validated['scorer_ids'] ?? []);
            $tournament->umpires()->sync($validated['umpire_ids'] ?? []);

            $existingInvites = \App\Models\TournamentInvitation::where('tournament_id', $tournament->id)
                ->pluck('invited_club_id')
                ->all();

            $newInvites = $opponentArray ?? [];

            // Delete removed invitations
            \App\Models\TournamentInvitation::where('tournament_id', $tournament->id)
                ->whereNotIn('invited_club_id', $newInvites)
                ->delete();

            // Create new invitations
            foreach ($newInvites as $invitedId) {
                if (!in_array($invitedId, $existingInvites, true)) {
                    \App\Models\TournamentInvitation::create([
                        'tournament_id' => $tournament->id,
                        'invited_club_id' => $invitedId,
                        'status' => 'pending',
                        'invited_at' => now(),
                    ]);
                }
            }
        } else {
            $tournament->scorers()->detach();
            $tournament->umpires()->detach();

            // Delete all invitations if type is no longer CLUB_TO_CLUB
            \App\Models\TournamentInvitation::where('tournament_id', $tournament->id)->delete();
        }

        return redirect()->route('admin.tournaments.index')->with('success', 'Tournament updated successfully.');
    }

    public function destroy(Tournament $tournament): RedirectResponse
    {
        $tournament->delete();
        return redirect()->route('admin.tournaments.index')->with('success', 'Tournament deleted successfully.');
    }

    public function updateStatus(Request $request, Tournament $tournament): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,soft_accepted,confirmed,rejected,open,full,closed,completed,cancelled'],
        ]);

        $tournament->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Tournament status updated successfully.');
    }
}
