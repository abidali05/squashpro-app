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
            ->with(['club:id,club_name,name,city,club_logo'])
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
            'registrations.player:id,name,email,gender,playing_level'
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

        return view('content.admin.tournaments.create', compact('clubs'));
    }

    public function store(Request $request): RedirectResponse
    {
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
                'integer',
                'different:club_id',
                'exists:users,id',
            ],
            'gender' => ['required', 'string', 'in:MALE,FEMALE,MIXED,OPEN'],
            'player_level' => ['required', 'array', 'min:1'],
            'player_level.*' => ['required', 'string', 'in:BEGINNER,INTERMEDIATE,ADVANCED,PROFESSIONAL,OPEN'],
            'age_group' => ['required', 'string', 'regex:/^\d+-\d+$/'],
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

        Tournament::create(array_merge($validated, [
            'tournament_image' => $imagePath,
            'allowed_player' => $validated['maximum_players'],
            'status' => 'open',
        ]));

        return redirect()->route('admin.tournaments.index')->with('success', 'Tournament created successfully.');
    }

    public function edit(Tournament $tournament): View
    {
        $clubs = User::query()
            ->where('role', 'club')
            ->where('status', 'active')
            ->orderBy('club_name')
            ->get(['id', 'club_name', 'name', 'city']);

        return view('content.admin.tournaments.edit', compact('tournament', 'clubs'));
    }

    public function update(Request $request, Tournament $tournament): RedirectResponse
    {
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
                'integer',
                'different:club_id',
                'exists:users,id',
            ],
            'gender' => ['required', 'string', 'in:MALE,FEMALE,MIXED,OPEN'],
            'player_level' => ['required', 'array', 'min:1'],
            'player_level.*' => ['required', 'string', 'in:BEGINNER,INTERMEDIATE,ADVANCED,PROFESSIONAL,OPEN'],
            'age_group' => ['required', 'string', 'regex:/^\d+-\d+$/'],
            'status' => ['required', 'string', 'in:pending,soft_accepted,confirmed,rejected,open,full,closed,completed,cancelled'],
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

        $tournament->update(array_merge($validated, [
            'tournament_image' => $imagePath,
            'allowed_player' => $validated['maximum_players'],
        ]));

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
